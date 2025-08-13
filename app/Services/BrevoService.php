<?php

namespace App\Services;

use Brevo\Client\Configuration;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Model\CreateContact;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

class BrevoService //instalar composer require getbrevo/brevo-php:^1.0
{
    protected ContactsApi $api;
    protected int|string $listId;

    public function __construct(string $apiKey, int|string $listId)
    {
        throw_if(!$apiKey,  'API key for Brevo not set.');
        throw_if(!$listId,  'Newsletter list ID not set.');

        $this->listId = $listId;

        $config = Configuration::getDefaultConfiguration()
                    ->setApiKey('api-key', $apiKey);

        $this->api = new ContactsApi(new GuzzleClient(), $config);
    }

    public function subscribeUser(string $email, array $attributes = []): void
    {
        $contact = new CreateContact([
            'email'         => $email,
            'attributes'    => $attributes,
            'listIds'       => [(int) $this->listId],
            'updateEnabled' => true,
        ]);

        try {
            $this->api->createContact($contact);
        } catch (\Throwable $e) {
            Log::error("Brevo subscribe error: {$e->getMessage()}");
        }
    }

    public function deleteUser(string $email): void
    {
        try {
            $this->api->deleteContact($email);
        } catch (\Throwable $e) {
            Log::error("Brevo delete error for {$email}: {$e->getMessage()}");
        }
    }
}

// Este service se puede eliminar, se llama en ClientCrud y ClientApi pero no se le pasa nunca la apikey