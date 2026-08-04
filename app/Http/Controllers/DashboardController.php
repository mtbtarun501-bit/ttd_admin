<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Revenue;
use App\Models\Devotee;
use App\Models\BookingType;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $isRegularUser = auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator']);

        // 1. Quick Stats
        $todaysBookings = $isRegularUser ? 0 : Booking::whereDate('created_at', Carbon::today())->count();
        $pendingApprovals = $isRegularUser ? 0 : Booking::where('status', 'pending')->count();
        $totalRevenue = $isRegularUser ? 0 : Revenue::sum('amount');
        
        $devoteeQuery = Devotee::query();
        if ($isRegularUser) {
            $devoteeQuery->where('user_id', auth()->id());
        }
        $activeDevotees = $devoteeQuery->count();

        // 2. Format total revenue nicely (e.g. 8.4M or 840K)
        $formattedRevenue = '₹' . number_format($totalRevenue, 0);
        if ($totalRevenue >= 10000000) {
            $formattedRevenue = '₹' . number_format($totalRevenue / 10000000, 2) . 'Cr';
        } elseif ($totalRevenue >= 100000) {
            $formattedRevenue = '₹' . number_format($totalRevenue / 100000, 2) . 'L';
        }

        // 3. Monthly Booking Trends (Last 6 Months)
        $months = [];
        $monthlyBookingsData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::today()->startOfMonth()->subMonths($i);
            $months[] = $date->format('M');
            
            $count = $isRegularUser ? 0 : Booking::whereYear('created_at', $date->year)
                            ->whereMonth('created_at', $date->month)
                            ->count();
            $monthlyBookingsData[] = $count;
        }

        // 4. Booking Types Distribution
        $bookingTypes = BookingType::all();
        $typeLabels = [];
        $typeData = [];
        $typeColors = ['#D4AF37', '#800000', '#3E2723', '#5D4037', '#e67e22', '#2ecc71'];

        if (!$isRegularUser) {
            foreach ($bookingTypes as $index => $type) {
                $count = Booking::where('booking_type_id', $type->id)->count();
                if ($count > 0) {
                    $typeLabels[] = $type->name;
                    $typeData[] = $count;
                }
            }
        }

        // If no bookings exist yet, provide a fallback so the chart doesn't look broken
        if (empty($typeData)) {
            $typeLabels = ['No Data'];
            $typeData = [1];
            $typeColors = ['#cccccc'];
        }

        return view('dashboard', compact(
            'todaysBookings',
            'pendingApprovals',
            'formattedRevenue',
            'activeDevotees',
            'months',
            'monthlyBookingsData',
            'typeLabels',
            'typeData',
            'typeColors'
        ));
    }
}
