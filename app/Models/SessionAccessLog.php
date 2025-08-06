<?php

namespace App\Models;

use App\Models\User;
use App\Models\Session;
use App\Models\Inscription;
use App\Traits\OwnedModelTrait;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

/**
 * Register acces in one session. Control in an out event
 */
class SessionAccessLog extends BaseModel
{

    use CrudTrait;
    use OwnedModelTrait;


    protected $fillable = [
        'session_id',
        'inscription_id',
        'out_event',
        'origin',
        'user_id',
        'created_at'
    ];

    protected $casts = [
        'out_event' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }
}
