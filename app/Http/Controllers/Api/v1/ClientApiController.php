<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Client;
use App\Models\Inscription;
use Illuminate\Http\Request;
use App\Services\BrevoService;
use Illuminate\Support\Facades\Hash;

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
        \Log::debug('Request keys', array_keys($request->all()));

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
        // TODO: this ClientUpdateApiRequest returns
        // Access denied when did not pass. Maybe we should put something in the API home.

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

        $client->answers = $client->answers()->get(); // Una sola consulta

        return $this->json($client);
    }


    /**
     * Display the specified resource.
     *
     * @method GET
     * @param  Client  $client
     */
    public function showCarts(Client $client)
    {
        $client->checkBrandOwnership();

        $carts = $client->carts()
            ->with(['inscriptions.session.event', 'groupPacks.inscriptions.session.event'])
            ->get();

        return $this->json($carts);
    }

    /**
     * Searchs a client by mail. It is used for checking if email already
     * exists during client booking process
     *
     * @method POST
     * @param Request $request
     */
    public function search(Request $request)
    {
        if (!$request->all()) {
            throw new \App\Exceptions\ApiException("Some filter needs to be applied");
        }

        $clientQuery = Client::ownedByBrand();

        foreach ($request->all() as $filter => $value) {
            $clientQuery->where($filter, $value);
        }

        $client = $clientQuery->first();

        return $this->json($client);
    }


    /**
     * It need to receive the current password or a valid reset password token otherwise.
     * 
     * Passwords are received already hashed.
     * 
     * @param Request $request
     */
    public function setPassword(Client $client, Request $request)
    {
        $clientQuery = Client::ownedByBrand()->where('id', $client->id);

        if ($request->get('reset_token')) {
            $clientQuery->where('reset_token', $request->get('reset_token'))
                ->where('reset_token_expires_on', '>', \Carbon\Carbon::now());
        } else {
            if (!Hash::check($request->get('old_password'), $client->password)) {
                return response()->json(['error' => 'Invalid password'], 403);
            }
        }

        $client = $clientQuery->firstOrFail();
        $client->password = $request->get('new_password'); // Almacena la nueva contraseña cifrada. new_password es Hash
        $client->reset_token = $client->reset_token_expires_on = null;
        $client->save();

        return response()->json(['success' => 'Password updated'], 200);
    }

    public function resetPassword(Client $client, Request $request)
    {
        $client->checkBrandOwnership();

        // we only generate new token if it is expired
        if (!$client->reset_token || $client->reset_token_expires_on < \Carbon\Carbon::now()) {
            $client->reset_token = md5(str_random());
            $client->reset_token_expires_on = \Carbon\Carbon::now()->addHour(); // expires in one hour
            $client->save();
        }

        // send email, añadido el trim, porque algunos emails se han guardado con espacios en blanco al final, i dan error
        (new \App\Services\MailerBrandService($client->brand->codeName))->getMailer()->to(trim($client->email))->send(new \App\Mail\ResetPasswordMail($client));

        return $this->json(null, 204);
    }

    /**
     * Subscribe user to newsletter
     * 
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

    public function deleteInscription(Request $request)
    {
        $cart_id = $request->cart;
        $inscription_id = $request->inscription;
        $cart_token = $request->cart_token;

        try {
            $cart = Cart::find($cart_id);
            $inscription = Inscription::find($inscription_id);

            //Comprovamos que el token enviado coincide con el token del carrito, i que la inscripcion pertenece a este carrito i el preu de la inscripcio es 0
            if ($cart->token == $cart_token && $inscription->cart->id == $cart->id && $inscription->price == 0) {
                $inscription->delete();
            } else {
                throw new \App\Exceptions\ApiException("Cart invalid Token or Inscription not from this Cart");
            }

            //Si despues de borrar la entrada, el carrito quedara vacio, borrar carrito
            if ($cart->inscriptions->count() == 0) {
                $cart->delete();
            }
        } catch (\Throwable $th) {
            throw new \App\Exceptions\ApiException("Inscription delete error");
        }

        return $this->json(null, 204);
    }

    /*
    /   Checkea si el email existe como soft-delete
    */
    public function checkSoftDeleted($email)
    {
        if (Client::ownedByBrand()->onlyTrashed()->where('email', $email)->exists()) {
            return $this->json(true, 200);
        }
        return $this->json(null, 204);
    }

    /* Checkea si el email existe en la brand */
    public function checkEmailExist($email)
    {
        if (Client::ownedByBrand()->where('email', $email)->exists()) {
            return $this->json(true, 200);
        }
        return $this->json(null, 204);
    }

    private function handleNewsletterSubscription(Client $client, $subscribe)
    {
        $apiKey = config('brevo_api_key');
        $newsletterListId = config('brevo.newsletter_list_id');

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
    }
}
