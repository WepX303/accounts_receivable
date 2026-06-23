@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="orderList">

                {{-- Filters --}}
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET" action="{{ route('report') }}">
                        <div class="row g-3 align-items-end">

                            <div class="col-xxl-3 col-md-6">
                                <label class="form-label">Search</label>
                                <div class="search-box">
                                    <input type="text" class="form-control" name="q"
                                        value="{{ $q ?? request('q') }}"
                                        placeholder="{{ __('pages/monthly_report.search_placeholder') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>

                            <div class="col-xxl-2 col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control"
                                    value="{{ $dateFrom ?? request('date_from') }}">
                            </div>

                            <div class="col-xxl-2 col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control"
                                    value="{{ $dateTo ?? request('date_to') }}">
                            </div>

                            <div class="col-xxl-3 col-md-6">
                                <label class="form-label">Branches</label>

                                <div class="dropdown w-100">
                                    <button
                                        class="form-control text-start d-flex justify-content-between align-items-center"
                                        type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                        aria-expanded="false">
                                        <span id="branchSelectedText">
                                            @if (!empty($selectedBranches))
                                                {{ count($selectedBranches) }} branch selected
                                            @else
                                                All Branches
                                            @endif
                                        </span>
                                        <i class="ri-arrow-down-s-line"></i>
                                    </button>

                                    <div class="dropdown-menu w-100 p-2 shadow"
                                        style="max-height: 280px; overflow-y: auto;">

                                        <div class="dropdown-divider"></div>

                                        @foreach ($branches as $b)
                                            <label class="dropdown-item d-flex align-items-center gap-2 branch-option">
                                                <input type="checkbox" class="form-check-input branch-checkbox m-0"
                                                    name="branches[]" value="{{ $b }}"
                                                    {{ in_array($b, $selectedBranches ?? []) ? 'checked' : '' }}>
                                                <span>{{ $b }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-xxl-2 col-md-6">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        Apply
                                    </button>

                                    <a href="{{ route('report') }}" class="btn btn-light border w-100">
                                        Clear
                                    </a>
                                </div>

                                <a href="{{ route('report.export', request()->query()) }}"
                                    class="btn btn-success w-100 mt-2">
                                    <i class="ri-file-excel-2-line"></i>
                                    Export
                                </a>
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

                                        $kt =
                                            $r->kt_cykdajy === null
                                                ? null
                                                : (float) str_replace(
                                                    [',', ' '],
                                                    ['.', ''],
                                                    trim((string) $r->kt_cykdajy),
                                                );
                                        $dt =
                                            $r->dt_girdeji === null
                                                ? null
                                                : (float) str_replace(
                                                    [',', ' '],
                                                    ['.', ''],
                                                    trim((string) $r->dt_girdeji),
                                                );
                                        $galyndy =
                                            $r->galyndy === null
                                                ? null
                                                : (float) str_replace(
                                                    [',', ' '],
                                                    ['.', ''],
                                                    trim((string) $r->galyndy),
                                                );

                                        $rowClass = '';

                                        if ($kt !== null && $dt !== null && $galyndy !== null) {
                                            $expectedGalyndy = $kt - $dt;

                                            if (abs($expectedGalyndy - $galyndy) > $tolerance) {
                                                $rowClass = 'table-danger';
                                            }
                                        }
                                    @endphp

                                    <tr class="{{ $rowClass }}">
                                        <td class="id">
                                            <span class="fw-medium text-primary">
                                                {{ $r->magazyn ?? '-' }}
                                                <div class="text-muted small">#{{ $r->id ?? '-' }}</div>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="fw-medium">{{ $r->karz_alyjy ?? '-' }}</div>
                                            <div class="text-muted small">{{ $r->pasport_belgisi ?? '-' }}</div>
                                        </td>

                                        <td style="white-space:nowrap;">
                                            <div class="fw-medium">{{ $r->telefon_belgisi ?? '-' }}</div>
                                            <div class="text-muted small">{{ $r->tiger_kody ?? '-' }}</div>
                                        </td>

                                        <td style="white-space:nowrap;">
                                            <div class="fw-medium">{{ $r->sertnama_nomeri ?? '-' }}</div>
                                        </td>

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
                                            {{ $r->galyndy === null ? '-' : number_format((float) $r->galyndy, 2) }}</td>

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
                                        <td colspan="24" class="text-center text-muted py-4">
                                            {{ __('pages/monthly_report.no_records') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.branch-checkbox');
            const selectedText = document.getElementById('branchSelectedText');
            const searchInput = document.getElementById('branchSearchInput');
            const options = document.querySelectorAll('.branch-option');

            function updateSelectedText() {
                const selected = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selected.length === 0) {
                    selectedText.textContent = 'All Branches';
                } else if (selected.length <= 2) {
                    selectedText.textContent = selected.join(', ');
                } else {
                    selectedText.textContent = selected.length + ' branches selected';
                }
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedText);
            });

            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const value = this.value.toLowerCase();

                    options.forEach(option => {
                        const text = option.textContent.toLowerCase();
                        option.style.display = text.includes(value) ? 'flex' : 'none';
                    });
                });
            }

            updateSelectedText();
        });
    </script>
@endsection
