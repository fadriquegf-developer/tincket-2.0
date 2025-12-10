<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Teatres Despi</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="manifest" href="site.webmanifest">
    <link rel="apple-touch-icon" href="icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
    <!-- Place favicon.ico in the root directory -->
    <style>
        /* Framework */

        html,
        body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            left: 0;
            top: 0;
            font-size: 14px;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            line-height: 1.12;
        }

        h1 {
            font-size: 2.5rem;
        }

        h2 {
            font-size: 2rem;
        }

        h3 {
            font-size: 20px;
        }

        h4 {
            font-size: 1.125rem;
        }

        h5 {
            font-size: 1rem;
        }

        h6 {
            font-size: 0.875rem;
        }

        p {
            font-size: 1.125rem;
            font-weight: 400;
        }

        .font-light {
            font-weight: 300;
        }

        .font-regular {
            font-weight: 400;
        }

        .font-heavy {
            font-weight: 700;
        }

        /* POSITIONING */

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }

        .justify {
            text-align: justify;
        }

        /* ==== GRID SYSTEM ==== */

        .container {
            max-width: 1140px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .row {
            position: relative;
            width: 100%;
        }

        .row [class^="col"] {
            float: left;
        }

        .col-05 {
            width: 4.1667%;
        }

        .col-1 {
            width: 8.3333%;
        }

        .col-2 {
            width: 16.6667%;
        }

        .col-25 {
            width: 20.8333%;
        }

        .col-3 {
            width: 25.0000%;
        }

        .col-35 {
            width: 29.1667%;
        }

        .col-4 {
            width: 33.3333%;
        }

        .col-5 {
            width: 41.6667%;
        }

        .col-6 {
            width: 50.0000%;
        }

        .col-7 {
            width: 58.3333%;
        }

        .col-75 {
            width: 62.5000%;
        }

        .col-8 {
            width: 66.6667%;
        }

        .col-9 {
            width: 75.0000%;
        }

        .col-10 {
            width: 83.3333%;
        }

        .col-11 {
            width: 91.6667%;
        }

        .col-12 {
            width: 100.0000%;
        }


        .row::after {
            content: "";
            display: table;
            clear: both;
        }

        .hidden {
            display: none;
        }

        .py {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .py-small {
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .pr {
            padding-right: 10px;
        }

        .pl {
            padding-left: 10px;
        }

        .p-0 {
            padding: 0px;
        }

        .m-0 {
            margin: 0;
        }

        .spacer {
            padding-top: 10px;
        }

        .spacer-big {
            padding-top: 20px;
        }


        /* particular styles */
        .title-overflow {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .border-bottom {
            border-bottom: 1px solid #000;
        }

        .img-fluid {
            max-width: 100%;
            max-height: 80px;
            height: auto;
        }

        .img-custom-logo {
            max-width: 100%;
            max-height: 100px;
            height: auto;
        }

        .text-center {
            text-align: center;
        }

        .img-fluid.logo {
            max-height: 80px;
        }

        .text-extra-small {
            font-size: .5em;
        }

        .text-small,
        .text-small p,
        .text-small li {
            font-size: .9em;
        }

        strong {
            font-weight: 700;
        }

        .container-qr {
            border: 1px solid black;
        }

        .img-qr {
            width: 100%;
        }

        .info-wrapper {
            padding: 16px;
            background-color: #E7EAE8 !important;
        }

        .money-line {
            text-align: end;
            padding-top: 12px;
        }

        .small {
            font-size: 65%;
        }

        .bg-line {
            padding: 8px;
        }

        .border-strip {
            border: 1px dashed black;
        }

        .banner {
            width: 100%;
            margin-bottom: 18px;
            text-align: center;
        }

        .banner img {
            max-height: 380px;
            width: auto !important;
        }

        /* ====== Ticket (container principal) ====== */
        #container-principal .ticket-row {
            margin: 0;
        }

        /* Caja QR (col izquierda) */
        #container-principal .ticket-qr-box {
            width: 94%;
            display: block;
            border: 1px solid black;
            padding: 15px;
        }

        #container-principal .ticket-qr-img {
            width: 100%;
            height: auto;
            display: block;
        }

        #container-principal .ticket-qr-code {
            padding-top: 8px;
            font-size: 12px;
            text-align: center;
        }

        /* Panel info (col derecha) */
        #container-principal .ticket-info {
            position: relative;
            background: #ebebeb;
            padding: 12px;
            min-height: 140px;
        }

        /* título y metadatos (dejamos hueco a la derecha para el contador) */
        #container-principal .ticket-body {
            padding-right: 16%;
        }

        #container-principal .ticket-title {
            margin: 0 0 6px 0;
            font-size: 18px;
            font-weight: 700;
        }

        #container-principal .ticket-meta {
            margin: 0;
            line-height: 1.2;
            font-size: 14px;
            padding-left: 10px;
        }

        #container-principal .ticket-meta b {
            font-weight: 700;
        }

        /* contador arriba-derecha */
        #container-principal .ticket-counter {
            position: absolute;
            top: 8%;
            right: 4%;
            font-size: 22px;
            font-weight: 700;
        }

        /* precio abajo-derecha */
        #container-principal .ticket-pricebox {
            position: absolute;
            right: 8%;
            bottom: 8%;
            text-align: right;
        }

        #container-principal .ticket-price {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
        }

        #container-principal .ticket-price-notes {
            margin-top: 6px;
            font-size: 12px;
            line-height: 1.2;
        }

        .legal p {
            font-size: 15px;
        }

        .custom-text ol,
        .custom-text ul,
        .custom-text p {
            font-size: .85em;
        }

        .custom-text ol,
        .custom-text ul {
            padding-left: 18px;
        }
    </style>

