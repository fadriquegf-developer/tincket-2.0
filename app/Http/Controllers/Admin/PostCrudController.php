<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\Taxonomy;
use App\Observers\PostObserver;
use Illuminate\Support\Str;
use App\Traits\AllowUsersTrait;
use App\Traits\SetsBrandOnCreate;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use App\Uploaders\WebpImageUploader;
use App\Http\Requests\PostCrudRequest;
use Intervention\Image\Laravel\Facades\Image;
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
    use CreateOperation { store as traitStore;
    }
    use UpdateOperation { update as traitUpdate;
    }
    use DeleteOperation;
    use \Backpack\Pro\Http\Controllers\Operations\DropzoneOperation;

    protected $brand;

    public function setup()
    {
        $this->brand = get_current_brand();

        CRUD::setModel(Post::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/post');
        CRUD::setEntityNameStrings(__('backend.menu.post'), __('backend.menu.posts'));

        CRUD::allowAccess('show');

    }

    protected function setupListOperation()
    {

        if ($this->isSuperuser()) {
            $this->crud->addButtonFromModelFunction('top', 'optimice-images', 'optimiceImages', 'end');
        }

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
                $taxonomy_id = optional($this->brand->extra_config)->posting_taxonomy_id;
                return $taxonomy_id
                    ? Taxonomy::whereParentId($taxonomy_id)->pluck('name', 'id')->toArray()
                    : [];
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
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(PostCrudRequest::class);
        $this->setupFields();
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(PostCrudRequest::class);
        $this->setupFields();
    }

    protected function setupFields()
    {
        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => __('backend.post.posttitle'),
            'wrapperAttributes' => ['class' => 'form-group col-xs-12 col-sm-6'],
        ]);

        CRUD::addField([
            'name' => 'slug',
            'label' => __('backend.post.slug'),
            'type' => 'slug',
            'target' => 'name',
            'wrapperAttributes' => ['class' => 'form-group col-xs-12 col-sm-6'],
        ]);

        CRUD::addField([
            'name' => 'lead',
            'type' => 'text',
            'label' => __('backend.post.lead'),
            'wrapperAttributes' => ['class' => 'form-group col-xs-12 col-sm-6'],
        ]);

        CRUD::addField([
            'name' => 'publish_on',
            'label' => __('backend.post.publish'),
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'DD/MM/YYYY HH:mm',
                'language' => 'ca',
            ],
            'wrapperAttributes' => ['class' => 'form-group col-xs-12 col-sm-6'],
        ]);

        //no funciona
        /* $taxonomy_id = optional(get_current_brand()->extra_config)->posting_taxonomy_id;
        if ($taxonomy_id) {
            CRUD::addField([
                'label' => __('backend.post.taxonomies'),
                'type' => 'checklist_from_builder',
                'name' => 'taxonomies',
                'entity' => 'taxonomy',
                'attribute' => 'name',
                'builder' => Taxonomy::whereParentId($taxonomy_id),
                'morph' => true,
                'hint' => '<span class="small">' . __('backend.post.select_as_many_taxonomies') . '</span>',
            ]);
        }
 */
        CRUD::addField([
            'name' => 'meta_title',
            'label' => __('backend.post.meta_title'),
            'type' => 'textarea',
            'wrapperAttributes' => ['class' => 'form-group col-xs-12 col-sm-6'],
        ]);

        CRUD::addField([
            'name' => 'meta_description',
            'label' => __('backend.post.meta_description'),
            'type' => 'textarea',
            'wrapperAttributes' => ['class' => 'form-group col-xs-12 col-sm-6'],
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
            'withFiles' => [
                'disk' => 'public',
                'uploader' => WebpImageUploader::class,
                'path' => 'uploads/' . get_current_brand()->code_name . '/post',
                'resize' => [
                    'max' => 1200,
                ],
                'conversions' => [
                    'md' => 800,
                    'sm' => 400,
                ],
            ],
        ]);

        CRUD::addField([
            'name' => 'gallery',
            'label' => __('backend.events.gallery'),
            'type' => 'dropzone',
            'upload' => true,
            'disk' => 'public',
            'prefix' => 'uploads/'
                . get_current_brand()->code_name
                . '/post/'
                . ($this->crud->getCurrentEntry()?->id ?? '__TEMP__')
                . '/',
        ]);
    }

    public function store(PostCrudRequest $request)
    {

        $response = $this->traitStore();
        $post = $this->crud->getCurrentEntry();

        PostObserver::processGalleryImages($post);

        $this->crud->entry->taxonomies()->sync($request->input('taxonomies', []));
        return $response;
    }

    public function update(PostCrudRequest $request)
    {
        $response = $this->traitUpdate();
        $this->crud->entry->taxonomies()->sync($request->input('taxonomies', []));
        return $response;
    }

    public function imagesToImage()
    {
        $posts = Post::withTrashed()->where('image', null)->get();
        foreach ($posts as $post) {
            $post->image = $post->images[0];
            $post->save();
        }
        return 'hecho';
    }

    public function optimizeImages()
    {

        $posts = Post::where('brand_id', $this->brand->id)->get();
        $destination_path = "uploads/{$this->brand->code_name}/post";

        foreach ($posts as $post) {
            $extension = pathinfo($post->image, PATHINFO_EXTENSION);
            if ($extension !== 'webp' && $post->image && $this->get_http_response_code($post->image) === "200") {
                $image_base64 = base64_encode(file_get_contents($post->image));
                $image = Image::make($image_base64)->resize(800, null, fn($c) => $c->aspectRatio())->encode('webp', 80);
                $filename = $post->id . "-post-image-" . str_random(6) . ".webp";

                if (\Storage::disk('public')->put("{$destination_path}/{$filename}", $image->stream())) {
                    $post->image = "{$destination_path}/{$filename}";
                    $post->save();
                }
            }
        }

        Alert::success(__('Imatges optimitzades correctament'))->flash();
        return back();
    }

    public function get_http_response_code($url)
    {
        try {
            $headers = get_headers($url);
            return substr($headers[0], 9, 3);
        } catch (\Throwable $th) {
            return '500';
        }
    }
}
