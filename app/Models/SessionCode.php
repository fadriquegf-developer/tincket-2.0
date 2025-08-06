<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class SessionCode extends BaseModel
{

    use CrudTrait;

    protected $table = 'session_codes';
    protected $fillable = ['session_id', 'name', 'code'];

    protected static function boot()
    {
        parent::boot();
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

}
