@extends('brands.tmf.emails.layout')
@section('content')
<p>
    Hola {{ $cart->client->name or '{name}' }},
</p>
<p>
    En aquest e-mail trobaràs adjuntes les entrades per assistir al Festival. Recorda imprimir-les o portar-les descarregades al mòbil. Podràs accedir ràpidament a la sala des de 15’ abans de l’inici de la sessió.<br>
    Si tens qualsevol dubte pots posar-te en contacte amb nosaltres en aquesta adreça de correu: <a href="mailto:info@torellomountainfilm.cat">info@torellomountainfilm.cat</a>.
</p>
<p>
    El teu codi de compra és: <span style="font-weight: bold;">{{ $cart->confirmation_code or '{code}' }}</span>
</p>
<p>
    Tens previst menjar alguna cosa abans d’entrar al cinema? Vas amb el temps just? O bé vols menjar o beure amb tranquil·litat per esperar que comenci la projecció?
</p>
<p>
    Al <b>Camp Base</b> trobaràs servei de barra amb els amics de l'Animal Bar. Ens portaran diferents opcions de pinxos, tant calents com freds, i alguns entrepans d'autor, amb opció vegetariana. No faltarà bona música i bon ambient.
</p>
<p>
    Del 18 al 22 obert cada dia a partir de les 18:00 fins les 22:30
</p>
@endsection
