<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\FormField;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;

class ClientsExtraExportOptimized implements
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldAutoSize,
    WithChunkReading,
    ShouldQueue
{
    use Exportable;

    protected $brandId;
    protected $formFields;
    protected $locale;

    public function __construct($brandId)
    {
        $this->brandId = $brandId;
        $this->locale = app()->getLocale();

        // Cache los form fields para no consultarlos por cada fila
        $this->formFields = Cache::remember(
            "export_form_fields_{$brandId}",
            300,
            function () use ($brandId) {
                return FormField::where('brand_id', $brandId)
                    ->whereNull('deleted_at')
                    ->orderBy('weight')
                    ->get(['id', 'label', 'type']);
            }
        );
    }

    public function query()
    {
        return Client::where('brand_id', $this->brandId)
            ->with(['answers.form_field']) // Eager load para evitar N+1
            ->orderBy('id');
    }

    public function chunkSize(): int
    {
        return 500; // Procesar de 500 en 500
    }

    public function headings(): array
    {
        $headings = [
            'ID',
            __('backend.client.name'),
            __('backend.client.surname'),
            __('backend.client.email'),
            __('backend.client.phone'),
            __('backend.client.mobile_phone'),
            __('backend.client.address'),
            __('backend.client.postal_code'),
            __('backend.client.city'),
            __('backend.client.province'),
            'DNI/NIE',
            __('backend.client.date_birth'),
            __('backend.client.locale'),
            __('backend.client.newsletter'),
            __('backend.client.num_session'),
            __('backend.client.created_at'),
        ];

        // Añadir campos personalizados
        foreach ($this->formFields as $field) {
            $label = $this->getFieldLabel($field->label);
            if (!in_array($label, $headings)) {
                $headings[] = $label;
            }
        }

        return $headings;
    }

    /**
     * @var Client $client
     */
    public function map($client): array
    {
        // Datos básicos del cliente
        $data = [
            $client->id,
            $client->name,
            $client->surname,
            $client->email,
            $client->phone,
            $client->mobile_phone,
            $client->address,
            $client->postal_code,
            $client->city,
            $client->province,
            $client->dni,
            $client->date_birth ? $client->date_birth->format('d/m/Y') : '',
            $client->locale,
            $client->newsletter ? 'Si' : 'No',
            $this->getClientSessionsCount($client),
            $client->created_at->format('d/m/Y H:i'),
        ];

        // Crear un índice de respuestas por field_id para acceso rápido
        $answersIndex = $client->answers->keyBy('field_id');

        // Añadir campos personalizados en el mismo orden
        foreach ($this->formFields as $field) {
            $answer = $answersIndex->get($field->id);

            if ($answer && $answer->answer) {
                $data[] = $this->formatAnswer($answer->answer, $field->type);
            } else {
                $data[] = '';
            }
        }

        return $data;
    }

    /**
     * Obtener número de sesiones del cliente (con cache temporal)
     */
    private function getClientSessionsCount($client): int
    {
        return Cache::remember(
            "export_client_{$client->id}_sessions",
            60, // Cache por 1 minuto durante la exportación
            function () use ($client) {
                return $client->inscriptions()
                    ->join('carts', 'inscriptions.cart_id', '=', 'carts.id')
                    ->whereNotNull('carts.confirmation_code')
                    ->distinct('inscriptions.session_id')
                    ->count('inscriptions.session_id');
            }
        );
    }

    /**
     * Formatear respuesta según el tipo de campo
     */
    private function formatAnswer($answer, $type): string
    {
        switch ($type) {
            case 'date':
                try {
                    return \Carbon\Carbon::parse($answer)->format('d/m/Y');
                } catch (\Exception $e) {
                    return $answer;
                }

            case 'datetime':
                try {
                    return \Carbon\Carbon::parse($answer)->format('d/m/Y H:i');
                } catch (\Exception $e) {
                    return $answer;
                }

            case 'boolean':
                return $answer ? 'Si' : 'No';

            case 'select_multiple':
            case 'multiselect':
            case 'checkbox':
                // Si es string con comas, dejarlo como está
                // Si es array, convertir a string
                if (is_array($answer)) {
                    return implode(', ', $answer);
                }
                return $answer;

            case 'number':
                return is_numeric($answer) ? number_format((float)$answer, 2, ',', '.') : $answer;

            default:
                return (string) $answer;
        }
    }

    /**
     * Obtener label traducido del campo
     */
    private function getFieldLabel($jsonLabel): string
    {
        if (empty($jsonLabel)) {
            return 'Campo sin nombre';
        }

        if (is_string($jsonLabel)) {
            $decoded = json_decode($jsonLabel, true);
            if (is_array($decoded)) {
                return $decoded[$this->locale] ??
                    $decoded['es'] ??
                    array_values($decoded)[0] ??
                    $jsonLabel;
            }
            return $jsonLabel;
        }

        if (is_array($jsonLabel)) {
            return $jsonLabel[$this->locale] ??
                $jsonLabel['es'] ??
                array_values($jsonLabel)[0] ??
                'Campo sin nombre';
        }

        return 'Campo sin nombre';
    }

    /**
     * Limpieza después de la exportación
     */
    public function __destruct()
    {
        // Limpiar cache temporal
        Cache::forget("export_form_fields_{$this->brandId}");

        // Limpiar cache de sesiones de clientes
        Client::where('brand_id', $this->brandId)
            ->pluck('id')
            ->each(function ($clientId) {
                Cache::forget("export_client_{$clientId}_sessions");
            });
    }
}
