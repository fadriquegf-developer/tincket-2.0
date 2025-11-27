<?php

namespace App\Imports;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\{
    SkipsFailures,
    SkipsOnFailure,
    ToModel,
    WithBatchInserts,
    WithChunkReading,
    WithHeadingRow,
    WithValidation,
    WithEvents
};
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterImport;

class ClientImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    WithBatchInserts,
    SkipsOnFailure,
    WithEvents
{
    use SkipsFailures;

    /** @var int */
    private int $brandId;

    /** @var int Contador de filas procesadas */
    private int $rowsProcessed = 0;

    /** @var int Límite máximo de filas */
    private const MAX_ROWS = 10000;

    public function __construct(int $brandId)
    {
        $this->brandId = $brandId;
    }

    /* -----------------------------------------------------------------
     |  Configuración de Maatwebsite/Excel
     | ----------------------------------------------------------------- */
    public function chunkSize(): int
    {
        return 500; // Procesamiento eficiente
    }

    public function batchSize(): int
    {
        return 100; // Inserciones por lote
    }

    /**
     * Reglas de validación FLEXIBLES para aceptar más formatos
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email', // Validación básica, sin :rfc,dns
                'max:255',
                function ($attribute, $value, $fail) {
                    // Prevenir CSV injection
                    if ($this->isSuspiciousValue($value)) {
                        $fail('El email contiene caracteres sospechosos.');
                    }
                }
            ],
            'name' => [
                'nullable',
                'max:255',
                // Regex más permisivo - acepta letras, espacios, guiones, puntos y apóstrofes
                'regex:/^[\p{L}\s\-\.\']*$/u',
            ],
            'surname' => [
                'nullable',
                'max:255',
                'regex:/^[\p{L}\s\-\.\']*$/u',
            ],
            // NO validar como string, dejar que se convierta automáticamente
            'phone' => [
                'nullable',
                'max:20',
            ],
            'mobile_phone' => [
                'nullable',
                'max:20',
            ],
            'dni' => [
                'nullable',
                'max:20',
                'regex:/^[A-Z0-9\-]*$/i',
            ],
            'postal_code' => [
                'nullable',
                'max:10',
            ],
            'locale' => [
                'nullable',
                'in:es,ca,gl',
            ],
            'newsletter' => [
                'nullable',
                // Acepta múltiples formatos
            ],
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function customValidationMessages()
    {
        return [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email no tiene un formato válido.',
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'surname.regex' => 'El apellido contiene caracteres no permitidos.',
            'dni.regex' => 'El DNI contiene caracteres no permitidos.',
            'phone.regex' => 'El teléfono contiene caracteres no permitidos.',
        ];
    }

    /* -----------------------------------------------------------------
     |  Conversión de cada fila a un modelo
     | ----------------------------------------------------------------- */
    public function model(array $row): ?Client
    {
        // Verificar límite de filas
        $this->rowsProcessed++;
        if ($this->rowsProcessed > self::MAX_ROWS) {
            throw new \Exception('Se ha excedido el límite máximo de ' . self::MAX_ROWS . ' filas.');
        }

        // Sanitizar toda la fila
        $row = $this->sanitizeRow($row);

        // 1) Validar email
        $email = $row['email'] ?? null;

        if (blank($email)) {
            return null;
        }

        // Verificar duplicado
        if ($this->emailExists($email)) {
            return null;
        }

        // 2) Nombre / apellido: al menos uno obligatorio
        $name = $row['name'] ?? null;
        $surname = $row['surname'] ?? null;

        if (is_null($name) && is_null($surname)) {
            return null;
        }

        // 3) Fecha de nacimiento
        $dateBirth = $this->parseDate($row['date_birth'] ?? null);

        // 4) Construir el modelo
        try {
            $client = new Client([
                'brand_id' => $this->brandId,
                'name' => $name ?? $surname,
                'surname' => $surname ?? $name,
                'email' => $email,
                'phone' => $this->sanitizePhone($row['phone'] ?? null),
                'mobile_phone' => $this->sanitizePhone($row['mobile_phone'] ?? null),
                'address' => $this->sanitizeText($row['address'] ?? null),
                'postal_code' => $row['postal_code'] ?? null,
                'city' => $this->sanitizeText($row['city'] ?? null),
                'province' => $this->sanitizeText($row['province'] ?? null),
                'dni' => $row['dni'] ?? null,
                'date_birth' => $dateBirth,
                'newsletter' => $this->parseBoolean($row['newsletter'] ?? false),
                'locale' => in_array($row['locale'] ?? '', ['es', 'ca', 'gl'])
                    ? $row['locale']
                    : 'es',
            ]);

            // Password: usar DNI si existe, sino '123456' (como el código original)
            $client->password = $row['dni'] ?: '123456';

            // Opcional: Si quieres forzar cambio en primer login
            // if (method_exists($client, 'forcePasswordChange')) {
            //     $client->forcePasswordChange();
            // }

            return $client;
        } catch (\Exception $e) {
            Log::error('Error creando cliente desde importación', [
                'error' => $e->getMessage(),
                'row_number' => $this->rowsProcessed
            ]);
            return null;
        }
    }

    /**
     * Eventos para logging
     */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {

            },

            AfterImport::class => function (AfterImport $event) {

            }
        ];
    }

    /* -----------------------------------------------------------------
    |  Métodos de seguridad y sanitización
     | ----------------------------------------------------------------- */

    /**
     * Sanitizar toda la fila
     */
    private function sanitizeRow(array $row): array
    {
        $sanitized = [];

        foreach ($row as $key => $value) {
            if (is_string($value)) {
                // Eliminar espacios
                $value = trim($value);

                // Eliminar caracteres de control
                $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);

                // Prevenir CSV injection
                if (in_array($value[0] ?? '', ['=', '+', '-', '@', '\t', '\r', '\n'])) {
                    $value = "'" . $value;
                }

                // Limitar longitud
                $value = mb_substr($value, 0, 500);
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * Detectar valores sospechosos
     */
    private function isSuspiciousValue($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);

        // Detectar fórmulas Excel
        $dangerousStarts = ['=', '+', '-', '@', '\t=', '\r=', '\n='];
        foreach ($dangerousStarts as $start) {
            if (str_starts_with($value, $start)) {
                return true;
            }
        }

        // Detectar caracteres de control
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Verificar si email existe (con caché)
     */
    private function emailExists(string $email): bool
    {
        static $existingEmails = null;

        if ($existingEmails === null) {
            // Cargar todos los emails una sola vez
            $existingEmails = Client::where('brand_id', $this->brandId)
                ->pluck('email')
                ->flip()
                ->toArray();
        }

        return isset($existingEmails[$email]);
    }

    /**
     * Parsear fecha
     */
    private function parseDate($value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('d/m/Y', $value);

            // Validar rango razonable
            if ($date->year < 1900 || $date->isFuture()) {
                return null;
            }

            return $date;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parsear booleano
     */
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(
            strtolower(trim((string) $value)),
            ['1', 'true', 'yes', 'si', 'sí'],
            true
        );
    }

    /**
     * Sanitizar teléfono
     */
    private function sanitizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $phone = preg_replace('/[^\d\s\+\-\(\)]/', '', $phone);
        return mb_substr($phone, 0, 20);
    }

    /**
     * Sanitizar texto
     */
    private function sanitizeText(?string $text): ?string
    {
        if (blank($text)) {
            return null;
        }

        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Ofuscar email para logs
     */
    private function obfuscateEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return 'invalid-email';
        }

        $name = substr($parts[0], 0, 2) . str_repeat('*', min(5, strlen($parts[0]) - 2));
        return $name . '@' . $parts[1];
    }

    /**
     * Obtener número de filas procesadas
     */
    public function getRowsProcessed(): int
    {
        return $this->rowsProcessed;
    }
}
