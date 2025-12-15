@extends('core.emails.layout')
@php($brand = $brand ?? $cart->brand)

@section('content')
    <p>
        Benvolgut/da {{ $cart->client->name ?? '' }} {{ $cart->client->surname ?? '' }},
    </p>

    <p>
        @if (str_starts_with($cart->confirmation_code ?? '', 'XXXXXXXXX'))
            Degut als problemes que vares tenir a l'hora del pagament de les entrades, t'enviem un enllaç per realitzar el
            pagament i poder mantenir les butaques reservades.
        @else
            T'enviem un enllaç per completar el pagament de les teves entrades a {{ $brand->name }}.
        @endif
    </p>

    <p>
        <strong>Preu total:</strong> {{ number_format($cart->price_sold, 2, ',', '.') }} €
    </p>

    <p>
        La direcció de pagament és la següent:<br>
        <a style="font-weight: bold; color: #007bff;" href="{{ $paymentUrl }}">{{ $paymentUrl }}</a>
    </p>

    <p>
        En cas que no us funcioni l'enllaç anterior, copieu i enganxeu la següent adreça en el vostre navegador:<br>
        <span style="word-break: break-all;">{{ $paymentUrl }}</span>
    </p>

    <p style="color: #666; font-size: 0.9em;">
        <em>Disposes de 60 dies per completar el pagament abans que caduqui la reserva.</em>
    </p>

    <p>
        Disculpeu les molèsties ocasionades.
    </p>

    <p>
        Salutacions cordials,<br>
        <strong>{{ $brand->name }}</strong>
    </p>
@endsection
