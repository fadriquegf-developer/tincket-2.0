<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inscripciones – {{ $session->event->name }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 landscape;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
        }

        h2 {
            margin: 5px 0 10px 0;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead th {
            border: 1px solid #000;
            padding: 3px;
            background: #ddd;
            font-size: 8px;
            font-weight: bold;
        }

        tbody td {
            border: 1px solid #ccc;
            padding: 2px;
            font-size: 8px;
            word-wrap: break-word;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* ✨ NUEVO: Estilos para columnas dinámicas */
        .metadata-column {
            font-size: 7px;
            background-color: #f0f8ff;
        }
    </style>
</head>

<body>
    <h2>{{ $session->event->name }} – {{ $session->starts_on->format('d/m/Y H:i') }}</h2>
    <table>
        <thead>
            <tr>
                {{-- Columnas estándar existentes --}}
                <th style="width: 6%;">Nombre</th>
                <th style="width: 8%;">Apellidos</th>
                <th style="width: 12%;">Email</th>
                <th style="width: 7%;">Teléfono</th>
                <th style="width: 7%;">Código</th>
                <th style="width: 6%;">Gateway</th>
                <th style="width: 8%;">Tarifa</th>
                <th style="width: 7%;">Butaca</th>
                <th style="width: 10%;">Barcode</th>
                <th style="width: 4%;">Valid.</th>
                <th style="width: 8%;">Fecha</th>

                {{-- ✨ NUEVO: Columnas dinámicas de form_fields --}}
                @if(isset($formFieldsUsed) && count($formFieldsUsed) > 0)
                    @foreach($formFieldsUsed as $fieldName => $fieldLabel)
                        <th class="metadata-column" style="width: {{ 100 / (11 + count($formFieldsUsed)) }}%;">
                            {{ $fieldLabel }}
                        </th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($inscriptions as $i)
                @php
                    // Decodificar metadata
                    $metadata = is_array($i->metadata) ? 
                        $i->metadata : 
                        json_decode($i->metadata, true) ?? [];
                @endphp
                <tr>
                    {{-- Columnas estándar --}}
                    <td>{{ $i->cart->client->name ?? '-' }}</td>
                    <td>{{ $i->cart->client->surname ?? '-' }}</td>
                    <td>{{ $i->cart->client->email ?? '-' }}</td>
                    <td>{{ $i->cart->client->phone ?? '-' }}</td>
                    <td>{{ $i->cart->confirmation_code ?? '-' }}</td>
                    <td>{{ $i->cart->confirmedPayment->gateway ?? '-' }}</td>
                    <td>{{ $i->rate->name ?? '-' }}</td>
                    <td>
                        @if ($i->slot)
                            {{ $i->slot->zone->name ?? '' }} {{ $i->slot->name }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $i->barcode }}</td>
                    <td>{{ $i->checked_at ? 'Sí' : 'No' }}</td>
                    <td>{{ $i->cart->created_at?->format('d/m/y H:i') }}</td>

                    {{-- ✨ NUEVO: Columnas dinámicas con valores de metadata --}}
                    @if(isset($formFieldsUsed) && count($formFieldsUsed) > 0)
                        @foreach($formFieldsUsed as $fieldName => $fieldLabel)
                            <td class="metadata-column">
                                @php
                                    $value = $metadata[$fieldName] ?? '';
                                    
                                    // Formatear el valor
                                    if (is_array($value)) {
                                        echo implode(', ', $value);
                                    } elseif (is_bool($value)) {
                                        echo $value ? 'Sí' : 'No';
                                    } elseif (empty($value)) {
                                        echo '-';
                                    } else {
                                        echo $value;
                                    }
                                @endphp
                            </td>
                        @endforeach
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top: 10px; font-size: 8px;">
        Total: {{ count($inscriptions) }} inscripciones
        @if(isset($formFieldsUsed) && count($formFieldsUsed) > 0)
            | Campos adicionales: {{ count($formFieldsUsed) }}
        @endif
    </p>
</body>

</html>