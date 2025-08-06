@extends('core.emails.layout')
@php($brand = $cart->brand)

@section('content')
<p>
    Estimado/a {{ $cart->client->name or '{name}' }},<br>
    <br>
    En este correo electrónico encontrarás las entradas adjuntas. Recuerda imprimirlas o descargarlas en tu móvil.
</p>
<p>
    Tu código de compra es: <span style="font-weight: bold;">{{ $cart->confirmation_code or '{code}' }}</span>
</p>
<p>
    ¡Esperamos que disfrutes del espectáculo!
</p>
<p>
    Saludos
</p>
@endsection
