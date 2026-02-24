@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="orderList">

                {{-- Filters --}}
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET" action="{{ route('report') }}">
                        <div class="row g-3 align-items-end">

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
                <div class="card-body pt-4">
                    <div class="table-responsive table-card mb-1">
                        <table class="table table-nowrap align-middle" id="orderTable">
                            <thead class="text-muted table-light">
                                <tr class="text-uppercase">
                                    <th>{{ __('pages/monthly_report.th.store') }} / {{ __('pages/monthly_report.th.id') }}
                                    </th>
                                    <th>{{ __('pages/monthly_report.th.borrower') }} /
                                        {{ __('pages/monthly_report.th.passport') }}</th>
                                    <th>{{ __('pages/monthly_report.th.phone') }} /
                                        {{ __('pages/monthly_report.th.tiger') }}</th>
                                    <th>{{ __('pages/monthly_report.th.contract') }}</th>

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
                                    @php
                                        $tolerance = 0.15;

                                        $ktRaw = $r->kt_cykdajy;
                                        $dtRaw = $r->dt_girdeji;

                                        $kt =
                                            $ktRaw === null
                                                ? null
                                                : (float) str_replace([',', ' '], ['.', ''], trim((string) $ktRaw));
                                        $dt =
                                            $dtRaw === null
                                                ? null
                                                : (float) str_replace([',', ' '], ['.', ''], trim((string) $dtRaw));

                                        $rowClass = '';

                                        if ($kt !== null && $dt !== null) {
                                            $diff = abs($kt - $dt);

                                            if ($diff > $tolerance) {
                                                $rowClass = 'table-danger';
                                            }
                                        }
                                    @endphp

                                    <tr  class="{{ $rowClass }}">
                                        {{-- Store / ID --}}
                                        <td class="id">
                                            <span class="fw-medium text-primary">
                                                {{ $r->magazyn ?? '-' }}
                                                <div class="text-muted small">
                                                    #{{ $r->id ?? '-' }}
                                                </div>
                                            </span>
                                        </td>

                                        {{-- Borrower / Passport --}}
                                        <td>
                                            <div class="fw-medium">{{ $r->karz_alyjy ?? '-' }}</div>
                                            <div class="text-muted small">
                                                {{ $r->pasport_belgisi ?? '-' }}
                                            </div>
                                        </td>

                                        {{-- Phone / Tiger --}}
                                        <td style="white-space:nowrap;">
                                            <div class="fw-medium">{{ $r->telefon_belgisi ?? '-' }}</div>
                                            <div class="text-muted small">
                                                {{ $r->tiger_kody ?? '-' }}
                                            </div>
                                        </td>

                                        {{-- Contract --}}
                                        <td style="white-space:nowrap;">
                                            <div class="fw-medium">{{ $r->sertnama_nomeri ?? '-' }}</div>
                                        </td>

                                        {{-- KT / DT --}}
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->kt_cykdajy === null ? '-' : number_format((float) $r->kt_cykdajy, 2) }}
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->dt_girdeji === null ? '-' : number_format((float) $r->dt_girdeji, 2) }}
                                            </div>
                                        </td>

                                        {{-- M1..M6 --}}
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->m1 === null ? '-' : number_format((float) $r->m1, 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->m2 === null ? '-' : number_format((float) $r->m2, 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->m3 === null ? '-' : number_format((float) $r->m3, 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->m4 === null ? '-' : number_format((float) $r->m4, 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->m5 === null ? '-' : number_format((float) $r->m5, 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->m6 === null ? '-' : number_format((float) $r->m6, 2) }}</div>
                                        </td>

                                        {{-- Monthly payment / Balance --}}
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->aylyk_tolegi === null ? '-' : number_format((float) $r->aylyk_tolegi, 2) }}
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-medium">
                                                {{ $r->galyndy === null ? '-' : number_format((float) $r->galyndy, 2) }}
                                            </div>
                                        </td>

                                        {{-- Loan / End date (string) --}}
                                        <td style="white-space:nowrap;">
                                            {{ $r->karz_alan_senesi ?? '-' }}
                                        </td>
                                        <td style="white-space:nowrap;">
                                            {{ $r->gutaryan_senesi ?? '-' }}
                                        </td>

                                        {{-- Category / Info / Note --}}
                                        <td>{{ $r->kategoriyasy ?? '-' }}</td>
                                        <td>{{ $r->maglumat ?? '-' }}</td>
                                        <td>{{ isset($r->bellik) && trim($r->bellik) !== '' ? $r->bellik : '-' }}</td>

                                        {{-- Will pay date --}}
                                        <td style="white-space:nowrap;">
                                            {{ $r->tolejek_senesi ? \Carbon\Carbon::parse($r->tolejek_senesi)->format('d.m.Y') : '-' }}
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ isset($r->statusy) && trim($r->statusy) !== '' ? $r->statusy : __('pages/monthly_report.status_missing') }}
                                            </span>
                                        </td>

                                        {{-- Created / Updated --}}
                                        <td class="text-end" style="white-space:nowrap;">
                                            {{ $r->created_at ? $r->created_at->format('Y-m-d H:i') : '-' }}
                                        </td>
                                        <td class="text-end" style="white-space:nowrap;">
                                            {{ $r->updated_at ? $r->updated_at->format('Y-m-d H:i') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="24" class="text-center text-muted py-4">
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
