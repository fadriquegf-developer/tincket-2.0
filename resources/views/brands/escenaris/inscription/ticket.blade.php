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
                            @if($inscription->cart->brand->getAttributes()['logo'])
                                <img class="image h1" style="width: 100%;" alt="{{ $inscription->cart->brand->name }}" src="{{ $inscription->cart->brand->logo }}"/>
                            @endif
                            <div>
                                <img class="logo" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAu0AAADkCAIAAACjTI3RAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTQ1IDc5LjE2MzQ5OSwgMjAxOC8wOC8xMy0xNjo0MDoyMiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTkgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6OERBNzQzNkYzNDg3MTFFQUI2QTdERTkyNjI0N0FEMkYiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6OERBNzQzNzAzNDg3MTFFQUI2QTdERTkyNjI0N0FEMkYiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo4REE3NDM2RDM0ODcxMUVBQjZBN0RFOTI2MjQ3QUQyRiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo4REE3NDM2RTM0ODcxMUVBQjZBN0RFOTI2MjQ3QUQyRiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pj/K7oEAADklSURBVHja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"/>
                            </div>
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
                                <span>{{ strtoupper($inscription->barcode) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-xs-8 conditions">
                            <p>
                            {{ __('tickets.footer-text') }}
                            </p>
                        </div>
                        <div class="col-xs-4 conditions text-right">
                            <p>
                            {{ $inscription->session->space->name }} | {{ $inscription->session->space->location->name }} <br>
                            </p>
                            <p>
                            {{ $inscription->session->space->location->address }} <br>
                            </p>
                            <p>
                            {{ $inscription->session->space->location->postal_code }} - {{ $inscription->session->space->location->city->name }}
                            </p>
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
