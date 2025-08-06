@extends(backpack_view('layouts.auth'))

@section('content')
    <div class="page page-center">
        <div class="container container-normal py-4">
            <div class="row align-items-center g-4">
                <div class="col-lg">
                    <div class="container-tight">
                        <div class="text-center mb-4 display-6 auth-logo-container">
                            <img src="/images/logo2.png" alt="Logo YWT" height="100">
                        </div>
                        <div class="card card-md">
                            <div class="card-body pt-0">
                                @include(backpack_view('auth.login.inc.form'))
                            </div>
                        </div>
                        @if (session('access_error'))
                            <div class="alert alert-danger text-center my-3">
                                {{ session('access_error') }}
                            </div>
                        @endif
                        @if (config('backpack.base.registration_open'))
                            <div class="text-center text-muted ">
                                <a tabindex="6"
                                    href="{{ route('backpack.auth.register') }}">{{ trans('backpack::base.register') }}</a>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-lg d-none d-lg-block">
                    <img src="/images/ticketio.png" alt="">
                </div>
            </div>
        </div>
    </div>
@endsection