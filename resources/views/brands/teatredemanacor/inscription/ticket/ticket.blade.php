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

            #placeholder {
                position:absolute;
                background: #ebebeb;
                height: 8cm;
                width: 13.4cm;
                overflow: hidden;
            }

            body {
                position: relative;
            }
            h1 {
                font-size: 14pt;
                max-height: 50px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .bar-code {
                font-size: 6pt;
                display: block;
            }
            .bar-code span {
                display: block;
                right: 0;
            }

            .m-0 {
                margin: 0 !important;
            }

            h2 {
                font-size: 18pt;
                font-weight: bold;
            }

            h3 {
                font-size: 14px;
            }


            h4 {
                font-size: 12px;
            }


            .mb-spacer {
                margin-bottom: .5cm;
            }

            .pb-spacer {
                padding-bottom: .5cm;
            }

            .pb-spacer-small {
                padding-bottom: .10cm;
            }

            small {
                font-size: 8pt;
            }

            .slot {
                border-bottom: 2pt solid black;
                padding-bottom: 2pt;
            }

            .address {
                font-size: 8pt;
            }

        </style>

    </head>
    <body>
        {{-- <div id="placeholder"></div> --}}
        <!--[if lte IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
        <![endif]-->

        <div class="container" style="padding-left: .1cm;">
        
            <div class="row mb-spacer">
                <div class="col-xs-12">
                    <h2 class="m-0">{{ $inscription->session->space->location->name }}</h2>
                </div>
            </div>

            <div class="row pb-spacer">
                <div class="col-xs-12">
                    <h1 class="m-0 pb-spacer-small">{{ $inscription->session->event->name }}</h1>
                    <h4 class="m-0">{{ $inscription->session->event->lead }} &nbsp;</h4>
                </div>
            </div>
        
            <div class="row pb-spacer-small">
                <div class="col-xs-12">
                    <h3 class="m-0 pb-spacer-small">{{ $inscription->session->starts_on->formatLocalized('%d/%m/%Y &nbsp; &nbsp; %H:%M h') }}</h3>
                </div>
            </div>

            <div class="row mb-spacer">
                <div class="col-xs-2">
                </div>
                <div class="col-xs-5">
                    {{ sprintf("€ %s | Tarifa: %s", number_format($inscription->price_sold, 2), $inscription->getRateName()) }}
                </div>
                <div class="col-xs-5 text-right">
                    <div>
                    @if(!empty($inscription->slot->name))
                    <strong class="slot">
                        &nbsp; {{ $inscription->slot->name ?? '' }} &nbsp;
                    </strong>
                    @endif
                    </div>
                </div>
            </div>

            <div class="row pb-spacer-small">
                <div class="col-xs-2">
                </div>
                <div class="col-xs-4 small">
                    {{-- <div class="address">
                    {{ $inscription->session->space->name }}
                    <br>
                    {{ $inscription->session->space->location->address }}
                    <br>
                    {{ $inscription->session->space->location->postal_code }} - {{ $inscription->session->space->location->town->name }}
                    </div> --}}
                    {{ $inscription->cart->confirmation_code }}
                </div>
                <div class="col-xs-4 text-right">
                    <div class="row">
                    <div class="bar-code" style="background-color:white;">
                        <img style="border: 1px solid white;" src="{{ sprintf('data:image/png;base64,%s',  (DNS1D::getBarcodePNG($inscription->barcode, "C39", 1, 50))) }}" />
                        <span>{{ $inscription->barcode }}</span>
                    </div>
                    </div>
                </div>
            </div>

           <div class="pb-spacer-small"></div> 
           <div class="pb-spacer-small"></div>
           <div class="pb-spacer-small"></div>
           <div class="pb-spacer-small"></div>

            <div class="row">
                <div class="col-xs-12 text-center">
                    <small>Q0700604B</small>
                </div>
            </div>

        </div>

</body>
</html>