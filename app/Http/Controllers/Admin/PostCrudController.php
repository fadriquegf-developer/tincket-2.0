<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\Taxonomy;
use App\Observers\PostObserver;
use App\Traits\AllowUsersTrait;
use App\Traits\CrudPermissionTrait;
use App\Uploaders\WebpImageUploader;
use App\Http\Requests\PostCrudRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

class PostCrudController extends CrudController
{
    use CrudPermissionTrait;
    use AllowUsersTrait;
    use ListOperation;
    use CreateOperation {
        store as traitStore;
    }
    use UpdateOperation {
        update as traitUpdate;
    }
    use DeleteOperation;
    use \Backpack\Pro\Http\Controllers\Operations\DropzoneOperation;

    protected $brand;

    public function setup()
    {
        $this->brand = get_current_brand();

        CRUD::setModel(Post::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/post');
        CRUD::setEntityNameStrings(__('menu.post'), __('menu.posts'));

        CRUD::allowAccess('show');
    }

    protected function setupListOperation()
    {

        CRUD::addFilter(
            [
                'name' => 'published',
                'type' => 'simple',
                'label' => __('backend.post.published'),
            ],
            false,
            function () {
                CRUD::addClause('published');
            }
        );

        CRUD::addFilter(
            [
                'name' => 'taxonomies',
                'type' => 'dropdown',
                'label' => __('backend.post.taxonomies'),
            ],
            function () {
                $brand = get_current_brand();
                $taxonomy_id = $brand->extra_config['posting_taxonomy_id'] ?? null;

                if (!$taxonomy_id) {
                    return [];
                }

                return Taxonomy::where('parent_id', $taxonomy_id)
                    ->active()
                    ->get()
                    ->mapWithKeys(fn($tax) => [
                        $tax->id => $tax->getTranslation('name', app()->getLocale())
                    ])
                    ->toArray();
            },
            function ($value) {
                CRUD::addClause('whereHas', 'taxonomies', function ($query) use ($value) {
                    $query->where('taxonomies.id', $value);
                });
            }
        );

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.post.posttitle')
        ]);

        CRUD::addColumn([
            'name' => 'slug',
            'label' => __('backend.post.postslug')
        ]);

        CRUD::addColumn([
            'label' => __('backend.post.category'),
            'type' => "select_multiple",
            'name' => 'taxonomies',
            'entity' => 'taxonomies',
            'attribute' => "name",
            'model' => Taxonomy::class,
        ]);

        CRUD::addColumn([
            'name' => 'publish_on',
            'label' => __('backend.post.publish_on'),
            'type' => 'date.str'
        ]);
        CRUD::addButtonFromView('top', 'page_help', 'page_help', 'end');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(PostCrudRequest::class);
        $this->setupFields();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupFields()
    {
        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => __('backend.post.posttitle'),
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'slug',
            'label' => __('backend.post.slug'),
            'type' => 'slug',
            'target' => 'name',
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'lead',
            'type' => 'text',
            'label' => __('backend.post.lead'),
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'publish_on',
            'label' => __('backend.post.publish'),
            'type' => 'datetime',
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);

        $taxonomy_id = get_current_brand()->extra_config['posting_taxonomy_id'];

        if ($taxonomy_id) {
            $options = Taxonomy::where('parent_id', $taxonomy_id)
                ->active()
                ->get()
                ->mapWithKeys(fn($tax) => [
                    $tax->id => $tax->getTranslation('name', app()->getLocale())
                ]);

            // Solo añadir el campo si hay opciones
            if ($options->isNotEmpty()) {
                CRUD::field([
                    'label' => __('backend.post.taxonomies'),
                    'type' => 'checklist',
                    'name' => 'taxonomies',
                    'entity' => 'taxonomies',
                    'attribute' => 'title',
                    'model' => Taxonomy::class,
                    'options' => fn() => $options,
                    'pivot' => true,
                ]);
            }
        }

        CRUD::addField([
            'name' => 'meta_title',
            'label' => __('backend.post.meta_title'),
            'type' => 'textarea',
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'meta_description',
            'label' => __('backend.post.meta_description'),
            'type' => 'textarea',
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'body',
            'label' => __('backend.post.postbody'),
            'type' => 'ckeditor',

        ]);

        CRUD::addField([
            'name' => 'image',
            'label' => __('backend.post.posterimage'),
            'type' => 'image',
            'crop' => true,
            'upload' => true,
            'prefix' => 'storage/', // Solo añadir storage/ al renderizar
            'withFiles' => [
                'disk' => 'public',
                'uploader' => WebpImageUploader::class,
                'path' => 'uploads/' . get_current_brand()->code_name . '/post/',
                'resize' => [
                    'max' => 1200,
                ],
                'conversions' => [
                    'md' => 992,
                    'sm' => 576,
                ],
                'custom_name' => 'image',
            ],
        ]);

        CRUD::addField([
            'name' => 'gallery',
            'label' => __('backend.events.gallery'),
            'type' => 'dropzone',
            'upload' => true,
            'disk' => 'public',
            'hint' => __('backend.events.minWidth'),
        ]);
    }

    public function store(PostCrudRequest $request)
    {
        $response = $this->traitStore();

        $taxonomies = $request->input('taxonomies');

        if (is_string($taxonomies)) {
            $taxonomies = json_decode($taxonomies, true);
        }

        $this->crud->entry->taxonomies()->sync($taxonomies ?? []);
        return $response;
    }

    public function update(PostCrudRequest $request)
    {
        $response = $this->traitUpdate();
        $taxonomies = $request->input('taxonomies');

        if (is_string($taxonomies)) {
            $taxonomies = json_decode($taxonomies, true);
        }

        $this->crud->entry->taxonomies()->sync($taxonomies ?? []);
        return $response;
    }
}
