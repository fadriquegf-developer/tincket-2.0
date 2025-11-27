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
    </style>
</head>

<body>
    <h2>{{ $session->event->name }} – {{ $session->starts_on->format('d/m/Y H:i') }}</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Nombre</th>
                <th style="width: 10%;">Apellidos</th>
                <th style="width: 15%;">Email</th>
                <th style="width: 8%;">Teléfono</th>
                <th style="width: 8%;">Código</th>
                <th style="width: 6%;">Gateway</th>
                <th style="width: 10%;">Tarifa</th>
                <th style="width: 8%;">Butaca</th>
                <th style="width: 12%;">Barcode</th>
                <th style="width: 5%;">Valid.</th>
                <th style="width: 10%;">Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inscriptions as $i)
                <tr>
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
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top: 10px; font-size: 8px;">Total: {{ count($inscriptions) }} inscripciones</p>
</body>

</html>
