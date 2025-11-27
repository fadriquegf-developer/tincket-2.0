<?php

namespace App\Models;

use App\Scopes\BrandScope;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailingBatch extends Model
{
    use SetsBrandOnCreate;

    protected $fillable = [
        'mailing_id',
        'brand_id',
        'batch_number',
        'recipients',
        'status',
        'started_at',
        'sent_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'recipients' => 'array',
        'started_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        // Aplicar scope de brand automáticamente
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function mailing(): BelongsTo
    {
        return $this->belongsTo(Mailing::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}