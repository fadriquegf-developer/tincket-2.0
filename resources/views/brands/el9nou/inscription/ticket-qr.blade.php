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
 /*            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            left: 0;
            top: 0; */
        }

        .border-left-dashed {
            border-left-style: dashed !important;
        }

        .border-bottom-dashed {
            border-bottom-style: dashed !important;
        }

        .logo-container{
            position: relative;
            text-align: center;
        }

        .logo {
            position: absolute;
            width: 180px;
            left: -82px;
            top: 80px;
            -webkit-transform: rotate(-90deg);
            -moz-transform: rotate(-90deg);
            -ms-transform: rotate(-90deg);
            -o-transform: rotate(-90deg);
            transform: rotate(-90deg);
        }

        .border {
            border: 1px solid black;
        }

        .border-top{
            border-top: 1px solid black;
        }

        .border-bottom{
            border-bottom: 1px solid black;
        }

        .text-white{
            color:white;
        }

        .h-100{
            height: 100%;
        }

        .py-2{
            padding: 1rem 0;
        }

        .pb-2{
            padding-bottom: 1rem;
        }

        .entrada-block {
            background-color: #fd0100;
            position: relative;
        }

        .entrada-block p {
            position: absolute;
            font-family: monospace;
            font-size: 2rem;
            left: -10px;
            bottom: 50px;
            -webkit-transform: rotate(-90deg);
            -moz-transform: rotate(-90deg);
            -ms-transform: rotate(-90deg);
            -o-transform: rotate(-90deg);
            transform: rotate(-90deg);
        }

        .bar-code {
            font-size: 0.6em;
            display: block;
            margin: 10px 0;
        }

        .bar-code span {
            display: block;
            right: 0;
        }

    </style>

</head>

<body>
    <div class="container">
        <div class="row" style="height: 270px;">
            <div class="col-xs-1 border entrada-block h-100">
                <p class="text-white">ENTRADA</p>
            </div>
            <div class="col-xs-10 border-top border-bottom contenido h-100">
                <div class="row">
                    <div class="col-xs-2 mt-3 bar-code" style="background-color:white;">
                        <img style="width:100%" style="border: 1px solid white;"
                            src="data:image/png;base64,{!! DNS2D::getBarcodePNG($inscription->barcode, 'QRCODE', 5, 5) !!}" />
                        <span>{{ strtoupper($inscription->barcode) }}</span>
                    </div>
                    <div class="col-xs-7">
                        <h3>{{ $inscription->session->event->name }}</h3>
                    </div>
                    <div class="col-xs-3 bar-code" style="background-color:white;">
                        <img width="100%" style="border: 1px solid white;"
                            src="{{ sprintf('data:image/png;base64,%s', DNS1D::getBarcodePNG(strtoupper($inscription->barcode), 'C39', 3, 90)) }}"
                            class="img-fluid img-responsive" />
                    </div>
                </div>
                <div class="row border-bottom-dashed border-bottom pb-2">
                    <div class="col-xs-2">
                        Día: <strong>{{ $inscription->session->starts_on->translatedFormat('d/m/Y') }}</strong>
                    </div>
                    <div class="col-xs-2">
                        Hora: <strong>{{ $inscription->session->starts_on->translatedFormat('H:i') }}</strong>
                    </div>
                    <div class="col-xs-4">
                        Preu: <strong>{{ sprintf('%s - %s €', $inscription->getRateName(), number_format($inscription->price_sold, 2)) }}</strong>
                    </div>
                    <div class="col-xs-4">
                        @if(!empty($inscription->slot->name))
                            Localitat: <strong >{{ $inscription->slot->name ?? '' }} </strong>
                        @endif
                    </div>
                </div>
                <div class="row border-bottom-dashed border-bottom py-2">
                    <div class="col-xs-6">
                        Nom Titular: <strong>{{ $inscription->cart->client->name }}
                            {{ $inscription->cart->client->surname }}</strong>
                    </div>
                    <div class="col-xs-6">
                        Organitza: <strong>{{ $inscription->session->brand->name }}</strong>
                    </div>
                </div>
                <div class="row py-2">
                    <div class="col-xs-12">
                        <p style="font-size: 10px;"><b>IMPORTANT:</b> Per a poder accedir a aquest espectable has de presentar aquesta entrada a
                            partir de l'hora d'obertura de portes el mateix dia de l'espectable. Recorda que el sistema
                            d'entrades no admetrà més còpia d'aquest
                            document, solament la primera será acceptada. Per a més seguretat l'organització es reserva el
                            dret a sol·licitar el DNI per accedir al recinte. L'organització es reserva el dret d'alterar o
                            modificar el programa de l'acter. No s'admeten devolucions ni canvis en les entrades.</p>
                    </div>
                    
                </div>
            </div>
            <div class="col-xs-1 border border-left-dashed h-100">
                @if ($inscription->cart->brand->getAttributes()['logo'])
                    <p class="logo-container">
                        <img class="logo" alt="{{ $inscription->cart->brand->name }}"
                            src="{{ $inscription->getLogo() }}" />
                    </p>
                @endif
            </div>
        </div>
    </div>
    @if($inscription->getBanner() != NULL)
        @include('core.inscription.ticket.banner', ['banner' => $inscription->getBanner()])
    @endif

</body>

</html>
