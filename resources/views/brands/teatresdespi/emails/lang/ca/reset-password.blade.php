@extends('brands.teatresdespi.emails.layout')
@section('content')
<p>
    Benvolgut {{ $client->name or '{name}' }},<br>
    <br>
    T'enviem aquest email ja que ens has sol·licitat un canvi de password.<br/>
    <br>
    Si no l'has sol·licitat tu pots ignorar aquest email. En cas contrari
    clica en el següent <a href="http://entrades.teatresdespi.cat/recuperar-contrasenya/{{ $client->reset_token }}">link per acabar de realitzar el canvi</a>.
</p>
<p>
    En cas de no poder accedir al link, podeu copiar-lo i enganxar-lo al vostre navegador : <b>http://entrades.teatresdespi.cat/recuperar-contrasenya/{{ $client->reset_token }}</b>
</p>
<p>
    Salutacions,
</p>
@endsection
