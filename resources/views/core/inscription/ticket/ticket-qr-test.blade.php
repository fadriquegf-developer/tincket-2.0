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
      font-size: 100%;
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

    .col-1 {
      width: 8.33%;
    }

    .col-2 {
      width: 16.66%;
    }

    .col-3 {
      width: 25%;
    }

    .col-4 {
      width: 33.33%;
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

    .img-fluid.logo {
      max-height: 35px;
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
    <div class="row border-bottom">
      <div class="col-2">
        <img class="img-fluid logo" alt="{{ $inscription->cart->brand->name }}" src="{{ $inscription->getLogo() }}" />
      </div>
      <div class="col-10">
        <div class="pl">
          <h4 class="p-0 m-0 font-heavy">
            {{ $inscription->cart->brand->name }}
          </h4>
        </div>
      </div>
    </div>
    <div class="spacer"></div>
    <div class="row">
      <div class="col-9">
        <div class="pr">
          <div class="spacer"></div>
          <div class="row">
            <div class="col-12">
              <h4 class="font-heavy title-overflow p-0 m-0">{{ $inscription->session->event->name }}</h4>
              @if($inscription->session->event->custom_text)
              <div class="text-small p-0 m-0">
                {{ mb_strimwidth(strip_tags($inscription->session->event->custom_text), 0, 145, "...") }}
              </div>
              @endif
              <p class="text-small title-overflow p-0 m-0">
              @if($inscription->session->event->name != $inscription->session->name)
              {{ $inscription->session->name }}
              @endif
              @if(isset($inscription->slot->name))
              | {{ $inscription->slot->name }}
              @endif
              </p>
              <div class="spacer"></div>
              @php
              setlocale(LC_TIME, "ca_ES.utf8");
              @endphp
              {{ sprintf("%s, %s h",
                                      ucfirst($inscription->session->starts_on->translatedFormat('l')),
                                      $inscription->session->starts_on->translatedFormat('d/m H:i')) }}
              <div class=""></div>
              <span>
              {{ sprintf("%s - %s €", $inscription->getRateName(), number_format($inscription->price_sold, 2)) }}
              </span>
              @if(isset($inscription->group_pack->pack->name))
              &nbsp;<i>{{ $inscription->group_pack->pack->name }}</i>
              @endif
              <div class="title-overflow">
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
              <div class="spacer"></div>
              <span>{{ sprintf("Nº %s", $inscription->cart->confirmation_code) }}</span>
              <div class="spacer"></div>
              @if(!$inscription->session->space->hide)
              <div class="text-small">
                {{ $inscription->session->space->name }} 
                  @if($inscription->session->space->name != $inscription->session->space->location->name)
                  | {{ $inscription->session->space->location->name }}
                  @endif
                <br>
                {{ $inscription->session->space->location->address }}
                {{ $inscription->session->space->location->postal_code }} - {{ $inscription->session->space->location->city->name }}
              </div>
              @endif
            </div>
          </div>
        </div>
      </div>
      <div class="col-3 right" style="background-color:white;">
        <div class="spacer"></div>
        <img class="img-fluid" style="border: 1px solid white;" src="data:image/png;base64,{!! DNS2D::getBarcodePNG($inscription->barcode, "QRCODE", 5,5) !!}" />
        <div class="spacer"></div>
        <span class="text-small">{{ $inscription->barcode }}</span>
      </div>
    </div>
  </div>

  <div class="col-10 text-extra-small" style="position: absolute; bottom: 10px; left: 0px;">
    {{ __('tickets.footer-text') }}
  </div>

</body>

</html>