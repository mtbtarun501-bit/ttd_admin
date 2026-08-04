<?php

namespace App\Services;

use App\Repositories\RevenueRepository;

class RevenueService
{
    protected RevenueRepository $repository;

    public function __construct(RevenueRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllRevenues()
    {
        return $this->repository->all();
    }

    public function createRevenue(array $data)
    {
        $data['created_by'] = auth()->id();
        return $this->repository->create($data);
    }

    public function updateRevenue($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function getRevenueById($id)
    {
        return $this->repository->find($id);
    }

    public function deleteRevenue($id)
    {
        return $this->repository->delete($id);
    }
}
