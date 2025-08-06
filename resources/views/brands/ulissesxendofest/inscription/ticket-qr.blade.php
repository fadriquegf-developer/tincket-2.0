<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Premia de Dalt</title>
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
            font-size: 1.375rem;
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
            font-weight: 200;
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
            width: 2%;
        }

        .col-1 {
            width: 8.33%;
        }

        .col-2 {
            width: 16.66%;
        }

        .col-25 {
            width: 20%;
        }

        .col-3 {
            width: 25%;
        }

        .col-35 {
            width: 29%;
        }

        .col-4 {
            width: 35.33%;
        }

        .col-5 {
            width: 47.66%;
        }

        .col-6 {
            width: 50%;
        }

        .col-7 {
            width: 58.33%;
        }

        .col-75 {
            width: 62%;
        }

        .col-8 {
            width: 66.66%;
        }

        .col-9 {
            width: 75%;
        }

        .col-10 {
            width: 83.33%;
        }

        .col-11 {
            width: 91.66%;
        }

        .col-12 {
            width: 100%;
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
            max-height: 35px;
        }

        .text-extra-small {
            font-size: .5em;
        }

        .text-small {
            font-size: .8em;
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
    </style>

</head>

<body>
    @php
        if ($inscription->group_pack) {
            $bg_color = $inscription->group_pack->pack->bg_color;
        } else {
            $bg_color = $inscription->session->session_bg_color;
        }
        $metadataArray = json_decode($inscription->metadata, true);
    @endphp
    @php
    setlocale(LC_TIME, "ca_ES.utf8");
    @endphp
    <div style="padding-left: 30px; background-color: {{ $bg_color }}">
        <!-- Lineas de colores --->
        <div class="container py">
            @php
                if ($inscription->group_pack) {
                    $bar_color = $inscription->group_pack->pack->color;
                } else {
                    $bar_color = $inscription->session->session_color;
                }
            @endphp
            <div class="row py-small">
                <div class="col-11" style="padding-right: 6px;">
                    <div class="bg-line" style=" background-color: {{ $bar_color }}"></div>
                </div>
                <div class="col-1" style="padding-left: 6px;">
                    <div class="bg-line" style=" background-color: {{ $bar_color }}"></div>
                </div>
            </div>
            <div class="row py-small">
                <div class="col-11" style="padding-right: 6px;">
                    <div class="bg-line" style=" background-color: {{ $bar_color }}"></div>
                </div>
                <div class="col-1" style="padding-left: 6px;">
                    <div class="bg-line" style=" background-color: {{ $bar_color }}"></div>
                </div>
            </div>
            <div class="row py-small">
                <div class="col-11" style="padding-right: 6px;">
                    <div class="bg-line" style=" background-color: {{ $bar_color }}"></div>
                </div>
                <div class="col-1" style="padding-left: 6px;">
                    <div class="bg-line" style=" background-color: {{ $bar_color }}"></div>
                </div>
            </div>
        </div>
        <!-- Fin de lineas de colores -->

        <!-- Header Personalitzat -->
        <div class="container" style="padding-bottom: 14px;">
            {{-- Pack > Sessio >Event --}}
            @if (isset($inscription->group_pack) && $inscription->group_pack->pack->custom_logo)
                <div class="row">
                    <div class="col-12">
                        <img src="/storage/uploads/{{ $inscription->group_pack->pack->custom_logo }}" width="100%" />
                    </div>
                </div>
            @elseif ($inscription->session->custom_logo)
                <div class="row">
                    <div class="col-12">
                        <img src="/storage/uploads/{{ $inscription->session->custom_logo }}" width="100%" />
                    </div>
                </div>
            @elseif($inscription->session->event->custom_logo)
                <div class="row">
                    <div class="col-12">
                        <img src="/storage/uploads/{{ $inscription->session->event->custom_logo }}" width="100%" />
                    </div>
                </div>
            @else
                <div class="row" style="padding: 10px 0px;">
                    <div class="col-12">
                        <h2 style="margin: 0px;text-transform: uppercase;">
                            <b>{{ $inscription->session->event->name }}</b>
                        </h2>
                        <h4 style="font-weight:400; margin-top: 4px; margin-bottom: 0px;">
                            {{ $inscription->session->starts_on->formatLocalized('%d %b de %Y') }}
                            -
                            {{ $inscription->session->space->name }}
                        </h4>
                    </div>
                </div>
            @endif
        </div>
        <!-- Fin Header Personalitzat -->

        <div class="container">
            <div class="row">
                <!-- Contenidor esquerra Info -->
                <div class="col-9" style="padding-right: 10px;">
                    <div
                        style="border-top: 2px solid black;border-bottom: 2px solid black; padding: 10px 0px; text-transform: uppercase; font-size: 22px;">
                        @if ($inscription->group_pack)
                            <b>{{ $inscription->group_pack->pack->name }}</b>
                        @else
                            <b>ENTRADA INDIVIDUAL - {{ $inscription->getRateName() }}</b>
                        @endif
                    </div>
                    <div style="padding: 10px 0px; text-transform: uppercase; font-size: 22px;">
                        {{ $inscription->session->name }} HORA:
                        {{ $inscription->session->starts_on->formatLocalized('%H:%M') }}
                    </div>
                    <div class="row" style="padding: 0px; margin: 0px;">
                        <div class="col-75">
                            <div
                                style="border-top: 2px solid black; border-bottom: 2px solid black; padding: 10px 0px; font-size: 18px;">
                                @if (isset($inscription->cart->client->name) && isset($inscription->cart->client->surname))
                                    Nom: {{ $metadataArray['nom-i-cognoms'] }}
                                @endif
                            </div>
                        </div>
                        <div class="col-05">
                            <p></p>
                        </div>
                        <div class="col-4">
                            <div
                                style="border-top: 2px solid black; border-bottom: 2px solid black; padding: 10px 0px; font-size: 18px;">
                                Municipi: {{ $metadataArray['municipi'] }}
                            </div>
                        </div>
                    </div>
                    <div class="row" style="padding: 0px; margin: 0px;">
                        <div class="col-75" style="padding: 10px 0px; font-size: 18px;">
                            Ref: {{ $inscription->cart->confirmation_code }}
                        </div>
                        <div class="col-05">
                            <p></p>
                        </div>
                        <div class="col-4">
                            <div style="padding: 10px 0px; font-size: 18px;">
                                Preu: {{ sprintf("%s €", number_format($inscription->price_sold, 2)) }}
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Fin Contenidor esquerra Info -->

                <!-- Contenidor dreta QR -->
                <div class="col-3">
                    <div class="row py" style="margin-left: 0px; padding-top: 0px;">
                        <div class="col-12" style="padding-left: 40px; background-color:white;">
                            <img class="img-qr" style="border: 1px solid white;"
                                src="data:image/png;base64,{!! DNS2D::getBarcodePNG($inscription->barcode, 'QRCODE', 5, 5) !!}" />
                        </div>
                        <div class="col-12" style="padding-top: 22px; padding-left: 40px;">
                            <p class="m-0 small" style="padding-top: 4px; text-align: center;">
                                {{ strtoupper($inscription->barcode) }}</p>
                        </div>
                    </div>
                </div>
                <!-- Fin Contenidor dreta QR -->

            </div>
            <div class="row" style="padding: 16px 0px;">
                <div class="col-15"></div>
                <div class="col-3 text-center">
                    <img src="https://ulissesxendofest.yesweticket.com/storage/uploads/ulissesxendofest/endofest.jpeg"
                        style="height: 85px" alt="Sponsors">
                </div>
                <div class="col-4 text-center" style="padding-top: 12px;">
                    <img src="https://ulissesxendofest.yesweticket.com/storage/uploads/ulissesxendofest/premia.jpeg"
                        style="height: 60px;" alt="Sponsors">
                </div>
                <div class="col-4 text-center" style="padding-top: 9px;">
                    <img src="https://ulissesxendofest.yesweticket.com/storage/uploads/ulissesxendofest/clinic-barcelona.jpeg"
                        style="height: 70px;" alt="Sponsors">
                </div>
            </div>
            <!-- Banners -->
            <div class="row">
                <div class="col-12 py">
                    @if ($inscription->getBanner() != null)
                        <img src="{{ $inscription->getBanner() }}" style="width: 100%;" alt="Banner">
                    @endif
                </div>
            </div>
            <!-- Fin Banners -->
        </div>
    </div>

    <div class="container" style="padding-left: 46px; padding-right: 16px;">
        <!-- Info legal -->
        <div class="row py">
            <div class="col-12">
                <div class="small">
                    @if ($inscription->session->event->custom_text)
                        {!! $inscription->session->event->custom_text !!}
                    @endif
                </div>
            </div>
        </div>
        <!-- Fin Info legal -->

        <!-- Footer Codi Barres + Info -->
        {{-- <div class="row">
            <div class="col-6" style="padding: 0px 10px;">
                <img width="100%"
                    src="{{ sprintf('data:image/png;base64,%s', DNS1D::getBarcodePNG(strtoupper($inscription->barcode), 'C39', 3, 90)) }}"
                    class="img-fluid img-responsive" />
                </p>
            </div>
            <div class="col-5 text-center py border-strip" style="padding: 22px 10px;">
            </div>
        </div> --}}
        <!--  Fin Footer Codi Barres + Info -->
    </div>

</body>

</html>
