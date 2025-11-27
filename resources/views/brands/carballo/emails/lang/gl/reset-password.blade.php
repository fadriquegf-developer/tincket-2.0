@extends('core.emails.layout')
@php($brand = $client->brand)

@section('content')
<p>
    Querido/a {{ $client->name ?? '{name}' }},<br>
    <br>
    Enviámosche este correo electrónico xa que solicitaches un cambio de contrasinal.<br/>
    <br>
    Se non o solicitaches, podes ignorar este correo electrónico. En caso contrario, prema na seguinte ligazón para 
    rematar de facer o cambio. <a href="{{ config('clients.frontend.url') }}/recuperar-contrasena/{{ $client->reset_token }}">Link </a>
</p>
<p>
    Saúdos
</p>
@endsection
