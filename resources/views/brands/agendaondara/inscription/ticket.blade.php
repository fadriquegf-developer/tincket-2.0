<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="manifest" href="site.webmanifest">
    <link rel="apple-touch-icon" href="icon.png">
    <!-- Place favicon.ico in the root directory -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">

    <style>
        html,
        body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            left: 0;
            top: 0;
            color: black !important;
        }
        .container{
            height: 15cm;
            width: 8cm;
            border: 1px solid black;
        }

        h1 {
            font-size: 2em;
            max-height: 50px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .image {
            height: 32px;
        }

        .event p {
            margin-bottom: 0;
            line-height: 1.5em;
            line-height: 1em;
            font-size: 16px;
        }

        p.extra-text {
            font-size: .8em;
            margin-bottom: 1px;
        }

        .conditions {
            bottom: 0;
        }

        .conditions p {
            margin-top: 0;
            margin-bottom: 0;
            line-height: 1em;
            font-size: 10px;
        }

        .confirmation-code {
            color: red;
            font-weight: 300;
            float: right;
        }

        .bar-code {
            font-size: 0.5em;
            display: block;
            margin: 10px 0;
        }

        .bar-code span {
            display: block;
            right: 0;
        }

        .text-extra-small {
            font-size: .5em;
        }

        .text-small {
            font-size: .8em;
        }
        .img-qr{
            width: 70%;
            margin-bottom: 8px;
        }
    </style>

</head>

<body>
    <div class="container">
        <div class="row">
            {{-- <div class="col-xs-12 text-center">
                <img class="image" alt="Logo Atenea 360" src="/storage/uploads/agenda-ondara/logo-atenea-360.png" />
            </div> --}}
            <div class="col-xs-12 text-center" style="margin-bottom: 16px;">
                <h4 style="margin-bottom: 0px"><b>{{ $inscription->session->event->name }}</b></h4>
                <h6 style="margin-top: 4px">{{$inscription->session->starts_on->formatLocalized('%d de %b de %Y')}}</h6>
            </div>
            <div class="col-xs-12 text-center" style="background-color:white;">
                <img class="img-qr" style="border: 1px solid white;" src="data:image/png;base64,{!! DNS2D::getBarcodePNG($inscription->barcode, "QRCODE", 5,5) !!}" />
                <h4 style="margin-top:4px !important;margin-bottom: 14px !important;">{{ strtoupper($inscription->barcode) }}</h4>
            </div>
            <div class="col-xs-12 text-center">
                <h5 style="margin-top: 0px;margin-bottom: 16px !important;"><b>{{ strtoupper($inscription->getRateName()) }}</b></h5>
            </div>
            <div class="col-xs-12">
                <div>
                    <b>Precio:</b> {{number_format($inscription->price_sold, 2)}} €
                </div>
                <div>
                    <b>Butaca:</b> @if(isset($inscription->slot->name)) {{ $inscription->slot->name }} @endif
                </div>
                <div>
                    <b>Fecha de generación: </b> {{$inscription->created_at->formatLocalized('%d/%m/%Y %H:%M')}}
                </div>
                <div>
                    <b>Fecha de inicio: </b> {{$inscription->session->starts_on->formatLocalized('%d de %b de %Y')}} 
                </div>
                <div>
                    <b>Hora de inicio: </b> {{$inscription->session->starts_on->formatLocalized('%H:%M')}} 
                </div>
                <div>
                    <b>Ubicación:</b>
                    <span>
                        {{ $inscription->session->space->name }} 
                              @if($inscription->session->space->name != $inscription->session->space->location->name)
                              | {{ $inscription->session->space->location->name }}
                              @endif
                             - 
                            {{ $inscription->session->space->location->address }} 
                            -
                            {{ $inscription->session->space->location->postal_code }} {{ $inscription->session->space->location->town->name }}
                      </span>
                </div>
            </div>
            <div class="col-xs-12 text-center">
                <b>AJUNTAMENT D'ONDARA - P0309500G</b>
            </div>
        </div>

    </div>


</body>

</html>