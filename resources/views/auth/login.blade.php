@extends('layouts.master-without-nav')

@section('content')
    <div class="auth-page-wrapper pt-5">
        <!-- auth page bg -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>

            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink"
                    viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
        </div>

        <!-- auth page content -->
        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <div>
                                <a href="index" class="d-inline-block auth-logo">
                                    <img src="{{ URL::asset('build/images/logo-light.png') }}" alt=""
                                        height="30">
                                </a>
                            </div>
                            <p class="mt-3 fs-15 fw-medium">{{ __('pages/auth_login.app_name') }}</p>
                        </div>
                    </div>
                </div>
                <!-- end row -->

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4 position-relative">

                            {{-- Language Switch --}}
                            <div class="position-absolute top-0 end-0 p-3">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-light btn-sm rounded-pill shadow-sm"
                                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                                        @php($loc = session('locale', 'tk'))

                                        @switch($loc)
                                            @case('ru')
                                                <img src="{{ URL::asset('build/images/flags/ru.svg') }}" height="16">
                                            @break

                                            @case('en')
                                                <img src="{{ URL::asset('build/images/flags/us.svg') }}" height="16">
                                            @break

                                            @case('tr')
                                                <img src="{{ URL::asset('build/images/flags/tr.svg') }}" height="16">
                                            @break

                                            @default
                                                <img src="{{ URL::asset('build/images/flags/tm.svg') }}" height="16">
                                        @endswitch
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-end shadow">
                                        <a href="{{ route('lang.switch', 'tk') }}" class="dropdown-item">
                                            <img src="{{ URL::asset('build/images/flags/tm.svg') }}" height="16"
                                                class="me-2 rounded">
                                            Turkmen
                                        </a>
                                        <a href="{{ route('lang.switch', 'ru') }}" class="dropdown-item">
                                            <img src="{{ URL::asset('build/images/flags/ru.svg') }}" height="16"
                                                class="me-2 rounded">
                                            Русский
                                        </a>
                                        <a href="{{ route('lang.switch', 'en') }}" class="dropdown-item">
                                            <img src="{{ URL::asset('build/images/flags/us.svg') }}" height="16"
                                                class="me-2 rounded">
                                            English
                                        </a>
                                        <a href="{{ route('lang.switch', 'tr') }}" class="dropdown-item">
                                            <img src="{{ URL::asset('build/images/flags/tr.svg') }}" height="16"
                                                class="me-2 rounded">
                                            Türkçe
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4 pt-5">

                                <div class="text-center mt-2">
                                    <h5 class="text-primary">{{ __('pages/auth_login.welcome_back') }}</h5>
                                    <p class="text-muted">{{ __('pages/auth_login.signin_to_continue') }}</p>
                                </div>
                                <div class="p-2 mt-4">
                                    <form action="{{ route('login.post') }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="username"
                                                class="form-label">{{ __('pages/auth_login.username') }}<span
                                                    class="text-danger">*</span></label>

                                            <input type="text" class="form-control @error('login') is-invalid @enderror"
                                                id="login" name="login"
                                                placeholder="{{ __('pages/auth_login.login_placeholder') }}"
                                                value="{{ old('login') }}">
                                            @error('login')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <div class="float-end">
                                            </div>
                                            <label class="form-label"
                                                for="password-input">{{ __('pages/auth_login.password') }}<span
                                                    class="text-danger">*</span></label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password"
                                                    class="form-control password-input pe-5 @error('password') is-invalid @enderror"
                                                    name="password"
                                                    placeholder="{{ __('pages/auth_login.enter_password') }}"
                                                    id="password-input">

                                                <button
                                                    class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon"
                                                    type="button" id="password-addon"><i
                                                        class="ri-eye-fill align-middle"></i></button>
                                                @error('password')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror

                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-success w-100"
                                                type="submit">{{ __('pages/auth_login.sign_in') }}</button>
                                        </div>

                                    </form>
                                </div>
                                <!-- end card body -->
                            </div>
                            <!-- end card -->

                            <div class="mt-4 text-center">
                            </div>

                        </div>
                    </div>
                    <!-- end row -->
                </div>
                <!-- end container -->
            </div>
            <!-- end auth page content -->

            <!-- footer -->
            <footer class="footer">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="text-center">
                                <p class="mb-0 text-muted">&copy;
                                    <script>
                                        document.write(new Date().getFullYear())
                                    </script> <br> Crafted by WepX
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
            <!-- end Footer -->
        </div>
    @endsection
    @section('script')
        <script src="{{ URL::asset('build/libs/particles.js/particles.js') }}"></script>
        <script src="{{ URL::asset('build/js/pages/particles.app.js') }}"></script>
        <script src="{{ URL::asset('build/js/pages/password-addon.init.js') }}"></script>
    @endsection
