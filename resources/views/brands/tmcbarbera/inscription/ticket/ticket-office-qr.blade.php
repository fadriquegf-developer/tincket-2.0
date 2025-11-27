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
      font-size: .9rem;
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
      line-height: 1.8;
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
      width: calc(100% - 65mm);
      margin-left: 12mm;
      padding-top: 14.5mm;
      /* padding-bottom: 40mm; */
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
      padding-right: 20px;
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
      height: auto;
    }

    .text-extra-small {
      font-size: .75em;
    }

    .text-small {
      font-size: .8em;
    }

    hr {
      border-top: .25mm solid #000;
    }
  </style>

</head>

<body>
  <!--[if lte IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
        <![endif]-->
  <div class="container">
    <div class="row">
      <div class="col-9">
        <div class="pr">
          <div class="spacer"></div>
          <div class="row">
            <div class="col-12">
              <h5 class="font-heavy title-overflow p-0 m-0">{{ $inscription->session->event->name }}</h5>
              <h6 class="title-overflow p-0 m-0">
              @if($inscription->session->event->name != $inscription->session->name)
              {{ $inscription->session->name }} |
              @endif
              {{ $inscription->slot->name ?? '' }}
              </h6>
              <div class="spacer"></div>
              @php
              setlocale(LC_TIME, "ca_ES.utf8");
              @endphp
              {{ sprintf("%s, %s h",
                          ucfirst($inscription->session->starts_on->translatedFormat('l')),
                          $inscription->session->starts_on->translatedFormat('d/m H:i')) }}
              <div class="spacer "></div>
              <span>
              {{ sprintf("%s - %s €", $inscription->getRateName(), number_format($inscription->price_sold, 2)) }}
              </span>
              @if(isset($inscription->group_pack->pack->name))
              &nbsp;<i>{{ $inscription->group_pack->pack->name }}</i>
              @endif
              <div class="spacer"></div>
              <span>{{ sprintf("Nº %s", $inscription->cart->confirmation_code) }}</span>
              <div class="spacer"></div>
              <div class="text-small">
                {{ $inscription->session->space->name }} 
              </div>
            </div>
          </div>
        </div>
      </div>


      <div class="col-3 center" style="background-color:white;">
        <div class="spacer"></div>
        <img class="img-fluid" style="border: 1px solid white;" src="data:image/png;base64,{!! DNS2D::getBarcodePNG($inscription->barcode, "QRCODE", 5,5) !!}" />
        <div class="spacer"></div>
        <span class="text-extra-small">{{ $inscription->barcode }}</span>
      </div>
    </div>
  </div>
</body>

</html>