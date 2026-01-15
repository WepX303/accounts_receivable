@extends('layouts.layouts-horizontal')


@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="orderList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Customers</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-success"><i
                                        class="ri-download-cloud-line me-1 align-bottom"></i>
                                    Fetch Customers</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form>
                        <div class="row g-3">
                            <div class="col-xxl-4 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search"
                                        placeholder="Search for order ID, customer, order status or something...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <!--end col-->
                        </div>
                        <!--end row-->
                    </form>
                </div>
                <div class="card-body pt-4">
                    <div>
                        <div class="table-responsive table-card mb-1">
                            <table class="table table-nowrap align-middle" id="orderTable">
                                <thead class="text-muted table-light">
                                    <tr class="text-uppercase">

                                        <th class="sort" data-sort="id">ID</th>
                                        <th data-sort="customer_name">Customer</th>
                                        <th data-sort="type">Type</th>
                                        <th data-sort="phone_number">Phone number</th>
                                        <th data-sort="brands">Brands</th>
                                        <th data-sort="gender">Gender</th>
                                        <th data-sort="status">Status</th>
                                        <th data-sort="total_purchase">Total Purchase</th>
                                        <th data-sort="cashback">Cashback</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    <tr>
                                        <td class="id"><a href="apps-ecommerce-order-details"
                                                class="fw-medium link-primary">#VZ2101</a></td>
                                        <td class="customer_name">Frank Hook</td>
                                        <td class="type">Mango Tshirt</td>
                                        <td class="phone_number">+1234567890</td>
                                        <td class="brands">Mango</td>
                                        <td class="gender">Male</td>
                                        <td class="status"><span
                                                class="badge bg-success-subtle text-success text-uppercase">Active</span>
                                        </td>
                                        <td class="total_purchase">
                                            $1969.00
                                        </td>
                                        <td class="cashback">
                                            $1,245.00
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end">
                            <div class="pagination-wrap hstack gap-2">
                                <a class="page-item pagination-prev disabled" href="#">
                                    Previous
                                </a>
                                <ul class="pagination listjs-pagination mb-0"></ul>
                                <a class="page-item pagination-next" href="#">
                                    Next
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/list.js/list.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/list.pagination.js/list.pagination.min.js') }}"></script>

    <!--ecommerce-customer init js -->
    <script src="{{ URL::asset('build/js/pages/ecommerce-order.init.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
