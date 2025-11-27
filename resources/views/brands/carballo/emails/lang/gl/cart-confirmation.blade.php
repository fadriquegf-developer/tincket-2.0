@extends('core.emails.layout')
@php($brand = $cart->brand)

@section('content')
<p>
    Querido/a {{ $cart->client->name ?? '{name}' }},<br>
    <br>
    Neste correo electrónico atoparás as entradas adxuntas. Lembra imprimilas ou levalas descargadas no teu móbil. 
</p>
<p>
    O teu código de compra é: <span style="font-weight: bold;">{{ $cart->confirmation_code ?? '{code}' }}</span>
</p>
<p>
    Esperamos que gocedes do espectáculo!
</p>
<p>
    Saúdos
</p>
@endsection
