@extends('brands.riudomenc.emails.layout')
@section('content')
<p>
    Benvolgut {{ $client->name ?? '{name}' }},<br>
    <br>
    T'enviem aquest email ja que ens has sol·licitat un canvi de password.<br/>
    <br>
    Si no l'has sol·licitat tu pots ignorar aquest email. En cas contrari
    clica en el següent <a href="http://entrades.casalriudomenc.cat/recuperar-contrasenya/{{ $client->reset_token }}">link per acabar de realitzar el canvi</a>.
</p>
<p>
    En cas de no poder accedir al link, podeu copiar-lo i enganxar-lo al vostre navegador : <b>http://entrades.casalriudomenc.cat/recuperar-contrasenya/{{ $client->reset_token }}</b>
</p>
<p>
    Salutacions,
</p>
<p style="font-weight: bold;">
    Casal Riudomenc - Teatre Auditori<br/>
    C/ Sant Jaume, 2<br/>
    43330 Riudoms<br/>
    T. 977 76 82 58
</p>
@endsection
