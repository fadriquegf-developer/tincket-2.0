<?php

namespace App\Models;

use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsUserOnCreate;
use App\Traits\SetsBrandOnCreate;
use App\Observers\MailingObserver;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Http\Controllers\Admin\MailingCrudController;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;

class Mailing extends BaseModel
{

    use OwnedModelTrait;
    use CrudTrait;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use Sluggable;
    use SluggableScopeHelpers;
    use SetsBrandOnCreate;
    use SetsUserOnCreate;
    use LogsActivity;

    const HTML_EXTENSION = "html";
    const PLAIN_EXTENSION = "plain";

    protected $hidden = [];
    protected $fillable = [
        'name',
        'locale',
        'subject',
        'slug',
        'emails',
        'content',
        'extra_content',
        'is_sent',
        'locale',
        'interests',
        'brand_id',
        'user_id',
    ];
    protected $appends = [];
    protected $fakeColumns = ['extra_content'];
    protected $casts = [
        'extra_content' => 'array',
        'interests' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        parent::observe(MailingObserver::class);
    }

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'slug_or_name',
            ],
        ];
    }

    public function getSlugOrNameAttribute()
    {
        if ($this->slug != '') {
            return $this->slug;
        }

        return $this->name;
    }

    public function getContentFileNameAttribute()
    {
        return sprintf("%s-%s", $this->id, $this->slug);
    }

    public function setEmailsAttribute($data)
    {
        // we clean new lines and spaces
        $data = str_replace("\r", ",", $data);
        $data = str_replace("\n", ",", $data);
        $data = array_values(array_filter(explode(",", $data)));

        $data = collect($data)->transform(function ($value) {
            $value = str_replace("'", "", $value);
            $value = str_replace("\"", "", $value);
            $value = trim($value);

            return $value;
        })->implode(',');

        $this->attributes['emails'] = $data;
    }

    public function editButton()   // ← SIN argumentos
    {
        // si ya está enviado, no pintamos nada
        if ($this->is_sent) {
            return '';
        }

        // botón Update estándar de Backpack
        return view('crud::buttons.update', [
            'entry' => $this,
            'crud' => app('crud'),   // instancia actual de CrudPanel
        ]);
    }

    public function scopePending($query)
    {
        return $query->whereNull('success_sent')
            ->whereNotNull('send_at')
            ->where('send_at', '<=', now());
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('success_sent');
    }

    public function getInterestsList(): string
    {

        $keys = collect($this->interests ?? [])
            ->filter(fn($v) => $v == 1 || $v === '1')
            ->keys();

        return $keys->isEmpty()
            ? '–' : $keys->implode(', ');
    }

    public function getExtraContentSummary(): string
    {
        $data = $this->extra_content;

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        // Obtener el sub-array; puede venir como string JSON también
        $entities = $data['embedded_entities'] ?? [];
        if (is_string($entities)) {
            $entities = json_decode($entities, true) ?: [];
        }

        // Formatear «Event #176, Page #54, …»
        $list = collect($entities)->map(function ($e) {
            $type = class_basename($e['embeded_type'] ?? '');
            $id = $e['embeded_id'] ?? '';
            return trim("$type #$id");
        })->filter();

        return $list->isEmpty() ? '–' : $list->implode(', ');
    }

    public function getEmbeddedEntitiesAttribute()
    {
        return collect($this->extra_content['embedded_entities'] ?? [])->map(function ($entity) {
            if (isset($entity['embeded_type']) && $entity['embeded_id'])
                return app()->make($entity['embeded_type'])->find($entity['embeded_id']);
        });
    }
}
