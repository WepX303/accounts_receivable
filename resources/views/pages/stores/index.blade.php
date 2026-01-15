@extends('layouts.layouts-horizontal')

@section('content')
    <div class="card">
        <div class="card-header border-0 rounded">
            <div class="row g-2">
                <!--end col-->
                <div class="col-xxl-3 ms-auto"></div>
                <!--end col-->
                <div class="col-lg-auto">
                    <div class="hstack gap-2">
                        <button type="button" class="btn btn-success"><i class="ri-download-cloud-line me-1 align-bottom"></i>
                            Fetch Stores</button>
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
    </div>
    <!--end card-->

    <div class="row mt-4">

        {{-- Seller 1 --}}
        <div class="col-xl-3 col-lg-6">
            <div class="card ribbon-box right overflow-hidden">
                <div class="card-body text-center p-4">
                    {{-- Trending --}}
                    <div class="ribbon ribbon-info ribbon-shape trending-ribbon">
                        <i class="ri-flashlight-fill text-white align-bottom"></i>
                        <span class="trending-ribbon-text">Trending</span>
                    </div>
                    <img src="{{ asset('build/images/companies/img-1.png') }}" alt="Shop Logo" height="45">
                    <h5 class="mb-1 mt-4">
                        <a href="#" class="link-primary">Tech Store</a>
                    </h5>
                    <p class="text-muted mb-4">John Doe</p>
                    {{-- Chart placeholder --}}
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div id="chart-seller1" data-colors='["#0ab39c"]' dir="ltr"></div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-lg-6 border-end-dashed border-end">
                            <h5>120</h5>
                            <span class="text-muted">Item Stock</span>
                        </div>
                        <div class="col-lg-6">
                            <h5>$5,430</h5>
                            <span class="text-muted">Wallet Balance</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('store.details') }}" class="btn btn-light w-100">View Details</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Seller 2 --}}
        <div class="col-xl-3 col-lg-6">
            <div class="card ribbon-box right overflow-hidden">
                <div class="card-body text-center p-4">
                    <img src="{{ asset('build/images/companies/img-2.png') }}" alt="Shop Logo" height="45">
                    <h5 class="mb-1 mt-4">
                        <a href="#" class="link-primary">Fashion Market</a>
                    </h5>
                    <p class="text-muted mb-4">Jane Smith</p>
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div id="chart-seller2" data-colors='["#f06548"]' dir="ltr"></div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-lg-6 border-end-dashed border-end">
                            <h5>78</h5>
                            <span class="text-muted">Item Stock</span>
                        </div>
                        <div class="col-lg-6">
                            <h5>$2,150</h5>
                            <span class="text-muted">Wallet Balance</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="#" class="btn btn-light w-100">View Details</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Seller 3 --}}
        <div class="col-xl-3 col-lg-6">
            <div class="card ribbon-box right overflow-hidden">
                <div class="card-body text-center p-4">
                    <img src="{{ asset('build/images/companies/img-3.png') }}" alt="Shop Logo" height="45">
                    <h5 class="mb-1 mt-4">
                        <a href="#" class="link-primary">Fashion Market</a>
                    </h5>
                    <p class="text-muted mb-4">Jane Smith</p>
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div id="chart-seller3" data-colors='["#f06548"]' dir="ltr"></div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-lg-6 border-end-dashed border-end">
                            <h5>78</h5>
                            <span class="text-muted">Item Stock</span>
                        </div>
                        <div class="col-lg-6">
                            <h5>$2,150</h5>
                            <span class="text-muted">Wallet Balance</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="#" class="btn btn-light w-100">View Details</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Seller 4 --}}
        <div class="col-xl-3 col-lg-6">
            <div class="card ribbon-box right overflow-hidden">
                <div class="card-body text-center p-4">
                    <img src="{{ asset('build/images/companies/img-4.png') }}" alt="Shop Logo" height="45">
                    <h5 class="mb-1 mt-4">
                        <a href="#" class="link-primary">Fashion Market</a>
                    </h5>
                    <p class="text-muted mb-4">Jane Smith</p>

                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div id="chart-seller4" data-colors='["#f06548"]' dir="ltr"></div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-lg-6 border-end-dashed border-end">
                            <h5>78</h5>
                            <span class="text-muted">Item Stock</span>
                        </div>
                        <div class="col-lg-6">
                            <h5>$2,150</h5>
                            <span class="text-muted">Wallet Balance</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="#" class="btn btn-light w-100">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end row-->

    <div id="pagination-element" class="d-flex justify-content-center mt-4"></div>

    <!-- pagination-element -->
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
