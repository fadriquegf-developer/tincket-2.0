@extends('core.emails.layout')
@section('content')
    <p>
        {{ $cart->client->name or '{name}' }},<br>
        <br>
        En este email encontrará adjuntas las entradas. Recuerde imprimirlas
        o traerlas descargadas en el móvil el dia del evento.
    </p>
    <p>
        Su código de compra es: <span style="font-weight: bold;">{{ $cart->confirmation_code or '{code}' }}</span>
    </p>
    <p>
        Esperamos que disfrute del espectáculo!
    </p>
    <p>
        Saludos cordiales,
    </p>
    <hr>
    <p style="font-weight: bold;">
        Demo de YesWeTicket <br>
        Plaça Javajan, 1<br>
        08570 Javaland<br>
        T 93 000 00 00
    </p>
@endsection
