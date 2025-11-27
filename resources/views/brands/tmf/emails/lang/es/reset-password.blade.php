@extends('brands.tmf.emails.layout')
@section('content')

<p>
    Estimado {{ $client->name or '{name}' }},<br>
    <br>
    Te enviamos este email puesto que nos has solicitado un cambio de password.<br/>
    <br>
    Si no lo has solicitado puedes ignorar este email. De lo contrario
    clica en lo siguiente <a href="http://torellomountainfilm.cat/recuperar-contrasenya/{{ $client->reset_token }}">link para terminar de realizar el cambio</a>.
</p>
<p>
    En caso de no poder acceder al link, puede copiarlo y pegarlo en su navegador: <b>http://torellomountainfilm.cat/recuperar-contrasenya/{{ $client->reset_token }}</b>
</p>
<p>
    Saludos,
</p>

<p>
    Instagram | <a href="https://www.instagram.com/torellomountainfilm/" target="_blank">torellomountainfilm</a> <br>
    Twiter | <a href="https://twitter.com/torellomountain" target="_blank">torellomountain</a>
</p>
<p style="font-weight: bold;">
    Festival de Cinema de Muntanya de Torelló. <br>
    Anselm Clavé, 5 3r 2a <br>
    08570 Torelló<br> <br>
    T 938 50 43 21
</p>
@endsection
