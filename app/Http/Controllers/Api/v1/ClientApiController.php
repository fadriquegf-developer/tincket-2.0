<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Client;
use App\Models\Inscription;
use Illuminate\Http\Request;
use App\Services\BrevoService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientApiController extends \App\Http\Controllers\Api\ApiController
{
    /**
     * Store a newly created resource in storage.
     *
     * @method POST
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(\App\Http\Requests\Api\ClientStoreApiRequest $request)
    {
        $client = new Client($request->all());
        $client->password = $request->get('password');
        $client->brand()->associate($request->get('brand'));
        $client->save();

        $this->handleNewsletterSubscription($client, $request->newsletter);

        return $this->json($client, 200);
    }

    /**
     * Updates existing client
     *
     * @method POST
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Client $client, \App\Http\Requests\Api\ClientUpdateApiRequest $request)
    {
        try {
            $client->name = $request->name;
            $client->surname = $request->surname;
            $client->email = $request->email;
            $client->phone = $request->phone;

            //Añadir aqui los campos extras creados
            $client->newsletter = $request->newsletter ?? false;
            $client->date_birth = $request->date_birth ?? null;
            $client->dni = isset($request->dni) ? $request->dni : null;
            $client->province = isset($request->province) ? $request->province : null;
            $client->city = isset($request->city) ? $request->city : null;
            $client->address = isset($request->address) ? $request->address : null;
            $client->postal_code = isset($request->postal_code) ? $request->postal_code : null;
            $client->mobile_phone = isset($request->mobile_phone) ? $request->mobile_phone : null;

            $client->save();

            $this->handleNewsletterSubscription($client, $request->newsletter);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('API: Client update failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $this->json($client, 200);
    }

    /**
     * Display the specified resource.
     *
     * @method GET
     * @param  Client  $client
     */
    public function show(Client $client)
    {
        $client->checkBrandOwnership();

        $client->answers = $client->answers()->get();

        return $this->json($client);
    }

    /**
     * Display carts with pagination
     *
     * @method GET
     * @param  Client  $client
     */
    public function showCarts(Request $request, Client $client)
    {
        $client->checkBrandOwnership();

        // PROBLEMA #4 SOLUCIONADO: Paginación forzada
        $perPage = min((int) $request->get('per_page', 20), 50); // Máximo 50

        $carts = $client->carts()
            ->with(['inscriptions.session.event', 'groupPacks.inscriptions.session.event'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $carts->items(),
            'meta' => [
                'current_page' => $carts->currentPage(),
                'last_page' => $carts->lastPage(),
                'per_page' => $carts->perPage(),
                'total' => $carts->total()
            ]
        ]);
    }

    /**
     * Search client with rate limiting
     * PROBLEMA #2 SOLUCIONADO: Rate limiting
     *
     * @method POST
     * @param Request $request
     */
    public function search(Request $request)
    {
        // Rate limiting: 10 búsquedas por minuto por IP
        $key = 'client_search:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('API: Client search rate limit exceeded', [
                'ip' => request()->ip(),
                'wait_seconds' => $seconds
            ]);

            return response()->json([
                'error' => 'Too many search attempts. Please wait ' . $seconds . ' seconds.'
            ], 429);
        }

        RateLimiter::hit($key, 60); // 1 minuto

        if (!$request->all()) {
            throw new \App\Exceptions\ApiException("Some filter needs to be applied");
        }

        // Validar y sanitizar inputs
        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|max:255',
            'dni' => 'sometimes|string|max:20',
            'phone' => 'sometimes|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clientQuery = Client::ownedByBrand();

        // Solo permitir búsqueda por campos específicos
        $allowedFilters = ['email', 'dni', 'phone', 'reset_token'];

        foreach ($request->all() as $filter => $value) {
            if (in_array($filter, $allowedFilters)) {
                $clientQuery->where($filter, $value);
            }
        }

        $client = $clientQuery->first();



        return $this->json($client);
    }


    /**
     * Verify client password
     * Endpoint para que el front verifique passwords sin exponer el hash
     * 
     * @method POST
     * @param Request $request
     */
    public function verifyPassword(Request $request)
    {
        // Rate limiting
        $key = 'verify_password:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('API: Password verification rate limit exceeded', ['ip' => request()->ip()]);
            return response()->json(['error' => ['message' => 'Too many attempts', 'code' => 429]], 429);
        }
        RateLimiter::hit($key, 60);

        // Validar
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6|max:100'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = $request->input('email');
        $password = $request->input('password');


        // Buscar cliente
        $client = Client::ownedByBrand()->where('email', $email)->first();

        if (!$client) {
            Log::warning('API: Client not found', ['email' => $email]);
            return response()->json(['error' => ['message' => 'Invalid credentials', 'code' => 401]], 401);
        }

        // Verificar password
        if (!Hash::check($password, $client->password)) {
            Log::warning('❌ API: Invalid password', ['client_id' => $client->id, 'ip' => request()->ip()]);
            return response()->json(['error' => ['message' => 'Invalid credentials', 'code' => 401]], 401);
        }

        // ✅ Retornar en formato compatible con Repository::getJsonResponse()
        // que espera { "data": {...} }
        return response()->json([
            'data' => [
                'valid' => true,
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'surname' => $client->surname,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'mobile_phone' => $client->mobile_phone,
                    'locale' => $client->locale,
                    'newsletter' => (bool) $client->newsletter,
                    'date_birth' => $client->date_birth ? $client->date_birth->format('Y-m-d') : null,
                    'dni' => $client->dni,
                    'province' => $client->province,
                    'city' => $client->city,
                    'address' => $client->address,
                    'postal_code' => $client->postal_code,
                    // ✅ CRÍTICO: Fechas como strings simples
                    'created_at' => $client->created_at ? $client->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $client->updated_at ? $client->updated_at->format('Y-m-d H:i:s') : null,
                ]
            ]
        ], 200);
    }

    /**
     * Set password with rate limiting
     * 
     * @param Request $request
     */
    public function setPassword(Client $client, Request $request)
    {
        // Rate limiting: 5 intentos por hora por cliente
        $key = 'password_change:' . $client->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('API: Password change rate limit exceeded', [
                'client_id' => $client->id
            ]);

            return response()->json([
                'error' => 'Too many password change attempts. Please wait ' . ceil($seconds / 60) . ' minutes.'
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hora

        $clientQuery = Client::ownedByBrand()->where('id', $client->id);

        // Si viene con reset_token (recuperación de contraseña)
        if ($request->get('reset_token')) {
            $clientQuery->where('reset_token', $request->get('reset_token'))
                ->where('reset_token_expires_on', '>', \Carbon\Carbon::now());
        }
        // Si viene con old_password (cambio normal)
        else if ($request->get('old_password')) {
            // ✅ CRÍTICO: old_password viene en TEXTO PLANO
            // Verificar ANTES de continuar
            $oldPasswordPlain = $request->get('old_password');

            if (!Hash::check($oldPasswordPlain, $client->password)) {
                Log::warning('API: Invalid old password attempt', [
                    'client_id' => $client->id,
                    'ip' => request()->ip()
                ]);
                return response()->json(['error' => 'Invalid current password'], 403);
            }

        } else {
            Log::error('API: Missing authentication method', [
                'client_id' => $client->id
            ]);
            return response()->json(['error' => 'Missing reset_token or old_password'], 400);
        }

        $client = $clientQuery->firstOrFail();

        // ✅ CRÍTICO: new_password viene en TEXTO PLANO
        // El mutador setPasswordAttribute() lo hasheará automáticamente
        $newPasswordPlain = $request->get('new_password');

        // Validación básica
        if (strlen($newPasswordPlain) < 6) {
            return response()->json(['error' => 'Password must be at least 6 characters'], 422);
        }

        if (strlen($newPasswordPlain) > 100) {
            return response()->json(['error' => 'Password is too long'], 422);
        }

        // Actualizar password (el mutador lo hashea automáticamente)
        $client->password = $newPasswordPlain;

        // Limpiar tokens de reset
        $client->reset_token = null;
        $client->reset_token_expires_on = null;

        $client->save();

        // Verificar que funcionó
        if (!Hash::check($newPasswordPlain, $client->password)) {
            Log::error('API: Password save verification failed', [
                'client_id' => $client->id,
                'hash_length' => strlen($client->password)
            ]);
            return response()->json(['error' => 'Password update failed'], 500);
        }

        return response()->json(['success' => 'Password updated'], 200);
    }

    /**
     * Reset password with rate limiting
     */
    public function resetPassword(Client $client, Request $request)
    {
        // Rate limiting: 3 resets por hora por email
        $key = 'password_reset:' . $client->email;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => 'Too many reset attempts. Please wait ' . ceil($seconds / 60) . ' minutes.'
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        $client->checkBrandOwnership();

        // we only generate new token if it is expired
        if (!$client->reset_token || $client->reset_token_expires_on < \Carbon\Carbon::now()) {
            $client->reset_token = \Illuminate\Support\Str::random(32); // Más seguro que md5
            $client->reset_token_expires_on = \Carbon\Carbon::now()->addHour();
            $client->save();
        }

        // send email
        $mailer = app(\App\Services\MailerService::class)->getMailerForBrand($client->brand);
        $mailer->to(trim($client->email))->send(new \App\Mail\ResetPasswordMail($client));

        return $this->json(null, 204);
    }

    /**
     * Subscribe user to newsletter
     */
    public function subscribe(Request $request)
    {
        $client = Client::ownedByBrand()->where('token_confirm_newsletter', $request->token)->firstOrFail();

        if ($client && $request->token === $client->token_confirm_newsletter) {
            $client->newsletter = 1;
            $client->save();
        } else {
            throw new \App\Exceptions\ApiException("Subscribe invalid Token");
        }

        return $this->json(null, 204);
    }

    public function registerInputs($brand)
    {
        return $this->json(Brand::find($brand)->register_inputs->toArray());
    }

    /**
     * Delete inscription with validation
     */
    public function deleteInscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart' => 'required|integer|exists:carts,id',
            'inscription' => 'required|integer|exists:inscriptions,id',
            'cart_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cart_id = $request->cart;
        $inscription_id = $request->inscription;
        $cart_token = $request->cart_token;

        try {
            $cart = Cart::find($cart_id);
            $inscription = Inscription::find($inscription_id);

            // Validación mejorada
            if (
                $cart->token !== $cart_token ||
                $inscription->cart_id !== $cart->id ||
                $inscription->price != 0
            ) {

                Log::warning('API: Invalid inscription deletion attempt', [
                    'cart_id' => $cart_id,
                    'inscription_id' => $inscription_id
                ]);

                throw new \App\Exceptions\ApiException("Invalid request");
            }

            $inscription->delete();

            // Si el carrito queda vacío, borrarlo
            if ($cart->inscriptions()->count() == 0) {
                $cart->delete();
            }
        } catch (\Throwable $th) {
            throw new \App\Exceptions\ApiException("Inscription delete error");
        }

        return $this->json(null, 204);
    }

    /**
     * Check soft deleted with rate limiting
     */
    public function checkSoftDeleted($email)
    {
        // Rate limiting para prevenir enumeración
        $key = 'check_email:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }

        RateLimiter::hit($key, 60);

        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email'], 422);
        }

        if (Client::ownedByBrand()->onlyTrashed()->where('email', $email)->exists()) {
            return $this->json(true, 200);
        }

        return $this->json(null, 204);
    }

    /**
     * Check email exists with rate limiting
     */
    public function checkEmailExist($email)
    {
        // Rate limiting compartido con checkSoftDeleted
        $key = 'check_email:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }

        RateLimiter::hit($key, 60);

        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email'], 422);
        }

        if (Client::ownedByBrand()->where('email', $email)->exists()) {
            return $this->json(true, 200);
        }

        return $this->json(null, 204);
    }

    /**
     * Handle newsletter subscription
     */
    private function handleNewsletterSubscription(Client $client, $subscribe)
    {
        try {
            $apiKey = brand_setting('brevo.api_key');
            $newsletterListId = brand_setting('brevo.newsletter_list_id');

            if ($apiKey && $newsletterListId) {
                $brevoService = new BrevoService($apiKey, $newsletterListId);
                if ($subscribe) {
                    $brevoService->subscribeUser($client->email, [
                        'FNAME' => $client->name,
                        'LNAME' => $client->surname,
                    ]);
                } else {
                    $brevoService->deleteUser($client->email);
                }
            }
        } catch (\Exception $e) {
            // Log error sin exponer datos sensibles
            Log::error('Newsletter subscription error', [
                'client_id' => $client->id,
                'action' => $subscribe ? 'subscribe' : 'unsubscribe',
                'error' => $e->getMessage()
            ]);
        }
    }
}
