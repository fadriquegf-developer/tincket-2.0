@extends('brands.tmf.emails.layout')
@section('content')
    <p>
        Hola {{ $cart->client->name or '{name}' }},
    </p>
    <p>
        En este correo encontrarás adjuntas las entradas para asistir al Festival. Recuerda imprimirlas
        o llevarlas descargadas en el móvil. Podrás acceder rápidamente a la sala desde 15 minutos
        antes del inicio de la sesión.<br>
        Si tienes cualquier duda puedes ponerte en contacto con nosotros en esta dirección de correo:
        <a href="mailto:info@torellomountainfilm.cat">info@torellomountainfilm.cat</a>.
    </p>
    <p>
        Tu código de compra es: <span style="font-weight: bold;">{{ $cart->confirmation_code or '{code}' }}</span>
    </p>
    <p>
        ¿Tienes pensado comer algo antes de entrar al cine? ¿Vas con el tiempo justo? ¿O bien
        quieres comer o beber con tranquilidad mientras esperas que comience la proyección?
    </p>
    <p>
        En el <b>Camp Base</b> encontrarás servicio de barra con los amigos de Animal Bar. Nos traerán
        diferentes opciones de pinchos, tanto calientes como fríos, y algunos bocadillos de autor, con opción
        vegetariana. No faltará buena música y buen ambiente.
    </p>
    <p>
        Del 17 al 21 abierto cada día a partir de las 18:00 hasta las 22:30
    </p>
@endsection
