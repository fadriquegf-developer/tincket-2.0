<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Job extends BaseModel
{
    use CrudTrait;
    use LogsActivity;
    public $timestamps = false;

    protected $table = 'jobs';

    protected $guarded = [];
}

