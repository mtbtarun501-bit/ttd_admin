<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;

class AgentLedgerService
{
    /**
     * Statuses that count as real money movement in the agent ledger.
     */
    public const TRACKED_STATUSES = ['confirmed', 'completed'];

    /**
     * Base bookings query restricted to the agent ledger scope.
     * Applies multi-tenant scoping for non-admin 'User' roles.
     */
    protected function baseQuery(?int $agentId = null, ?string $month = null, ?int $year = null, ?string $partnerType = null): Builder
    {
        $query = Booking::query()
            ->whereNotNull('bookings.agent_id')
            ->whereIn('bookings.status', self::TRACKED_STATUSES);

        if (auth()->check() && auth()->user()->hasRole('User') && !auth()->user()->hasAnyRole(['Super Admin', 'Operator'])) {
            $query->where('bookings.created_by', auth()->id());
        }

        if ($agentId) {
            $query->where('bookings.agent_id', $agentId);
        }

        if ($month) {
            [$year, $mon] = explode('-', $month);
            $query->whereYear('bookings.booking_date', $year)->whereMonth('bookings.booking_date', (int) $mon);
        }

        if ($year) {
            $query->whereYear('bookings.booking_date', $year);
        }

        if ($partnerType) {
            $query->whereHas('agent', function ($q) use ($partnerType) {
                $q->where('agent_type', $partnerType);
            });
        }

        return $query;
    }

    /**
     * Aggregate ledger per agent for a given period (month is optional, 'YYYY-MM').
     */
    public function aggregatePerAgent(?string $month = null)
    {
        $sub = $this->baseQuery(null, $month)->selectRaw(
            'agent_id,
             COUNT(*) as bookings_count,
             COALESCE(SUM(ticket_count), 0) as tickets,
             COALESCE(SUM(total_amount) - SUM(service_charge), 0) as spent,
             COALESCE(SUM(service_charge), 0) as earned,
             COALESCE(SUM(total_amount), 0) as total'
        )->groupBy('agent_id');

        return Agent::query()
            ->leftJoinSub($sub, 'ledger', 'ledger.agent_id', '=', 'agents.id')
            ->select(
                'agents.*',
                'ledger.bookings_count',
                'ledger.tickets',
                'ledger.spent',
                'ledger.earned',
                'ledger.total'
            );
    }

    /**
     * Overall ledger totals across all agents for a given period.
     */
    public function totals(?string $month = null, ?string $partnerType = null)
    {
        return $this->baseQuery(null, $month, null, $partnerType)
            ->selectRaw(
                'COUNT(*) as bookings_count,
                 COALESCE(SUM(ticket_count), 0) as tickets,
                 COALESCE(SUM(total_amount) - SUM(service_charge), 0) as spent,
                 COALESCE(SUM(service_charge), 0) as earned,
                 COALESCE(SUM(total_amount), 0) as total'
            )
            ->first();
    }

    /**
     * Jan-Dec ledger matrix (bookings, tickets, spent, earned, total) for the given year.
     */
    public function perMonthMatrix(?int $agentId, ?int $year = null): array
    {
        $year = $year ?? (int) now()->year;

        $rows = $this->baseQuery($agentId)
            ->whereYear('booking_date', $year)
            ->selectRaw(
                'MONTH(booking_date) as m,
                 COUNT(*) as bookings_count,
                 COALESCE(SUM(ticket_count), 0) as tickets,
                 COALESCE(SUM(total_amount) - SUM(service_charge), 0) as spent,
                 COALESCE(SUM(service_charge), 0) as earned,
                 COALESCE(SUM(total_amount), 0) as total'
            )
            ->groupBy('m')
            ->get()
            ->keyBy('m');

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $row = $rows->get($i);
            $months[$i] = (object) [
                'bookings_count' => (int) ($row->bookings_count ?? 0),
                'tickets' => (float) ($row->tickets ?? 0),
                'spent' => (float) ($row->spent ?? 0),
                'earned' => (float) ($row->earned ?? 0),
                'total' => (float) ($row->total ?? 0),
            ];
        }

