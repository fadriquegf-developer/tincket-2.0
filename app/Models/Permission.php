<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\Permission\Models\Permission as OriginalPermission;

class Permission extends OriginalPermission
{
    use CrudTrait;
    use LogsActivity;

    protected $fillable = ['name', 'guard_name', 'updated_at', 'created_at'];
}
