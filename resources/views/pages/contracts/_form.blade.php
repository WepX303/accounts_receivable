{{--
    The contract form, for a new contract and for changing a draft.
    $form: the values to start from; $action, $method, $submitLabel; $branches.
--}}
@php
    $val = fn (string $key) => old($key, $form[$key] ?? '');
    $goods = old('goods', $form['goods'] ?? []);
    $history = old('history', $form['history'] ?? []);
    $historyKeys = ['contract', 'date', 'amount', 'paid', 'remaining', 'status', 'closed_at', 'duration', 'overdue'];
    $branch = $val('branch') ?: (count($branches) === 1 ? $branches[0] : '');
@endphp

<form method="POST" action="{{ $action }}" id="contractForm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <input type="hidden" name="credit_source_id" value="{{ $val('credit_source_id') }}">

    {{-- Buyer --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('pages/contracts.section_buyer') }}</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('pages/contracts.fields.branch') }}</label>
                    <select name="branch" class="form-select" required>
                        <option value=""></option>
                        @foreach ($branches as $b)
                            <option value="{{ $b }}" @selected($branch === $b)>{{ $b }}</option>
                        @endforeach
                        @if ($branch !== '' && ! in_array($branch, $branches, true))
                            <option value="{{ $branch }}" selected>{{ $branch }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('pages/contracts.fields.contract_date') }}</label>
                    <input type="date" name="contract_date" class="form-control" required
                        value="{{ $val('contract_date') instanceof \Carbon\Carbon ? $val('contract_date')->toDateString() : $val('contract_date') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('pages/contracts.fields.customer_name') }}</label>
                    <input type="text" name="customer_name" class="form-control" maxlength="255" required
                        value="{{ $val('customer_name') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('pages/contracts.fields.passport_series') }}</label>
                    <input type="text" name="passport_series" class="form-control" maxlength="20" placeholder="I-AŞ"
                        value="{{ $val('passport_series') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('pages/contracts.fields.passport_number') }}</label>
                    <input type="text" name="passport_number" class="form-control" maxlength="30"
                        value="{{ $val('passport_number') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('pages/contracts.fields.phone') }}</label>
                    <input type="text" name="phone" class="form-control" maxlength="100" value="{{ $val('phone') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('pages/contracts.fields.address') }}</label>
                    <textarea name="address" class="form-control" rows="2" maxlength="1000">{{ $val('address') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3 --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('pages/contracts.section_terms') }}</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('pages/contracts.fields.months') }}</label>
                    <input type="number" name="months" class="form-control" min="1" max="120" value="{{ $val('months') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('pages/contracts.fields.monthly_amount') }}</label>
                    <input type="text" name="monthly_amount" class="form-control" inputmode="decimal" maxlength="30"
                        value="{{ $val('monthly_amount') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('pages/contracts.fields.installment_part') }}</label>
                    <input type="text" name="installment_part" class="form-control" maxlength="100"
                        value="{{ $val('installment_part') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Goods: the seven rows of page 2 --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('pages/contracts.section_goods') }}</h5>
            <p class="text-muted small mb-0">{{ __('pages/contracts.goods_hint') }}</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>{{ __('pages/contracts.fields.goods_name') }}</th>
                            <th style="width: 110px;">{{ __('pages/contracts.fields.quantity') }}</th>
                            <th style="width: 150px;">{{ __('pages/contracts.fields.price') }}</th>
                            <th style="width: 150px;">{{ __('pages/contracts.fields.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 7; $i++)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td><input type="text" name="goods[{{ $i }}][name]" class="form-control form-control-sm" maxlength="100" value="{{ $goods[$i]['name'] ?? '' }}"></td>
                                <td><input type="text" name="goods[{{ $i }}][quantity]" class="form-control form-control-sm" maxlength="20" value="{{ $goods[$i]['quantity'] ?? '' }}"></td>
                                <td><input type="text" name="goods[{{ $i }}][price]" class="form-control form-control-sm" inputmode="decimal" maxlength="30" value="{{ $goods[$i]['price'] ?? '' }}"></td>
                                <td><input type="text" name="goods[{{ $i }}][total]" class="form-control form-control-sm" inputmode="decimal" maxlength="30" value="{{ $goods[$i]['total'] ?? '' }}"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Earlier credits --}}
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <div class="flex-grow-1">
                <h5 class="card-title mb-0">{{ __('pages/contracts.section_history') }}</h5>
                <p class="text-muted small mb-0">{{ __('pages/contracts.history_hint') }}</p>
            </div>
            <button type="button" class="btn btn-sm btn-soft-primary" id="addHistoryRow">
                <i class="ri-add-line align-bottom"></i> {{ __('pages/contracts.add_row') }}
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="historyTable">
                    <thead class="table-light">
                        <tr>
                            @foreach ($historyKeys as $k)
                                <th>{{ __('pages/contracts.history.' . $k) }}</th>
                            @endforeach
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (array_values($history) as $i => $row)
                            <tr>
                                @foreach ($historyKeys as $k)
                                    <td><input type="text" name="history[{{ $i }}][{{ $k }}]" class="form-control form-control-sm" maxlength="50" value="{{ $row[$k] ?? '' }}"></td>
                                @endforeach
                                <td><button type="button" class="btn btn-sm btn-soft-danger js-remove-row" title="{{ __('pages/contracts.remove_row') }}"><i class="ri-delete-bin-line"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-end mb-4">
        <button type="submit" class="btn btn-success">
            <i class="ri-save-line align-bottom me-1"></i> {{ $submitLabel }}
        </button>
    </div>
</form>

<template id="historyRowTemplate">
    <tr>
        @foreach ($historyKeys as $k)
            <td><input type="text" data-key="{{ $k }}" class="form-control form-control-sm" maxlength="50"></td>
        @endforeach
        <td><button type="button" class="btn btn-sm btn-soft-danger js-remove-row" title="{{ __('pages/contracts.remove_row') }}"><i class="ri-delete-bin-line"></i></button></td>
    </tr>
</template>

<script>
    (function () {
        const body = document.querySelector('#historyTable tbody');
        const template = document.getElementById('historyRowTemplate');
        let next = body.rows.length;

        document.getElementById('addHistoryRow').addEventListener('click', function () {
            const row = template.content.firstElementChild.cloneNode(true);
            row.querySelectorAll('input[data-key]').forEach(function (input) {
                input.name = 'history[' + next + '][' + input.dataset.key + ']';
            });
            next++;
            body.appendChild(row);
            row.querySelector('input').focus();
        });

        body.addEventListener('click', function (e) {
            const button = e.target.closest('.js-remove-row');
            if (button) {
                button.closest('tr').remove();
            }
        });
    })();
</script>
