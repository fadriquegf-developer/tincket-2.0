<?php

namespace App\Models;

use App\Models\Page;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\HasTranslations;

class MenuItem extends BaseModel
{
    use CrudTrait;
    use SoftDeletes;
    use SetsBrandOnCreate;
    use HasTranslations;
    use LogsActivity;
    use OwnedModelTrait;

    protected $table = 'menu_items';
    protected $fillable = ['name', 'type', 'link', 'page_id', 'parent_id', 'brand_id'];
    public $translatable = ['name', 'link'];
    protected $casts = [
        'link' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            if ($item->type === 'page_link') {
                $item->link = null;
            }

            if ($item->type === 'internal_link' && empty($item->link)) {
                $item->link = '#';
            }
        });
    }

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    private function getLinksArray()
    {
        $locales = array_fill_keys(array_keys(config('backpack.crud.locales')), null);

        switch ($this->type) {
            case 'external_link':
                return array_merge($locales, json_decode($this->attributes['link'] ?? '[]', true));
            case 'internal_link':
                return array_merge($locales, json_decode($this->attributes['link'] ?? '[]', true));
            default: //page_link
                if ($this->page)
                    return array_merge($locales, json_decode($this->page->getAttributes()['slug'] ?? '[]', true));
                return [];
        }
    }

    /**
     * Get all menu items, in a hierarchical collection.
     * @param int $id get the tree of subtree to be appended as children nodes
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getTree($id = null)
    {
        $menu = self::ownedByBrand()->whereParentId($id)->orderBy('lft')->get();

        $locales = array_keys(config('backpack.crud.locales'));

        $links = array_fill_keys($locales, null);

        $menu->each(function ($menu) use ($links) {
            $menu->attributes['link'] = json_encode($menu->getLinksArray());
            $menu->children = static::getTree($menu->id);
            unset($menu->page);
        });

        return $menu;
    }
}
