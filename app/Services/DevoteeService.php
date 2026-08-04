<?php

namespace App\Services;

use App\Repositories\DevoteeRepository;

class DevoteeService
{
    protected DevoteeRepository $repository;

    public function __construct(DevoteeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllDevotees()
    {
        return $this->repository->all();
    }

    public function createDevotee(array $data)
    {
        // Handle photo upload here if necessary
        return $this->repository->create($data);
    }

    public function updateDevotee($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function getDevoteeById($id)
    {
        return $this->repository->find($id);
    }

    public function deleteDevotee($id)
    {
        return $this->repository->delete($id);
    }
}
