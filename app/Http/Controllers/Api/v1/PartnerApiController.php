<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Code;
use App\Models\Event;
use App\Models\Setting;
use App\Services\MailerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use App\Http\Resources\PartnerResource;
use App\Models\Brand;
use App\Models\User;
use App\Scopes\BrandScope;
use Carbon\Carbon;

class PartnerApiController extends \App\Http\Controllers\Api\ApiController
{
    /**
     * Display all taxonomies in a hierarchical tree
     */
    public function index()
    {
        $partners = request()->get('brand')->partnershipedChildBrands;

        // Usar Resource para controlar qué datos se exponen
        return $this->json(PartnerResource::collection($partners));
    }

    public function show($code_name)
    {
        $brand = request()->get('brand');
        $partners = $brand->partnershipedChildBrands;

        $partner = $partners->where('code_name', $code_name)->first();

        if (!$partner) {
            abort(404);
        }

        return $this->json(new PartnerResource($partner));
    }

    public function events($id)
    {
        $brand = request()->get('brand');

        $events = Event::withoutGlobalScope(BrandScope::class)
            ->published()
            ->where('brand_id', $id)
            ->whereHas('taxonomies', function ($query) use ($brand) {
                $query->where('brand_id', $brand->id);
            })
            // CAMBIO CRÍTICO: usar whereHas('sessions') directamente con los filtros
            ->whereHas('sessions', function ($query) {
                $query->withoutGlobalScope(BrandScope::class) // ← IMPORTANTE
                    ->where('ends_on', '>', Carbon::now())
                    ->where('visibility', 1)
                    ->whereHas('space', function ($spaceQuery) {
                        $spaceQuery->withoutGlobalScope(BrandScope::class) // ← IMPORTANTE
                            ->whereNotNull('location_id');
                    });
            })
            ->with([
                'brand.capability',
                'taxonomies',
                'sessions' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class)
                        ->where('ends_on', '>', Carbon::now())
                        ->where('visibility', 1)
                        ->with([
                            'brand.capability',
                            'space' => function ($spaceQuery) {
                                $spaceQuery->withoutGlobalScope(BrandScope::class)
                                    ->with(['location' => function ($locQuery) {
                                        $locQuery->withoutGlobalScope(BrandScope::class);
                                    }]);
                            }
                        ])
                        ->orderBy('starts_on', 'ASC');
                }
            ])
            ->get()
            ->each(function ($event) {
                \App\Services\Api\EventService::addAttributes($event);
            });

        // sort by sesson starts_on
        $sorted = $events->sortBy(function ($event) {
            return $event->sessions->min('starts_on');
        })->values();

        return $this->json($sorted);
    }

    public function store(\App\Http\Requests\Api\PartnerStoreApiRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                // Verificar el código de activación con lock para evitar race conditions
                $code = Code::lockForUpdate()
                    ->where('brand_id', request()->get('brand.id'))
                    ->where('keycode', $request->codi_alta)
                    ->whereNull('promotor_id')
                    ->first();

                if (!$code) {
                    Log::warning('Invalid or used activation code attempt', [
                        'code' => $request->codi_alta,
                        'ip' => $request->ip(),
                        'brand_id' => request()->get('brand.id')
                    ]);

                    return $this->json([
                        'message' => __('api.partner.invalid_code'),
                        'error' => true
                    ], 400);
                }

                // Crear la marca
                $brand = (new \App\Services\BrandCreationService())
                    ->withJavajanTpv()
                    ->create($request->validated());

                // Asignar capabilities a la marca (capability 3 - promotor)
                $brand->capability()->associate(3);
                $brand->save();

                // Asignar el código de activación a la nueva marca
                $code->promotor_id = $brand->id;
                $code->used_at = now();
                $code->save();

                // Crear el usuario promotor
                $user = (new \App\Services\Api\UserService())->createPromotorUser($request, $brand->id);

                // Asignar el usuario a la marca
                $brand->users()->attach($user->id);

                // Asignar administradores adicionales si existen
                if ($request->extra_admins && is_array($request->extra_admins)) {
                    $validAdmins = \App\Models\User::whereIn('id', $request->extra_admins)
                        ->pluck('id')
                        ->toArray();

                    if (!empty($validAdmins)) {
                        $brand->users()->attach($validAdmins);
                    }
                }

                // Asociar marca padre
                $primary_brand = request()->get('brand');
                $brand->parent_id = $primary_brand->id;
                $brand->save();

                // Asociar usuarios administradores de la marca principal
                $superAdmins = $primary_brand->getBrandSuperAdmins();
                if (!empty($superAdmins)) {
                    $brand->users()->syncWithoutDetaching($superAdmins);
                }

                // Actualizar configuraciones de la marca
                $this->updateBrandSettings($brand);

                // Enviar correo electrónico (sin incluir password en el response)
                $this->sendEmailPartner($request, $user, $brand);

                // Retornar respuesta exitosa SIN DATOS SENSIBLES
                return $this->json([
                    'success' => true,
                    'error' => false,
                    'message' => __('backend.partner.created_successfully'),
                    'data' => [
                        'brand_code' => $brand->code_name,
                        'user_email' => $user->email,
                        // NO incluir: password, api_token, IDs internos, etc.
                    ]
                ], 201);
            });
        } catch (\App\Exceptions\ApiException $e) {
            Log::error('API Exception in partner creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        } catch (\App\Exceptions\BrandCreationException $e) {
            Log::error('Brand creation failed', [
                'error' => $e->getMessage(),
                'data' => $e->getErrorData()
            ]);

            return $this->json([
                'error' => true,
                'message' => __('api.partner.creation_failed')
            ], 422);
        } catch (\Exception $e) {
            Log::critical('Unexpected error in partner creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // NUNCA exponer detalles del error interno
            return $this->json([
                'error' => true,
                'message' => __('api.partner.unexpected_error')
            ], 500);
        }
    }

    /**
     * Actualiza las configuraciones de la marca
     */
    private function updateBrandSettings(Brand $brand): void
    {
        $settings = [
            'backpack.base.project_name' => $brand->name,
            'backpack.base.logo_lg' => htmlspecialchars($brand->name, ENT_QUOTES, 'UTF-8'),
            'backpack.base.logo_mini' => mb_substr($brand->name, 0, 1)
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['brand_id' => $brand->id, 'key' => $key],
                ['value' => $value]
            );
        }
    }

    /**
     * Crea subdominio en cPanel
     */
    public function createSubdomain($code_name)
    {
        try {
            // Validar code_name
            if (!preg_match('/^[a-z0-9_-]+$/', $code_name)) {
                throw new \InvalidArgumentException('Invalid subdomain format');
            }

            // Verificar que las credenciales de cPanel estén configuradas
            if (!config('services.cpanel.username') || !config('services.cpanel.api_key')) {
                throw new \RuntimeException('CPanel credentials not configured');
            }

            $client = new \GuzzleHttp\Client([
                'base_uri' => config('services.cpanel.base_uri'),
                'timeout' => config('services.cpanel.timeout', 30),
                'headers' => [
                    'Authorization' => 'cpanel ' . config('services.cpanel.username') . ':' . config('services.cpanel.api_key'),
                    'Accept' => 'application/json',
                ]
            ]);

            $response = $client->request('GET', '/execute/SubDomain/addsubdomain', [
                'query' => [
                    'domain' => $code_name,
                    'rootdomain' => config('services.cpanel.root_domain', 'yesweticket.com'),
                    'dir' => config('services.cpanel.subdomain_dir', '/public_html/engine/master/public')
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return true;
            }

            Log::error('Failed to create subdomain', [
                'subdomain' => $code_name,
                'status' => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exception creating subdomain', [
                'subdomain' => $code_name,
                'error' => $e->getMessage()
            ]);

            // Notificar a admins pero no exponer el error
            return false;
        }
    }

    /**
     * Envía email al partner recién creado
     */
    public function sendEmailPartner(\Illuminate\Http\Request $request, User $user, Brand $brand)
    {
        try {
            $brandParent = $request->get('brand');
            $mailer = app(MailerService::class)->getMailerForBrand($brandParent);

            $mailer->alwaysReplyTo(config('mail.reply_to.address'), config('mail.reply_to.name'));
            $mailer->alwaysFrom(config('mail.from.address'), config('mail.from.name'));

            $mailerContent = new \App\Mail\GenericMailer();
            $mailerContent->view = config('base.emails.alta-promotor');
            $mailerContent->subject = __('mail.partner.welcome_subject');

            // Solo incluir información necesaria en el email
            $mailerContent->viewData = [
                'name' => $user->name,
                'email' => $user->email,
                'code_name' => $brand->code_name,
                'front_url' => rtrim(config('clients.frontend.url', ''), '/'),
                // La contraseña debe ser enviada por un canal seguro separado o 
                // usar un link de reset password
                'reset_link' => $this->generateSecurePasswordResetLink($user)
            ];

            // Enviar a los destinatarios configurados
            $recipients = array_filter([
                $user->email,
                ...(array)$request->extra_emails
            ]);

            if (!empty($recipients)) {
                $mailer->to($recipients)->send($mailerContent);
            }

            // Email de notificación a admins (sin datos sensibles)
            if (config('mail.admin_notifications')) {
                $adminEmails = config('mail.admin_emails', []);
                if (!empty($adminEmails)) {
                    $mailer->to($adminEmails)->send(new \App\Mail\AdminNotification(
                        'Nuevo partner creado: ' . $brand->code_name
                    ));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send partner email', [
                'user_email' => $user->email,
                'error' => $e->getMessage()
            ]);
            // No lanzar excepción para no romper el flujo principal
        }
    }

    /**
     * Genera un link seguro de reset password
     */
    private function generateSecurePasswordResetLink(User $user): string
    {
        $token = app('auth.password.broker')->createToken($user);
        return url(route('password.reset', [
            'token' => $token,
            'email' => $user->email
        ], false));
    }
}
