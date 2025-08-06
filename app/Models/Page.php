<?php

namespace App\Models;

use App\Models\Brand;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\BackpackSluggable;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\HasTranslations;
use Backpack\CRUD\app\Models\Traits\SpatieTranslatable\SluggableScopeHelpers;

class Page extends BaseModel
{
    use CrudTrait;
    use HasTranslations;
    use SoftDeletes;
    use SetsBrandOnCreate;
    use BackpackSluggable;
    use SluggableScopeHelpers;
    use LogsActivity;
    use OwnedModelTrait;

    protected $table = 'pages';
    protected $fillable = ['template', 'name', 'title', 'slug', 'content', 'extras', 'brand_id'];
    protected $casts = ['extras' => 'array'];
    protected $fakeColumns = ['extras'];
    public $translatable = ['title', 'slug', 'content', 'extras'];

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function sluggable(): array
    {
        return [
            'slug' => ['source' => 'slug_or_title'],
        ];
    }

    public function getSlugOrTitleAttribute()
    {
        return $this->slug ?: $this->title;
    }

    public function getTemplateName()
    {
        return ucfirst(str_replace('_', ' ', $this->template));
    }

    public function getPageLink()
    {
        return url($this->slug);
    }

    public function getOpenButton()
    {
        $url = rtrim(brand_setting('clients.frontend.url'), '/') . '/pagina/' . $this->slug . '/' . $this->id;

        return '<a class="btn btn-sm btn-link pr-0" href="' . $url . '" target="_blank">' .
            '<i class="la la-eye"></i> ' . __('backend.page.open') . '</a>';
    }



}
