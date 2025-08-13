@extends('brands.cirvianum.emails.layout')
@section('content')
<p>
    Benvolgut/da {{ $client->name ?? '{name}' }},<br>
    <br>
    T'enviem aquest email ja que ens has sol·licitat un canvi de password.<br/>
    <br>
    Si no l'has sol·licitat tu pots ignorar aquest email. En cas contrari
    clica en el següent <a href="https://teatrecirvianum.cat/recuperar-contrasenya/{{ $client->reset_token }}">link per acabar de realitzar el canvi</a>.
</p>
<p>
    En cas de no poder accedir al link, podeu copiar-lo i enganxar-lo al vostre navegador : <b>https://teatrecirvianum.cat/recuperar-contrasenya/{{ $client->reset_token }}</b>
</p>
<p>
    Salutacions,
</p>
<p>
    Facebook | <a href="https://www.facebook.com/Teatrecirvianum/" target="_blank">Teatre Cirviànum</a> <br>
    Twiter | <a href="https://twitter.com/teatrecirvianum" target="_blank">Teatre Cirviànum</a>
</p>
<p style="font-weight: bold;">
    Teatre Cirviànum de Torelló <br>
    Plaça Nova, 10<br>
    08570 Torelló<br>
    T 938 50 40 95
</p>
@endsection
