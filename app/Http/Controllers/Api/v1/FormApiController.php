<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\FormField;
use App\Services\Api\ClientPreferencesService;

class FormApiController extends \App\Http\Controllers\Api\ApiController
{
    /**
     * Display a list of form fields
     * 
     * @param int $id Form ID (reserved for future multi-form support)
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // ✅ SIMPLIFICADO: Sin filtro innecesario
        $fields = FormField::ownedByBrand()
            ->orderBy('weight', 'asc')
            ->get()
            ->map(function ($field) {
                return [
                    'id' => $field->id,
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'required' => $field->required,
                    'config' => $field->getTranslatedConfig(request()->header('Accept-Language', 'es')),
                    'weight' => $field->weight,
                ];
            });

        return $this->json($fields);
    }

    /**
     * Store/Update form answers for a client
     * 
     * @param int $id Form ID (reserved for future use)
     * @param Client $client
     * @param \App\Http\Requests\Api\FormApiRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store($id, Client $client, \App\Http\Requests\Api\FormApiRequest $request)
    {
        try {
            $service = new ClientPreferencesService();
            $results = $service->update($client, $request);

            // Verificar si hubo errores de validación
            if (!empty($results['errors'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Algunos campos tienen errores de validación',
                    'errors' => $results['errors'],
                    'warnings' => $results['warnings'] ?? [],
                    'saved' => $results['success']
                ], 422); // 422 Unprocessable Entity para errores de validación
            }

            // Si hubo warnings pero no errores
            if (!empty($results['warnings'])) {
                return $this->json([
                    'success' => true,
                    'message' => 'Preferencias actualizadas con advertencias',
                    'warnings' => $results['warnings'],
                    'saved' => $results['success']
                ], 200);
            }

            // Todo exitoso
            return $this->json([
                'success' => true,
                'message' => 'Preferencias actualizadas correctamente',
                'saved' => $results['success']
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error in FormApiController@store: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'Error al actualizar las preferencias',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Check if the user has all required fields completed
     * 
     * @param Client $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerCheckRequired(Client $client)
    {
        try {
            $client->checkBrandOwnership();

            // Obtener campos requeridos del brand
            $requiredFields = FormField::ownedByBrand()
                ->where(function ($query) {
                    // Buscar campos marcados como required
                    $query->whereRaw("JSON_EXTRACT(config, '$.required') = true")
                        ->orWhereRaw("JSON_CONTAINS(config, '\"required\"', '$.rules')");
                })
                ->get();

            // Verificar si el cliente tiene respuestas para todos los campos requeridos
            $missingFields = [];
            $completedFields = [];

            foreach ($requiredFields as $field) {
                $answer = $client->answers()
                    ->where('field_id', $field->id)
                    ->first();

                if (!$answer || empty($answer->answer) || $answer->answer === '[]') {
                    // Campo faltante o vacío
                    $fieldLabel = is_array($field->label)
                        ? ($field->label[app()->getLocale()] ?? reset($field->label))
                        : $field->label;

                    $missingFields[] = [
                        'id' => $field->id,
                        'name' => $field->name,
                        'label' => $fieldLabel,
                        'type' => $field->type
                    ];
                } else {
                    $completedFields[] = $field->id;
                }
            }

            // Verificar también los register_inputs si existen
            $register_inputs = request()->get('brand')->register_inputs ?? [];
            $missingRegisterInputs = [];

            foreach ($register_inputs as $input) {
                if ($input->pivot->required && empty($client->{$input->name_form})) {
                    $missingRegisterInputs[] = $input->name_form;
                }
            }

            $allComplete = empty($missingFields) && empty($missingRegisterInputs);

            return $this->json([
                'complete' => $allComplete,
                'missing_fields' => $missingFields,
                'missing_inputs' => $missingRegisterInputs,
                'total_required' => count($requiredFields),
                'total_completed' => count($completedFields),
                'completion_percentage' => count($requiredFields) > 0
                    ? round((count($completedFields) / count($requiredFields)) * 100, 2)
                    : 100
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in FormApiController@registerCheckRequired: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'Error al verificar campos requeridos',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get client's current form answers
     * 
     * @param Client $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientAnswers(Client $client)
    {
        try {
            $client->checkBrandOwnership();

            $service = new ClientPreferencesService();
            $preferences = $service->getPreferences($client);

            return $this->json([
                'success' => true,
                'preferences' => $preferences
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in FormApiController@getClientAnswers: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'Error al obtener las preferencias',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}
