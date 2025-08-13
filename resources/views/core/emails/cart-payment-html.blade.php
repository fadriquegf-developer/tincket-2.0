@extends('core.emails.layout')
@php($brand = $cart->brand)

@section('content')
<p>
    Benvolgut/da {{ $cart->client->name ?? '{name}' }},<br>
    <br>
    Degut al problemes que vares tenir a l'hora del pagament de les entrades per el carrito {{ $cart->confirmation_code }} al {{$brand->name}}, t'enviam un enllaç per realitzar el pagament i poder mantenir les butaques reservades.
</p>
<p>
    La direcció de pagament es la seguent: <a style="font-weight: bold;" href="{{config('clients.frontend.url')}}/reserva/pagament/carrito/{{ $cart->token }}">Enllaç del pagament</a>
</p>
<p>
    En cas que no us funcioni l'enllaç anterior, copieu i enganxeu la següent adreça en el vostre navegador<br>
    {{config('clients.frontend.url')}}/reserva/pagament/carrito/{{ $cart->token }}
</p>
<p>
    Disculpin les molesties,
</p>
<p>
    Salutacions
</p>
@endsection
