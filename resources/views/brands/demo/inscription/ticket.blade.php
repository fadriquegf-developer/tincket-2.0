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
                width: 100%;
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
                            <img class="logo" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAPgAAABLCAYAAABdj0rQAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTQ1IDc5LjE2MzQ5OSwgMjAxOC8wOC8xMy0xNjo0MDoyMiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NEExMjA5NzBENjRFMTFFOTk3NTNFMDMxRjIyRjg3NTQiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NEExMjA5NkZENjRFMTFFOTk3NTNFMDMxRjIyRjg3NTQiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTggKE1hY2ludG9zaCkiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo1NTE3RTE3RDQwQ0ExMUU4QkU5RUEyQzcxNDMwNzlDMSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo1NTE3RTE3RTQwQ0ExMUU4QkU5RUEyQzcxNDMwNzlDMSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PkWFIbcAABxPSURBVHja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" />
                        </div>
                        <div class="col-xs-8 event">
                            {{ DNS1D::getBarcodeHTML($inscription->barcode, "C39", 1.2, 50)) }}
                            <p><strong>{{ $inscription->slot->name ?? '' }} +++</strong></p>
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
                        <div class="col-xs-7 conditions">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                            <p>Proin tristique vestibulum sodales.</p>
                            <p>Aliquam ultrices arcu ultrices est ultricies, in porttitor neque temp.</p>
                        </div>
                        <div class="col-xs-5 conditions text-right">
                            <p class="mt-2"><strong>Acme Theater</strong></p>
                            <p>Main Street, 5 01234 SPRINGFIELD</p>
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
