<?php

namespace App\Http\Controllers;

use App\Services\BookingService;
use App\Models\BookingType;
use App\Models\Devotee;
use App\Http\Requests\StoreBookingRequest;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use App\Models\Booking;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Booking::with(['devotee', 'bookingType', 'creator'])->select('bookings.*');
            
            if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
                $query->where('created_by', auth()->id());
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('devotee_name', function($row){
                    return $row->devotee->name ?? 'N/A';
                })
                ->addColumn('booking_type', function($row){
                    return $row->bookingType->name ?? 'N/A';
                })
                ->addColumn('booked_by', function($row){
                    return $row->creator->name ?? 'System';
                })
                ->editColumn('status', function($row){
                    if (strtolower($row->status) === 'pending') {
                        return '<span class="badge bg-warning text-dark px-3 py-2" style="border-radius:20px;"><i class="fas fa-clock me-1"></i> Pending / Not Booked</span>';
                    } elseif (strtolower($row->status) === 'confirmed') {
                        return '<span class="badge bg-success px-3 py-2" style="border-radius:20px;"><i class="fas fa-check-circle me-1"></i> Booked</span>';
                    } elseif (strtolower($row->status) === 'cancelled') {
                        return '<span class="badge bg-danger px-3 py-2" style="border-radius:20px;"><i class="fas fa-times-circle me-1"></i> Cancelled</span>';
                    } elseif (strtolower($row->status) === 'completed') {
                        return '<span class="badge bg-primary px-3 py-2" style="border-radius:20px;"><i class="fas fa-check-double me-1"></i> Completed</span>';
                    }
                    return '<span class="badge bg-secondary px-3 py-2" style="border-radius:20px;">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex align-items-center gap-1">';
                    $btn .= '<a href="'.route('bookings.show', $row->id).'" class="btn btn-sm btn-info text-white"><i class="fas fa-eye"></i></a>';
                    $btn .= '<button type="button" class="btn btn-sm btn-warning text-dark edit-status-btn" data-id="'.$row->id.'" data-status="'.$row->status.'" data-created-by="'.$row->created_by.'" data-bs-toggle="modal" data-bs-target="#editStatusModal"><i class="fas fa-edit"></i> Edit</button>';
                    $btn .= '<form action="'.route('bookings.destroy', $row->id).'" method="POST" class="m-0 p-0" style="display:inline-block;">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button type="submit" class="btn btn-sm btn-danger" style="background:var(--temple-maroon); border:none;" onclick="return confirm(\'Are you sure you want to cancel this booking?\')"><i class="fas fa-times"></i> Cancel</button>
                              </form>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $admins = \App\Models\User::role(['Super Admin', 'Operator'])->get();
        return view('bookings.index', compact('admins'));
    }

    public function create()
    {
        // Only fetch Heads of Families and Individuals (exclude dependents)
        $query = Devotee::whereNull('head_devotee_id');
        
        if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            $query->where('user_id', auth()->id());
        }
        
        $devotees = $query->get();
        $bookingTypes = BookingType::all();
        $admins = \App\Models\User::role(['Super Admin', 'Operator'])->get();
        return view('bookings.create', compact('devotees', 'bookingTypes', 'admins'));
    }

    public function store(StoreBookingRequest $request)
    {
        try {
            $this->bookingService->createBooking($request->validated());
            return redirect()->route('bookings.index')->with('success', 'Booking created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $booking = Booking::with(['devotee', 'bookingType', 'attendees'])->findOrFail($id);
        $this->authorizeAccess($booking);
        return view('bookings.show', compact('booking'));
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeAccess($booking);
        $this->bookingService->deleteBooking($id);
        return redirect()->route('bookings.index')->with('success', 'Booking cancelled successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'created_by' => 'nullable|exists:users,id'
        ]);

        $booking = Booking::findOrFail($id);
        $this->authorizeAccess($booking);
        
        $data = ['status' => $request->status];
        if ($request->has('created_by')) {
            $data['created_by'] = $request->created_by;
        }
        
        $booking->update($data);

        return redirect()->route('bookings.index')->with('success', 'Booking updated successfully.');
    }
    
    public function getFamilyMembers($devotee_id)
    {
        $query = Devotee::with('preferredBookingType')
            ->where(function($q) use ($devotee_id) {
                $q->where('id', $devotee_id)->orWhere('head_devotee_id', $devotee_id);
            });
            
        if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            $query->where('user_id', auth()->id());
        }

        $members = $query->get();
        return response()->json($members);
    }

    protected function authorizeAccess(Booking $booking)
    {
        if (auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            if ($booking->created_by !== auth()->id()) {
                abort(403, 'Unauthorized access to this record.');
            }
        }
    }
}
