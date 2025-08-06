<?php

namespace App\Models;

use App\Scopes\BrandScope;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use App\Traits\AllowUsersTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Backpack\CRUD\app\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasRoles;
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;
    use SetsBrandOnCreate;
    use LogsActivity;
    use AllowUsersTrait;
    /* use HasApiTokens; */

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'name',
        'email',
        'password',
        'api_token',
        'allowed_ips'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token'
    ];

    protected $tags = ['allowed_ips'];



    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        // Solo aplicar el BrandScope si NO es engine
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }

        static::deleting(function ($user) {
            if ($user->id == 1) {
                return false;
            }
        });
    }

    public function getBrandsList()
    {
        return $this->brands->pluck('name')->join(', ');
    }


    /* public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    } */



    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'brand_user')->withTimestamps();
    }

    public function updateNotifications()
    {
        return $this->belongsToMany(UpdateNotification::class)->withTimestamps();
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function Events()
    {
        return $this->hasMany(Event::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
   

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
    /* public function setPasswordAttribute($value) {
        $this->attributes['password'] = Hash::make($value);
    } */
}
