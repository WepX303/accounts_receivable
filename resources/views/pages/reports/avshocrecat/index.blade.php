@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="orderList">

                {{-- Filters --}}
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET" action="{{ route('report') }}">
                        <div class="row g-3 align-items-end">

                            {{-- Search --}}
                            <div class="col-xxl-6 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control" name="q" value="{{ request('q') }}"
                                        placeholder="{{ __('pages/monthly_report.search_placeholder') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Table --}}
                <div class="card-body pt-4">
                    <div class="table-responsive table-card mb-1">
                        <table class="table table-nowrap align-middle" id="orderTable">
                            <thead class="text-muted table-light">
                                <tr class="text-uppercase">
                                    <th>{{ __('pages/monthly_report.th.id') }}</th>
                                    <th>{{ __('pages/monthly_report.th.store') }}</th>
                                    <th>{{ __('pages/monthly_report.th.borrower') }}</th>
                                    <th>{{ __('pages/monthly_report.th.phone') }}</th>
                                    <th>{{ __('pages/monthly_report.th.passport') }}</th>
                                    <th>{{ __('pages/monthly_report.th.contract') }}</th>
                                    <th>{{ __('pages/monthly_report.th.tiger') }}</th>

                                    <th class="text-end">{{ __('pages/monthly_report.th.kt_expense') }}</th>
                                    <th class="text-end">{{ __('pages/monthly_report.th.dt_income') }}</th>

                                    <th class="text-end">{{ __('pages/monthly_report.th.m1') }}</th>
                                    <th class="text-end">{{ __('pages/monthly_report.th.m2') }}</th>
                                    <th class="text-end">{{ __('pages/monthly_report.th.m3') }}</th>
                                    <th class="text-end">{{ __('pages/monthly_report.th.m4') }}</th>
                                    <th class="text-end">{{ __('pages/monthly_report.th.m5') }}</th>
                                    <th class="text-end">{{ __('pages/monthly_report.th.m6') }}</th>

                                    <th class="text-end">{{ __('pages/monthly_report.th.monthly_payment') }}</th>
                                    <th class="text-end">{{ __('pages/monthly_report.th.balance') }}</th>

                                    <th>{{ __('pages/monthly_report.th.loan_date') }}</th>
                                    <th>{{ __('pages/monthly_report.th.end_date') }}</th>

                                    <th>{{ __('pages/monthly_report.th.category') }}</th>
                                    <th>{{ __('pages/monthly_report.th.info') }}</th>
                                    <th>{{ __('pages/monthly_report.th.note') }}</th>

                                    <th>{{ __('pages/monthly_report.th.will_pay_date') }}</th>
                                    <th>{{ __('pages/monthly_report.th.status') }}</th>

                                    <th class="text-end">{{ __('pages/monthly_report.th.created') }}</th>
                                    <th class="text-end">{{ __('pages/monthly_report.th.updated') }}</th>
                                </tr>
                            </thead>

                            <tbody class="list form-check-all">
                                @forelse ($rows as $r)
                                    <tr>
                                        <td class="id">
                                            <span class="fw-medium text-primary">#{{ $r->id }}</span>
                                        </td>

                                        <td>{{ $r->magazyn ?? '-' }}</td>

                                        <td>
                                            <div class="fw-medium">{{ $r->karz_alyjy ?? '-' }}</div>
                                        </td>

                                        <td style="white-space:nowrap;">{{ $r->telefon_belgisi ?? '-' }}</td>
                                        <td style="white-space:nowrap;">{{ $r->pasport_belgisi ?? '-' }}</td>
                                        <td style="white-space:nowrap;">{{ $r->sertnama_nomeri ?? '-' }}</td>
                                        <td style="white-space:nowrap;">{{ $r->tiger_kody ?? '-' }}</td>

                                        <td class="text-end">
                                            {{ $r->kt_cykdajy === null ? '-' : number_format((float) $r->kt_cykdajy, 2) }}
                                        </td>
                                        <td class="text-end">
                                            {{ $r->dt_girdeji === null ? '-' : number_format((float) $r->dt_girdeji, 2) }}
                                        </td>

                                        <td class="text-end">{{ $r->m1 === null ? '-' : number_format((float) $r->m1, 2) }}
                                        </td>
                                        <td class="text-end">{{ $r->m2 === null ? '-' : number_format((float) $r->m2, 2) }}
                                        </td>
                                        <td class="text-end">{{ $r->m3 === null ? '-' : number_format((float) $r->m3, 2) }}
                                        </td>
                                        <td class="text-end">{{ $r->m4 === null ? '-' : number_format((float) $r->m4, 2) }}
                                        </td>
                                        <td class="text-end">{{ $r->m5 === null ? '-' : number_format((float) $r->m5, 2) }}
                                        </td>
                                        <td class="text-end">{{ $r->m6 === null ? '-' : number_format((float) $r->m6, 2) }}
                                        </td>

                                        <td class="text-end">
                                            {{ $r->aylyk_tolegi === null ? '-' : number_format((float) $r->aylyk_tolegi, 2) }}
                                        </td>
                                        <td class="text-end">
                                            {{ $r->galyndy === null ? '-' : number_format((float) $r->galyndy, 2) }}
                                        </td>

                                        {{-- String date alanlar (şimdilik) --}}
                                        <td style="white-space:nowrap;">{{ $r->karz_alan_senesi ?? '-' }}</td>
                                        <td style="white-space:nowrap;">{{ $r->gutaryan_senesi ?? '-' }}</td>

                                        <td>{{ $r->kategoriyasy ?? '-' }}</td>
                                        <td>{{ $r->maglumat ?? '-' }}</td>
                                        <td>{{ isset($r->bellik) && trim($r->bellik) !== '' ? $r->bellik : '-' }}</td>

                                        <td style="white-space:nowrap;">
                                            {{ $r->tolejek_senesi ? \Carbon\Carbon::parse($r->tolejek_senesi)->format('d.m.Y') : '-' }}
                                        </td>

                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ isset($r->statusy) && trim($r->statusy) !== '' ? $r->statusy : __('pages/monthly_report.status_missing') }}
                                            </span>
                                        </td>



                                        <td class="text-end" style="white-space:nowrap;">
                                            {{ $r->created_at ? $r->created_at->format('Y-m-d H:i') : '-' }}
                                        </td>
                                        <td class="text-end" style="white-space:nowrap;">
                                            {{ $r->updated_at ? $r->updated_at->format('Y-m-d H:i') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="26" class="text-center text-muted py-4">
                                            {{ __('pages/monthly_report.no_records') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            {{ __('pages/monthly_report.total') }}: {{ $rows->total() }} |
                            {{ __('pages/monthly_report.page') }}: {{ $rows->currentPage() }} / {{ $rows->lastPage() }}
                        </div>
                        <div>
                            {{ $rows->onEachSide(1)->links('vendor.pagination.custom') }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/list.js/list.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
@endsection
