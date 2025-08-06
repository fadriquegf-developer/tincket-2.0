<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Status extends BaseModel
{
    use CrudTrait;
    use HasTranslations;
    use LogsActivity;

    public $translatable = [
        'name'
    ];
}
