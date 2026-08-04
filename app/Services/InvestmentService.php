<?php

namespace App\Services;

use App\Repositories\InvestmentRepository;

class InvestmentService
{
    protected InvestmentRepository $repository;

    public function __construct(InvestmentRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllInvestments()
    {
        return $this->repository->all();
    }

    public function getGroupedInvestments()
    {
        return $this->repository->getGroupedInvestments();
    }

    public function getInvestmentsByInvestor($investorName)
    {
        return $this->repository->getInvestmentsByInvestor($investorName);
    }

    public function createInvestment(array $data)
    {
        return $this->repository->create($data);
    }

    public function updateInvestment($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function getInvestmentById($id)
    {
        return $this->repository->find($id);
    }

    public function deleteInvestment($id)
    {
        return $this->repository->delete($id);
    }
}
