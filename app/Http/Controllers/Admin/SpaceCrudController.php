<?php

namespace App\Http\Controllers\Admin;

use DB;
use App\Models\Slot;
use App\Models\User;
use App\Models\Zone;
use App\Models\Space;
use App\Models\Location;
use Illuminate\Support\Str;
use App\Models\SpaceConfiguration;
use App\Traits\CrudPermissionTrait;
use App\Http\Requests\SpaceCrudRequest;
use Illuminate\Support\Facades\Storage;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\Pro\Http\Controllers\Operations\FetchOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class SpaceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy;
    }
    use CrudPermissionTrait;
    use FetchOperation;

    public function setup()
    {
        CRUD::setModel(Space::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/space');
        CRUD::setEntityNameStrings(__('backend.menu.space'), __('backend.menu.spaces'));
        $this->setAccessUsingPermissions();
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.space.spacename'),
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere(DB::raw('lower(name)'), 'like', '%' . strtolower($searchTerm) . '%');
            },
        ]);
        CRUD::addColumn([
            'label' => __('backend.space.location'),
            'type' => 'select',
            'name' => 'location_id',
            'entity' => 'location',
            'attribute' => 'name',
            'model' => Location::class,
        ]);
        CRUD::addColumn([
            'label' => __('backend.space.created-by'),
            'type' => 'select',
            'name' => 'user_id',
            'entity' => 'user',
            'attribute' => 'email',
            'model' => User::class,
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(SpaceCrudRequest::class);
        $this->addBasicFields($isCreate = true);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(SpaceCrudRequest::class);
        $this->addBasicFields($isCreate = false);

        $space = $this->crud->getCurrentEntry();
        if ($space->svg_path && \Storage::disk('public')->exists($space->svg_path)) {
            $this->setLayoutTab($space);
        }
    }

    public function store(SpaceCrudRequest $request)
    {
        $response = $this->traitStore();
        $space = $this->crud->getCurrentEntry();
        $this->parseSvgPath($space);
        return $response;
    }

    public function update(SpaceCrudRequest $request)
    {
        $this->updateSlotLabels($request);
        return $this->traitUpdate();
    }

    /** 
     * Añade todos los campos “Basic”, inyectando el upload sólo en create.
     */
    private function addBasicFields(bool $isCreate)
    {
        CRUD::addField([
            'name' => 'name',
            'type' => 'text',
            'label' => __('backend.space.spacename'),
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'capacity',
            'type' => 'number',
            'label' => __('backend.space.spacecapacity'),
            'wrapperAttributes' => ['class' => 'form-group col-md-6'],
            'tab' => 'Basic',
        ]);

        if ($isCreate) {
            CRUD::addField([
                'name' => 'svg_path',
                'type' => 'upload',
                'label' => __('backend.space.svglayout'),
                'withFiles' => [
                    'disk' => 'public',
                    'path' => 'uploads/' . get_current_brand()->code_name . '/spaces',
                    'deleteWhenEntryIsDeleted' => true,
                ],
                'tab' => 'Basic',
            ]);
        }

        CRUD::addField([
            'name' => 'description',
            'label' => __('backend.space.space_description'),
            'type' => 'ckeditor',
            'extraPlugins' => ['oembed'],
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'location',
            'type' => 'relationship',
            'label' => __('backend.space.location'),
            'ajax' => true,
            'inline_create' => [
                'entity' => 'location',
                'modalClass' => 'modal-lg',
                'entity_permission' => false,
            ],
            'minimum_input_length' => 0,
            'attribute' => 'name',
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'hide',
            'type' => 'switch',
            'label' => __('backend.space.hide'),
            'tab' => 'Basic',
        ]);

        CRUD::addField([
            'name' => 'zoom',
            'type' => 'switch',
            'label' => __('backend.space.zoom'),
            'tab' => 'Basic',
        ]);
    }

    /**
     * Muestra el tab “Layout” con tu campo personalizado de SVG.
     * **Importante**: aquí el `type` debe coincidir con tu archivo
     * blade de campo. Si lo tenías en
     * resources/views/vendor/backpack/crud/fields/tincket/svg_layout.blade.php,
     * usa `'type' => 'tincket.svg_layout'`.
     */
    private function setLayoutTab(Space $space)
    {
        $this->data['slots_map'] = $space->slots->map(fn($slot) => [
            'id' => $slot->id,
            'name' => $slot->name,
            'status_id' => $slot->status_id,
            'comment' => $slot->comment,
            'x' => $slot->x,
            'y' => $slot->y,
            'zone_id' => $slot->zone_id,
        ])->all();

        // Todas las zonas del espacio
        $this->data['zones_map'] = $space->zones->map(fn($z) => [
            'id'    => $z->id,
            'name'  => $z->name,
            'color' => $z->color,
        ])->all();

        CRUD::addField([
            'name' => 'svg_path',
            'type' => 'svg_layout', // <–– o el namespace que uses para tu blade
            'label' => '',
            'wrapperAttributes' => ['class' => 'col-xs-12 text-center'],
            'tab' => 'Layout',
        ]);
    }

    /**
     * Lee el SVG recién subido, inyecta data-slot-id, crea los Slots
     * y deja sólo un fichero en disco: el que ya lleva los id.
     */

    private function parseSvgPath(Space $space)
    {

        if ($space->svg_path) {

            $defaultZone = Zone::firstOrCreate(['space_id' => $space->id, 'name' => 'Zona 1']);
            $svg_path = \Storage::disk('public')->path($space->svg_path);

            $doc = new \DOMDocument;
            $doc->preserveWhiteSpace = false;
            $doc->load($svg_path);

            $xpath = new \DOMXPath($doc);
            // register the default namespace
            $xpath->registerNameSpace('svg', 'http://www.w3.org/2000/svg');
            $query = "//svg:*[@class='slot']";
            $entries = $xpath->query($query);

            foreach ($entries as $entry) {
                $slot = new Slot();
                $slot->space()->associate($space);
                $posX = $entry->getAttribute('data-position-x');
                $posY = $entry->getAttribute('data-position-y');
                $slot->x = ($posX !== '') ? $posX : null;
                $slot->y = ($posY !== '') ? $posY : null;
                $slot->zone_id = $defaultZone->id;
                $slot->save();

                $oldNode = $entry;
                $newNode = $entry->cloneNode();

                $newNode->setAttribute('data-slot-id', $slot->id);
                $entry->parentNode->replaceChild($newNode, $oldNode);
            }

            $space->capacity = $entries->length;


            $doc->save($svg_path);
            $space->svg_path = $doc->documentURI;

        }
    }




    private function updateSlotLabels(SpaceCrudRequest $request): void
    {
        $payload = json_decode($request->input('slot_labels', '[]'), true);
        if (!is_array($payload) || empty($payload)) {
            return;
        }

        DB::transaction(function () use ($payload) {
            foreach ($payload as $data) {
                if (empty($data['id'])) {
                    continue;
                }
                $slot = Slot::find($data['id']);
                if (!$slot) {
                    continue;
                }

                // campos propios
                foreach (['name', 'comment', 'x', 'y', 'status_id', 'zone_id'] as $col) {
                    if (array_key_exists($col, $data)) {
                        $slot->{$col} = $data[$col] !== '' ? $data[$col] : null;
                    }
                }
                $slot->save();
            }
        });
    }

    public function destroy($id)
    {
        // 1) impide borrado si hay sesiones
        $space = $this->crud->query->newQuery()->findOrFail($id);
        if ($space->sessions()->count() > 0) {
            abort(403, 'No puedes eliminar un espacio con sesiones asociadas.');
        }

        // 2) borra el SVG de disco
        if ($space->svg_path && \Storage::disk('public')->exists($space->svg_path)) {
            \Storage::disk('public')->delete($space->svg_path);
        }

        // 3) llama al destroy del trait, que hace el soft/force-delete
        return $this->traitDestroy($id);
    }

    public function fetchLocation()
    {
        return $this->fetch(Location::class);
    }
}
