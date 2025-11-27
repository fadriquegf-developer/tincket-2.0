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
            body {
            }
            h1 {
                font-size: 2em;
                max-height: 50px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                text-transform: uppercase;
            }
            img.logo {
                width: 50%;
            }
            .event p {
                margin-bottom: 0;
                line-height: 1.5em;
                line-height: 1em;
                font-size: 16px;
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
        </style>

    </head>
    <body>
        <!--[if lte IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
        <![endif]-->
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <div class="row">
                        <div class="col-xs-4">                            
                            <img class="logo" src="data:image/png;base64,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"/>
                        </div>
                        <div class="col-xs-8 event">
                            <p><strong>{{ $inscription->slot->name ?? '' }}</strong></p>
                            <h1>{{ $inscription->session->event->name }}</h1>
                            <div class="row">
                                <div class="col-xs-12">
                                    <p class="confirmation-code text-right">{{ $inscription->cart->confirmation_code }}</p>
                                    <p>{{ $inscription->session->starts_on->translatedFormat('d/m/Y H:i') }}</p>
                                    <p>{{ sprintf("%s - %s €", $inscription->getRateName(), number_format($inscription->price_sold, 2)) }}</p>
                                    @if(isset($inscription->group_pack->pack->name))
                                    <p><i>{{ $inscription->group_pack->pack->name }}</i></p>
                                    @endif
                                </div>
                            </div>

                            <div class="bar-code" style="background-color:white;">
                                <img width="50%" style="border: 1px solid white;" src="{{ sprintf('data:image/png;base64,%s',  (DNS1D::getBarcodePNG(strtoupper($inscription->barcode), "C39", 3, 90))) }}" class="img-fluid img-responsive" />
                                <span>{{ strtoupper($inscription->barcode) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-xs-8 conditions">
                            <p>No es permetrà l'entrada iniciat l'espectacle</p>
                            <p>En cas que s'anul·li l'acte es tornarà l'import de l'entrada</p>
                            <p>L'organització es reserva el dret d'alterar o modificar el programa de l'acte</p>
                        </div>
                        <div class="col-xs-4 conditions text-right">
                            <p class="mt-2"><strong>Teatre Eòlia</strong></p>
                            <p>c/Bailèn, 23, Barcelona</p>
        		            <p>T. 933 19 23 97</p>
                       </div>
                    </div>
                </div>
            </div>
        </div>
        @if($inscription->getBanner() != NULL)
            @include('core.inscription.ticket.banner', ['banner' => $inscription->getBanner()])
        @endif
    </div>
</body>
</html>
