<?php

namespace App\Models;

use App\Models\Client;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Model;

class FormFieldAnswer extends BaseModel
{

    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'form_field_answers';
    protected $fillable = ['client_id', 'field_id', 'answer'];

    public function form_field()
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

}
