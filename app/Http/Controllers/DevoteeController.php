<?php

namespace App\Http\Controllers;

use App\Models\Devotee;
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
            $query = Devotee::with(['headFamilyMember'])->select('devotees.*');
            
            // Apply multi-tenant data scoping
            if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
                $query->where('user_id', auth()->id());
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('family_status', function($row){
                    if ($row->is_head_of_family) {
                        return '<span class="badge bg-primary">Head of Family</span>';
                    } elseif ($row->head_devotee_id) {
                        return '<span class="badge bg-info">Family of: ' . htmlspecialchars($row->headFamilyMember->name ?? 'Unknown') . '</span>';
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
                    
                    if ($row->is_head_of_family) {
                        $btn .= '<a href="'.route('devotees.create_family_member', $row->id).'" class="btn btn-sm btn-success me-1" title="Add Family Member"><i class="fas fa-user-plus"></i></a>';
                    }

                    $btn .= '<a href="'.route('devotees.edit', $row->id).'" class="btn btn-sm btn-primary me-1" style="background:var(--temple-gold); border:none;"><i class="fas fa-edit"></i></a>';
                    $btn .= '<a href="'.route('devotees.show', $row->id).'" class="btn btn-sm btn-info text-white me-1"><i class="fas fa-eye"></i></a>';
                    $btn .= '<form action="'.route('devotees.destroy', $row->id).'" method="POST" style="display:inline-block;">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button type="submit" class="btn btn-sm btn-danger" style="background:var(--temple-maroon); border:none;" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-trash"></i></button>
                              </form>';
                    return $btn;
                })
                ->rawColumns(['family_status', 'action'])
                ->make(true);
        }

        return view('devotees.index');
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
