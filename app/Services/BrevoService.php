<?php

namespace App\Services;

use Brevo\Client\ApiException;
use Brevo\Client\Configuration;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\ObjectSerializer;
use Illuminate\Support\Facades\Log;
use Brevo\Client\Model\CreateContact;
use GuzzleHttp\Client as GuzzleClient;

class BrevoService
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

    public function subscribeUser(string $email, array $attributes = []): array
{
    $contact = new CreateContact([
        'email'         => $email,
        'attributes'    => $attributes,
        'listIds'       => [(int) $this->listId],
        'updateEnabled' => true,
    ]);

    try {
        [$body, $status, $headers] = $this->api->createContactWithHttpInfo($contact);

        $bodyArr = is_object($body)
            ? ObjectSerializer::sanitizeForSerialization($body)
            : $body;

        return ['ok' => true, 'status' => $status, 'headers' => $headers, 'body' => $bodyArr];
    } catch (ApiException $e) {
        $respBody = $e->getResponseBody();
        $respArr  = is_string($respBody) ? json_decode($respBody, true) : $respBody;

        \Log::error('Brevo subscribe error', [
            'status'  => $e->getCode(),
            'headers' => $e->getResponseHeaders(),
            'body'    => $respArr,
            'message' => $e->getMessage(),
        ]);

        return ['ok' => false, 'status' => $e->getCode(), 'headers' => $e->getResponseHeaders(), 'body' => $respArr, 'message' => $e->getMessage()];
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