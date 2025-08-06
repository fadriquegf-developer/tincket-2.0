<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\Mailing;
use App\Models\Setting;
use App\Mail\MailingMail;
use App\Models\FormField;
use Illuminate\Http\Request;
use App\Traits\AllowUsersTrait;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use App\Services\MailerBrandService;
use Illuminate\Support\Facades\Mail;
use Backpack\CRUD\app\Library\Widget;
use App\Http\Requests\MailingCrudRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class MailingCrudController extends CrudController
{

    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use CrudPermissionTrait;
    use AllowUsersTrait;


    public function setup()
    {
        CRUD::setModel(Mailing::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/mailing');
        CRUD::setEntityNameStrings(__('backend.menu.mail'), __('backend.menu.mails'));
        //$this->setAccessUsingPermissions();
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

        if ($this->isSuperuser()) {
            CRUD::addButtonFromView('line', 'send', 'send', 'end');
        }

        CRUD::addButton('line', 'preview', 'view', 'crud::buttons.show', 'end');
        CRUD::addButtonFromModelFunction('line', 'edit_if_not_sent', 'editButton', 'end');
        //CRUD::addButton('line', 'revisions', 'view', 'crud::buttons.revisions', 'end');
        CRUD::addButton('line', 'delete', 'view', 'crud::buttons.delete', 'end');

        CRUD::setValidation(MailingCrudRequest::class);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.mail.campaign_name'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'slug',
            'label' => __('backend.mail.campaign_slug'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'subject',
            'label' => __('backend.mail.subject'),
        ]);

        CRUD::addColumn([
            'name' => 'is_sent',
            'label' => __('backend.mail.status'),
            'type' => 'boolean',
            'options' => [0 => 'Pending', 1 => 'Sent'],
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
        //Si venimos de filtrar usuarios
        if (session('session') || session('client.search') || session('client.from_to')) {
            $value = session('session');
            $queryClients = Client::ownedByBrand()->where('newsletter', true);

            if (session('session')) {
                $queryClients->whereHas('carts', function ($query) use ($value) {
                    $query->whereHas('inscriptions', function ($query) use ($value) {
                        $query->whereHas('session', function ($query) use ($value) {
                            $query->where('id', $value);
                        });
                    });
                });
            }

            if (session('client.search')) {
                $queryClients->where(function ($query) {
                    $query->where('email', 'like', "%" . session('client.search') . "%")
                        ->orWhere('surname', 'like', "%" . session('client.search') . "%")
                        ->orWhere('surname', 'like', "%" . session('client.search') . "%");
                });
            }


            if (session('client.from_to')) {
                $dates = json_decode(session('client.from_to'));
                $queryClients->where('created_at', '>=', $dates->from);
                $queryClients->where('created_at', '<=', $dates->to . ' 23:59:59');
            }

            $users = $queryClients->get()->pluck('email')->toArray();
            $users = implode(",", $users);
        }

        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.mail.campaign_name'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ]
        ]);

        CRUD::addField([
            'name' => 'slug',
            'label' => __('backend.mail.campaign_slug'),
            'type' => 'slug',
            'target' => 'name',
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ]
        ]);

        CRUD::addField([
            'name' => 'subject',
            'label' => __('backend.mail.subject'),
            'type' => 'text',
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
            ]
        ]);

        CRUD::addField([
            'name' => 'locale',
            'label' => __('backend.mail.locale'),
            'type' => 'select_from_array',
            'options' => config('backpack.crud.locales'),
            'wrapperAttributes' => [
                'class' => 'form-group col-xs-12 col-sm-6',
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
            'content' => asset('js/mailing.js'),
        ]);

        CRUD::addField([
            'name' => 'content',
            'label' => __('backend.mail.contents'),
            'type' => 'ckeditor',
            
        ]);

        CRUD::addField([
            'name' => 'embedded_entities',
            'label' => __('backend.mail.extra_content'),
            'type' => 'embedded_entities', // nombre del blade
            'fake' => true,
            'store_in' => 'extra_content',
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        /*if (\Auth::user()->hasRole('admin')) {
            CRUD::addField([
                'name' => 'testing_email',
                'label' => __('backend.mail.test_it'),
                'type' => 'tincket.mailings.test_it',
            ]);
        }*/
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
        \Log::info('[MailingCrudController] Enviando mailing', [
            'mailing_id' => $id,
        ]);
        $mailing = Mailing::findOrFail($id);

        // Divide en chunks de 500
        $chunks = array_chunk(explode(',', $mailing->emails), 500);

        foreach ($chunks as $chunk) {
            // El mailer de la marca
            $mailer = (new MailerBrandService($mailing->brand->code_name))->getMailer();

            \Log::info('[MailingCrudController] Encolando mailing', ['bcc' => $chunk]);

            $mailer->to('noreply@yesweticket.com')->queue(new MailingMail($mailing, false, $chunk));
        }

        $mailing->update(['is_sent' => true]);

        Alert::success('Mailing encolado correctamente')->flash();
        return back();
    }

    private function addFieldInterests(): void
    {
        /* 1. localizar opciones ---------------------------------------- */
        $fieldId = Setting::where('brand_id', get_current_brand()->id)
            ->where('key', 'ywt.mail_interests_field_id')
            ->value('value');
        if (!$fieldId)
            return;

        $formField = FormField::find($fieldId);
        if (!$formField)
            return;

        /* 2. añadir UNA vez el hidden raíz ----------------------------- */
        CRUD::addField([
            'name' => 'interests',
            'type' => 'hidden',
            'value' => '',
        ]);

        /* 3. valores guardados (para edición) -------------------------- */
        $saved = optional($this->crud->getCurrentEntry())->interests ?? [];

        /* 4. casillas --------------------------------------------------- */
        foreach ($formField->config->options->values as $opt) {

            CRUD::addField([
                'name' => "interests[{$opt->key}]",
                'label' => $opt->labels->{brand_setting('app.locale')},
                'type' => 'checkbox',
                'default' => ($saved[$opt->key] ?? 0) == 1 ? 1 : 0,
                'wrapperAttributes' => ['class' => 'form-group col-md-2'],
            ]);
        }
    }

}
