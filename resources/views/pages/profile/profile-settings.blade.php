@extends('layouts.layouts-horizontal')

@section('content')
    <div class="position-relative mx-n4 mt-n4">

        {{-- <div class="profile-wid-bg profile-setting-img">
            <img src="{{ URL::asset('build/images/profile-bg.jpg') }}" class="profile-wid-img" alt="">          
        </div> --}}

        <div class="profile-wid-bg profile-setting-img position-relative" style="border-radius: 4px; overflow: hidden;">
            <img src="{{ URL::asset('build/images/galaxy/img-3.png') }}" class="profile-wid-img" alt="">



            <!-- Dil değiştirici sağ üst köşe -->
            <div class="dropdown position-absolute" style="top: 20px; right: 20px;">
                <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                    style="width: 40px; height: 50px; padding: 8px;" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    @switch(Session::get('lang'))
                        @case('ru')
                            <img src="{{ URL::asset('build/images/flags/russia.svg') }}" class="rounded" height="28">
                        @break

                        @case('en')
                            <img src="{{ URL::asset('build/images/flags/us.svg') }}" class="rounded" height="28">
                        @break`

                        @default
                            <img src="{{ URL::asset('build/images/flags/tm.svg') }}" class="rounded" height="28">
                    @endswitch
                </button>

                <div class="dropdown-menu dropdown-menu-end">
                    <a href="{{ url('index/ae') }}" class="dropdown-item">
                        <img src="{{ URL::asset('build/images/flags/tm.svg') }}" height="18" class="me-2 rounded">
                        Turkmen
                    </a>

                    <a href="{{ url('index/ru') }}" class="dropdown-item">
                        <img src="{{ URL::asset('build/images/flags/russia.svg') }}" height="20" class="me-2 rounded">
                        Русский
                    </a>

                    <a href="{{ url('index/en') }}" class="dropdown-item">
                        <img src="{{ URL::asset('build/images/flags/us.svg') }}" height="20" class="me-2 rounded">
                        English
                    </a>
                </div>
            </div>
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
                                <i class="fas fa-home"></i>
                                User Details
                            </a>
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
                                            <label for="firstnameInput" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="firstnameInput" name="firstname"
                                                placeholder="Enter your firstname"
                                                value="{{ old('firstname', $user->firstname) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="lastnameInput" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="lastnameInput" name="lastname"
                                                placeholder="Enter your lastname"
                                                value="{{ old('lastname', $user->lastname) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="phonenumberInput" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phonenumberInput"
                                                name="phonenumber" placeholder="Enter your phone number"
                                                value="{{ old('phonenumber', $user->phonenumber) }}"
                                                pattern="[0-9]{10,15}" title="Lütfen sadece rakam girin"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="emailInput" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="emailInput" name="email"
                                                placeholder="Enter your email" value="{{ old('email', $user->email) }}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="oldpasswordInput" class="form-label">Old Password</label>
                                            <input type="password" class="form-control" id="oldpasswordInput"
                                                name="old_password" placeholder="Enter current password">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="newpasswordInput" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="newpasswordInput"
                                                name="new_password" placeholder="Enter new password">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="confirmpasswordInput" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="confirmpasswordInput"
                                                name="new_password_confirmation" placeholder="Confirm password">
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="d-flex justify-content-end gap-2 mt-3 flex-wrap">
                                <!-- Logout butonu -->
                                <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">Logout</button>
                                </form>
                                <!-- Save butonu -->
                                <button type="submit" form="profileForm" class="btn btn-success">Save</button>
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
