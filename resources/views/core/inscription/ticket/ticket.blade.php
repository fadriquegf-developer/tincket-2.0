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
                            <img class="image h1" style="width: 100%;" alt="{{ $inscription->cart->brand->name }}"
                                src="{{ $inscription->getLogo() }}" />
                        @endif
                    </div>
                    <div class="col-xs-8 event">

                        <p><strong>{{ $inscription->slot->name ?? '' }}</strong></p>
                        <h2>{{ $inscription->session->event->name }}</h2>
                        @if($inscription->session->event->custom_text)
                            <div class="extra-text">{!! $inscription->session->event->custom_text !!}</div>
                        @endif
                        <div class="row">
                            <div class="col-xs-12">
                                <p class="confirmation-code text-right">{{ $inscription->cart->confirmation_code }}</p>
                                <p>{{ $inscription->session->starts_on->translatedFormat('d/m/Y H:i') }}</p>
                                <p>{{ sprintf("%s - %s €", $inscription->getRateName(), number_format($inscription->price_sold, 2)) }}
                                </p>
                                @if(isset($inscription->group_pack->pack->name))
                                    <p><i>{{ $inscription->group_pack->pack->name }}</i></p>
                                @endif

                                @php($metadata = json_decode($inscription->metadata))
                                @if(!empty($metadata))
                                    @foreach ($metadata as $property => $value)
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
                            <img width="50%" style="border: 1px solid white;"
                                src="{{ sprintf('data:image/png;base64,%s', (DNS1D::getBarcodePNG(strtoupper($inscription->barcode), "C39", 3, 90))) }}"
                                class="img-fluid img-responsive" />
                            <span>{{ strtoupper($inscription->barcode) }}</span>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-xs-8 conditions">
                    </div>
                    @if(!$inscription->session->space->hide)
                        <div class="col-xs-4 conditions text-right">
                            <p>
                                {{ $inscription->session->space->name }}
                                @if($inscription->session->space->name != $inscription->session->space->location->name)
                                    | {{ $inscription->session->space->location->name }} <br>
                                @endif
                            </p>
                            <p>
                                {{ $inscription->session->space->location->address }} <br>
                            </p>
                            <p>
                                {{ $inscription->session->space->location->postal_code }} -
                                {{ $inscription->session->space->location->town->name }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-10 text-extra-small" style="position: absolute; bottom: 10px; left: 0px;">
        {{ __('tincket/tickets.footer-text') }}
    </div>
</body>

</html>