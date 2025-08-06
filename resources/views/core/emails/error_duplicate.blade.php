@extends('core.emails.layout')
@php
    $brand = $cart->brand;
@endphp

@section('content')
    <h3>Benvolgut/da {{ $cart->client->name or '{name}' }},</h3>
    <p>
        Lamentem informar-vos que hem trobat una incidència durant el processament de compra de les seves entrades({{ $cart->id }}).
        @if ($payment->created_at->diffInMinutes($payment->updated_at) > 15)
            La causa podria estar relacionada amb el llarg temps entre
            l'inici del pagament({{ $payment->created_at->format('d/m/Y h:i:s') }}) i la seva
            realització({{ $payment->updated_at->format('d/m/Y h:i:s') }}).
        @endif
    </p>
    <p>
        Entenem com de decebedor pot ser això, però una de les butaques que ha sol·licitat ja no està disponible. Per aquest
        motiu no és possible processar la cistella. Aviat revisarem la seva comanda i li retornarem l'import pagat.
    </p>
    <p>
        Gràcies per la vostra comprensió i paciència.
    </p>
    <p>
        Atentament,<br>
        {{ $brand->name }}
    </p>
@endsection
