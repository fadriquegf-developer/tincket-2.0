<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignatedRate extends BaseModel
{
    protected $table = 'assignated_rates';

    protected $fillable = [
        'session_id',
        'rate_id',
        'price',
        'max_on_sale',
        'max_per_order',
        'available_since',
        'available_until',
        'is_public',
        'is_private',
        'max_per_code',
        'validator_class',
        'assignated_rate_type',
        'assignated_rate_id',
    ];

    protected $casts = [
        'validator_class' => 'array',
    ];
    
    public $timestamps = false;

    public function rate()
    {
        return $this->belongsTo(Rate::class);
    }

    protected $appends = ['form_id'];

    public function getFormIDAttribute()
    {
        return $this->rate->form_id ?? null;
    }
}
