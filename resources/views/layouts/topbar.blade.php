<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                {{-- MOBILE MENU BUTTON --}}
                <button type="button" class="btn btn-sm px-3 fs-16 header-item d-lg-none" data-bs-toggle="collapse"
                    data-bs-target="#topnav-menu-content" aria-controls="topnav-menu-content" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <i class="bx bx-menu fs-22"></i>
                </button>
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="{{ route('dashboard') }}" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="17">
                        </span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="17">
                        </span>
                    </a>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                        data-toggle="fullscreen">
                        <i class='bx bx-fullscreen fs-22'></i>
                    </button>
                </div>
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button"
                        class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                </div>

                {{-- 🌍 Language Switcher --}}
                <div class="dropdown ms-1 header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                        style="width: 40px; height: 40px; padding: 6px;" data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">

                        @php($loc = session('locale', 'tk'))

                        @switch($loc)
                            @case('ru')
                                <img src="{{ URL::asset('build/images/flags/ru.svg') }}" class="rounded" height="22">
                            @break

                            @case('en')
                                <img src="{{ URL::asset('build/images/flags/us.svg') }}" class="rounded" height="22">
                            @break

                            @case('tr')
                                <img src="{{ URL::asset('build/images/flags/tr.svg') }}" class="rounded" height="22">
                            @break

                            @default
                                <img src="{{ URL::asset('build/images/flags/tm.svg') }}" class="rounded" height="22">
                        @endswitch
                    </button>

                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="{{ route('lang.switch', 'tk') }}" class="dropdown-item">
                            <img src="{{ URL::asset('build/images/flags/tm.svg') }}" height="18"
                                class="me-2 rounded">
                            Turkmen
                        </a>

                        <a href="{{ route('lang.switch', 'ru') }}" class="dropdown-item">
                            <img src="{{ URL::asset('build/images/flags/ru.svg') }}" height="18"
                                class="me-2 rounded">
                            Русский
                        </a>

                        <a href="{{ route('lang.switch', 'en') }}" class="dropdown-item">
                            <img src="{{ URL::asset('build/images/flags/us.svg') }}" height="18"
                                class="me-2 rounded">
                            English
                        </a>

                        <a href="{{ route('lang.switch', 'tr') }}" class="dropdown-item">
                            <img src="{{ URL::asset('build/images/flags/tr.svg') }}" height="18"
                                class="me-2 rounded">
                            Türkçe
                        </a>
                    </div>
                </div>
                {{-- 🌍 Language Switcher --}}


                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user"
                                src="{{ asset('build/images/users/user.jpg') }}" alt="User Avatar">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">
                                    {{ $user->fullname ?? __('menu.guest') }}
                                </span>
                                <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">
                                    {{ $user->role ?? '-' }}
                                </span>
                            </span>
                        </span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <a class="dropdown-item" href="{{ route('profile') }}"><i
                                class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">{{ __('menu.profile') }}</span></a>
                        {{-- <a class="dropdown-item" href="apps-chat"><i
                                class="mdi mdi-message-text-outline text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">{{ __('menu.messages') }}</span></a> --}}
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item " href="javascript:void();"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i
                                class="bx bx-power-off font-size-16 align-middle me-1"></i> <span
                                key="t-logout">{{ __('menu.logout') }}</span></a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                            style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
