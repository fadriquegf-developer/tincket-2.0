<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Client;
use App\Models\Session;
use App\Models\FormField;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Imports\ClientImport;
use App\Services\BrevoService;
use App\Traits\AllowUsersTrait;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ClientRequest;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\Pro\Http\Controllers\Operations\TrashOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\Pro\Http\Controllers\Operations\BulkTrashOperation;
use Backpack\Pro\Http\Controllers\Operations\CustomViewOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

class ClientCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation {
        update as traitUpdate;
    }
    use ShowOperation;
    use CustomViewOperation;
    use TrashOperation;
    use BulkTrashOperation;
    use CrudPermissionTrait;
    use AllowUsersTrait;

    // Cache keys
    const CACHE_KEY_FORM_FIELDS = 'brand_%d_form_fields';
    const CACHE_TTL_FORM_FIELDS = 3600; // 1 hora

    // Límites
    const IMPORT_MAX_ROWS = 5000;
    const IMPORT_RATE_LIMIT = 3;
    const EXPORT_CHUNK_SIZE = 1000;

    public function setup()
    {
        CRUD::setModel(Client::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/client');
        CRUD::setEntityNameStrings(__('menu.client'), __('menu.clients'));

        // Activar sistema de permisos
        if (!$this->isSuperuser()) {
            $this->setAccessUsingPermissions();
        }

        // Optimización: Eager loading global para todas las operaciones
        $this->crud->query = $this->crud->query->with(['brand']);
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('core.client.show');

        // En el show SÍ mostramos todos los campos, incluidos los personalizados
        $this->setupBasicColumns();
        $this->addFormFieldColumnsForShow(); // Versión específica para show
    }

    protected function setupListOperation()
    {
        // Configuración de permisos y botones
        CRUD::denyAccess('bulkDestroy');
        CRUD::denyAccess('destroy');
        CRUD::enableExportButtons();

        // Botones según capability
        $capability = get_brand_capability();
        CRUD::addButtonFromView('top', 'import_csv', 'client_import_button', 'end');

        if ($capability !== 'promoter') {
            $this->crud->addButtonFromView('top', 'newsletter', 'newsletter', 'end');
        }

        // Configurar filtros
        $this->setupFilters();

        // Configurar columnas básicas (sin campos personalizados)
        $this->setupBasicColumns();

        // Optimización: Limitar registros por página
        $this->crud->setDefaultPageLength(25);
        $this->crud->setPageLengthMenu([10, 25, 50, 100]);

        // NO cargar campos personalizados en el listado por defecto
        // Solo si se solicita explícitamente
        if (request()->has('include_custom_fields')) {
            $this->addLimitedFormFieldColumns(); // Solo campos esenciales
        }
    }

    protected function setupBasicColumns()
    {
        $capability = get_brand_capability();

        CRUD::addColumn([
            'name' => 'id',
            'label' => __('menu.client') . ' id',
            'type' => 'text',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('id', '=', $searchTerm);
            }
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('backend.client.name'),
            'type' => 'text',
            'searchLogic' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'surname',
            'label' => __('backend.client.surname'),
            'type' => 'text',
            'searchLogic' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'label' => __('backend.client.email'),
            'type' => 'email',
            'searchLogic' => 'text'
        ]);

        // Columna optimizada para número de sesiones
        CRUD::addColumn([
            'name' => 'sessions_count',
            'label' => __('backend.client.num_session'),
            'type' => 'closure',
            'function' => function ($entry) {
                // Usar cache para evitar recálculo constante
                return Cache::remember(
                    "client_{$entry->id}_sessions_count",
                    300, // 5 minutos
                    function () use ($entry) {
                    return $entry->getNumSessions();
                }
                );
            },
            'orderable' => false,
            'searchable' => false
        ]);

        CRUD::addColumn([
            'name' => 'newsletter',
            'label' => __('backend.client.newsletter'),
            'type' => 'boolean',
            'options' => [0 => 'No', 1 => 'Si']
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => __('backend.client.created_at'),
            'type' => 'datetime',
        ]);

        // Columnas adicionales según el contexto
        if ($this->crud->getCurrentOperation() === 'show') {
            CRUD::addColumn([
                'name' => 'phone',
                'label' => __('backend.client.phone'),
                'type' => 'text',
            ]);

            CRUD::addColumn([
                'name' => 'mobile_phone',
                'label' => __('backend.client.mobile_phone'),
                'type' => 'text',
            ]);

            CRUD::addColumn([
                'name' => 'locale',
                'label' => __('backend.client.locale'),
                'type' => 'select_from_array',
                'options' => ['ca' => __('backend.client.ca'), 'es' => __('backend.client.es'), 'gl' => __('backend.client.gl')],
            ]);
        }

        // Solo para engine mostrar brand_id
        if ($capability === 'engine') {
            CRUD::addColumn([
                'name' => 'brand_id',
                'label' => __('backend.client.brand_id'),
                'type' => 'select',
                'entity' => 'brand',
                'attribute' => 'name',
                'model' => \App\Models\Brand::class,
            ]);
        }
    }

    protected function setupFilters()
    {
        // Filtro por sesión
        CRUD::addFilter([
            'name' => 'session',
            'type' => 'select2',
            'label' => trans('backend.client.session')
        ], function () {
            return Cache::remember(
                sprintf('brand_%d_sessions_filter', get_current_brand_id()),
                600,
                function () {
                    return Session::with('event:id,name')
                        ->where('brand_id', get_current_brand_id())
                        ->orderBy('starts_on', 'DESC')
                        ->limit(100)
                        ->get()
                        ->mapWithKeys(function ($session) {
                            // Usar name si existe, sino usar name_filter
                            $label = $session->name ?: $session->name_filter;

                            return [$session->id => $label];
                        })
                        ->toArray();
                }
            );
        }, function ($value) {
            $this->crud->query->whereHas('carts.inscriptions', function ($query) use ($value) {
                $query->where('session_id', $value);
            });
        });

        // Filtro por rango de fechas
        CRUD::addFilter([
            'name' => 'from_to',
            'label' => __('backend.client.from_to'),
            'type' => 'date_range',
        ], false, function ($value) {
            $dates = json_decode($value);
            $this->crud->addClause('where', 'created_at', '>=', $dates->from);
            $this->crud->addClause('where', 'created_at', '<=', $dates->to . ' 23:59:59');
        });

        // Filtro por newsletter
        CRUD::addFilter([
            'name' => 'newsletter',
            'type' => 'simple',
            'label' => __('backend.client.newsletter')
        ], false, function () {
            $this->crud->addClause('where', 'newsletter', '=', 1);
        });
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ClientRequest::class);
        $this->setupFormFields();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupFormFields()
    {
        // Campos básicos
        CRUD::addField([
            'name' => 'name',
            'label' => __('backend.client.name'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6 required']
        ]);

        CRUD::addField([
            'name' => 'surname',
            'label' => __('backend.client.surname'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6 required']
        ]);

        CRUD::addField([
            'name' => 'email',
            'label' => __('backend.client.email'),
            'type' => 'email',
            'wrapper' => ['class' => 'form-group col-md-6 required']
        ]);

        CRUD::addField([
            'name' => 'locale',
            'label' => __('backend.client.locale'),
            'type' => 'select_from_array',
            'options' => ['es' => 'Español', 'ca' => 'Català', 'gl' => 'Galego'],
            'allows_null' => false,
            'default' => 'es',
            'wrapper' => ['class' => 'form-group col-md-6 required']
        ]);

        CRUD::addField([
            'name' => 'phone',
            'label' => __('backend.client.phone'),
            'type' => 'phone',
            'config' => [
                'initialCountry' => 'es',
                'preferredCountries' => ['es', 'fr', 'pt'],
            ],
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'mobile_phone',
            'label' => __('backend.client.mobile_phone'),
            'type' => 'phone',
            'config' => [
                'initialCountry' => 'es',
                'preferredCountries' => ['es', 'fr', 'pt'],
            ],
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        // Dirección
        CRUD::addField([
            'name' => 'address',
            'label' => __('backend.client.address'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'postal_code',
            'label' => __('backend.client.postal_code'),
            'type' => 'text',
            'attributes' => ['maxlength' => 10],
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'province',
            'label' => __('backend.client.province'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'city',
            'label' => __('backend.client.city'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        // Datos adicionales
        CRUD::addField([
            'name' => 'dni',
            'label' => 'DNI/NIE',
            'type' => 'text',
            'attributes' => ['maxlength' => 20],
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'date_birth',
            'label' => __('backend.client.date_birth'),
            'type' => 'date',
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        // Password con info
        if ($this->crud->getCurrentOperation() === 'create') {
            CRUD::addField([
                'name' => 'password',
                'label' => __('backend.user.password'),
                'type' => 'password',
                'wrapper' => ['class' => 'form-group col-md-6 required'],
            ]);

            CRUD::addField([
                'name' => 'password_confirmation',
                'label' => __('backend.user.password_confirmation'),
                'type' => 'password',
                'wrapper' => ['class' => 'form-group col-md-6 required']
            ]);
        } else {
            CRUD::addField([
                'name' => 'password',
                'label' => __('backend.user.password'),
                'type' => 'password',
                'hint' => __('backend.user.password_hint_update'),
                'wrapper' => ['class' => 'form-group col-md-6'],
            ]);

            CRUD::addField([
                'name' => 'password_confirmation',
                'label' => __('backend.user.password_confirmation'),
                'type' => 'password',
                'wrapper' => ['class' => 'form-group col-md-6']
            ]);
        }

        CRUD::addField([
            'name' => 'separator',
            'type' => 'custom_html',
            'value' => '<hr>'
        ]);

        // Newsletter
        CRUD::addField([
            'name' => 'newsletter',
            'label' => 'Newsletter',
            'type' => 'switch',
            'hint' => __('backend.user.newsletter_hint'),
            'wrapper' => ['class' => 'form-group col-md-12']
        ]);
    }

    public function update(ClientRequest $request)
    {
        // Obtener estado antes de actualizar
        $clientBeforeUpdate = $this->crud->model->findOrFail($request->id);
        $newsletterBefore = $clientBeforeUpdate->newsletter;

        // Obtener todos los datos validados
        $data = $request->validated();

        // CRÍTICO: Extraer password ANTES de hacer el update
        $newPassword = null;
        if (!empty($data['password'])) {
            $newPassword = $data['password'];

        }

        // Remover password del array de datos para el mass assignment
        unset($data['password']);
        unset($data['password_confirmation']);

        // Actualizar con Backpack (sin password)
        $this->crud->update($request->id, $data);

        // Refrescar la entrada
        $this->crud->entry = $this->crud->model->findOrFail($request->id);
        $client = $this->crud->entry;

        // AHORA actualizar el password si existe
        if ($newPassword !== null) {
            // Opción 1: Usar el mutador (recomendado)
            $client->password = $newPassword;
            $client->save();

            // Verificar que funcionó
            $client->refresh();
            $canVerify = \Hash::check($newPassword, $client->password);

        }

        // Actualizar preferencias si existe el servicio
        if (class_exists('\App\Services\Api\ClientPreferencesService')) {
            (new \App\Services\Api\ClientPreferencesService())->update($client, $request);
        }

        // Justo después de actualizar el password, añade esto:
        if ($newPassword !== null) {
            $client->password = $newPassword;
            $client->save();

            // DEBUGGING - REMOVER EN PRODUCCIÓN
            $client->refresh();

            // Test 1: Verificar que el password guardado es un hash válido
            $isValidHash = strlen($client->password) === 60 && str_starts_with($client->password, '$2y$');

            // Test 2: Verificar que podemos autenticar con el nuevo password
            $canAuthenticate = \Hash::check($newPassword, $client->password);

            // Test 3: Info del hash
            $hashInfo = \Hash::info($client->password);

            // Si no puede autenticar, mostrar alerta
            if (!$canAuthenticate) {
                Alert::warning('⚠️ Password guardado pero la verificación falló. Revisa los logs.')->flash();
            }
        }
        // FIN DEBUGGING

        // Gestionar suscripción a Brevo
        $this->handleBrevoSubscription($client, $newsletterBefore);

        // Devolver respuesta estándar de Backpack
        Alert::success(trans('backpack::crud.update_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($client->getKey());
    }

    protected function handleBrevoSubscription($client, $newsletterBefore)
    {
        $apiKey = brand_setting('brevo.api_key');
        $newsletterListId = brand_setting('brevo.newsletter_list_id');

        if (!$apiKey || !$newsletterListId) {
            return;
        }

        try {
            $brevoService = new BrevoService($apiKey, $newsletterListId);

            if ($client->newsletter && !$newsletterBefore) {
                $brevoService->subscribeUser($client->email, [
                    'FNAME' => $client->name,
                    'LNAME' => $client->surname,
                ]);
            } elseif (!$client->newsletter && $newsletterBefore) {
                $brevoService->deleteUser($client->email);
            }
        } catch (\Exception $e) {
            Log::error('Error con Brevo', [
                'error' => $e->getMessage(),
                'client_id' => $client->id
            ]);
            // No fallar la operación por un error de Brevo
        }
    }

    /**
     * Importación de clientes con validaciones mejoradas
     */
    public function import()
    {
        // Rate limiting
        if (!$this->checkImportRateLimit()) {
            Alert::error(__('backend.alert.import_rate_limit'))->flash();
            return back();
        }

        // Validación del archivo
        $validator = Validator::make(request()->all(), [
            'csv' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        if ($validator->fails()) {
            Alert::error(__('backend.alert.csv'))->flash();
            return back();
        }

        $file = request()->file('csv');

        // Validación de seguridad
        $contentValidation = $this->validateCsvSecurity($file);
        if (!$contentValidation['valid']) {
            Alert::error($contentValidation['message'])->flash();
            return back();
        }

        // Validación de headers
        if (!$this->validateCsvHeaders($file)) {
            return back();
        }

        // Ejecutar importación
        try {
            $import = new ClientImport(get_current_brand_id());

            Excel::import($import, $file, null, \Maatwebsite\Excel\Excel::CSV);

            $this->handleImportResults($import);
        } catch (\Exception $e) {
            Log::error('Error importando clientes', [
                'exception' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            Alert::error(__('backend.alert.import_client.error'))->flash();
        }

        return back();
    }

    private function checkImportRateLimit(): bool
    {
        $key = 'client_import:' . auth()->id();

        if (RateLimiter::tooManyAttempts($key, self::IMPORT_RATE_LIMIT)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Import rate limit exceeded', [
                'user_id' => auth()->id(),
                'wait_seconds' => $seconds
            ]);
            return false;
        }

        RateLimiter::hit($key, 3600);
        return true;
    }

    private function validateCsvSecurity($file): array
    {
        try {
            $handle = fopen($file->getRealPath(), 'r');
            if (!$handle) {
                return ['valid' => false, 'message' => __('backend.alert.csv_cannot_read')];
            }

            $firstLine = fgets($handle);
            if (empty($firstLine)) {
                fclose($handle);
                return ['valid' => false, 'message' => __('backend.alert.csv_empty')];
            }

            // Verificar encoding
            if (!mb_check_encoding($firstLine, 'UTF-8')) {
                // Intentar convertir
                $firstLine = mb_convert_encoding($firstLine, 'UTF-8', 'auto');
            }

            // Contar líneas
            $lineCount = 1;
            while (!feof($handle)) {
                fgets($handle);
                $lineCount++;
                if ($lineCount > self::IMPORT_MAX_ROWS + 1) {
                    fclose($handle);
                    return [
                        'valid' => false,
                        'message' => __('backend.alert.csv_too_large', ['max' => self::IMPORT_MAX_ROWS])
                    ];
                }
            }

            fclose($handle);

            // Verificar contenido sospechoso
            $suspiciousCount = $this->checkForSuspiciousContent($file);
            if ($suspiciousCount > 3) {
                return [
                    'valid' => false,
                    'message' => __('backend.alert.csv_suspicious')
                ];
            }

            return ['valid' => true, 'message' => 'OK'];
        } catch (\Exception $e) {
            Log::error('Error validating CSV', ['error' => $e->getMessage()]);
            return ['valid' => false, 'message' => __('backend.alert.csv_invalid')];
        }
    }

    private function checkForSuspiciousContent($file): int
    {
        $csv = new \SplFileObject($file->getRealPath());
        $csv->setFlags(\SplFileObject::READ_CSV);

        $suspiciousCount = 0;
        $rowsChecked = 0;

        while (!$csv->eof() && $rowsChecked < 10) {
            $row = $csv->fgetcsv();
            if ($row && is_array($row)) {
                foreach ($row as $cell) {
                    if ($this->isCellSuspicious($cell)) {
                        $suspiciousCount++;
                    }
                }
            }
            $rowsChecked++;
        }

        return $suspiciousCount;
    }

    private function isCellSuspicious($cell): bool
    {
        if (!is_string($cell)) {
            return false;
        }

        $cell = trim($cell);

        // Detectar fórmulas de Excel
        $dangerousStarts = ['=', '+', '-', '@', '\t=', '\r=', '\n='];
        foreach ($dangerousStarts as $start) {
            if (str_starts_with($cell, $start)) {
                return true;
            }
        }

        // Detectar caracteres de control
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $cell)) {
            return true;
        }

        return false;
    }

    private function validateCsvHeaders($file): bool
    {
        $expected = [
            'name',
            'surname',
            'email',
            'phone',
            'mobile_phone',
            'locale',
            'date_birth',
            'dni',
            'province',
            'city',
            'address',
            'postal_code',
            'newsletter'
        ];

        $csv = new \SplFileObject($file->getRealPath());
        $csv->setFlags(\SplFileObject::READ_CSV);
        $header = array_map('mb_strtolower', array_map('trim', $csv->fgetcsv()));

        if ($missing = array_diff($expected, $header)) {
            Alert::error(
                __('backend.alert.import_client.missing_columns') . ': ' . implode(', ', $missing)
            )->flash();
            return false;
        }

        return true;
    }

    private function handleImportResults($import)
    {
        $processed = $import->getRowsProcessed();
        $failures = $import->failures();
        $failureCount = count($failures);

        if ($failureCount > 0) {
            if ($failureCount > $processed * 0.5) {
                Alert::error(__('backend.alert.import_client.too_many_errors', [
                    'failed' => $failureCount,
                    'total' => $processed
                ]))->flash();
            } else {
                Alert::warning(__('backend.alert.import_client.partial_success', [
                    'imported' => $processed - $failureCount,
                    'failed' => $failureCount
                ]))->flash();
            }

            // Guardar errores en sesión para mostrar
            $this->storeImportErrors($failures);
        } else {
            Alert::success(__('backend.alert.import_client.success', [
                'count' => $processed
            ]))->flash();
        }
    }

    private function storeImportErrors($failures): void
    {
        $errors = [];
        $maxErrors = 10;

        foreach ($failures as $index => $failure) {
            if ($index >= $maxErrors)
                break;

            $errors[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ];
        }

        if (!empty($errors)) {
            session()->flash('import_errors', $errors);
        }
    }

    /**
     * Añadir columnas de campos personalizados SOLO para Show
     */
    protected function addFormFieldColumnsForShow()
    {
        $brandId = get_current_brand_id();

        // Cache los campos por 1 hora
        $extraFields = Cache::remember(
            sprintf(self::CACHE_KEY_FORM_FIELDS, $brandId),
            self::CACHE_TTL_FORM_FIELDS,
            function () use ($brandId) {
                return FormField::where('brand_id', $brandId)
                    ->whereNull('deleted_at')
                    ->orderBy('weight')
                    ->get(['id', 'type', 'label', 'config']);
            }
        );

        if ($extraFields->isEmpty()) {
            return;
        }

        // Para show, cargar las respuestas del cliente actual
        $clientId = request()->segment(3); // Obtener ID del cliente de la URL
        if (!$clientId) {
            return;
        }

        // Cargar todas las respuestas de una vez
        $answers = \App\Models\FormFieldAnswer::where('client_id', $clientId)
            ->whereIn('field_id', $extraFields->pluck('id'))
            ->get()
            ->keyBy('field_id');

        $locale = app()->getLocale();

        foreach ($extraFields as $field) {
            $label = $this->getFieldLabel($field->label, $locale);
            $optionsMap = $this->getFieldOptionsMap($field->config, $locale);

            CRUD::addColumn([
                'name' => 'form_field_' . $field->id,
                'label' => $label,
                'type' => 'closure',
                'function' => function ($client) use ($field, $answers, $optionsMap) {
                    return $this->formatFieldAnswer(
                        $answers->get($field->id),
                        $field->type,
                        $optionsMap
                    );
                },
                'escaped' => false
            ]);
        }
    }

    /**
     * Añadir solo campos personalizados esenciales para el listado
     */
    protected function addLimitedFormFieldColumns()
    {
        // Solo añadir los 3 primeros campos más importantes
        $brandId = get_current_brand_id();

        $extraFields = Cache::remember(
            sprintf(self::CACHE_KEY_FORM_FIELDS . '_limited', $brandId),
            self::CACHE_TTL_FORM_FIELDS,
            function () use ($brandId) {
                return FormField::where('brand_id', $brandId)
                    ->whereNull('deleted_at')
                    ->where('weight', '<=', 3) // Solo los primeros 3
                    ->orderBy('weight')
                    ->get(['id', 'type', 'label']);
            }
        );

        foreach ($extraFields as $field) {
            CRUD::addColumn([
                'name' => 'form_field_' . $field->id,
                'label' => $this->getFieldLabel($field->label, app()->getLocale()),
                'type' => 'text',
                'orderable' => false,
                'searchable' => false
            ]);
        }
    }

    private function getFieldLabel($jsonLabel, $locale)
    {
        if (empty($jsonLabel)) {
            return '-';
        }

        if (is_string($jsonLabel)) {
            $arr = json_decode($jsonLabel, true);
            if (!is_array($arr)) {
                return $jsonLabel;
            }
        } else {
            $arr = (array) $jsonLabel;
        }

        return $arr[$locale] ?? array_values($arr)[0] ?? '-';
    }

    private function getFieldOptionsMap($jsonConfig, $locale)
    {
        if (empty($jsonConfig)) {
            return [];
        }

        $cfg = is_string($jsonConfig) ? json_decode($jsonConfig, true) : (array) $jsonConfig;

        if (!isset($cfg['options']) || !is_array($cfg['options'])) {
            return [];
        }

        $map = [];
        foreach ($cfg['options'] as $option) {
            if (!isset($option['value']))
                continue;

            $label = $option['label'] ?? $option['value'];
            if (is_array($label)) {
                $label = $label[$locale] ?? array_values($label)[0] ?? $option['value'];
            }

            $map[$option['value']] = $label;
        }

        return $map;
    }

    private function formatFieldAnswer($answer, $fieldType, $optionsMap)
    {
        if (!$answer || empty($answer->answer)) {
            return '-';
        }

        $raw = $answer->answer;

        switch ($fieldType) {
            case 'date':
                try {
                    return Carbon::parse($raw)->format('d/m/Y');
                } catch (\Exception $e) {
                    return $raw;
                }

            case 'datetime':
                try {
                    return Carbon::parse($raw)->format('d/m/Y H:i');
                } catch (\Exception $e) {
                    return $raw;
                }

            case 'select':
            case 'radio':
                return $optionsMap[$raw] ?? $raw;

            case 'select_multiple':
            case 'multiselect':
            case 'checkbox':
                $keys = array_filter(array_map('trim', explode(',', $raw)));
                $labels = array_map(function ($key) use ($optionsMap) {
                    return $optionsMap[$key] ?? $key;
                }, $keys);
                return implode(', ', $labels);

            case 'boolean':
                return $raw ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>';

            case 'number':
                return is_numeric($raw) ? number_format((float) $raw, 2) : $raw;

            default:
                return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Exportación con mailing newsletter
     */
    public function toMailing(Request $request)
    {
        $q = Client::where('brand_id', get_current_brand_id())
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('newsletter', 1);

        // Aplicar filtros
        if ($request->filled('session')) {
            $sessionId = (int) $request->get('session');
            $q->whereHas('carts.inscriptions', function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId);
            });
        }

        if ($request->filled('from_to')) {
            $dates = json_decode($request->get('from_to'));
            if ($dates && !empty($dates->from)) {
                $q->where('created_at', '>=', $dates->from);
            }
            if ($dates && !empty($dates->to)) {
                $q->where('created_at', '<=', $dates->to . ' 23:59:59');
            }
        }

        // Obtener emails únicos
        $emails = $q->pluck('email')
            ->filter(fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        // Guardar en cache para evitar URL gigante
        $key = 'mailing_recipients:' . Str::uuid();
        Cache::put($key, $emails->toJson(), now()->addMinutes(5));

        return redirect()->route('mailing.create', ['recipients_key' => $key]);
    }

    /**
     * Exportación optimizada
     */
    public function export()
    {
        // Verificar si existe la clase optimizada, si no usar la original
        $exportClass = class_exists('\App\Exports\ClientsExtraExportOptimized')
            ? '\App\Exports\ClientsExtraExportOptimized'
            : '\App\Exports\ClientsExtraExport';

        return Excel::download(
            new $exportClass(get_current_brand_id()),
            'clients_' . date('Y-m-d') . '.xlsx'
        );
    }
}