</head>

<body>

    <!-- Header Personalitzat -->
    <div class="container" style="padding-left: 6%; padding-right: 6%;">
        <div class="row">
            <div class="col-12" style="padding-left: 6px; text-align:right;">
                <img class="img-fluid logo" alt="{{ $inscription->cart->brand->name }}"
                    src="{{ $inscription->getLogo() }}" />
            </div>
        </div>
    </div>
    <!-- Fin Header Personalitzat -->

    {{-- Banner --}}
    <div class="banner">
        @if ($inscription->getBanner() != null)
            <img src="{{ $inscription->getBanner() }}" style="width: 100%;" alt="Banner">
        @endif
    </div>


    <div class="container" id="container-principal" style="padding-left: 6%; padding-right: 6%;">
        <div class="row ticket-row">
            {{-- Columna QR (izquierda) --}}
            <div class="col-3">
                <div class="ticket-qr-box">
                    <img class="ticket-qr-img" src="data:image/png;base64,{!! DNS2D::getBarcodePNG($inscription->barcode, 'QRCODE', 5, 5) !!}" />
                </div>
                <div style="padding-top: 14px; text-align:center;">
                    {{ $inscription->barcode }}
                </div>
            </div>

            {{-- Columna info (derecha) --}}
            <div class="col-9">
                <div class="ticket-info">
                    {{-- contador arriba derecha (pon tu propia lógica si la tienes) --}}
                    @php
                        // IDs ordenados por id de TODAS las inscripciones del carrito (incluye packs)
                        $ids = $inscription->cart->allInscriptions()->orderBy('id')->pluck('id');

                        // posición (1-based) de la inscripción actual
                        $idx = $ids->search($inscription->id);
                        $position = $idx === false ? 1 : $idx + 1;

                        // total de inscripciones del carrito
                        $total = $ids->count();
                    @endphp

                    <div class="ticket-counter">
                        {{ $position }} de {{ $total }}
                    </div>


                    {{-- cuerpo: título + metadatos --}}
                    <div class="ticket-body">
                        <div class="ticket-title">
                            {{ $inscription->session->event->name }}
                        </div>

                        <p class="ticket-meta">
                            @if (!$inscription->session->space->hide)
                                <b>Lloc: </b>
                                <span class="text-small">
                                    {{ $inscription->session->space->name }}
                                    @if ($inscription->session->space->name != $inscription->session->space->location->name)
                                        | {{ $inscription->session->space->location->name }}
                                    @endif
                                    <br>
                                </span>
                                <b>Adreça:</b>
                                <span class="text-small">
                                    {{ $inscription->session->space->location->address }}
                                    {{ $inscription->session->space->location->postal_code }} -
                                    {{ $inscription->session->space->location->city->name }}
                                </span>
                                <br>
                            @endif
                            <b>Data i hora:</b>
                            @php
                                setlocale(LC_TIME, 'ca_ES.utf8');
                            @endphp
                            {{ sprintf('%s, %s h', ucfirst($inscription->session->starts_on->translatedFormat('l')), $inscription->session->starts_on->translatedFormat('d/m H:i')) }}
                            <br>
                            <b>Codi d'entrada:</b> {{ strtoupper($inscription->cart->confirmation_code) }}<br>
                            @if ($inscription->getRateName())
                                <b>Tipus d'entrada:</b> {{ $inscription->getRateName() }}
                            @endif
                            <br>
                            @if (isset($inscription->slot->name))
                                <b>Butaca: </b> {{ $inscription->slot->name }}<br>
                            @else
                                <b>Butaca: </b> Sense Numerar<br>
                            @endif
                            <b>Preu:</b> {{ number_format($inscription->price_sold, 2, ',', '.') }}€
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info legal -->
    <div class="container py" style="padding-left: 8%; padding-right: 8%;">
        <div class="row py" style="margin:0;">
            <div class="col-12 text-small custom-text" style="padding: 1rem 0rem;">
                @if ($inscription->session->event->custom_text)
                    {!! $inscription->session->event->custom_text !!}
                @else
                    {!! trans('tickets.custom-text-teatres-despi') !!}
                @endif
            </div>
        </div>
    </div>
    <!-- Fin Info legal -->

</body>

</html>
