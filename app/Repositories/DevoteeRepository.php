<?php

namespace App\Repositories;

use App\Models\Devotee;

class DevoteeRepository extends BaseRepository
{
    public function __construct(Devotee $model)
    {
        parent::__construct($model);
    }
}
