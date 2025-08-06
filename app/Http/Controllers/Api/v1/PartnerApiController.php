<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Code;
use App\Models\Setting;
use App\Models\Event;
use Illuminate\Support\Facades\Http;

class PartnerApiController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * Display all taxonomies in a hirearchical tree
     *
     */
    public function index()
    {
        $partners = request()->get('brand')->partnershipedChildBrands;

        return $this->json($partners);
    }

    public function show($code_name)
    {

        $brand =  request()->get('brand');
        $partners = $brand->partnershipedChildBrands;

        $partner = $partners->where('code_name', $code_name)->first();

        if (!$partner) {
            abort(404);
        }

        return $this->json($partner);
    }

    public function events($id)
    {
        $brand = request()->get('brand');

        $events = Event::published()->where('brand_id', $id)
            ->whereHas('taxonomies', function ($query) use ($brand) {
                $query->where('brand_id', $brand->id);
            })->whereHas('next_sessions', function ($query) {
                return $query
                    ->has('space.location');
            })->get()
            ->sortBy('next_session.starts_on')
            ->each(function ($event) {
                \App\Services\Api\EventService::addAttributes($event);
            });


        return $this->json($events);
    }

    public function store(\App\Http\Requests\Api\PartnerStoreApiRequest $request)
    {
        // Iniciar una transacción para asegurar la atomicidad
        return \DB::transaction(function () use ($request) {
            // Verificar el código de activación
            $code = Code::where([
                ['brand_id', '=', request()->get('brand.id', null)],
                ['keycode', '=', $request->codi_alta]
            ])->first();

            if (!$code || $code->promotor_id !== null) {
                return $this->json([
                    'message' => 'El codi no existeix o ja ha estat utilitzat',
                    'error' => true
                ], 400); // Respuesta con error 400
            }

            try {
                // Crear la marca
                $brand = (new \App\Services\BrandCreationService())->withJavajanTpv()->create($request->all());

                

                // Asignar capabilities a la marca (capability 3)
                $brand->capability()->associate(3);

                // Asignar el código de activación a la nueva marca creada
                $code->promotor_id = $brand->id;
                $code->save();

                // Crear el usuario promotor
                $user = (new \App\Services\Api\UserService())->createPromotorUser($request);

                // Asignar el usuario creado a la marca
                $brand->users()->attach($user->id);

                // Asignar administradores adicionales si existen
                if ($request->extra_admins) {
                    $brand->users()->attach($request->extra_admins);
                }

                // Añadir la nueva marca a la lista de marcas asociadas de la marca principal
                
                $primary_brand = request()->get('brand');
                $brand->parent_id = $primary_brand->id;
                $brand->save();

                // Asociar usuarios administradores de la marca principal a la nueva marca
                $brand->users()->syncWithoutDetaching($primary_brand->getBrandSuperAdmins());

                // Actualizar configuraciones de la marca (nombre y logo)
                Setting::where('brand_id', $brand->id)->where('key', 'backpack.base.project_name')->update(['value' => $brand->name]);
                Setting::where('brand_id', $brand->id)->where('key', 'backpack.base.logo_lg')->update(['value' => $brand->name]);
                Setting::where('brand_id', $brand->id)->where('key', 'backpack.base.logo_mini')->update(['value' => substr($brand->name, 0, 1)]);

                // Enviar correo electrónico al cliente con los datos del nuevo promotor
                //$this->sendEmailPartner($request);

                // Retornar respuesta exitosa
                return $this->json([
                    'user' => $user,
                    'error' => false,
                    'message' => "Alta de promotor realitzada correctament. En les pròximes 24h el teu domini serà donat d'alta. Hem enviat un email amb les teves dades d'accés."
                ], 200);
            } catch (\App\Exceptions\ApiException $e) {
                // Manejar errores específicos de la API
                return $this->json([
                    'error' => true,
                    'message' => $e->getMessage()
                ], 400);
            } catch (\Exception $e) {
                // Manejar cualquier otra excepción
                return $this->json([
                    'error' => true,
                    'message' => 'Ocurrió un error inesperado. Intenta nuevamente más tarde.'
                ], 500);
            }
        });
    }


    public function createSubdomain($code_name)
    {

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://yesweticket.com:2083',
            'headers' => [
                'Authorization' => 'cpanel yesweticket:' . env('CPANEL_API_KEY'),
                // needed when Laravel validation fails to notify client 
                // (then error code is 422)
                'Accept'                => 'application/json',
            ]
        ]);

        $response = $client->request('GET', '/execute/SubDomain/addsubdomain?domain=' . $code_name . '&rootdomain=yesweticket.com&dir=/public_html/engine/master/public');

        if ($response->getStatusCode() === 200) {
            return true;
        }
        //Enviar email con la razon del fallo
        \Log::error($response->reason);
        return false;
    }

    public function sendEmailPartner(\Illuminate\Http\Request $request)
    {
        $mailer = (new \App\Services\MailerBrandService(request()->get('brand')->code_name))->getMailer();
        $params = $request->json();

        $mailer->alwaysReplyTo($params->get('email'), $params->get('name'));
        $mailer->alwaysFrom('noreply@yesweticket.com', 'YesWeTicket');

        $mailerContent = new \App\Mail\GenericMailer();
        $mailerContent->view = config('base.emails.alta-promotor');
        $mailerContent->subject = config('mail.contact.subject', "Alta promotor");
        $mailerContent->viewData = [
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => $request->get('password'),
            'code_name' => $request->get('code_name'),
            'front_url' => rtrim(config('clients.frontend.url', ''), '/')
        ];

        // Temporally disable send email to user Only sent to javajan
        //Enviamos emails a los extra emails que mande el cliente
        if ($request->extra_emails) {
            $mailer->to($request->extra_emails)->send($mailerContent);
        }
        $mailer->to(['fadrique.javajan@gmail.com', 'gemma.javajan@gmail.com'])->send($mailerContent);
    }
}
