@extends('layouts.layouts-horizontal')


@section('content')
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Invoices Sent</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +89.24 %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">$<span class="counter-value"
                                    data-target="559.25">0</span>k</h4>
                            <span class="badge bg-warning me-1">2,258</span> <span class="text-muted">
                                Invoices sent</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-light rounded fs-3">
                                <i data-feather="file-text" class="text-success icon-dual-success"></i>
                            </span>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div><!-- end card -->
        </div><!-- end col -->

        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Paid Invoices</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-danger fs-14 mb-0">
                                <i class="ri-arrow-right-down-line fs-13 align-middle"></i> +8.09 %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">$<span class="counter-value"
                                    data-target="409.66">0</span>k</h4>
                            <span class="badge bg-warning me-1">1,958</span> <span class="text-muted">
                                Paid by clients</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-light rounded fs-3">
                                <i data-feather="check-square" class="text-success icon-dual-success"></i>
                            </span>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div><!-- end card -->
        </div><!-- end col -->

        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Unpaid Invoices</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-danger fs-14 mb-0">
                                <i class="ri-arrow-right-down-line fs-13 align-middle"></i> +9.01 %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">$<span class="counter-value"
                                    data-target="136.98">0</span>k</h4>
                            <span class="badge bg-warning me-1">338</span> <span class="text-muted">
                                Unpaid by clients</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-light rounded fs-3">
                                <i data-feather="clock" class="text-success icon-dual-success"></i>
                            </span>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div><!-- end card -->
        </div><!-- end col -->

        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Cancelled Invoices</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +7.55 %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">$<span class="counter-value"
                                    data-target="84.20">0</span>k</h4>
                            <span class="badge bg-warning me-1">502</span> <span class="text-muted">
                                Cancelled by clients</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-light rounded fs-3">
                                <i data-feather="x-octagon" class="text-success icon-dual-success"></i>
                            </span>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div><!-- end card -->
        </div><!-- end col -->
    </div> <!-- end row-->
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="orderList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Accounts Receivable Customers</h5>
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
                    <form method="GET" action="{{ route('customers') }}">
                        <div class="row g-3">
                            <div class="col-xxl-6 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control" name="q" value="{{ request('q') }}"
                                        placeholder="Search name, phone, passport, contract...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body pt-4">
                    <div>
                        <div class="table-responsive table-card mb-1">
                            <table class="table table-nowrap align-middle" id="orderTable">
                                <thead class="text-muted table-light">
                                    <tr class="text-uppercase">
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Customer Status</th>
                                        <th>Phone</th>
                                        {{-- <th>Branch</th>
                                        <th>Contract</th> --}}
                                        <th>Branch | Contract</th>
                                        {{-- <th>Status</th> --}}
                                        <th>Amount</th>
                                        <th>Paid</th>
                                        <th>Local Remaining</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @foreach ($credit_users as $c)
                                        <tr>
                                            {{-- ID --}}
                                            {{-- <td class="id">
                                                <span class="fw-medium text-primary">
                                                    #{{ $c->logicalref }}
                                                    <div class="text-muted small">
                                                        {{ $c->date_ ?? '-' }}
                                                    </div>
                                                </span>
                                            </td> --}}
                                            <td class="id">
                                                <span class="fw-medium text-primary">
                                                    #{{ $c->logicalref }}
                                                    <div class="text-muted small">
                                                        {{ $c->date_ ? \Carbon\Carbon::parse($c->date_)->format('Y-m-d') : '-' }}
                                                    </div>
                                                </span>
                                            </td>

                                            {{-- Customer --}}
                                            <td class="customer_name">
                                                <div class="fw-medium">{{ $c->name }}</div>
                                                <div class="text-muted small">
                                                    Passport: {{ $c->passport ?? '-' }}
                                                </div>
                                            </td>
                                            {{-- Customer Status --}}
                                            <td class="customer_status">
                                                <div class="fw-medium">{{ $c->custstatus ?: 'UNKNOWN' }} / @if ($c->active)
                                                        <span class="badge bg-success-subtle text-success text-uppercase">
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger text-uppercase">
                                                            Blok
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-muted small">
                                                    {{ $c->clientref ?? '-' }}
                                                </div>
                                            </td>
                                            {{-- Phone --}}
                                            <td class="phone_number">
                                                {{ $c->phone ?? '-' }}
                                            </td>

                                            {{-- Branch --}}
                                            {{-- <td>
                                                {{ $c->branch ?? '-' }}
                                            </td> --}}
                                            {{-- Contract --}}
                                            {{-- <td>
                                                {{ $c->contract ?? '-' }}
                                            </td> --}}

                                            <td class="branch_contract">
                                                <div class="fw-medium">{{ $c->branch }} / {{ $c->contract }}</div>
                                            </td>

                                            {{-- Status --}}
                                            {{-- <td class="status">
                                                @if ($c->active)
                                                    <span class="badge bg-success-subtle text-success text-uppercase">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger text-uppercase">
                                                        Blok
                                                    </span>
                                                @endif
                                            </td> --}}

                                            {{-- Amount --}}
                                            <td>
                                                <div
                                                    class="fw-medium {{ $c->amount_local !== null ? 'text-primary' : '' }}">
                                                    {{ number_format($c->amount_local ?? ($c->amount ?? 0), 2) }}
                                                </div>

                                                @if ($c->amount_local !== null)
                                                    <div class="small text-muted">
                                                        Remote: {{ number_format($c->amount ?? 0, 2) }}
                                                    </div>
                                                @endif
                                            </td>
                                            {{-- Paid --}}
                                            <td>
                                                <div
                                                    class="fw-medium {{ $c->paid_local !== null ? 'text-primary' : '' }}">
                                                    {{ number_format($c->paid_local ?? ($c->paid ?? 0), 2) }}
                                                </div>

                                                @if ($c->paid_local !== null)
                                                    <div class="small text-muted">
                                                        Remote: {{ number_format($c->paid ?? 0, 2) }}
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- Local Remaining --}}
                                            {{-- <td class="text-end">
                                                @if ($c->local_remaining === null)
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        LOCAL MISSING
                                                    </span>
                                                @else
                                                    <div class="fw-medium text-primary">
                                                        {{ number_format($c->local_remaining, 2) }}
                                                    </div>
                                                @endif
                                            </td> --}}

                                            {{-- Local Remaining + Remote Remaining --}}
                                            <td class="text-end">
                                                @if ($c->local_remaining === null)
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        LOCAL MISSING
                                                    </span>

                                                    {{-- LOCAL yoksa yine de remote remaining göster --}}
                                                    <div class="small text-muted mt-1">
                                                        Remote: {{ number_format($c->remote_remaining ?? 0, 2) }}
                                                    </div>
                                                @else
                                                    <div class="fw-medium text-primary">
                                                        {{ number_format($c->local_remaining, 2) }}
                                                    </div>

                                                    {{-- LOCAL varsa remote remaining’i altına yaz --}}
                                                    <div class="small text-muted">
                                                        Remote: {{ number_format($c->remote_remaining ?? 0, 2) }}
                                                    </div>
                                                @endif
                                            </td>


                                            {{-- Note --}}
                                            <td>
                                                {{ $c->note ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{-- Pagination --}}
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted small">
                                Toplam: {{ $credit_users->total() }} |
                                Sayfa: {{ $credit_users->currentPage() }} / {{ $credit_users->lastPage() }}
                            </div>
                            <div>
                                {{ $credit_users->onEachSide(1)->links('vendor.pagination.custom') }}
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
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
