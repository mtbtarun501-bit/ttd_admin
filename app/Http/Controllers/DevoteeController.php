<?php

namespace App\Http\Controllers;

use App\Imports\DevoteeImport;
use App\Imports\MonthlyDevoteeImport;
use App\Models\Devotee;
use App\Models\Booking;
use App\Models\BookingType;
use App\Models\Agent;
use App\Services\DevoteeService;
use App\Http\Requests\StoreDevoteeRequest;
use App\Http\Requests\UpdateDevoteeRequest;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
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
            'Name', 'Age', 'Gender', 'Family', 'Aadhaar', 'Phone', 'Email', 'City', 'State', 'Pincode', 'Gothram', 'Remarks', 'Referred'
        ];
        
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            // Add a sample row
            fputcsv($file, ['John Doe', '30', 'Male', 'Doe Family', '123456789012', '9876543210', 'john@example.com', 'Tirupati', 'Andhra Pradesh', '517501', 'Kashyapa', 'VIP Darshan preferred', 'Nikhil']);
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
            $file = $request->file('import_file');

            // The monthly partner workbook is a single-column block layout; the
            // old template (Name/Age/Gender/Family/.../Referred) is still
            // supported for backward compatibility. Detect which format the
            // uploaded file uses before importing.
            $isTabular = $this->looksLikeTabularTemplate($file);
            $importer = $isTabular ? new DevoteeImport() : new MonthlyDevoteeImport();

            DB::transaction(function () use ($importer, $file) {
                Excel::import($importer, $file);
            });

            if ($isTabular) {
                return redirect()->back()->with('success', 'Devotees imported successfully.');
            }

            $summary = $importer->summary();
            return redirect()->back()->with('success', sprintf(
                'Monthly import completed: %d families, %d members imported, %d duplicates skipped.',
                $summary['families'],
                $summary['created'],
                $summary['skipped']
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Import Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    /**
     * Detect whether the uploaded workbook follows the old tabular template
     * (has a heading row) instead of the single-column monthly block layout.
     */
    protected function looksLikeTabularTemplate(UploadedFile $file): bool
    {
        $sheets = Excel::toArray(new class implements ToArray
        {
            public function array(array $array): array
            {
                return $array;
            }
        }, $file);

        $rows = $sheets[0] ?? [];

        foreach ($rows as $row) {
            $cells = is_array($row) ? array_values($row) : [$row];
            $cells = array_map(fn ($cell) => strtolower(trim((string) ($cell ?? ''))), $cells);
            $cells = array_filter($cells, fn ($cell) => $cell !== '');

            if (empty($cells)) {
                continue;
            }

            foreach ($cells as $cell) {
                if (in_array($cell, [
                    'name', 'full name', 'age', 'gender', 'aadhaar',
                    'aadhaar number', 'family', 'referred', 'phone', 'remarks',
                ], true)) {
                    return true;
                }
            }

            break; // only inspect the first non-empty row
        }

        return false;
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
                'gender' => $devotee->gender,
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
            $query = Devotee::with(['headFamilyMember', 'referredAgent'])
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
                        if ($row->referred_devotee_id) {
                            return '<span class="badge bg-primary">Family</span> <span class="badge bg-info">Referred by ' . htmlspecialchars($row->referredAgent->name ?? 'Unknown') . '</span>';
                        }
                        return '<span class="badge bg-primary">Referred Agent (Head)</span>';
                    } elseif ($row->head_devotee_id) {
                        return '<span class="badge bg-info">Agent: ' . htmlspecialchars($row->headFamilyMember->name ?? 'Unknown') . '</span>';
                    }
                    return '<span class="badge bg-secondary">Individual</span>';
                })
                ->filterColumn('family_status', function($query, $keyword) {
                    $query->orWhereHas('headFamilyMember', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    })->orWhereHas('referredAgent', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('action', function($row){
                    $btn = '';
                    
                    $btn .= '<button type="button" class="btn btn-sm btn-warning me-1 manage-booking-btn" data-id="'.$row->id.'" data-name="'.htmlspecialchars($row->name).'" title="Manage Booking"><i class="fas fa-ticket-alt"></i></button>';
                    $btn .= '<a href="'.route('devotees.edit', $row->id).'" class="btn btn-sm btn-primary me-1" title="Edit Details"><i class="fas fa-edit"></i></a>';
                    $referredLabel = $row->referred_devotee_id
                        ? ($row->referredAgent->name ?? '')
                        : ($row->is_head_of_family ? $row->name : ($row->headFamilyMember->name ?? ''));
                    $btn .= '<button type="button" class="btn btn-sm btn-outline-primary me-1 edit-referred-btn" data-id="'.$row->id.'" data-name="'.htmlspecialchars($row->name).'" data-referred="'.htmlspecialchars($referredLabel).'" title="Edit Referred Name"><i class="fas fa-user-tag"></i></button>';
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
        $agents = Devotee::where('is_head_of_family', true)->get();
        $partnerAgents = Agent::all();

        return view('devotees.index', compact('bookingTypes', 'agents', 'partnerAgents'));
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

        $agents = Devotee::where('is_head_of_family', true)->get();

        return view('devotees.show', compact('devotee', 'agents'));
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
            'booked_by_name' => 'nullable|string|max:255',
            'booking_type_id' => 'required|exists:booking_types,id',
            'ticket_count' => 'required|integer|min:1',
            'agent_id' => 'nullable|exists:agents,id',
        ]);

        $bookingType = BookingType::find($request->booking_type_id);
        $price = $bookingType->price ?? 0;

        $agentType = null;
        $agent = null;
        if ($request->filled('agent_id')) {
            $agent = Agent::find($request->agent_id);
            $agentType = $agent?->agent_type;
        }
        $commission = $bookingType->commissionForType($agentType) ?? 0;

        $ticketCount = $request->ticket_count;
        $totalCommission = $commission * $ticketCount;
        $totalAmount = ($price * $ticketCount) + $totalCommission;

        $booking = new Booking();
        $booking->booking_no = 'BKG-' . strtoupper(uniqid());
        $booking->devotee_id = $devotee->id;
        $booking->booking_type_id = $bookingType->id;
        $booking->booking_date = now();
        $booking->status = 'pending';
        $booking->agent_id = $request->filled('agent_id') ? $request->agent_id : null;
        $booking->booked_by_name = $agent?->name ?? $request->booked_by_name;
        $booking->ticket_count = $ticketCount;
        $booking->service_charge = $totalCommission;
        $booking->total_amount = $totalAmount;
        $booking->created_by = auth()->id();
        $booking->save();

        // Attach the devotee as attendee automatically for quick booking
        $booking->attendees()->attach($devotee->id);

        $bookedBy = $agent?->name ?? $request->booked_by_name;

        return response()->json([
            'success' => true,
            'message' => 'Booking saved successfully. Amount used by ' . $bookedBy . ': ₹' . number_format($totalAmount, 2)
        ]);
    }

    public function updateReferred(Request $request, $id)
    {
        $devotee = Devotee::findOrFail($id);
        $this->authorizeAccess($devotee);

        $request->validate([
            'referred_name' => 'nullable|string|max:255',
        ]);

        $referredName = trim((string) $request->input('referred_name', ''));

        if ($devotee->is_head_of_family) {
            $isFamilyGroup = $devotee->referred_devotee_id !== null || $devotee->familyMembers()->exists();

            if ($isFamilyGroup) {
                // Editing the referred agent that this family is credited to.
                if ($referredName === '') {
                    $devotee->update(['referred_devotee_id' => null]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Referral removed from this family.',
                        'referred' => '',
                    ]);
                }

                $agent = $this->resolveReferredAgent($referredName);
                $devotee->update(['referred_devotee_id' => $agent->id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Referred agent updated successfully.',
                    'name' => $devotee->name,
                    'referred' => $agent->name,
                ]);
            }

            if ($referredName === '') {
                return response()->json(['success' => false, 'message' => 'Agent name cannot be empty.'], 422);
            }
            $devotee->update(['name' => $referredName]);
            return response()->json([
                'success' => true,
                'message' => 'Agent renamed successfully.',
                'name' => $devotee->name,
                'referred' => $devotee->name,
            ]);
        }

        if ($referredName === '') {
            $devotee->update(['head_devotee_id' => null]);
            return response()->json([
                'success' => true,
                'message' => 'Devotee is now a standalone member.',
                'referred' => '',
            ]);
        }

        $agent = $this->resolveReferredAgent($referredName);
        $devotee->update(['head_devotee_id' => $agent->id]);

        return response()->json([
            'success' => true,
            'message' => 'Referred updated successfully.',
            'referred' => $agent->name,
        ]);
    }

    /**
     * Find or create a referred-agent devotee by name.
     */
    protected function resolveReferredAgent(string $referredName): Devotee
    {
        $agent = Devotee::where('name', 'like', trim($referredName))->first();

        if (!$agent) {
            $agent = Devotee::create([
                'user_id' => auth()->id() ?? 1,
                'name' => trim($referredName),
                'age' => 30,
                'gender' => 'Unknown',
                'is_head_of_family' => true,
                'remarks' => 'Auto-created from Referred edit',
            ]);
        } elseif (!$agent->is_head_of_family) {
            $agent->is_head_of_family = true;
            $agent->save();
        }

        return $agent;
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
