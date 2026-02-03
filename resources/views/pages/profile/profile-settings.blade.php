@extends('layouts.layouts-horizontal')

@section('content')
    <div class="position-relative mx-n4 mt-n4">

        {{-- <div class="profile-wid-bg profile-setting-img">
            <img src="{{ URL::asset('build/images/profile-bg.jpg') }}" class="profile-wid-img" alt="">          
        </div> --}}

        <div class="profile-wid-bg profile-setting-img position-relative" style="border-radius: 4px; overflow: hidden;">
            <img src="{{ URL::asset('build/images/galaxy/img-3.png') }}" class="profile-wid-img" alt="">



            <!-- Dil değiştirici sağ üst köşe -->

            {{-- <div class="dropdown position-absolute" style="top: 20px; right: 20px;">
                <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                    style="width: 40px; height: 50px; padding: 8px;" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">

                    @php($loc = session('locale', 'tk'))

                    @switch($loc)
                        @case('ru')
                            <img src="{{ URL::asset('build/images/flags/ru.svg') }}" class="rounded" height="28">
                        @break

                        @case('en')
                            <img src="{{ URL::asset('build/images/flags/us.svg') }}" class="rounded" height="28">
                        @break

                        @case('tr')
                            <img src="{{ URL::asset('build/images/flags/tr.svg') }}" class="rounded" height="28">
                        @break

                        @default
                            <img src="{{ URL::asset('build/images/flags/tm.svg') }}" class="rounded" height="28">
                    @endswitch
                </button>

                <div class="dropdown-menu dropdown-menu-end">
                    <a href="{{ route('lang.switch', 'tk') }}" class="dropdown-item">
                        <img src="{{ URL::asset('build/images/flags/tm.svg') }}" height="18" class="me-2 rounded">
                        Turkmen
                    </a>

                    <a href="{{ route('lang.switch', 'ru') }}" class="dropdown-item">
                        <img src="{{ URL::asset('build/images/flags/ru.svg') }}" height="18" class="me-2 rounded">
                        Русский
                    </a>

                    <a href="{{ route('lang.switch', 'en') }}" class="dropdown-item">
                        <img src="{{ URL::asset('build/images/flags/us.svg') }}" height="18" class="me-2 rounded">
                        English
                    </a>

                    <a href="{{ route('lang.switch', 'tr') }}" class="dropdown-item">
                        <img src="{{ URL::asset('build/images/flags/tr.svg') }}" height="18" class="me-2 rounded">
                        Türkçe
                    </a>
                </div>
            </div> --}}

        </div>
    </div>

    <div class="row">
        <div class="col-xxl-3">
            <div class="card mt-n5">
                <div class="card-body p-4">
                    <div class="text-center">
                        <div class="profile-user position-relative d-inline-block mx-auto  mb-4">
                            <img src="{{ URL::asset('build/images/users/user.jpg') }}"
                                class="rounded-circle avatar-xl img-thumbnail user-profile-image" alt="user-profile-image">
                        </div>
                        <h5 class="fs-16 mb-1">{{ $user->fullname }}</h5>
                        <p class="text-muted mb-0">{{ $user->position ?? $user->role }}</p>
                    </div>
                </div>
            </div>
            <!--end card-->
        </div>
        <!--end col-->
        <div class="col-xxl-9">
            <div class="card mt-xxl-n5">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails" role="tab">
                                <i class="fas fa-home"></i>{{ __('pages/profile.user_details') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content">
                        <div class="tab-pane active" id="personalDetails" role="tabpanel">
                            {{-- Hatalar ve mesajlar --}}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            @if (session('info'))
                                <div class="alert alert-info">{{ session('info') }}</div>
                            @endif
                            <form id="profileForm" action="{{ route('profile.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="_profile_update" value="1">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="firstnameInput" class="form-label">{{ __('pages/profile.first_name') }}</label>
                                            <input type="text" class="form-control" id="firstnameInput" name="firstname"
                                                placeholder="{{ __('pages/profile.enter_first_name') }}"
                                                value="{{ old('firstname', $user->firstname) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="lastnameInput" class="form-label">{{ __('pages/profile.last_name') }}</label>
                                            <input type="text" class="form-control" id="lastnameInput" name="lastname"
                                                placeholder="{{ __('pages/profile.enter_last_name') }}"
                                                value="{{ old('lastname', $user->lastname) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="phonenumberInput" class="form-label">{{ __('pages/profile.phone_number') }}</label>
                                            <input type="tel" class="form-control" id="phonenumberInput"
                                                name="phonenumber" placeholder="{{ __('pages/profile.enter_phone') }}"
                                                value="{{ old('phonenumber', $user->phonenumber) }}" pattern="[0-9]{10,15}"
                                                title="{{ __('pages/profile.digits_only') }}"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="emailInput" class="form-label">{{ __('pages/profile.email') }}</label>
                                            <input type="email" class="form-control" id="emailInput" name="email"
                                                placeholder="{{ __('pages/profile.enter_email') }}" value="{{ old('email', $user->email) }}"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="oldpasswordInput" class="form-label">{{ __('pages/profile.old_password') }}</label>
                                            <input type="password" class="form-control" id="oldpasswordInput"
                                                name="old_password" placeholder="{{ __('pages/profile.enter_old_password') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="newpasswordInput" class="form-label">{{ __('pages/profile.new_password') }}</label>
                                            <input type="password" class="form-control" id="newpasswordInput"
                                                name="new_password" placeholder="{{ __('pages/profile.enter_new_password') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="confirmpasswordInput" class="form-label">{{ __('pages/profile.confirm_password') }}</label>
                                            <input type="password" class="form-control" id="confirmpasswordInput"
                                                name="new_password_confirmation" placeholder="{{ __('pages/profile.confirm_new_password') }}">
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="d-flex justify-content-end gap-2 mt-3 flex-wrap">
                                <!-- Logout butonu -->
                                <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">{{ __('menu.logout') }}</button>
                                </form>
                                <!-- Save butonu -->
                                <button type="submit" form="profileForm"
                                    class="btn btn-success">{{ __('common.save') }}</button>
                            </div>
                        </div>
                        <!--end tab-pane-->
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection
@section('script')
    <script src="{{ URL::asset('build/js/pages/profile-setting.init.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
