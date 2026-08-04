<?php

namespace App\Http\Controllers;

use App\Models\PhoneUsage;
use App\Models\SevaType;
use App\Services\PhoneUsageService;
use App\Http\Requests\StorePhoneUsageRequest;
use App\Http\Requests\UpdatePhoneUsageRequest;
use App\Http\Requests\StorePhoneUsageBookingRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class PhoneUsageController extends Controller
{
    protected PhoneUsageService $phoneUsageService;

    public function __construct(PhoneUsageService $phoneUsageService)
    {
        $this->phoneUsageService = $phoneUsageService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PhoneUsage::with(['serviceStatuses.sevaType'])->select('phone_usages.*');
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('can_book_today', function($row) {
                    $eligibleSevas = $this->phoneUsageService->getEligibleSevasToday($row);
                    if ($eligibleSevas->isEmpty()) {
                        return '<span class="text-danger fw-bold">None</span>';
                    }
                    return $eligibleSevas->map(function($seva) {
                        return '<span class="badge bg-success mb-1">' . htmlspecialchars($seva->name) . '</span>';
                    })->implode(' ');
                })
                ->addColumn('next_eligible_date', function($row) {
                    // Get the earliest next eligible date for sevas that are NOT eligible today
                    $today = Carbon::today();
                    $upcoming = $row->serviceStatuses->filter(function($status) use ($today) {
                        return $status->next_eligible_date && $status->next_eligible_date->greaterThan($today);
                    })->sortBy('next_eligible_date')->first();
                    
                    if ($upcoming) {
                        return $upcoming->next_eligible_date->format('d M Y');
                    }
                    
                    return '<span class="text-muted">N/A</span>';
                })
                ->addColumn('action', function($row) {
                    $viewBtn = '<a href="'.route('phone-usages.show', $row->id).'" class="btn btn-sm btn-info me-1"><i class="fas fa-eye"></i></a>';
                    $editBtn = '<a href="'.route('phone-usages.edit', $row->id).'" class="btn btn-sm btn-primary me-1"><i class="fas fa-edit"></i></a>';
                    $deleteBtn = '<form action="'.route('phone-usages.destroy', $row->id).'" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this phone usage record completely?\')">
                                    '.csrf_field().'
                                    '.method_field('DELETE').'
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                  </form>';
                    return '<div class="d-flex">' . $viewBtn . $editBtn . $deleteBtn . '</div>';
                })
                ->rawColumns(['can_book_today', 'next_eligible_date', 'action'])
                ->make(true);
        }

        return view('phone_usages.index');
    }

    public function create()
    {
        $sevas = SevaType::orderBy('display_order')->get();
        return view('phone_usages.create', compact('sevas'));
    }

    public function store(StorePhoneUsageRequest $request)
    {
        $data = $request->validated();
        $sevaDates = $request->input('seva_dates', []);
        
        $this->phoneUsageService->createPhoneUsage($data, $sevaDates);

        return redirect()->route('phone-usages.index')->with('success', 'Phone usage recorded successfully.');
    }

    public function show(PhoneUsage $phoneUsage)
    {
        $phoneUsage->load(['serviceStatuses.sevaType', 'bookingHistories.sevaType', 'bookingHistories.creator']);
        
        // Sort booking histories newest first
        $phoneUsage->setRelation('bookingHistories', $phoneUsage->bookingHistories->sortByDesc('booking_date'));
        
        // Sevas for the add booking modal
        $sevas = SevaType::orderBy('display_order')->get();

        return view('phone_usages.show', compact('phoneUsage', 'sevas'));
    }

    public function edit(PhoneUsage $phoneUsage)
    {
        return view('phone_usages.edit', compact('phoneUsage'));
    }

    public function update(UpdatePhoneUsageRequest $request, PhoneUsage $phoneUsage)
    {
        $phoneUsage->update($request->validated());

        return redirect()->route('phone-usages.index')->with('success', 'Phone usage updated successfully.');
    }

    public function destroy(PhoneUsage $phoneUsage)
    {
        $phoneUsage->delete();
        return redirect()->route('phone-usages.index')->with('success', 'Phone usage record deleted successfully.');
    }
    
    public function storeBooking(StorePhoneUsageBookingRequest $request, PhoneUsage $phoneUsage)
    {
        $data = $request->validated();
        
        $this->phoneUsageService->addBooking(
            $phoneUsage,
            $data['seva_type_id'],
            $data['booking_date'],
            $data['remarks'] ?? null,
            auth()->id()
        );
        
        return redirect()->route('phone-usages.show', $phoneUsage->id)->with('success', 'Booking recorded and eligibility updated.');
    }
}
