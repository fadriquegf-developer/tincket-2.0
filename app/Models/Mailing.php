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
        'locale',
        'interests',
        'brand_id',
        'user_id',
        'status',
        'total_recipients',
        'batches_sent',
        'batches_failed',
        'processing_started_at',
        'processing_completed_at',
        'sent_at',
        'failed_at',
        'error_message',
    ];
    protected $appends = [];
    protected $fakeColumns = ['extra_content'];
    protected $casts = [
        'extra_content' => 'array',
        'interests' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'total_recipients' => 'integer',
        'batches_sent' => 'integer',
        'batches_failed' => 'integer',
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

    public function batches()
    {
        return $this->hasMany(MailingBatch::class);
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

    public function editButton()
    {
        // Si ya está enviado, no pintar nada
        if ($this->status === 'sent' || $this->status === 'processing') {
            return '';
        }

        // Botón Update estándar de Backpack
        return view('crud::buttons.update', [
            'entry' => $this,
            'crud' => app('crud'),
        ]);
    }

    public function getInterestsList(): string
    {
        $keys = collect($this->interests ?? [])
            ->filter(fn($v) => $v == 1 || $v === '1')
            ->keys();

        return $keys->isEmpty() ? '—' : $keys->implode(', ');
    }

    public function getExtraContentSummary(): string
    {
        $data = $this->extra_content;

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        $entities = $data['embedded_entities'] ?? [];
        if (is_string($entities)) {
            $entities = json_decode($entities, true) ?: [];
        }

        $list = collect($entities)->map(function ($e) {
            $type = class_basename($e['embeded_type'] ?? '');
            $id = $e['embeded_id'] ?? '';
            return trim("$type #$id");
        })->filter();

        return $list->isEmpty() ? '—' : $list->implode(', ');
    }

    public function getEmbeddedEntitiesAttribute()
    {
        return collect($this->extra_content['embedded_entities'] ?? [])->map(function ($entity) {
            if (isset($entity['embeded_type']) && $entity['embeded_id'])
                return app()->make($entity['embeded_type'])->find($entity['embeded_id']);
        });
    }


    /**
     * Obtener badge de estado mejorado con información clara
     */
    public function getStatusBadge(): string
    {
        switch ($this->status ?? 'draft') {
            case 'draft':
                return '
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary" style="min-width: 100px;">
                        <i class="la la-edit"></i> BORRADOR
                    </span>
                    <small class="text-muted">Sin enviar</small>
                </div>';

            case 'processing':
                $progress = $this->getProgress();
                $color = $progress < 30 ? 'danger' : ($progress < 70 ? 'warning' : 'info');
                return '
                <div class="d-flex flex-column">
                    <span class="badge bg-info mb-1" style="min-width: 120px;">
                        <i class="la la-spinner la-spin"></i> ENVIANDO...
                    </span>
                    <div class="progress" style="height: 20px; min-width: 120px;">
                        <div class="progress-bar bg-' . $color . ' progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: ' . $progress . '%"
                             aria-valuenow="' . $progress . '" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            ' . $progress . '%
                        </div>
                    </div>
                    <small class="text-muted mt-1">
                        ' . $this->batches_sent . ' de ' . ($this->batches_sent + $this->batches_failed) . ' lotes
                    </small>
                </div>';

            case 'sent':
                return '
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success" style="min-width: 100px;">
                        <i class="la la-check-circle"></i> ENVIADO
                    </span>
                    <div class="d-flex flex-column">
                        <small class="text-success">
                            <i class="la la-users"></i> ' . number_format($this->total_recipients) . ' destinatarios
                        </small>
                        ' . ($this->sent_at ? '<small class="text-muted">' . $this->sent_at->diffForHumans() . '</small>' : '') . '
                    </div>
                </div>';

            case 'partial':
                $successRate = $this->getProgress();
                $badgeColor = $successRate >= 80 ? 'warning' : 'danger';
                return '
                <div class="d-flex flex-column">
                    <span class="badge bg-' . $badgeColor . ' mb-1" style="min-width: 140px;">
                        <i class="la la-exclamation-triangle"></i> ENVÍO PARCIAL
                    </span>
                    <div class="d-flex gap-3">
                        <small class="text-success">
                            <i class="la la-check"></i> ' . $this->batches_sent . ' enviados
                        </small>
                        <small class="text-danger">
                            <i class="la la-times"></i> ' . $this->batches_failed . ' fallidos
                        </small>
                    </div>
                    <div class="progress mt-1" style="height: 15px;">
                        <div class="progress-bar bg-success" style="width: ' . $successRate . '%"></div>
                        <div class="progress-bar bg-danger" style="width: ' . (100 - $successRate) . '%"></div>
                    </div>
                </div>';

            case 'failed':
                return '
                <div class="d-flex flex-column">
                    <span class="badge bg-danger mb-1" style="min-width: 100px;">
                        <i class="la la-times-circle"></i> FALLÓ
                    </span>
                    ' . ($this->error_message ?
                    '<small class="text-danger text-truncate" style="max-width: 200px;" title="' . htmlspecialchars($this->error_message) . '">
                            ' . \Str::limit($this->error_message, 30) . '
                        </small>' : '') . '
                    ' . ($this->failed_at ? '<small class="text-muted">' . $this->failed_at->diffForHumans() . '</small>' : '') . '
                </div>';

            default:
                return '<span class="badge bg-secondary">' . strtoupper($this->status ?? 'DESCONOCIDO') . '</span>';
        }
    }

    /**
     * Obtener badge simple (para vistas compactas)
     */
    public function getStatusBadgeSimple(): string
    {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Borrador</span>',
            'processing' => '<span class="badge bg-info">Procesando</span>',
            'sent' => '<span class="badge bg-success">Enviado</span>',
            'partial' => '<span class="badge bg-warning">Parcial</span>',
            'failed' => '<span class="badge bg-danger">Error</span>',
        ];

        return $badges[$this->status ?? 'draft'] ?? '<span class="badge bg-secondary">Sin estado</span>';
    }

    /**
     * Obtener icono del estado
     */
    public function getStatusIcon(): string
    {
        $icons = [
            'draft' => 'la-edit text-secondary',
            'processing' => 'la-spinner la-spin text-info',
            'sent' => 'la-check-circle text-success',
            'partial' => 'la-exclamation-triangle text-warning',
            'failed' => 'la-times-circle text-danger',
        ];

        return $icons[$this->status ?? 'draft'] ?? 'la-question-circle';
    }

    /**
     * Obtener color del estado para gráficos
     */
    public function getStatusColor(): string
    {
        $colors = [
            'draft' => '#6c757d',      // Gris
            'processing' => '#0dcaf0',  // Cyan
            'sent' => '#198754',        // Verde
            'partial' => '#ffc107',     // Amarillo
            'failed' => '#dc3545',      // Rojo
        ];

        return $colors[$this->status ?? 'draft'] ?? '#6c757d';
    }

    /**
     * Obtener descripción legible del estado
     */
    public function getStatusDescription(): string
    {
        $descriptions = [
            'draft' => 'El mailing está guardado pero no se ha enviado',
            'processing' => 'El mailing se está enviando en este momento',
            'sent' => 'El mailing se envió correctamente a todos los destinatarios',
            'partial' => 'El mailing se envió pero algunos destinatarios no lo recibieron',
            'failed' => 'El envío falló y debe revisarse',
        ];

        return $descriptions[$this->status ?? 'draft'] ?? 'Estado desconocido';
    }

    /**
     * Calcular progreso del envío
     */
    public function getProgress(): int
    {
        $total = $this->batches_sent + $this->batches_failed;
        if ($total === 0) return 0;

        return round(($this->batches_sent / $total) * 100);
    }

    /**
     * Verificar si el mailing está en proceso
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Verificar si el mailing se puede editar
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'failed']);
    }

    /**
     * Verificar si el mailing se puede enviar
     */
    public function canBeSent(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Obtener información resumida del estado
     */
    public function getStatusInfo(): array
    {
        return [
            'status' => $this->status,
            'progress' => $this->getProgress(),
            'total_recipients' => $this->total_recipients ?? 0,
            'batches_sent' => $this->batches_sent ?? 0,
            'batches_failed' => $this->batches_failed ?? 0,
            'started_at' => $this->processing_started_at?->format('d/m/Y H:i'),
            'completed_at' => $this->processing_completed_at?->format('d/m/Y H:i'),
            'duration' => $this->processing_started_at && $this->processing_completed_at
                ? $this->processing_started_at->diffForHumans($this->processing_completed_at, true)
                : null,
        ];
    }
}
