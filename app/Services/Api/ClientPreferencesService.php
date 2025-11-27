<?php

namespace App\Services\Api;

use App\Models\Client;
use App\Models\FormField;
use App\Models\FormFieldAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * @return array Results with success and errors
     */
    public function update(Client $client, Request $request)
    {
        // Ensure the client belongs to the correct brand
        $client->checkBrandOwnership();

        // Prepare results
        $results = [
            'success' => [],
            'errors' => [],
            'warnings' => []
        ];

        // Get form fields owned by brand with their configurations
        $brandFields = FormField::ownedByBrand()
            ->get()
            ->keyBy('id');

        // Filter answers from the request
        $form_answers = collect($request->all())
            ->filter(function ($value, $key) {
                return str_starts_with($key, 'answer-');
            })
            ->mapWithKeys(function ($answer, $key) {
                $field_id = (int) str_replace('answer-', '', $key);
                return [$field_id => $answer];
            });

        // Use transaction for data integrity
        DB::beginTransaction();

        try {
            foreach ($form_answers as $field_id => $answer) {
                // Skip if field doesn't belong to brand
                if (!$brandFields->has($field_id)) {
                    $results['warnings'][] = "Campo {$field_id} no pertenece a esta marca";
                    continue;
                }

                $field = $brandFields->get($field_id);

                // Validate the answer
                $validation = $this->validateAnswer($field, $answer);

                if (!$validation['valid']) {
                    $results['errors'][$field_id] = $validation['errors'];
                    continue;
                }

                // Process and save the answer
                $processedAnswer = $this->processAnswer($field, $answer);

                if (empty($processedAnswer)) {
                    // Delete answer if empty
                    FormFieldAnswer::where('client_id', $client->id)
                        ->where('field_id', $field_id)
                        ->delete();

                    $results['success'][$field_id] = 'deleted';
                } else {
                    // Update or create the answer
                    $savedAnswer = FormFieldAnswer::updateOrCreate(
                        [
                            'client_id' => $client->id,
                            'field_id'  => $field_id
                        ],
                        [
                            'answer' => $processedAnswer
                        ]
                    );

                    $results['success'][$field_id] = $savedAnswer->id;
                }
            }

            // Check required fields that weren't submitted
            $this->checkRequiredFields($client, $brandFields, $form_answers->keys()->toArray(), $results);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating client preferences: ' . $e->getMessage());
            throw $e;
        }

        return $results;
    }

    /**
     * Validate an answer based on field type and configuration
     * 
     * @param FormField $field
     * @param mixed $answer
     * @return array ['valid' => bool, 'errors' => array]
     */
    protected function validateAnswer(FormField $field, $answer)
    {
        $rules = [];
        $messages = [];

        // Get field label for messages
        $fieldLabel = is_array($field->label)
            ? ($field->label[app()->getLocale()] ?? reset($field->label))
            : $field->label;

        // Build validation rules based on field type
        switch ($field->type) {
            case 'text':
                $rules['answer'] = ['nullable', 'string', 'max:255'];
                break;

            case 'textarea':
                $rules['answer'] = ['nullable', 'string', 'max:5000'];
                break;

            case 'date':
                $rules['answer'] = ['nullable', 'date', 'date_format:Y-m-d'];
                $messages['answer.date_format'] = "El campo {$fieldLabel} debe tener el formato AAAA-MM-DD";
                break;

            case 'select':
            case 'radio':
                $validOptions = $this->getValidOptions($field);
                if (!empty($validOptions)) {
                    $rules['answer'] = ['nullable', 'string', 'in:' . implode(',', $validOptions)];
                    $messages['answer.in'] = "El valor seleccionado para {$fieldLabel} no es válido";
                }
                break;

            case 'checkbox':
                $rules['answer'] = ['nullable', 'array'];
                $validOptions = $this->getValidOptions($field);
                if (!empty($validOptions)) {
                    $rules['answer.*'] = ['string', 'in:' . implode(',', $validOptions)];
                    $messages['answer.*.in'] = "Uno o más valores seleccionados para {$fieldLabel} no son válidos";
                }
                break;

            default:
                $rules['answer'] = ['nullable', 'string', 'max:5000'];
        }

        // Add required rule if necessary
        if ($field->required && !in_array('nullable', $rules['answer'])) {
            $key = array_search('nullable', $rules['answer']);
            if ($key !== false) {
                $rules['answer'][$key] = 'required';
            }
            $messages['answer.required'] = "El campo {$fieldLabel} es obligatorio";
        }

        // Add custom rules from config if they exist
        $config = $field->config;
        if (isset($config['rules'])) {
            $customRules = is_array($config['rules']) ? $config['rules'] : explode('|', $config['rules']);
            $customRules = array_filter($customRules, fn($rule) => !str_starts_with($rule, 'required'));
            $rules['answer'] = array_merge($rules['answer'], $customRules);
        }

        $validator = Validator::make(
            ['answer' => $answer],
            $rules,
            $messages
        );

        return [
            'valid' => $validator->passes(),
            'errors' => $validator->errors()->get('answer')
        ];
    }

    /**
     * Process the answer based on field type
     * 
     * @param FormField $field
     * @param mixed $answer
     * @return string
     */
    protected function processAnswer(FormField $field, $answer)
    {
        if ($answer === null || $answer === '') {
            return '';
        }

        switch ($field->type) {
            case 'checkbox':
                // For checkboxes, save as JSON array
                if (is_array($answer)) {
                    // Filter empty values and re-index
                    $answer = array_values(array_filter($answer));
                    return json_encode($answer);
                }
                return '[]';

            case 'date':
                // Ensure consistent format for dates
                try {
                    if ($answer instanceof \DateTime) {
                        return $answer->format('Y-m-d');
                    }
                    $date = new \DateTime($answer);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    return $answer;
                }

            case 'select':
            case 'radio':
            case 'text':
            case 'textarea':
            default:
                // For other types, save as string
                return (string) $answer;
        }
    }

    /**
     * Get valid option values from field configuration
     * 
     * @param FormField $field
     * @return array
     */
    protected function getValidOptions(FormField $field)
    {
        $config = $field->config;

        if (!isset($config['options']) || !is_array($config['options'])) {
            return [];
        }

        // Extract valid values from the new format
        return collect($config['options'])
            ->pluck('value')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Check if required fields are missing
     * 
     * @param Client $client
     * @param \Illuminate\Support\Collection $brandFields
     * @param array $submittedFieldIds
     * @param array &$results
     */
    protected function checkRequiredFields($client, $brandFields, $submittedFieldIds, &$results)
    {
        $requiredFields = $brandFields->filter(function ($field) {
            return $field->required;
        });

        foreach ($requiredFields as $field) {
            // If field wasn't submitted, check if it has an existing answer
            if (!in_array($field->id, $submittedFieldIds)) {
                $existingAnswer = FormFieldAnswer::where('client_id', $client->id)
                    ->where('field_id', $field->id)
                    ->first();

                if (!$existingAnswer || empty($existingAnswer->answer)) {
                    $fieldLabel = is_array($field->label)
                        ? ($field->label[app()->getLocale()] ?? reset($field->label))
                        : $field->label;

                    $results['errors'][$field->id] = ["El campo {$fieldLabel} es obligatorio"];
                }
            }
        }
    }

    /**
     * Get all preferences for a client with formatted values
     * 
     * @param Client $client
     * @return \Illuminate\Support\Collection
     */
    public function getPreferences(Client $client)
    {
        $answers = FormFieldAnswer::where('client_id', $client->id)
            ->with('form_field')
            ->get();

        return $answers->map(function ($answer) {
            $field = $answer->form_field;
            $value = $answer->answer;

            // Format value based on field type
            switch ($field->type) {
                case 'checkbox':
                    $values = json_decode($value, true) ?? [];
                    $config = $field->getTranslatedConfig();
                    $options = collect($config['options'] ?? []);

                    $displayValue = $options->whereIn('value', $values)
                        ->pluck('label')
                        ->implode(', ');
                    break;

                case 'select':
                case 'radio':
                    $config = $field->getTranslatedConfig();
                    $option = collect($config['options'] ?? [])
                        ->firstWhere('value', $value);

                    $displayValue = $option['label'] ?? $value;
                    break;

                default:
                    $displayValue = $value;
            }

            return [
                'field_id' => $field->id,
                'field_name' => $field->name,
                'field_label' => $field->label,
                'field_type' => $field->type,
                'value' => $value,
                'display_value' => $displayValue,
            ];
        });
    }
}
