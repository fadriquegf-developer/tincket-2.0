@extends('core.emails.layout')

@section('content')
<p>
    {!! trans('backend.contact.notification') !!}
</p>

<p>
 {{ trans('backend.contact.regards') }}
</p>
@endsection
