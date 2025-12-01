<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackRule extends BaseModel
{

    protected $fillable = ['number_sessions', 'percent_pack', 'price_pack', 'all_sessions'];

    protected $casts = [
        'all_sessions' => 'boolean',
    ];

    public function pack()
    {
        return $this->belongsTo(Pack::class);
    }

    public function setAllSessionsAttribute($value)
    {
        $this->attributes['all_sessions'] = $value;

        // if "all_sessions" is checked, the "number_sessions" makes no sense, 
        // so set it to NULL
        if ($value) {
            $this->attributes['number_sessions'] = null;
        }
    }
}
