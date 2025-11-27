@extends('brands.tmf.emails.layout')
@section('content')

<p>
    Dear {{ $client->name or '{name}' }},<br>
    <br>
    We are sending you this email because you have requested a password change.<br/>
    <br>
    If you have not requested it, you can ignore this email. Otherwise
    click on the following <a href="http://torellomountainfilm.cat/recuperar-contrasenya/{{ $client->reset_token }}">link to finish making the change</a>.
</p>
<p>
    If you cannot access the link, you can copy and paste it into your browser: <b>http://torellomountainfilm.cat/recuperar-contrasenya/{{ $client->reset_token }}</b>
</p>
<p>
    Greeting,
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
