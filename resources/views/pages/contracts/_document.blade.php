{{--
    The contract as it reads on paper, with its details written into the blanks.
    $form: the values; $editable: blanks are inputs; $branches;
    when editable: $action, $method, $submitLabel. $number: the contract's number, if it has one.

    Only the screen looks like this. The printed copy is always the bank-approved
    template with the details written onto it (ContractPdfService).
--}}
@php
    $editable = $editable ?? true;
    $val = function (string $key) use ($form) {
        $v = old($key, $form[$key] ?? '');

        return $v instanceof \Carbon\Carbon ? $v->toDateString() : $v;
    };
    $goods = old('goods', $form['goods'] ?? []);
    $history = old('history', $form['history'] ?? []);
    $historyKeys = ['contract', 'date', 'amount', 'paid', 'remaining', 'status', 'closed_at', 'duration', 'overdue'];
    $historyHeads = ['Şertnama №', 'Senesi', 'Möçberi', 'Tölenen', 'Galyndy', 'Ýagdaýy', 'Ýapylan senesi', 'Möhleti', 'Gijikdirme'];
    $branch = $val('branch') ?: (count($branches) === 1 ? $branches[0] : '');
    $months = ['', 'ýanwar', 'fewral', 'mart', 'aprel', 'maý', 'iýun', 'iýul', 'awgust', 'sentýabr', 'oktýabr', 'noýabr', 'dekabr'];
    $date = $val('contract_date') ? \Carbon\Carbon::parse($val('contract_date')) : null;

    // A blank of the paper: an input while editable, the written value otherwise.
    $blank = function (string $name, string $width, array $attrs = []) use ($editable, $val) {
        $value = (string) $val($name);
        if (! $editable) {
            return '<span class="ct-filled" style="min-width:' . $width . '">' . e($value !== '' ? $value : ' ') . '</span>';
        }
        $extra = '';
        foreach ($attrs as $k => $v) {
            $extra .= ' ' . $k . '="' . e($v) . '"';
        }

        return '<input type="text" name="' . e($name) . '" value="' . e($value) . '" class="ct-blank" style="width:' . $width . '"' . $extra . '>';
    };
@endphp

