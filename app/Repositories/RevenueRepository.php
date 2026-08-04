<?php

namespace App\Repositories;

use App\Models\Revenue;

class RevenueRepository extends BaseRepository
{
    public function __construct(Revenue $model)
    {
        parent::__construct($model);
    }
}
