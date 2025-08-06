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
            .dotted-border {
                border-top: 1px dashed black;
                padding-top: 25px;
                margin-top: 20px;
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
                            @if($inscription->cart->brand->getAttributes()['logo'])
                                <img class="image h1" style="width: 100%;" alt="{{ $inscription->cart->brand->name }}" src="{{ $inscription->cart->brand->logo }}"/>
                            @endif
                        </div>
                        <div class="col-xs-8 event">
                            <p><strong>{{ $inscription->slot->name ?? '' }}</strong></p>
                            <h1>{{ $inscription->session->event->name }}</h1>
                            <div class="row">
                                <div class="col-xs-12">
                                    <p class="confirmation-code text-right">{{ $inscription->cart->confirmation_code }}</p>
                                    <p>{{ $inscription->session->starts_on->formatLocalized('%d/%m/%Y %H:%M') }}</p>
                                    <p>{{ sprintf("%s - %s €", $inscription->getRateName(), number_format($inscription->price_sold, 2)) }}</p>
                                    @if(isset($inscription->group_pack->pack->name))
                                    <p><i>{{ $inscription->group_pack->pack->name }}</i></p>
                                    @endif

                                    @php($metadata = json_decode($inscription->metadata))
                                    @if(!empty($metadata))
                                        @foreach ($metadata as $property => $value )
                                            <span class="text-muted">
                                            @if($loop->index != 0)
                                             |
                                            @endif
                                             {{ $value }}
                                            </span>
                                        @endforeach
                                    @endif

                                </div>
                            </div>

                            <div class="bar-code" style="background-color:white;">
                                <img width="50%" style="border: 1px solid white;" src="{{ sprintf('data:image/png;base64,%s',  (DNS1D::getBarcodePNG(strtoupper($inscription->barcode), "C39", 3, 90))) }}" class="img-fluid img-responsive" />
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-xs-8 conditions small">
                            {{ $inscription->session->space->name }} | {{ $inscription->session->space->location->name }} <br>
                            {{ $inscription->session->space->location->address }} <br>
                            {{ $inscription->session->space->location->postal_code }} - {{ $inscription->session->space->location->town->name }}
                        </div>
                        <div class="col-xs-4 conditions text-right">
                        </div>
                    </div>
                    <div class="row">
                        <div class="dotted-border">
                        <ul>
                            <li>Espectacle no recomanat a infants menors de 3 anys</li>
                            <li>Tots els infants han d’anar obligatòriament acompanyats per una persona major d’edat.</li>
                            <li>Les sessions començaran puntualment segons l'horari previst. Per agilitzar l’entrada dels grups, es recomana fer acte de presència 5 minuts abans de l’inici de la sessió. Un cop començada la sessió no es podrà accedir al recinte de la fàbrica.</li>
                            <li>A l’entrada de cada sessió es comprovaran les entrades, els noms dels infants i el no i DNI de les persones adultes que els acompanyin.</li>
                            <li>L’organització es reserva el dret d’admissió</li>
                            <li>Un cop escollit dia i hora no es podran fer canvis ni es retornaran ells diners en cas de no poder assistir</li>
                        </ul>
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
