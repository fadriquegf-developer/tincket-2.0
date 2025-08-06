<?php

namespace App\Models;


use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\HasTranslations;

class UpdateNotification extends BaseModel
{

    use CrudTrait;
    use LogsActivity;
    use HasTranslations;
    

    protected $table   = 'update_notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'version',
        'subject', // t
        'content' // t
    ];

    public $translatable = [
        'subject',
        'content'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

}
