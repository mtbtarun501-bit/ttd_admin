<?php

namespace App\Http\Controllers;

use App\Imports\DevoteeImport;
use App\Models\Devotee;
use App\Models\Booking;
use App\Models\BookingType;
use App\Services\DevoteeService;
use App\Http\Requests\StoreDevoteeRequest;
use App\Http\Requests\UpdateDevoteeRequest;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DevoteeExport;

class DevoteeController extends Controller
{
    protected DevoteeService $devoteeService;

    public function __construct(DevoteeService $devoteeService)
    {
        $this->devoteeService = $devoteeService;
    }

    public function downloadTemplate()
    {
        $headers = [
            'Name', 'Age', 'Gender', 'Aadhaar', 'Phone', 'Email', 'City', 'State', 'Pincode', 'Gothram', 'Remarks', 'Referred'
        ];
        
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            // Add a sample row
            fputcsv($file, ['John Doe', '30', 'Male', '123456789012', '9876543210', 'john@example.com', 'Tirupati', 'Andhra Pradesh', '517501', 'Kashyapa', 'VIP Darshan preferred', 'Nikhil']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=devotees_import_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new DevoteeImport, $request->file('import_file'));
            return redirect()->back()->with('success', 'Devotees imported successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Import Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        // Enforce data scoping for CSV export
        return Excel::download(new DevoteeExport($request->query('search')), 'devotees_list_' . date('Y_m_d') . '.csv');
    }

    public function exportJson(Request $request)
    {
        $query = Devotee::query();

        // Apply multi-tenant data scoping
        if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            $query->where('user_id', auth()->id());
        }

        if ($request->has('agent_id') && !empty($request->agent_id)) {
            $agentId = $request->agent_id;
            $query->where(function($q) use ($agentId) {
                $q->where('head_devotee_id', $agentId)
                  ->orWhere('id', $agentId);
            });
        }

        if ($request->has('search') && !empty($request->query('search'))) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('aadhaar', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('state', 'like', "%{$search}%")
                  ->orWhereHas('headFamilyMember', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $devotees = $query->get()->map(function ($devotee) {
            return [
                'name' => $devotee->name,
                'age' => $devotee->age,
                'adhar number' => $devotee->aadhaar,
                'email' => $devotee->email,
                'gothram' => $devotee->gothram,
                'state' => $devotee->state,
                'city' => $devotee->city,
                'pin code' => $devotee->pin_code,
            ];
        });

        $fileName = 'devotees_list_' . date('Y_m_d_H_i_s') . '.json';
        
        return response()->streamDownload(function () use ($devotees) {
            echo json_encode($devotees, JSON_PRETTY_PRINT);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Only show Heads of Family or Standalone Individuals. Hide child members from the main list.
            $query = Devotee::with(['headFamilyMember'])
                            ->whereNull('head_devotee_id')
                            ->select('devotees.*');
            
            // Apply multi-tenant data scoping
            if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
                $query->where('user_id', auth()->id());
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('family_status', function($row){
                    if ($row->is_head_of_family) {
                        return '<span class="badge bg-primary">Referred Agent (Head)</span>';
                    } elseif ($row->head_devotee_id) {
                        return '<span class="badge bg-info">Agent: ' . htmlspecialchars($row->headFamilyMember->name ?? 'Unknown') . '</span>';
                    }
                    return '<span class="badge bg-secondary">Individual</span>';
                })
                ->filterColumn('family_status', function($query, $keyword) {
                    $query->orWhereHas('headFamilyMember', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('action', function($row){
                    $btn = '';
                    
                    $btn .= '<button type="button" class="btn btn-sm btn-warning me-1 manage-booking-btn" data-id="'.$row->id.'" data-name="'.htmlspecialchars($row->name).'" title="Manage Booking"><i class="fas fa-ticket-alt"></i></button>';
                    $btn .= '<a href="'.route('devotees.show', $row->id).'" class="btn btn-sm btn-info text-white me-1" title="View"><i class="fas fa-eye"></i></a>';
                    $btn .= '<form action="'.route('devotees.destroy', $row->id).'" method="POST" style="display:inline-block;">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button type="submit" class="btn btn-sm btn-danger" style="background:var(--temple-maroon); border:none;" onclick="return confirm(\'Are you sure?\')" title="Delete"><i class="fas fa-trash"></i></button>
                              </form>';
                    return $btn;
                })
                ->rawColumns(['family_status', 'action'])
                ->make(true);
        }

        $bookingTypes = \App\Models\BookingType::all();
        $users = \App\Models\User::all();
        $agents = Devotee::where('is_head_of_family', true)->get();

        return view('devotees.index', compact('bookingTypes', 'users', 'agents'));
    }

    public function create()
    {
        $bookingTypes = BookingType::all();
        return view('devotees.create', compact('bookingTypes'));
    }

    public function createFamilyMember($head_id)
    {
        $head = Devotee::findOrFail($head_id);
        $this->authorizeAccess($head);

        $bookingTypes = BookingType::all();
        return view('devotees.create_family_member', compact('head', 'bookingTypes'));
    }

    public function store(StoreDevoteeRequest $request)
    {
        $data = $request->validated();
        
        // Ensure checkboxes are properly handled as booleans
        $data['is_head_of_family'] = $request->has('is_head_of_family');
        // Attach the current user's ID
        $data['user_id'] = auth()->id();
        
        $this->devoteeService->createDevotee($data);
        return redirect()->route('devotees.index')->with('success', 'Devotee created successfully.');
    }

    public function show($id)
    {
        $devotee = Devotee::with(['headFamilyMember', 'familyMembers', 'preferredBookingType'])->findOrFail($id);
        $this->authorizeAccess($devotee);

        return view('devotees.show', compact('devotee'));
    }

    public function edit($id)
    {
        $devotee = Devotee::findOrFail($id);
        $this->authorizeAccess($devotee);

        $bookingTypes = BookingType::all();
        return view('devotees.edit', compact('devotee', 'bookingTypes'));
    }

    public function quickBooking(Request $request, $id)
    {
        $devotee = Devotee::findOrFail($id);
        $this->authorizeAccess($devotee);

        $request->validate([
            'booked_by_name' => 'required|string|max:255',
            'booking_type_id' => 'required|exists:booking_types,id',
            'ticket_count' => 'required|integer|min:1',
        ]);

        $bookingType = BookingType::find($request->booking_type_id);
        $price = $bookingType->price ?? 0;
        $commission = $bookingType->commission_rate ?? 0;

        $ticketCount = $request->ticket_count;
        $totalCommission = $commission * $ticketCount;
        $totalAmount = ($price * $ticketCount) + $totalCommission;

        $booking = new Booking();
        $booking->booking_no = 'BKG-' . strtoupper(uniqid());
        $booking->devotee_id = $devotee->id;
        $booking->booking_type_id = $bookingType->id;
        $booking->booking_date = now();
        $booking->status = 'pending';
        $booking->ticket_count = $ticketCount;
        $booking->service_charge = $totalCommission;
        $booking->total_amount = $totalAmount;
        $booking->booked_by_name = $request->booked_by_name;
        $booking->created_by = auth()->id();
        $booking->save();

        // Attach the devotee as attendee automatically for quick booking
        $booking->attendees()->attach($devotee->id);

        return response()->json([
            'success' => true,
            'message' => 'Booking saved successfully. Amount used by ' . $request->booked_by_name . ': ₹' . number_format($totalAmount, 2)
        ]);
    }

    public function update(UpdateDevoteeRequest $request, $id)
    {
        $devotee = Devotee::findOrFail($id);
        $this->authorizeAccess($devotee);

        $data = $request->validated();
        
        // Handle checkbox
        $data['is_head_of_family'] = $request->has('is_head_of_family');

        $this->devoteeService->updateDevotee($id, $data);
        return redirect()->route('devotees.index')->with('success', 'Devotee updated successfully.');
    }

    public function destroy($id)
    {
        $devotee = Devotee::findOrFail($id);
        $this->authorizeAccess($devotee);

        if ($devotee->is_head_of_family) {
            // Cascade delete family members so they don't get orphaned and block future imports
            Devotee::where('head_devotee_id', $devotee->id)->delete();
        }

        $this->devoteeService->deleteDevotee($id);
        return redirect()->route('devotees.index')->with('success', 'Devotee deleted successfully.');
    }

    /**
     * Enforce multi-tenant authorization check
     */
    protected function authorizeAccess(Devotee $devotee)
    {
        if (auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            if ($devotee->user_id !== auth()->id()) {
                abort(403, 'Unauthorized access to this record.');
            }
        }
    }
}
