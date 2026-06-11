@extends('layouts.master-without-nav')


@section('body')

<body>
    @endsection
    @section('content')
    <!-- auth-page wrapper -->
    <div class="auth-page-wrapper py-5 d-flex justify-content-center align-items-center min-vh-100">

        <!-- auth-page content -->
        <div class="auth-page-content overflow-hidden p-0">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-7 col-lg-8">
                        <div class="text-center">
                            <img src="{{ URL::asset('build/images/error400-cover.png') }}" alt="error img"
                                class="img-fluid">
                            <div class="mt-3">
                                <h3 class="text-uppercase">{{__('errors.404_title')}}</h3>
                                <p class="text-muted mb-4">{{__('errors.404_desc')}}</p>
                                <a href="{{route('dashboard')}}" class="btn btn-success"><i
                                        class="mdi mdi-home me-1"></i>{{__('errors.back_home')}}</a>
                            </div>
                        </div>
                    </div><!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end auth-page content -->
    </div>
    <!-- end auth-page-wrapper -->
    @endsection