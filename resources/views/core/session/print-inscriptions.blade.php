<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inscripciones — {{ $session->event->name }}</title>
    <style>
        /* Márgenes de página reducidos */
        @page {
            margin: 10mm;
        }
        /* Forzar tamaño de fuente más pequeño */
        body {
            font-family: sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        h1, h2, h3, h4, h5 {
            margin: .2em 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;                /* distribución fija de columnas */
            word-wrap: break-word;              /* forzar salto de palabra */
        }
        thead th {
            border: 1px solid #000;
            padding: 4px;
            background: #eee;
            font-size: 9px;
        }
        tbody td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 9px;
        }
        /* Opcional: alternar fondo en impresión (zebra) */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <h2>Inscripciones — {{ $session->event->name }}</h2>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Confirmación</th>
                <th>Plataforma</th>
                <th>Tarifa</th>
                <th>Posición</th>
                <th>Código de barras</th>
                <th>Validado</th>
                <th>DNI</th>
                <th>Creado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inscriptions as $i)
                @php($c = optional(optional($i->cart)->client))
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->surname }}</td>
                    <td>{{ $c->email }}</td>
                    <td>{{ $c->phone }}</td>
                    <td>{{ optional($i->cart)->confirmation_code }}</td>
                    <td>{{ optional($i->cart->payment)->gateway }}</td>
                    <td>{{ optional($i->rate)->name }}</td>
                    <td>{{ optional($i->slot)->name ?? 'n/a' }}</td>
                    <td>{{ $i->barcode }}</td>
                    <td>{{ $i->checked_at ? 'Sí' : 'No' }}</td>
                    <td>{{ data_get(json_decode($i->metadata, true), 'dni', 'n/a') }}</td>
                    <td>{{ optional($i->cart)->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
