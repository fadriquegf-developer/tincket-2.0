@extends('core.emails.layout')
@php($brand = $client->brand)

@section('content')
<p>
    Estimado/a {{ $client->name ?? '{name}' }},<br>
    <br>
    Le enviamos este correo electrónico porque ha solicitado un cambio de contraseña.<br/>
    <br>
    Si no lo ha solicitado, puede ignorar este correo electrónico. De lo contrario
    haga clic en Siguiente <a href="{{ config('clients.frontend.url') }}/recuperar-contrasena/{{ $client->reset_token }}">link </a>para rematar de facer o cambio.
</p>
<p>
    En caso de no poder acceder al link, puede copiarlo y pegarlo en su navegador: <b>{{ config('clients.frontend.url') }}/recuperar-contrasena/{{ $client->reset_token }}</b>
</p>
<p>
    Saludos
</p>
@endsection
