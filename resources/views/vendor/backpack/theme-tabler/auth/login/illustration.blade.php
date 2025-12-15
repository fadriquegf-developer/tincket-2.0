@extends(backpack_view('layouts.auth'))

@section('content')
    {{-- Contenedor de copos de nieve --}}
    <div class="snowflakes" aria-hidden="true">
        @for ($i = 0; $i < 50; $i++)
            <div class="snowflake">❄</div>
        @endfor
    </div>

    <div class="page page-center">
        <div class="container container-normal py-4">
            <div class="row align-items-center g-4">
                <div class="col-lg">
                    <div class="container-tight">
                        <div class="text-center mb-4 display-6 auth-logo-container">
                            {{-- <img src="/images/logo2.png" alt="Logo YWT" height="100"> --}}
                            {{-- Logo Nadal --}}
                            <img src="/images/logo_nadal.png" alt="Logo YWT" height="140">
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
                    {{-- Logo Ticketio normal --}}
                    {{-- <img src="/images/ticketio.png" alt=""> --}}
                    {{-- Logo Ticketio Nadal --}}
                    <img src="/images/ticketio_nadal.png" alt="">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after_styles')
    <style>
        .snowflakes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }

        .snowflake {
            position: absolute;
            top: -20px;
            color: #fff;
            font-size: 1rem;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.8);
            animation: snowfall linear infinite;
            opacity: 0.8;
        }

        @keyframes snowfall {
            0% {
                transform: translateY(-20px) rotate(0deg);
            }

            100% {
                transform: translateY(100vh) rotate(360deg);
            }
        }

        /* Variaciones para hacer más natural el efecto */
        @keyframes sway {

            0%,
            100% {
                transform: translateX(0);
            }

            50% {
                transform: translateX(20px);
            }
        }
    </style>
@endpush

@push('after_scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const snowflakes = document.querySelectorAll('.snowflake');
            const symbols = ['❄', '❅', '❆', '•'];

            snowflakes.forEach((flake) => {
                // Posición horizontal aleatoria
                flake.style.left = Math.random() * 100 + '%';

                // Tamaño aleatorio
                const size = Math.random() * 1 + 0.5;
                flake.style.fontSize = size + 'rem';

                // Duración de caída aleatoria (más lento = más natural)
                const duration = Math.random() * 5 + 8;
                flake.style.animationDuration = duration + 's';

                // Retraso aleatorio para que no caigan todos a la vez
                const delay = Math.random() * 10;
                flake.style.animationDelay = delay + 's';

                // Opacidad variable
                flake.style.opacity = Math.random() * 0.6 + 0.4;

                // Símbolo aleatorio
                flake.textContent = symbols[Math.floor(Math.random() * symbols.length)];
            });
        });
    </script>
@endpush