        return $months;
    }

    /**
     * Rolling spend/earned totals across all agents for the last N months.
     */
    public function monthlyTrend(int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth()->toDateString();

        $rows = $this->baseQuery()
            ->where('booking_date', '>=', $start)
            ->selectRaw(
                'DATE_FORMAT(booking_date, "%Y-%m") as ym,
                 COALESCE(SUM(total_amount) - SUM(service_charge), 0) as spent,
                 COALESCE(SUM(service_charge), 0) as earned'
            )
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $labels = [];
        $spent = [];
        $earned = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $labels[] = now()->subMonths($i)->format('M Y');
            $spent[] = (float) ($rows[$key]->spent ?? 0);
            $earned[] = (float) ($rows[$key]->earned ?? 0);
        }

        return compact('labels', 'spent', 'earned');
    }

    /**
     * Individual bookings for an agent within the given month ('YYYY-MM').
     */
    public function monthlyBookings(int $agentId, ?string $month = null)
    {
        $query = $this->baseQuery($agentId);

        if ($month) {
            [$year, $mon] = explode('-', $month);
            $query->whereYear('booking_date', $year)->whereMonth('booking_date', (int) $mon);
        }

        return $query->with(['bookingType', 'devotee', 'agent'])
            ->orderByDesc('booking_date')
            ->get();
    }

    /**
     * Overall ledger totals across all agents for a given year.
     */
    public function yearTotals(int $year, ?string $partnerType = null)
    {
        return $this->baseQuery(null, null, $year, $partnerType)
            ->selectRaw(
                'COUNT(*) as bookings_count,
                 COALESCE(SUM(ticket_count), 0) as tickets,
                 COALESCE(SUM(total_amount) - SUM(service_charge), 0) as spent,
                 COALESCE(SUM(service_charge), 0) as earned,
                 COALESCE(SUM(total_amount), 0) as total'
            )
            ->first();
    }

    /**
     * Overall earned month-by-month (all agents combined) for a year.
     * Each entry carries per-month stats plus a running cumulative earned.
     */
    public function overallMonthly(int $year, ?string $partnerType = null): array
    {
        $rows = $this->baseQuery(null, null, $year, $partnerType)
            ->selectRaw(
                'MONTH(booking_date) as m,
                 COUNT(*) as bookings_count,
                 COALESCE(SUM(ticket_count), 0) as tickets,
                 COALESCE(SUM(total_amount) - SUM(service_charge), 0) as spent,
                 COALESCE(SUM(service_charge), 0) as earned,
                 COALESCE(SUM(total_amount), 0) as total'
            )
            ->groupBy('m')
            ->get()
            ->keyBy('m');

        $months = [];
        $cumulative = 0.0;

        for ($i = 1; $i <= 12; $i++) {
            $row = isset($rows[$i]) ? $this->mapRow($rows[$i]) : $this->zeroRow();
            $cumulative += $row->earned;
            $months[$i] = [
                'data' => $row,
                'cumulative' => $cumulative,
            ];
        }

        return $months;
    }

    /**
     * 12-point earned/spent series across all agents (for the trend chart).
     */
    public function annualTrend(int $year, ?string $partnerType = null): array
    {
        $months = $this->overallMonthly($year, $partnerType);

        $labels = [];
        $earned = [];
        $spent = [];

        foreach ($months as $m => $entry) {
            $labels[] = now()->setMonth($m)->format('M');
            $earned[] = $entry['data']->earned;
            $spent[] = $entry['data']->spent;
        }

        return compact('labels', 'earned', 'spent');
    }

    /**
     * Agents x 12 months matrix for the year (with per-month stats and year totals).
     */
    public function yearlyMatrix(int $year, ?string $partnerType = null): array
    {
        $rows = $this->baseQuery(null, null, $year, $partnerType)
            ->selectRaw(
                'agent_id, MONTH(booking_date) as m,
                 COUNT(*) as bookings_count,
                 COALESCE(SUM(ticket_count), 0) as tickets,
                 COALESCE(SUM(total_amount) - SUM(service_charge), 0) as spent,
                 COALESCE(SUM(service_charge), 0) as earned,
                 COALESCE(SUM(total_amount), 0) as total'
            )
            ->groupBy('agent_id', 'm')
            ->get()
            ->groupBy('agent_id');

        $agentsQuery = Agent::query();
        if ($partnerType) {
            $agentsQuery->where('agent_type', $partnerType);
        }

        $matrix = [];

        foreach ($agentsQuery->orderBy('name')->get() as $agent) {
            $months = [];
            $total = $this->zeroRow();

            for ($i = 1; $i <= 12; $i++) {
                $row = $rows->has($agent->id) ? $rows[$agent->id]->firstWhere('m', $i) : null;
                $cell = $row ? $this->mapRow($row) : $this->zeroRow();
                $months[$i] = $cell;

                $total->bookings_count += $cell->bookings_count;
                $total->tickets += $cell->tickets;
                $total->spent += $cell->spent;
                $total->earned += $cell->earned;
                $total->total += $cell->total;
            }

            $matrix[] = [
                'agent' => $agent,
                'months' => $months,
                'total' => $total,
            ];
        }

        return $matrix;
    }

    /**
     * Top agents by earned revenue for the year.
     */
    public function topAgents(int $year, int $limit = 5, ?string $partnerType = null)
    {
        return $this->baseQuery(null, null, $year, $partnerType)
            ->with('agent')
            ->selectRaw(
                'agent_id,
                 COALESCE(SUM(service_charge), 0) as earned,
                 COUNT(*) as bookings_count'
            )
            ->groupBy('agent_id')
            ->orderByDesc('earned')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->agent?->name ?? 'Unknown',
                    'earned' => (float) $row->earned,
                    'bookings_count' => (int) $row->bookings_count,
                ];
            });
    }

    /**
     * Earned revenue split between In Partner / Out Partner for the year.
     */
    public function partnerSplit(int $year, ?string $partnerType = null): array
    {
        $rows = $this->baseQuery(null, null, $year, $partnerType)
            ->join('agents as ag', 'ag.id', '=', 'bookings.agent_id')
            ->selectRaw('ag.agent_type, COALESCE(SUM(bookings.service_charge), 0) as earned')
            ->groupBy('ag.agent_type')
            ->get();

        return [
            'in' => (float) ($rows->firstWhere('agent_type', Agent::TYPE_IN_PARTNER)?->earned ?? 0),
            'out' => (float) ($rows->firstWhere('agent_type', Agent::TYPE_OUT_PARTNER)?->earned ?? 0),
        ];
    }

    /**
     * Aggregate ledger per agent for a given year (drives the ranking DataTable).
     */
    public function aggregatePerYear(int $year, ?string $partnerType = null)
    {
        $sub = $this->baseQuery(null, null, $year, $partnerType)->selectRaw(
            'agent_id,
             COUNT(*) as bookings_count,
             COALESCE(SUM(ticket_count), 0) as tickets,
             COALESCE(SUM(total_amount) - SUM(service_charge), 0) as spent,
             COALESCE(SUM(service_charge), 0) as earned,
             COALESCE(SUM(total_amount), 0) as total'
        )->groupBy('agent_id');

        $agentsQuery = Agent::query();
        if ($partnerType) {
            $agentsQuery->where('agent_type', $partnerType);
        }

        return $agentsQuery
            ->leftJoinSub($sub, 'ledger', 'ledger.agent_id', '=', 'agents.id')
            ->select(
                'agents.*',
                'ledger.bookings_count',
                'ledger.tickets',
                'ledger.spent',
                'ledger.earned',
                'ledger.total'
            );
    }

    /**
     * Individual bookings for an agent across a full year (drill-down detail).
     */
    public function perYearBookings(int $agentId, int $year)
    {
        return $this->baseQuery($agentId)
            ->whereYear('booking_date', $year)
            ->with(['bookingType', 'devotee'])
            ->orderByDesc('booking_date')
            ->get();
    }

    protected function zeroRow(): object
    {
        return (object) ['bookings_count' => 0, 'tickets' => 0, 'spent' => 0.0, 'earned' => 0.0, 'total' => 0.0];
    }

    protected function mapRow($row): object
    {
        return (object) [
            'bookings_count' => (int) $row->bookings_count,
            'tickets' => (float) $row->tickets,
            'spent' => (float) $row->spent,
            'earned' => (float) $row->earned,
            'total' => (float) $row->total,
        ];
    }
}