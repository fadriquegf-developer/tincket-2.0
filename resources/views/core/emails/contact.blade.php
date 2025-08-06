@extends('core.emails.layout')

@section('content')
<p>
    {{ $name }} ({{ $email }}) ha escrit:
</p>
<p>
    {{ $content }}
</p>
@if($recaptcha !== null)
<p>
    Codi de verificació captcha: {{$recaptcha}}
</p>
@endif
@endsection
