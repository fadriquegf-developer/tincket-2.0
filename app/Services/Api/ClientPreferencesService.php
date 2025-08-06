<?php

namespace App\Services\Api;

use App\Models\Client;
use App\Models\FormField;
use App\Models\FormFieldAnswer;
use Illuminate\Http\Request;

/**
 * Service responsible for handling client preferences.
 * 
 * It is used in public API controller and Engine controllers.
 */
class ClientPreferencesService extends AbstractService
{
    /**
     * Update the client preferences based on the request.
     * 
     * @param Client $client
     * @param Request $request
     */
    public function update(Client $client, Request $request)
    {
        // Ensure the client belongs to the correct brand
        $client->checkBrandOwnership();

        // Filter answers from the request
        $form_answers = collect($request->all())
            ->filter(function ($value, $key) {
                return starts_with($key, 'answer-');
            })
            ->map(function ($answer, $key) {
                $field_id = (int) str_replace('answer-', '', $key);
                return [
                    'field_id' => $field_id,
                    'answer'   => is_array($answer) ? implode(',', $answer) : $answer,
                ];
            });

        // Get the allowed field IDs owned by the brand
        $allowed_answer_field_ids = FormField::ownedByBrand()->pluck('id')->toArray();

        // Process the form answers and store them
        $form_answers->each(function ($form_answer) use ($client, $allowed_answer_field_ids) {
            if (in_array($form_answer['field_id'], $allowed_answer_field_ids)) {
                if (empty($form_answer['answer'])) {
                    // Delete answer if the field is empty
                    FormFieldAnswer::where('client_id', $client->id)
                        ->where('field_id', $form_answer['field_id'])
                        ->delete();
                } else {
                    // Update or create the answer if it has a value
                    FormFieldAnswer::updateOrCreate(
                        [
                            'client_id' => $client->id,
                            'field_id'  => $form_answer['field_id']
                        ],
                        [
                            'answer'    => $form_answer['answer']
                        ]
                    );
                }
            }
        });
    }
}
