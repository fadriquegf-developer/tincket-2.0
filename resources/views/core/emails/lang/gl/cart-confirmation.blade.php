@extends('core.emails.layout')
@section('content')
    <p>
        {{ $cart->client->name or '{name}' }},<br>
        <br>
        Neste correo atoparás as entradas adxuntas. Lembra imprimilas ou traelas descargadas no teu móbil o día do evento.
    </p>
    <p>
        O teu código de compra é: <span style="font-weight: bold;">{{ $cart->confirmation_code or '{code}' }}</span>
    </p>
    <p>
        Goza do espectáculo!
    </p>
    <p>
        saúdos ingrávidos,
    </p>
    <hr>
    <p style="font-weight: bold;">
        Sala Ingrávida <br>
        Rúa Pérez Leirós #3 Baixo<br>
        36400 O Porriño, Pontevedra<br>
        T 986331622
    </p>
@endsection
