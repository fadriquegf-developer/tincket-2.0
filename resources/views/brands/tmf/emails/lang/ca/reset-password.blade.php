@extends('brands.tmf.emails.layout')
@section('content')

<p>
    Benvolgut {{ $client->name or '{name}' }},<br>
    <br>
    T'enviem aquest email ja que ens has sol·licitat un canvi de password.<br/>
    <br>
    Si no l'has sol·licitat tu pots ignorar aquest email. En cas contrari
    clica en el següent <a href="http://torellomountainfilm.cat/recuperar-contrasenya/{{ $client->reset_token }}">link per acabar de realitzar el canvi</a>.
</p>
<p>
    En cas de no poder accedir al link, podeu copiar-lo i enganxar-lo al vostre navegador : <b>http://torellomountainfilm.cat/recuperar-contrasenya/{{ $client->reset_token }}</b>
</p>
<p>
    Salutacions,
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
