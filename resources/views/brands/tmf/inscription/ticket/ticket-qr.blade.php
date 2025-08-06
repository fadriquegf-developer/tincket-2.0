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
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@200&display=swap" rel="stylesheet">
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
.noto-font{
  font-family: 'Noto Serif', serif;
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
  font-size: 1rem;
  font-weight: 200;
  line-height: 1.8;
}

.font-size-13 {
  font-size: 14px;
}

.font-light {
  /* font-weight: 300; */
  font-weight: lighter;
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
  width: 33%;
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

.col-20 {
  width: 20%;
}

.col-40 {
  width: 40%;
}

.col-2-5 {
  width: 20.83%;
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

.pb {
  padding-bottom: 15px;
}

.pt {
  padding-top: 15px;
}

.pr {
    padding-right: 15px;
}

.pl {
    padding-left: 15px;
}


.p-0 {
    padding: 0px;
}

.m-0 {
    margin: 0;
}

.spacer {
    padding-top: 15px;
}

/* particular styles */

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.text-left {
  text-align: left;
}

.text-muted {
    color: #999;
}


.text-white {
    color: #fff;
}

.title-overflow {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.border-top {
    border-top: 1px solid #444;
}

.border-bottom {
    border-bottom: 1px solid #444;
}

.border-right {
    border-right: 1px solid #444;
}

.border-left {
    border-left: 1px solid #444;
}

.img-fluid {
  max-width: 100%;
  height: auto;
}

.bg-black {
  /* background-color: #000; */
  background-color: #1d1d1b;
}

/*2020*/

.col-b-23 {
  width: 23%;
}

.col-b-20 {
  width: 20%;
}

.col-b-16 {
  width: 16%;
}

.col-b-15 {
  width: 15%;
}

.col-b-20 {
  width: 20%;
}
.border{
  border: 1px solid black;
}
.title{
  font-family: 'digiantiqua-lt-lightcondensed-regular', sans-serif;
}
</style>

</head>

<body style="padding-top: 20px;">        

  <div class="container border-right">
    <div class="row border-left border-bottom">
      <div class="col-9">
          <div class="bg-black py pl pr">
            <div class="row font-light text-white font-size-13">
              <div class="col-12 text-center title noto-font" style="font-size: 23px; text-align: center;">
                BBVA Torelló Mountain Film Festival<br> 42nd Edition 15—24 Nov 2024
              </div>
            </div>
            <div class="row font-regular text-white font-size-13">
            </div>
          </div>
          <div class=" border-right border-bottom">
            <div class="row font-size-13 py px">
              <div class="col-6 py" >
                <div class="pl">
                  Dia: <br>
                  @php
                  setlocale(LC_TIME, "ca_ES.utf8");
                  @endphp
                  {{ sprintf("%s, %s", ucfirst($inscription->session->starts_on->formatLocalized('%A')), $inscription->session->starts_on->formatLocalized('%d/%m')) }}
                  <br>
                  @if($inscription->session->space->name != $inscription->session->space->location->name)
                    {{ $inscription->session->space->location->name }}
                    @endif
                </div>
              </div>
              <div class="col-3 py">
              Hora: <br>
              {{ sprintf("%sH", $inscription->session->starts_on->formatLocalized('%H.%M')) }}
              </div>
              <div class="col-3 py">
              Preu: <br>
              {{ sprintf("%s €", number_format($inscription->price_sold, 2)) }}
              </div>
            </div>
            
          </div>

        <div class="border-right">
          <div class="row">
            <div class="col-3">
              <p> </p>
            </div>
            <div class="col-6 py">
                <div>
                   <img class="img-fluid" src="/images/logos-entrada-tmf.png" style="width: 100%" />
                </div>
            </div>
            <div class="col-3">
              <p></p>
            </div>
          </div>
        </div>

      </div>
      <div class="col-3">
          <div class="py border-top text-center" style="background-color:white;">
          <img class="img-fluid" style="border: 1px solid white;" src="data:image/png;base64,{!! DNS2D::getBarcodePNG($inscription->barcode, "QRCODE", 6,6) !!}"/>
          <div class="spacer"></div>
          <small style="font-size: 12px; font-weight:bold;">{{ $inscription->barcode }}</small>
           <div class="spacer" style="padding-top: 4px;"></div>
          <small style="font-size: 12px; font-weight:bold;">{{ sprintf("Nº %s", $inscription->cart->confirmation_code) }}</small>
          </div>
      </div>
    </div>
  </div>

</body>

</html>