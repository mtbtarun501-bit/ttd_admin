<?php

namespace App\Http\Controllers;

use App\Models\Devotee;
use App\Models\Booking;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AgentAccountController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Devotee::where('is_head_of_family', true)
                ->withCount('primaryBookings as total_bookings')
                ->withSum('primaryBookings as total_tickets', 'ticket_count')
                ->withSum('primaryBookings as total_commission', 'service_charge')
                ->withSum('primaryBookings as total_amount', 'total_amount');

            // Apply multi-tenant data scoping
            if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
                $query->where('user_id', auth()->id());
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('total_tickets', function($row){
                    return $row->total_tickets ?? 0;
                })
                ->editColumn('total_commission', function($row){
                    return $row->total_commission ? '₹' . number_format($row->total_commission, 2) : '₹0.00';
                })
                ->editColumn('total_amount', function($row){
                    return $row->total_amount ? '₹' . number_format($row->total_amount, 2) : '₹0.00';
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('agent-accounts.show', $row->id).'" class="btn btn-sm text-white" style="background:var(--temple-gold);"><i class="fas fa-file-invoice-dollar"></i> View Ledger</a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('agent_accounts.index');
    }

    public function show($id)
    {
        $agent = Devotee::where('is_head_of_family', true)->findOrFail($id);
        
        // Multi-tenant check
        if (auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            if ($agent->user_id !== auth()->id()) {
                abort(403, 'Unauthorized access to this record.');
            }
        }

        $bookings = Booking::with(['bookingType'])
            ->where('devotee_id', $agent->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalTickets = $bookings->sum('ticket_count');
        $totalCommission = $bookings->sum('service_charge');
        $totalAmount = $bookings->sum('total_amount');

        return view('agent_accounts.show', compact('agent', 'bookings', 'totalTickets', 'totalCommission', 'totalAmount'));
    }
}
