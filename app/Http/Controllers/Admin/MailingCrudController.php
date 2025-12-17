<?php

namespace App\Http\Controllers\Admin;

use App\Models\Mailing;
use App\Models\Setting;
use App\Models\FormField;
use Illuminate\Http\Request;
use App\Traits\AllowUsersTrait;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\MailingCrudRequest;
use App\Jobs\ProcessMailingJob;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class MailingCrudController extends CrudController
{

    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use CrudPermissionTrait;
    use AllowUsersTrait;


    public function setup()
    {
        CRUD::setModel(Mailing::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/mailing');
        CRUD::setEntityNameStrings(__('menu.mail'), __('menu.mails'));
        $this->setAccessUsingPermissions();
        $this->crud->allowAccess('send');

        $this->crud->setLabeller(function ($val) {
            return __('backend.mail.' . $val);
        });
        $this->crud->orderBy('created_at', 'desc');



        return $this;
    }

    protected function setupListOperation()
    {
        CRUD::removeAllButtonsFromStack('line');

        CRUD::addButtonFromView('line', 'send', 'send', 'beginning');
        CRUD::addButton('line', 'preview', 'view', 'crud::buttons.show', 'end');
        CRUD::addButtonFromModelFunction('line', 'edit_if_not_sent', 'editButton', 'end');
        CRUD::addButton('line', 'delete', 'view', 'crud::buttons.delete', 'end');

        CRUD::setValidation(MailingCrudRequest::class);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.mail.campaign_name'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'subject',
            'label' => __('backend.mail.subject'),
        ]);

        CRUD::addColumn([
            'name' => 'status_badge',
            'label' => __('backend.mail.status'),
            'type' => 'model_function',
            'function_name' => 'getStatusBadge',
            'escaped' => false,
            'limit' => true
        ]);

        CRUD::addColumn([
            'name' => 'total_recipients',
            'label' => __('backend.mail.recipients'),
            'type' => 'text',
            'value' => function ($entry) {
                if (!$entry->total_recipients) {
                    // Contar destinatarios si no está calculado
                    $count = substr_count($entry->emails, ',') + 1;
                    return number_format($count);
                }
                return number_format($entry->total_recipients);
            }
        ]);

        CRUD::addColumn([
            'name' => 'sent_at',
            'label' => __('backend.mail.sent_at'),
            'type' => 'datetime',
        ]);
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::removeColumns(['extra_content', 'interests', 'brand_id', 'user_id', 'emails']);

        CRUD::addColumn([
            'name' => 'emails',
            'label' => __('backend.mail.emails'),
            'type' => 'emails_collapse',
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'extra_content_summary',
            'label' => __('backend.mail.extra_content'),
            'type' => 'model_function',
            'function_name' => 'getExtraContentSummary',
            'limit' => 200,
        ]);

        CRUD::addColumn([
            'name' => 'interests_list',
            'label' => __('backend.mail.interests'),
            'type' => 'model_function',
            'function_name' => 'getInterestsList',
        ]);
    }


    protected function setupCreateOperation()
    {
        $this->crud->setValidation(MailingCrudRequest::class);

        $users = '';

        if (request()->filled('recipients_key')) {
            $key = request('recipients_key');
            $users = (string) Cache::get($key, '');

            // 👇 Borrar la cache una vez usada
            Cache::forget($key);
        }

        $users = collect(explode(',', str_replace(';', ',', $users)))
            ->map(fn($e) => trim($e))
            ->filter(fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->implode(',');

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.mail.campaign_name'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ]
        ]);

        CRUD::addField([
            'name' => 'slug',
            'label' => __('backend.mail.campaign_slug'),
            'type' => 'slug',
            'target' => 'name',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ]
        ]);

        CRUD::addField([
            'name' => 'subject',
            'label' => __('backend.mail.subject'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ]
        ]);

        CRUD::addField([
            'name' => 'locale',
            'label' => __('backend.mail.locale'),
            'type' => 'select_from_array',
            'options' => config('backpack.crud.locales'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ]
        ]);

        if (CRUD::getCurrentOperation() == 'create') {
            CRUD::addField([
                'name' => 'emails',
                'label' => __('backend.mail.emails'),
                'type' => 'textarea',
                'value' => $users
            ]);
        }

        if (CRUD::getCurrentOperation() == 'update') {
            CRUD::addField([
                'name' => 'emails',
                'label' => __('backend.mail.emails'),
                'type' => 'textarea',
            ]);
        }

        $this->addFieldInterests();

        CRUD::addField([
            'name' => 'load_emails',
            'label' => '',
            'type' => 'custom_html',
            'value' => "<div class=\"text-end\">"
                . "<span "
                . "data-emails-target=\"[name='emails']\" "
                . "data-emails-api=\"" . route('apibackend.client.subscribed') . "\" "
                . "data-emails-for-api=\"" . route('apibackend.client.subscribed-to') . "\" "
                . "class=\"btn btn-default btn-load-emails\">"
                . "<span class=\"la la-cloud-upload me-2\"></span> Load client emails"
                . "</span>"
                . "</div>"

        ]);

        Widget::add([
            'type' => 'script',
            'content' => \Illuminate\Support\Facades\Vite::asset('resources/js/mailing.js'),
        ]);

        CRUD::addField([
            'name' => 'content',
            'label' => __('backend.mail.contents'),
            'type' => 'ckeditor',

        ]);

        CRUD::addField([
            'name' => 'embedded_entities',
            'label' => __('backend.mail.extra_content'),
            'type' => 'embedded_entities',
            'fake' => true,
            'store_in' => 'extra_content',
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        if (\Auth::user()->hasRole('admin')) {
            CRUD::addField([
                'name' => 'testing_email',
                'label' => __('backend.mail.test_it'),
                'type' => 'test_it',
            ]);
        }
    }

    public function store(MailingCrudRequest $request)
    {
        $req = $this->crud->getRequest();

        // embedded_entities  (ya está como array)
        $req->merge([
            'embedded_entities' => json_decode(
                $req->input('embedded_entities', '[]'),
                true
            ),
        ]);

        $this->crud->setRequest($req);
        return $this->traitStore();
    }

    public function update(MailingCrudRequest $request)
    {
        $req = $this->crud->getRequest();

        $req->merge([
            'embedded_entities' => json_decode(
                $req->input('embedded_entities', '[]'),
                true
            ),
        ]);

        $this->crud->setRequest($req);
        return $this->traitUpdate();
    }


    public function send(Request $request, $id)
    {
        try {
            $mailing = Mailing::findOrFail($id);

            // Validar permisos y brand
            if (!$this->crud->hasAccess('send')) {
                abort(403);
            }

            // Verificar que el mailing pertenece a la brand actual
            if ($mailing->brand_id !== get_current_brand()->id) {
                abort(403, 'No puedes enviar mailings de otra brand');
            }

            if ($mailing->status === 'sent' || $mailing->status === 'processing') {
                Alert::warning('Este mailing ya fue enviado o está en proceso')->flash();
                return back();
            }

            // Validar que está en estado draft
            if ($mailing->status !== 'draft') {
                Alert::warning('Solo se pueden enviar mailings en estado borrador')->flash();
                return back();
            }

            // CAMBIAR ESTADO INMEDIATAMENTE
            $mailing->update([
                'status' => 'processing',
                'processing_started_at' => now()
            ]);

            // Despachar job principal
            ProcessMailingJob::dispatch($mailing)
                ->onQueue('mailings')
                ->delay(now()->addSeconds(5));

            Alert::success('Mailing encolado correctamente. Recibirás una notificación cuando se complete.')->flash();

            return back();
        } catch (\Exception $e) {
            \Log::error('[MailingCrudController] Error al enviar mailing', [
                'mailing_id' => $id,
                'error' => $e->getMessage()
            ]);

            Alert::error('Error al procesar el mailing: ' . $e->getMessage())->flash();
            return back();
        }
    }

    private function addFieldInterests(): void
    {
        /* 1. localizar opciones ---------------------------------------- */
        $fieldId = Setting::where('brand_id', get_current_brand()->id)
            ->where('key', 'ywt.mail_interests_field_id')
            ->value('value');

        if (!$fieldId) {
            \Log::warning('[MAILING DEBUG] No se encontró field_id de intereses');
            return;
        }

        $formField = FormField::find($fieldId);
        if (!$formField) {
            \Log::warning('[MAILING DEBUG] No se encontró FormField', ['field_id' => $fieldId]);
            return;
        }

        /* 2. añadir UNA vez el hidden raíz ----------------------------- */
        CRUD::addField([
            'name' => 'interests',
            'type' => 'hidden',
            'value' => '',
        ]);

        /* 3. valores guardados (para edición) -------------------------- */
        $saved = optional($this->crud->getCurrentEntry())->interests ?? [];

        /* 4. casillas --------------------------------------------------- */
        $locale = brand_setting('app.locale');

        foreach ($formField->config['options'] as $opt) {
            $label = $opt['label'][$locale] ?? reset($opt['label']);
            $value = $opt['value'];

            CRUD::addField([
                'name' => "interests[{$opt['value']}]",
                'label' => $label, // <- ¡string limpio!
                'type' => 'checkbox',
                'default' => ($saved[$opt['value']] ?? 0) == 1 ? 1 : 0,
                'wrapperAttributes' => ['class' => 'form-group col-md-2'],
                'attributes' => [
                    'class' => 'interest-checkbox',
                    'data-interest' => $value
                ],
            ]);
        }
    }
}
