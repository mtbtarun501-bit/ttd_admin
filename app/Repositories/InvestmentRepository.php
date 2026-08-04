<?php

namespace App\Repositories;

use App\Models\Investment;

class InvestmentRepository extends BaseRepository
{
    public function __construct(Investment $model)
    {
        parent::__construct($model);
    }

    public function getGroupedInvestments()
    {
        return $this->model->select('investor_name')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('MAX(investment_date) as last_investment_date')
            ->groupBy('investor_name')
            ->get();
    }

    public function getInvestmentsByInvestor($investorName)
    {
        return $this->model->where('investor_name', $investorName)
            ->orderBy('investment_date', 'desc')
            ->get();
    }
}