<style>
    .ct-paper { background: #fff; color: #000; font-family: "Times New Roman", Times, serif; font-size: 15px; line-height: 1.55;
        max-width: 860px; margin: 0 auto 1.5rem; padding: 48px 56px; box-shadow: 0 2px 14px rgba(0, 0, 0, .12); border-radius: 2px; }
    .ct-paper h6 { font-family: inherit; font-weight: 700; text-align: center; color: #000; font-size: 15px; margin: 14px 0 4px; }
    .ct-paper p { margin: 0 0 2px; text-align: justify; }
    .ct-blank, .ct-filled { font: inherit; color: #0b3d91; border: 0; border-bottom: 1px solid #000; background: #fffbe6;
        padding: 0 4px; margin: 0 2px; display: inline-block; vertical-align: baseline; }
    .ct-blank:focus { outline: none; background: #fff3c4; }
    .ct-filled { background: transparent; }
    textarea.ct-blank { display: block; width: 100%; resize: vertical; margin: 2px 0; }
    .ct-mirror { color: #0b3d91; }
    .ct-line { display: block; border-bottom: 1px solid #000; min-height: 1.5em; }
    .ct-table { width: 100%; border-collapse: collapse; margin: 6px 0; }
    .ct-table th, .ct-table td { border: 1px solid #000; padding: 2px 4px; font-size: 14px; }
    .ct-table th { text-align: center; font-weight: 400; }
    .ct-table input { width: 100%; border: 0; font: inherit; color: #0b3d91; background: #fffbe6; padding: 0 2px; }
    .ct-table .ct-filled { border: 0; min-width: 0; padding: 0; margin: 0; }
    .ct-history th { background: #eee; font-weight: 700; font-size: 12px; }
    .ct-history td, .ct-history input { font-size: 12px; }
    .ct-pagebreak { border-top: 2px dashed #bbb; margin: 28px -56px; }
    @media (max-width: 768px) { .ct-paper { padding: 20px 14px; font-size: 13px; } .ct-pagebreak { margin: 20px -14px; } }
</style>

@if ($editable)
<form method="POST" action="{{ $action }}" id="contractForm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <input type="hidden" name="credit_source_id" value="{{ $val('credit_source_id') }}">

    {{-- What the paper does not show --}}
    <div class="card">
        <div class="card-body d-flex flex-wrap align-items-end gap-3">
            <div>
                <label class="form-label mb-1">{{ __('pages/contracts.fields.branch') }}</label>
                <select name="branch" class="form-select" required style="min-width: 180px;">
                    <option value=""></option>
                    @foreach ($branches as $b)
                        <option value="{{ $b }}" @selected($branch === $b)>{{ $b }}</option>
                    @endforeach
                    @if ($branch !== '' && ! in_array($branch, $branches, true))
                        <option value="{{ $branch }}" selected>{{ $branch }}</option>
                    @endif
                </select>
            </div>
            <div class="text-muted small flex-grow-1">{{ __('pages/contracts.document_hint') }}</div>
            <button type="submit" class="btn btn-success">
                <i class="ri-save-line align-bottom me-1"></i> {{ $submitLabel }}
            </button>
        </div>
    </div>
@endif

<div class="ct-paper">
    {{-- Page 1 --}}
    <h6 style="margin-top:0">Bahasyny tölemegi gaýra goýmak bilen satyn almak-satmak<br>
        ŞERTNAMASY № <span class="ct-mirror">{{ $number ?? '' }}</span></h6>

    <p class="d-flex justify-content-between flex-wrap">
        <span>
            @if ($editable)
                <input type="date" name="contract_date" value="{{ $val('contract_date') }}" class="ct-blank" required>
            @else
                “<span class="ct-filled">{{ $date?->format('d') }}</span>”
                <span class="ct-filled" style="min-width:110px">{{ $date ? $months[$date->month] : '' }}</span>
                20<span class="ct-filled">{{ $date?->format('y') }}</span>-nji ýyl
            @endif
        </span>
        <span>Aşgabat şäheri.</span>
    </p>

    <p style="text-indent: 40px; margin-top: 8px;">
        Telekeçi <b>Gurbanmuhammedow Iskander Halmuhammedowiç,</b> tapgygy A №0088116 belgili patent,
        27.02.2024-nji ýylda berlen №0057883 belgili bellige alyş şahadatnamanyň esasynda hereket edýän, mundan beýläk “Satyjy”
        diýip atlandyrylýan, bir tarapdan, we raýat
        {!! $blank('customer_name', '340px', ['required' => 'required', 'maxlength' => '255']) !!}
        pasport {!! $blank('passport_series', '80px', ['maxlength' => '20', 'placeholder' => 'I-AŞ']) !!}
        № {!! $blank('passport_number', '110px', ['maxlength' => '30']) !!},
    </p>
    @if ($editable)
        <textarea name="address" rows="2" maxlength="1000" class="ct-blank"
            placeholder="{{ __('pages/contracts.fields.address') }}">{{ $val('address') }}</textarea>
    @else
        <span class="ct-line ct-mirror">{{ $val('address') }}</span>
    @endif
    <p>
        ýazgyda duran, telefon belgisi
        {!! $blank('phone', '260px', ['maxlength' => '100']) !!},
        mundan beýläk “Alyjy” diýip atlandyrylýan, beýleki tarapdan şu aşakdakylar barada şertnama baglaşdylar.
    </p>

    <h6>1. ŞERTNAMANYŇ MAZMUNY:</h6>
    <p>1.1.Şu şertnamany baglaşmak üçin taraplaryň özara ylalaşygy esas bolup durýar.</p>
    <p>1.2. “Alyjy” “Satyja” ylalaşylan nyrhy belli-belli wagtlarda bölek-bölekden tölemäge we satyn alnan emlägi kabul etmäge borçludyr.</p>
    <p>1.3. Şertnama baglaşylandan soň, taraplar harytlary kabul edip almak-bermek ykrarnamasyna gol çekýärler.</p>

    <h6>2. ŞERTNAMANY BAGLAŞMAGYŇ ESASY ŞERTLERI.</h6>
    <p>2.1. “Satyjy” “alyjynyň” günäsi bilen ýüze çykan we harydyň zaýalanmagyna getiren hereketler üçin jogapkärçilik çekmeýär.</p>
    <p>2.2. Şertnama “Alyjynyň” aşakdaky resminamalary beren ýagdaýynda bahasyny tölemegi gaýra goýmak bilen baglaşylýar:</p>
    <p>-“Alyjynyň” pasportynyň nusgasy;</p>

    <h6 style="font-weight:400">3. ÖZARA HASAPLAŞYKLARYŇ TERTIBI:</h6>
    <p>
        3.1. “Alyjy” tarapyndan satyn alynan harytlarynyň bahasyny tölemegi gaýra goýmak
        {!! $blank('months', '60px', ['inputmode' => 'numeric', 'maxlength' => '3']) !!}
        aýyň dowamynda, her aýda
        {!! $blank('monthly_amount', '100px', ['inputmode' => 'decimal', 'maxlength' => '30']) !!}
        manatdan amala aşyrylýar. Töleg “Satyjynyň” kassasyna nagt görnüşinde, nagt däl görnüşinde bolsa
        terminalyň üsti bilen – bank karty arkaly amala aşyrylýar.
    </p>
    <p>
        3.2. Harydyň bahasynyň
        {!! $blank('installment_part', '200px', ['maxlength' => '100']) !!}
        möçberindäki bölegi şu şertnama baglaşylandan soň, indiki aýdan başlap, bölekler boýunça her aý üçin tölegiň
        amala aşyrylýan şol senenama aýynyň şertnama gol çekilen gününden gijä galman tölenilmäge degişli.
    </p>

    <h6>4. ŞERTNAMANYŇ HEREKET EDIŞ MÖHLETI.</h6>
    <p>4.1. Şertnama deň hukuk güýjüne eýe bolan iki nusgada düzülip, taraplar harytlary sanap we hil taýdan barlap, gol çekilen senesinden güýje girýär we harydyň bahasy doly tölenip üzülýänçä hereket edýär.</p>

    <h6>5. GOŞMAÇA ŞERTLER.</h6>
    <p>5.1. “Alyjynyň” şu Şertnama boýunça öz hukuklaryny we borçlaryny üçünji taraplara geçirmäge hukugy ýok.</p>
    <p>5.2. “Alyjy” Harydyň bahasyny tölemegi gaýra goýup almak üçin bilkastlaýyn nädogry ýa-da täsirsiz, galp resminamalary bermegi üçin administratiw we jenaýat jogapkärçiligine çekilip bilner.</p>
    <p>5.3. “Satyjynyň” “Alyjy” harydyň bahasyny gaýra goýup tölemek babatda öz borçlaryny ýerine ýetirmän, bergisini wagtly-wagtynda (iki senenama aýyň dowamynda yzygiderli) üzmekden boýun gaçyran halatynda, degişli hukuk goraýjy edaralara arza bilen ýüz tutmaga hukugy bardyr.</p>
    <p>5.4. Salgylarynyň, telefon belgileriniň, bank maglumatlarynyň üýtgän ýagdaýynda, Taraplar bu barada bir-birine haýal etmän we ýazmaça görnüşde habar bermäge borçludyrlar. Şeýle habarnamalaryň bolmadyk ýagdaýynda şu Şertnamada görkezilen maglumatlar we salgylar boýunça iberilen duýduryşlar adresata gowşurylan hasaplanylýar.</p>

    <h6>6. JEDELLERI ÇÖZMEGIŇ TERTIBI.</h6>
    <p>6.1. Şu Şertnama ýerine ýetirilen mahaly ýüze çykýan jedeller taraplaryň özara gepleşikleri arkaly çözülýär.</p>
    <p>6.2. Eger “Alyjy” harydy yzyna tabşyrmak bilen şertnamany ýatyrmak islän ýagdaýynda alan möhletinden 1 günüň dowamynda yzyna getirmäge borçludyr.</p>
    <p>6.3. Eger, taraplaryň arasynda gepleşikler netijesinde ylalaşyk gazanylmadyk halatynda jedeller Türkmenistanyň kanunçylygyna laýyklykda degişli arbitraž kazyýetleri tarapyndan çözülýär.</p>

    <h6>7. ŞU ŞERTNAMA BOÝUNÇA HARYTLARYŇ SANAWY<br>WE NYRH HASAPLAŞYKLARYNYŇ TERTIBI:</h6>
    <h6>TARAPLARYŇ HUKUK SALGYLARY:</h6>
    <div class="row">
        <div class="col-sm-6">
            <b>SATYJY</b>:<br>
            Telekeçi: Gurbanmuhammedow Iskander Halmuhammedowiç<br>
            Bank rekwizitleri: PTB “Türkmen-Türk” Aşgabat ş.<br>
            H/h: 23206934173169208543000<br>
            K/h: 21101934110100500001000<br>
            S/k: 101 122 518 624<br>
            MFO: 390 101 731<br>
            Satyş hünärmeni: ______________________
        </div>
        <div class="col-sm-6">
            <div class="text-center"><b>ALYJY:</b></div>
            {{-- Written in by hand when signed --}}
            @for ($i = 0; $i < 6; $i++)
                <span class="ct-line"></span>
            @endfor
            <span class="ct-line">goly:</span>
        </div>
    </div>

    <div class="ct-pagebreak"></div>

    {{-- Page 2 --}}
    <div class="text-end">
        <span class="ct-filled ct-mirror" data-mirror="date" style="min-width:140px">{{ $date?->format('d.m.Y') }}</span> ýyldaky<br>
        № <span class="ct-filled ct-mirror" style="min-width:180px">{{ $number ?? '' }}</span><br>
        <b>Belgili Şertnamanyň №01 Goşundysy.</b>
    </div>
    <h6>HARYDY KABUL EDIP ALMAK-BERMEK<br>YKRARNAMASY</h6>
    <p>
        1. <span class="ct-filled ct-mirror" data-mirror="date" style="min-width:110px">{{ $date?->format('d.m.Y') }}</span> ýyldaky
        № <span class="ct-filled ct-mirror" style="min-width:150px">{{ $number ?? '' }}</span>
        belgili Taraplaryň arasynda baglaşylan Şertnamanyň 1.3. we 7 bendine laýyklykda “Satyjy” aşakdaky assortimentde
        we mukdarda Harytlary “Alyja” geçirýär, “Alyjy” bolsa kabul edip alýar:
    </p>
    <table class="ct-table">
        <thead>
            <tr><th style="width:40px">T/b</th><th>Harydyň ady</th><th style="width:90px">Mukdary</th><th style="width:130px">Biriniň bahasy</th><th style="width:130px">Umumy bahasy</th></tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < 7; $i++)
                <tr>
                    <td class="text-center">{{ ! $editable && empty($goods[$i]['name']) ? '' : $i + 1 }}</td>
                    @foreach (['name' => 100, 'quantity' => 20, 'price' => 30, 'total' => 30] as $k => $max)
                        <td>
                            @if ($editable)
                                <input type="text" name="goods[{{ $i }}][{{ $k }}]" maxlength="{{ $max }}" value="{{ $goods[$i][$k] ?? '' }}"
                                    @if ($k !== 'name') inputmode="decimal" @endif>
                            @else
                                <span class="ct-filled">{{ $goods[$i][$k] ?? '' }}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>
    <p>Şertnamanyň şertlerine laýyklykda geçirilen Harydyň bahasy ______________________ manat ________ teňňe.</p>
    <p>2. “Alyjy” tarapyndan kabul edilen haryt Şertnamanyň talaplaryna laýyk gelýän hile we assortimente eýedir. “Alyjy” kabul edilen harytlara hiç hili şikaýat bildirmedi.</p>
    <p>3. Şu Ykrarnama deň hukuk güýjüne eýe bolan iki nusgada düzüldi, Taraplaryň her biri üçin bir nusgada berilýär we Taraplaryň arasyndaky baglaşylan Şertnama goşulyp (Goşundy №1), onuň aýrylmaz bölegi bolup durýar.</p>
    <div class="row mt-2 mb-3">
        <div class="col-6">“Alyjy”<br>______________________</div>
        <div class="col-6">“Satyjy”<br>______________________</div>
    </div>

    {{-- The buyer's earlier credits --}}
    <div class="d-flex align-items-center mt-3">
        <b class="flex-grow-1">Alyjynyň öňki karzlary</b>
        @if ($editable)
            <button type="button" class="btn btn-sm btn-soft-primary" id="addHistoryRow">
                <i class="ri-add-line align-bottom"></i> {{ __('pages/contracts.add_row') }}
            </button>
        @endif
    </div>
    <div class="table-responsive">
        <table class="ct-table ct-history" id="historyTable">
            <thead>
                <tr>
                    <th style="width:28px">T/b</th>
                    @foreach ($historyHeads as $head)
                        <th>{{ $head }}</th>
                    @endforeach
                    @if ($editable)
                        <th style="width:34px"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse (array_values($history) as $i => $row)
                    <tr>
                        <td class="text-center js-row-no">{{ $i + 1 }}</td>
                        @foreach ($historyKeys as $k)
                            <td>
                                @if ($editable)
                                    <input type="text" name="history[{{ $i }}][{{ $k }}]" maxlength="50" value="{{ $row[$k] ?? '' }}">
                                @else
                                    {{ $row[$k] ?? '' }}
                                @endif
                            </td>
                        @endforeach
                        @if ($editable)
                            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 js-remove-row" title="{{ __('pages/contracts.remove_row') }}"><i class="ri-delete-bin-line"></i></button></td>
                        @endif
                    </tr>
                @empty
                    @unless ($editable)
                        <tr><td colspan="10">Öňki karz ýok.</td></tr>
                    @endunless
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($editable)
        <div class="text-muted small mt-1">{{ __('pages/contracts.history_hint') }}</div>
    @endif
</div>

@if ($editable)
    <div class="text-end mb-4">
        <button type="submit" class="btn btn-success">
            <i class="ri-save-line align-bottom me-1"></i> {{ $submitLabel }}
        </button>
    </div>
</form>

<template id="historyRowTemplate">
    <tr>
        <td class="text-center js-row-no"></td>
        @foreach ($historyKeys as $k)
            <td><input type="text" data-key="{{ $k }}" maxlength="50"></td>
        @endforeach
        <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 js-remove-row" title="{{ __('pages/contracts.remove_row') }}"><i class="ri-delete-bin-line"></i></button></td>
    </tr>
</template>

<script>
    (function () {
        const form = document.getElementById('contractForm');
        const body = document.querySelector('#historyTable tbody');
        const template = document.getElementById('historyRowTemplate');
        let next = body.rows.length;

        const renumber = function () {
            body.querySelectorAll('.js-row-no').forEach(function (cell, i) { cell.textContent = i + 1; });
        };

        document.getElementById('addHistoryRow').addEventListener('click', function () {
            const row = template.content.firstElementChild.cloneNode(true);
            row.querySelectorAll('input[data-key]').forEach(function (input) {
                input.name = 'history[' + next + '][' + input.dataset.key + ']';
            });
            next++;
            body.appendChild(row);
            renumber();
            row.querySelector('input').focus();
        });

        body.addEventListener('click', function (e) {
            const button = e.target.closest('.js-remove-row');
            if (button) {
                button.closest('tr').remove();
                renumber();
            }
        });

        // Page 2 repeats the contract's date.
        const date = form.querySelector('[name="contract_date"]');
        const mirror = function () {
            const shown = date.value ? date.value.split('-').reverse().join('.') : '';
            form.querySelectorAll('[data-mirror="date"]').forEach(function (el) { el.textContent = shown; });
        };
        date.addEventListener('input', mirror);
        mirror();
    })();
</script>
@endif
