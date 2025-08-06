<?php

namespace App\Imports;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\{
    SkipsFailures,
    SkipsOnFailure,
    ToModel,
    WithBatchInserts,
    WithChunkReading,
    WithHeadingRow,
    WithValidation
};

class ClientImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    WithBatchInserts,
    SkipsOnFailure
{
    use SkipsFailures;

    /** @var int */
    private int $brandId;

    public function __construct(int $brandId)
    {
        $this->brandId = $brandId;
    }

    /* -----------------------------------------------------------------
     |  Configuración de Maatwebsite/Excel
     | ----------------------------------------------------------------- */
    public function chunkSize(): int
    {
        return 1000;
    }
    public function batchSize(): int
    {
        return 1000;
    }

    /** Reglas mínimas de validación por fila */
    public function rules(): array
    {
        return [
            'email'   => ['required', 'email'],
            'name'    => ['nullable', 'string'],
            'surname' => ['nullable', 'string'],
        ];
    }

    /* -----------------------------------------------------------------
     |  Conversión de cada fila a un modelo
     | ----------------------------------------------------------------- */
    public function model(array $row): ?Client
    {
        //  1) El email ya existe ⇒ saltar
        $email = $row['email'] ?? null;

        if (
            blank($email) ||
            Client::where('brand_id', $this->brandId)
            ->where('email', $email)
            ->exists()
        ) {
            Log::info('Cliente omitido (email vacío o duplicado)', ['email' => $email]);
            return null;
        }

        //  2) Nombre / apellido: al menos uno obligatorio
        $name     = $row['name']     ?? null;
        $surname  = $row['surname']  ?? null;

        if (is_null($name) && is_null($surname)) {
            Log::info('Cliente omitido (sin nombre y apellido)', ['row' => $row]);
            return null;
        }

        //  3) Fecha de nacimiento (d/m/Y) → Carbon
        $dateBirth = null;
        if (!blank($row['date_birth'])) {
            try {
                $dateBirth = Carbon::createFromFormat('d/m/Y', $row['date_birth']);
            } catch (\Throwable $e) {
                Log::info('Formato de fecha no válido; se omite', ['date_birth' => $row['date_birth']]);
            }
        }

        //  4) Construir el modelo
        return new Client([
            'brand_id'     => $this->brandId,
            'name'         => $name     ?? $surname,
            'surname'      => $surname  ?? $name,
            'email'        => $email,
            //   El mutador del modelo se encarga de hashear ↓
            'password'     => $row['dni'] ?: '123456',

            'phone'        => $row['phone']         ?? null,
            'mobile_phone' => $row['mobile_phone']  ?? null,
            'address'      => $row['address']       ?? null,
            'postal_code'  => $row['postal_code']   ?? null,
            'city'         => $row['city']          ?? null,
            'province'     => $row['province']      ?? null,
            'dni'          => $row['dni']           ?? null,
            'date_birth'   => $dateBirth,
            'newsletter'   => filter_var($row['newsletter'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'locale'       => $row['locale']        ?? 'es',
        ]);
    }
}
