@php
    $t = fn (string $key, array $r = []) => __('emails/daily_payment_report.' . $key, $r);

    $meta = $report['meta'];
    $s = $report['summary'];
    $cmp = $report['comparison'];
    $audit = $report['audit'];
    $sch = $report['schedule'];
    $pf = $report['portfolio'];
    $cur = $meta['currency'];

    $money = fn ($v) => number_format((float) $v, 2);
    $int = fn ($v) => number_format((int) $v);
    $pct = fn ($v) => number_format((float) $v, 1) . '%';

    // Shared inline styles — email clients need these repeated on every element.
    $cell = 'padding:10px 14px; border:1px solid #e5e7eb; font-size:14px; line-height:20px; color:#111827;';
    $cellR = $cell . ' text-align:right; white-space:nowrap;';
    $th = 'padding:10px 14px; border:1px solid #e5e7eb; background-color:#f8fafc; font-size:13px; line-height:18px; font-weight:700; color:#374151; text-align:left;';
    $thR = $th . ' text-align:right;';
    $label = $cell . ' background-color:#f9fafb; font-weight:600;';
    $table = 'width:100%; border-collapse:collapse; border:1px solid #e5e7eb; margin-bottom:8px;';
    $h2 = 'margin:32px 0 6px 0; font-size:17px; line-height:24px; color:#0f172a; font-weight:700;';
    $desc = 'margin:0 0 12px 0; font-size:13px; line-height:19px; color:#6b7280;';
    $totalCell = 'padding:10px 14px; border:1px solid #e5e7eb; background-color:#f1f5f9; font-size:14px; line-height:20px; font-weight:700; color:#0f172a;';
    $totalCellR = $totalCell . ' text-align:right; white-space:nowrap;';
    $sub = 'display:block; font-size:11px; line-height:15px; font-weight:400; color:#9ca3af;';

    // Label/value card used where a wide grid would not fit a phone.
    $cardHead = 'padding:10px 14px; font-size:15px; line-height:22px; font-weight:700;';
    $cardKey = 'padding:9px 14px; border-top:1px solid #e5e7eb; font-size:13px; line-height:19px; color:#4b5563;';
    $cardVal = 'padding:9px 14px; border-top:1px solid #e5e7eb; font-size:14px; line-height:19px; color:#111827; text-align:right; white-space:nowrap; font-weight:600;';

    $hasPayments = $s['tx_count'] > 0;
    $auditCount = $audit['voids']['count'] + $audit['corrections']['count'] + $audit['backdated']['count'];
    $topBranch = $report['branches'][0] ?? null;
    $trendMax = max(array_map(fn ($d) => $d['net'], $report['trend'])) ?: 1;
    $trendHasData = collect($report['trend'])->sum('net') > 0;

    $methodLabel = function (string $m) use ($t) {
        return in_array($m, ['cash', 'card', 'phone', 'mixed', 'unknown'], true)
            ? $t('methods.' . $m)
            : $m;
    };

    // Green when the number moved the right way, red when it did not.
    $delta = function (?float $v) use ($pct) {
        if ($v === null) {
            return '<span style="color:#9ca3af;">—</span>';
        }
        $color = $v > 0.05 ? '#047857' : ($v < -0.05 ? '#b91c1c' : '#6b7280');
        $sign = $v > 0.05 ? '▲ +' : ($v < -0.05 ? '▼ ' : '');

        return '<span style="color:' . $color . '; font-weight:600;">' . $sign . $pct($v) . '</span>';
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $t('header.title') }} — {{ $meta['date_label'] }}</title>
    <style>
        /* Clients that keep <style> get tighter tables on a phone. The layout
           below is built to fit without this too, for the ones that strip it. */
        @media only screen and (max-width: 600px) {
            .wrap { padding: 12px 6px !important; }
            .card { border-radius: 0 !important; }
            .pad { padding: 18px 14px !important; }
            table td, table th { padding: 8px 8px !important; font-size: 13px !important; }
            .sub { font-size: 11px !important; }
            .hero-amount { font-size: 26px !important; }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">

    <div class="wrap" style="width:100%; background-color:#f3f4f6; padding:28px 12px;">
        <div class="card" style="max-width:860px; margin:0 auto; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">

            {{-- ============================ HEADER ============================ --}}
            <div style="background-color:#0f172a; padding:26px 30px;">
                <h1 style="margin:0; font-size:23px; line-height:30px; color:#ffffff; font-weight:700;">
                    {{ $t('header.title') }}
                </h1>
                <p style="margin:6px 0 0 0; font-size:15px; line-height:22px; color:#93c5fd; font-weight:600;">
                    {{ $meta['date_label'] }} · {{ $t('weekdays.' . $meta['weekday']) }}
                </p>
                <p style="margin:10px 0 0 0; font-size:13px; line-height:19px; color:#cbd5e1;">
                    {{ $t('header.subtitle') }}
                </p>
                <p style="margin:6px 0 0 0; font-size:13px; line-height:19px; color:#cbd5e1;">
                    {{ $t('meta.branches') }}:
                    <strong style="color:#ffffff;">
                        {{ empty($meta['branches']) ? $t('meta.all_branches') : implode(', ', $meta['branches']) }}
                    </strong>
                </p>
                <p style="margin:8px 0 0 0; font-size:12px; line-height:18px; color:#fcd34d;">
                    {{ $t('header.auto_notice') }}
                </p>
            </div>

            <div class="pad" style="padding:26px 30px 32px 30px;">

                {{-- ============================= HERO ============================= --}}
                <table role="presentation" style="width:100%; border-collapse:collapse; background-color:#0b3b2e; border-radius:8px; margin-bottom:22px;">
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="margin:0; font-size:13px; line-height:18px; color:#a7f3d0; text-transform:uppercase; letter-spacing:0.5px;">
                                {{ $t('hero.net_collected') }}
                            </p>
                            <p class="hero-amount" style="margin:6px 0 0 0; font-size:34px; line-height:42px; color:#ffffff; font-weight:700;">
                                {{ $money($s['net']) }} <span style="font-size:20px;">{{ $cur }}</span>
                            </p>
                            <p style="margin:8px 0 0 0; font-size:14px; line-height:20px; color:#d1fae5;">
                                {{ $int($s['tx_count']) }} {{ $t('hero.transactions') }}
                                &nbsp;·&nbsp;
                                {{ $int($s['customer_count']) }} {{ $t('hero.customers') }}
                            </p>
                        </td>
                        <td style="padding:22px 24px; text-align:right; vertical-align:bottom;">
                            <p style="margin:0; font-size:12px; line-height:18px; color:#a7f3d0;">
                                {{ $t('comparison.yesterday') }}
                            </p>
                            <p style="margin:4px 0 0 0; font-size:18px; line-height:24px; color:#ffffff; font-weight:700;">
                                @if ($cmp['yesterday']['net_delta_pct'] === null)
                                    —
                                @else
                                    {{ ($cmp['yesterday']['net_delta_pct'] > 0 ? '+' : '') . $pct($cmp['yesterday']['net_delta_pct']) }}
                                @endif
                            </p>
                            <p style="margin:4px 0 0 0; font-size:12px; line-height:18px; color:#d1fae5;">
                                {{ $money($cmp['yesterday']['net']) }} {{ $cur }}
                            </p>
                        </td>
                    </tr>
                </table>

                {{-- ========================== HIGHLIGHTS ========================== --}}
                <h2 style="{{ $h2 }} margin-top:0;">{{ $t('highlights.title') }}</h2>
                <table role="presentation" style="width:100%; border-collapse:collapse; border:1px solid #e5e7eb; background-color:#f9fafb; margin-bottom:8px;">
                    <tr>
                        <td style="padding:14px 18px;">
                            <ul style="margin:0; padding-left:18px; font-size:14px; line-height:23px; color:#111827;">
                                @if ($hasPayments)
                                    <li>{!! $t('highlights.collected', [
                                        'count' => $int($s['tx_count']),
                                        'customers' => $int($s['customer_count']),
                                        'net' => '<strong>' . $money($s['net']) . '</strong>',
                                        'currency' => $cur,
                                    ]) !!}</li>
                                @else
                                    <li style="color:#b45309;">{{ $t('highlights.no_payments') }}</li>
                                @endif

                                @php $yd = $cmp['yesterday']['net_delta_pct']; @endphp
                                @if ($yd === null)
                                    <li>{{ $t('highlights.no_yesterday') }}</li>
                                @elseif ($yd > 0.05)
                                    <li>{{ $t('highlights.vs_yesterday_up', ['pct' => number_format($yd, 1), 'prev' => $money($cmp['yesterday']['net']), 'currency' => $cur]) }}</li>
                                @elseif ($yd < -0.05)
                                    <li>{{ $t('highlights.vs_yesterday_down', ['pct' => number_format(abs($yd), 1), 'prev' => $money($cmp['yesterday']['net']), 'currency' => $cur]) }}</li>
                                @else
                                    <li>{{ $t('highlights.vs_yesterday_flat', ['prev' => $money($cmp['yesterday']['net']), 'currency' => $cur]) }}</li>
                                @endif

                                @if ($topBranch)
                                    <li>{{ $t('highlights.top_branch', [
                                        'branch' => $topBranch['branch'],
                                        'net' => $money($topBranch['net']),
                                        'currency' => $cur,
                                        'share' => number_format($topBranch['share'], 1),
                                    ]) }}</li>
                                @endif

                                @if ($auditCount > 0)
                                    <li style="color:#b91c1c; font-weight:600;">{{ $t('highlights.audit_events', [
                                        'voids' => $audit['voids']['count'],
                                        'corrections' => $audit['corrections']['count'],
                                        'backdated' => $audit['backdated']['count'],
                                    ]) }}</li>
                                @else
                                    <li style="color:#047857;">{{ $t('highlights.audit_clean') }}</li>
                                @endif

                            </ul>
                        </td>
                    </tr>
                </table>

                {{-- ========================= CASH SUMMARY ========================= --}}
                <h2 style="{{ $h2 }}">{{ $t('cash.title') }}</h2>
                <table role="presentation" style="{{ $table }}">
                    <tr>
                        <td style="{{ $label }} width:60%;">{{ $t('cash.gross') }}</td>
                        <td style="{{ $cellR }}">{{ $money($s['gross']) }} {{ $cur }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('cash.change') }}</td>
                        <td style="{{ $cellR }} color:#b45309;">− {{ $money($s['change']) }} {{ $cur }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }} background-color:#ecfdf5;">
                            {{ $t('cash.net') }}
                            <span style="display:block; font-size:12px; line-height:17px; font-weight:400; color:#6b7280;">{{ $t('cash.net_hint') }}</span>
                        </td>
                        <td style="{{ $cellR }} background-color:#ecfdf5; font-size:16px; font-weight:700; color:#047857;">
                            {{ $money($s['net']) }} {{ $cur }}
                        </td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('cash.cash') }}</td>
                        <td style="{{ $cellR }}">{{ $money($s['cash']) }} {{ $cur }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('cash.card') }}</td>
                        <td style="{{ $cellR }}">{{ $money($s['card']) }} {{ $cur }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('cash.phone') }}</td>
                        <td style="{{ $cellR }}">{{ $money($s['phone']) }} {{ $cur }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('cash.avg_ticket') }}</td>
                        <td style="{{ $cellR }}">{{ $money($s['avg_ticket']) }} {{ $cur }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('cash.max_ticket') }}</td>
                        <td style="{{ $cellR }}">{{ $money($s['max_ticket']) }} {{ $cur }}</td>
                    </tr>
                </table>

                {{-- ========================== COMPARISON ========================== --}}
                <h2 style="{{ $h2 }}">{{ $t('comparison.title') }}</h2>
                <table role="presentation" style="{{ $table }}">
                    <thead>
                        <tr>
                            <th style="{{ $th }}">{{ $t('comparison.period') }}</th>
                            <th style="{{ $thR }}">{{ $t('comparison.amount') }}</th>
                            <th style="{{ $thR }}">{{ $t('comparison.change') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Single days, all measured against today. --}}
                        <tr>
                            <td colspan="3" style="{{ $th }} font-size:12px; text-transform:uppercase; letter-spacing:0.4px;">
                                {{ $t('comparison.day_group') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }} font-weight:700;">{{ $t('comparison.today') }}</td>
                            <td style="{{ $cellR }} font-weight:700;">{{ $money($cmp['today']['net']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }} color:#9ca3af;">—</td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }}">{{ $t('comparison.yesterday') }}</td>
                            <td style="{{ $cellR }}">{{ $money($cmp['yesterday']['net']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }}">{!! $delta($cmp['yesterday']['net_delta_pct']) !!}</td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }}">{{ $t('comparison.avg7') }}</td>
                            <td style="{{ $cellR }}">{{ $money($cmp['avg7']['net']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }}">{!! $delta($cmp['avg7']['net_delta_pct']) !!}</td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }}">{{ $t('comparison.prev_month_day', ['label' => $cmp['prev_month_day']['label']]) }}</td>
                            <td style="{{ $cellR }}">{{ $money($cmp['prev_month_day']['net']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }}">{!! $delta($cmp['prev_month_day']['net_delta_pct']) !!}</td>
                        </tr>

                        {{-- Running totals, measured against month-to-date. --}}
                        <tr>
                            <td colspan="3" style="{{ $th }} font-size:12px; text-transform:uppercase; letter-spacing:0.4px;">
                                {{ $t('comparison.period_group') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }} font-weight:700;">{{ $t('comparison.mtd', ['days' => $cmp['mtd']['days_elapsed']]) }}</td>
                            <td style="{{ $cellR }} font-weight:700;">{{ $money($cmp['mtd']['net']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }} color:#6b7280;">{{ $int($cmp['mtd']['tx_count']) }} {{ $t('hero.transactions') }}</td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }}">{{ $t('comparison.prev_mtd', ['label' => $cmp['prev_mtd']['label']]) }}</td>
                            <td style="{{ $cellR }}">{{ $money($cmp['prev_mtd']['net']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }}">{!! $delta($cmp['prev_mtd']['net_delta_pct']) !!}</td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }}">{{ $t('comparison.mtd_daily_avg') }}</td>
                            <td style="{{ $cellR }}">{{ $money($cmp['mtd']['daily_avg']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }} color:#9ca3af;">—</td>
                        </tr>
                    </tbody>
                </table>
                <p style="{{ $desc }}">{{ $t('comparison.legend') }}</p>

                {{-- ============================ TREND ============================ --}}
                <h2 style="{{ $h2 }}">{{ $t('trend.title', ['days' => count($report['trend'])]) }}</h2>
                @if ($trendHasData)
                    <table role="presentation" style="width:100%; border-collapse:collapse; border:1px solid #e5e7eb; margin-bottom:8px;">
                        @foreach ($report['trend'] as $d)
                            @php
                                $w = $trendMax > 0 ? max(1, (int) round($d['net'] / $trendMax * 100)) : 1;
                                $isDay = $d['is_report_day'];
                                $barColor = $isDay ? '#047857' : '#93c5fd';
                                $rowBg = $isDay ? '#ecfdf5' : '#ffffff';
                            @endphp
                            <tr>
                                <td style="padding:5px 12px; background-color:{{ $rowBg }}; font-size:12px; line-height:18px; color:#374151; white-space:nowrap; width:62px; {{ $isDay ? 'font-weight:700;' : '' }}">
                                    {{ $d['label'] }}
                                </td>
                                <td style="padding:5px 8px; background-color:{{ $rowBg }};">
                                    @if ($d['net'] > 0)
                                        <table role="presentation" style="border-collapse:collapse; width:{{ $w }}%;">
                                            <tr>
                                                <td style="background-color:{{ $barColor }}; height:14px; border-radius:3px; font-size:1px; line-height:14px;">&nbsp;</td>
                                            </tr>
                                        </table>
                                    @else
                                        <span style="font-size:11px; line-height:14px; color:#d1d5db;">·</span>
                                    @endif
                                </td>
                                <td style="padding:5px 12px; background-color:{{ $rowBg }}; font-size:12px; line-height:18px; color:#111827; text-align:right; white-space:nowrap; width:130px; {{ $isDay ? 'font-weight:700;' : '' }}">
                                    {{ $money($d['net']) }}
                                </td>
                                <td style="padding:5px 12px; background-color:{{ $rowBg }}; font-size:12px; line-height:18px; color:#6b7280; text-align:right; white-space:nowrap; width:46px;">
                                    {{ $d['tx_count'] }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <p style="{{ $desc }}">{{ $t('trend.no_data') }}</p>
                @endif

                {{-- =========================== METHODS =========================== --}}
                <h2 style="{{ $h2 }}">{{ $t('methods.title', ['date' => $meta['date_label']]) }}</h2>
                <table role="presentation" style="{{ $table }}">
                    <thead>
                        <tr>
                            <th style="{{ $th }}">{{ $t('table.method') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.count') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.net') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.share') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['methods'] as $m)
                            <tr>
                                <td style="{{ $cell }}">{{ $methodLabel($m['method']) }}</td>
                                <td style="{{ $cellR }}">{{ $int($m['tx_count']) }}</td>
                                <td style="{{ $cellR }}">{{ $money($m['net']) }} {{ $cur }}</td>
                                <td style="{{ $cellR }} color:#6b7280;">{{ $pct($m['share']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="{{ $cell }} text-align:center; color:#6b7280;">{{ $t('table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- =========================== BRANCHES =========================== --}}
                <h2 style="{{ $h2 }}">{{ $t('branches.title') }}</h2>

                @php
                    $branchRows = function (array $b) use ($t, $money, $int, $pct, $cur) {
                        return [
                            [$t('table.count'), $int($b['tx_count']), ''],
                            [$t('table.gross'), $money($b['gross']) . ' ' . $cur, 'color:#6b7280;'],
                            [$t('table.change'), '− ' . $money($b['change']) . ' ' . $cur, 'color:#b45309;'],
                            [$t('table.net'), $money($b['net']) . ' ' . $cur, 'font-size:15px; color:#047857;'],
                            [$t('table.cash'), $money($b['cash']) . ' ' . $cur, ''],
                            [$t('table.card'), $money($b['card']) . ' ' . $cur, ''],
                            [$t('table.phone'), $money($b['phone']) . ' ' . $cur, ''],
                            [$t('table.share'), $pct($b['share']), 'color:#6b7280;'],
                        ];
                    };

                    $branchCards = array_map(fn ($b) => [$b['branch'], $branchRows($b), false], $report['branches']);

                    if (count($report['branches']) > 0) {
                        $branchCards[] = [$t('table.total'), $branchRows($s + ['share' => 100.0]), true];
                    }
                @endphp

                @forelse ($branchCards as [$name, $rows, $isTotal])
                    <table role="presentation"
                        style="width:100%; border-collapse:collapse; border:1px solid {{ $isTotal ? '#0f172a' : '#e5e7eb' }}; margin-bottom:14px;">
                        <tr>
                            <td colspan="2"
                                style="{{ $cardHead }} background-color:{{ $isTotal ? '#0f172a' : '#f8fafc' }}; color:{{ $isTotal ? '#ffffff' : '#0f172a' }};">
                                {{ $name }}
                            </td>
                        </tr>
                        @foreach ($rows as [$rowLabel, $rowValue, $rowStyle])
                            <tr>
                                <td style="{{ $cardKey }} width:58%;">{{ $rowLabel }}</td>
                                <td style="{{ $cardVal }} {{ $rowStyle }}">{{ $rowValue }}</td>
                            </tr>
                        @endforeach
                    </table>
                @empty
                    <p style="{{ $desc }}">{{ $t('table.empty') }}</p>
                @endforelse

                {{-- =========================== CASHIERS =========================== --}}
                <h2 style="{{ $h2 }}">{{ $t('cashiers.title') }}</h2>
                <table role="presentation" style="{{ $table }}">
                    <thead>
                        <tr>
                            <th style="{{ $th }}">{{ $t('table.cashier') }}</th>
                            <th style="{{ $th }}">{{ $t('table.branch') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.count') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.net') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['cashiers'] as $c)
                            <tr>
                                <td style="{{ $cell }}">{{ $c['cashier'] }}</td>
                                <td style="{{ $cell }} color:#6b7280;">{{ $c['branch'] }}</td>
                                <td style="{{ $cellR }}">{{ $int($c['tx_count']) }}</td>
                                <td style="{{ $cellR }} font-weight:600;">{{ $money($c['net']) }} {{ $cur }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="{{ $cell }} text-align:center; color:#6b7280;">{{ $t('table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- ========================= TOP PAYMENTS ========================= --}}
                @if (count($report['top_payments']) > 0)
                    <h2 style="{{ $h2 }}">{{ $t('top.title', ['count' => count($report['top_payments'])]) }}</h2>
                    <table role="presentation" style="{{ $table }}">
                        <thead>
                            <tr>
                                <th style="{{ $th }}">{{ $t('table.customer') }}</th>
                                <th style="{{ $th }}">{{ $t('table.method') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.net') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['top_payments'] as $p)
                                <tr>
                                    <td style="{{ $cell }}">
                                        {{ $p['customer'] }}
                                        <span class="sub" style="{{ $sub }}">{{ $p['contract'] }} · {{ $p['branch'] }}</span>
                                    </td>
                                    <td style="{{ $cell }} color:#6b7280;">
                                        {{ $methodLabel($p['method']) }}
                                        <span class="sub" style="{{ $sub }}">{{ $p['time'] }}</span>
                                    </td>
                                    <td style="{{ $cellR }} font-weight:700;">{{ $money($p['net']) }} {{ $cur }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- =========================== SCHEDULE =========================== --}}
                <h2 style="{{ $h2 }}">{{ $t('schedule.title') }}</h2>
                <p style="{{ $desc }}">{{ $t('schedule.desc') }}</p>

                @php
                    $scheduleCards = [
                        [$t('schedule.due_title'), true, [
                            [$t('schedule.due_credits'), $int($sch['due']['credit_count']), ''],
                            [$t('schedule.due_expected'), $money($sch['due']['expected']) . ' ' . $cur, 'color:#6b7280;'],
                            [$t('schedule.due_paid_credits'), $int($sch['due']['paid_credit_count'])
                                . '  (' . $t('schedule.payments_suffix', ['count' => $int($sch['due']['paid_payment_count'])]) . ')', 'color:#047857;'],
                            [$t('schedule.due_paid_amount'), $money($sch['due']['paid_amount']) . ' ' . $cur, 'font-size:15px; color:#047857;'],
                            [$t('schedule.due_unpaid_credits'), $int($sch['due']['unpaid_credit_count']), 'color:#b91c1c;'],
                            [$t('schedule.due_missing'), $money($sch['due']['missing']) . ' ' . $cur, 'font-size:15px; color:#b91c1c;'],
                            [$t('schedule.due_rate'), $pct($sch['due']['rate']), ''],
                        ]],
                        [$t('schedule.not_due_title'), false, [
                            [$t('schedule.not_due_credits'), $int($sch['not_due']['credit_count']), ''],
                            [$t('schedule.not_due_payments'), $int($sch['not_due']['payment_count']), ''],
                            [$t('schedule.not_due_amount'), $money($sch['not_due']['amount']) . ' ' . $cur, 'font-size:15px;'],
                            [$t('schedule.early_title'), $int($sch['not_due']['early']['credit_count'])
                                . '  (' . $money($sch['not_due']['early']['amount']) . ' ' . $cur . ')', 'color:#047857;'],
                            [$t('schedule.late_title'), $int($sch['not_due']['late']['credit_count'])
                                . '  (' . $money($sch['not_due']['late']['amount']) . ' ' . $cur . ')', 'color:#b45309;'],
                        ]],
                    ];
                @endphp

                @foreach ($scheduleCards as [$cardName, $isPrimary, $rows])
                    <table role="presentation"
                        style="width:100%; border-collapse:collapse; border:1px solid {{ $isPrimary ? '#0f172a' : '#e5e7eb' }}; margin-bottom:14px;">
                        <tr>
                            <td colspan="2"
                                style="{{ $cardHead }} background-color:{{ $isPrimary ? '#0f172a' : '#f8fafc' }}; color:{{ $isPrimary ? '#ffffff' : '#0f172a' }};">
                                {{ $cardName }}
                            </td>
                        </tr>
                        @foreach ($rows as [$rowLabel, $rowValue, $rowStyle])
                            <tr>
                                <td style="{{ $cardKey }} width:58%;">{{ $rowLabel }}</td>
                                <td style="{{ $cardVal }} {{ $rowStyle }}">{{ $rowValue }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endforeach

                <p style="{{ $desc }}">
                    {{ $t('schedule.not_due_desc') }}
                    {{ $t('schedule.early_desc') }}
                    {{ $t('schedule.late_desc') }}
                </p>

                {{-- ============================ AUDIT ============================ --}}
                <h2 style="{{ $h2 }}">{{ $t('audit.title') }}</h2>

                @if ($auditCount === 0 && count($audit['idle_branches']) === 0)
                    <table role="presentation" style="width:100%; border-collapse:collapse; border:1px solid #a7f3d0; background-color:#ecfdf5; margin-bottom:8px;">
                        <tr>
                            <td style="padding:14px 18px; font-size:14px; line-height:21px; color:#065f46;">
                                ✓ {{ $t('audit.all_clear') }}
                            </td>
                        </tr>
                    </table>
                @endif

                {{-- Voids --}}
                @if ($audit['voids']['count'] > 0)
                    <h3 style="margin:20px 0 4px 0; font-size:15px; line-height:22px; color:#b91c1c; font-weight:700;">
                        {{ $t('audit.voids_title') }} —
                        {{ $t('audit.voids_summary', ['count' => $audit['voids']['count'], 'amount' => $money($audit['voids']['amount']), 'currency' => $cur]) }}
                    </h3>
                    <p style="{{ $desc }}">{{ $t('audit.voids_desc') }}</p>
                    <table role="presentation" style="{{ $table }}">
                        <thead>
                            <tr>
                                <th style="{{ $th }}">{{ $t('table.customer') }}</th>
                                <th style="{{ $th }}">{{ $t('table.payment_at') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.amount') }}</th>
                                <th style="{{ $th }}">{{ $t('table.actor') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($audit['voids']['rows'] as $r)
                                <tr>
                                    <td style="{{ $cell }}">
                                        {{ $r['customer'] }}
                                        <span class="sub" style="{{ $sub }}">{{ $r['contract'] }} · {{ $r['branch'] }}</span>
                                    </td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $r['payment_at'] }}</td>
                                    <td style="{{ $cellR }} color:#b91c1c; font-weight:700;">− {{ $money($r['net']) }}</td>
                                    <td style="{{ $cell }}">
                                        {{ $r['actor'] }}
                                        <span class="sub" style="{{ $sub }}">{{ $r['reason'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            @if ($audit['voids']['count'] > count($audit['voids']['rows']))
                                <tr>
                                    <td colspan="4" style="{{ $cell }} color:#6b7280; font-style:italic;">
                                        {{ $t('table.more', ['count' => $audit['voids']['count'] - count($audit['voids']['rows'])]) }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                @endif

                {{-- Corrections --}}
                @if ($audit['corrections']['count'] > 0)
                    <h3 style="margin:20px 0 4px 0; font-size:15px; line-height:22px; color:#b45309; font-weight:700;">
                        {{ $t('audit.corrections_title') }} —
                        {{ $t('audit.corrections_summary', ['count' => $audit['corrections']['count'], 'diff' => ($audit['corrections']['diff'] > 0 ? '+' : '') . $money($audit['corrections']['diff']), 'currency' => $cur]) }}
                    </h3>
                    <p style="{{ $desc }}">
                        {{ $t('audit.corrections_desc') }}
                        @if ($audit['corrections']['date_moved_count'] > 0)
                            <span style="color:#b45309; font-weight:600;">{{ $t('audit.corrections_date_moved', ['count' => $audit['corrections']['date_moved_count']]) }}</span>
                        @endif
                    </p>
                    <table role="presentation" style="{{ $table }}">
                        <thead>
                            <tr>
                                <th style="{{ $th }}">{{ $t('table.customer') }}</th>
                                <th style="{{ $th }}">{{ $t('table.payment_date') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.amount_change') }}</th>
                                <th style="{{ $th }}">{{ $t('table.actor') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($audit['corrections']['rows'] as $r)
                                <tr>
                                    <td style="{{ $cell }}">
                                        {{ $r['customer'] }}
                                        <span class="sub" style="{{ $sub }}">{{ $r['contract'] }} · {{ $r['branch'] }}</span>
                                    </td>
                                    <td style="{{ $cell }}">
                                        @if ($r['date_moved'])
                                            <span style="color:#6b7280; text-decoration:line-through;">{{ $r['old_payment_at'] }}</span>
                                            <span class="sub" style="{{ $sub }} color:#b45309; font-weight:700;">→ {{ $r['new_payment_at'] }}</span>
                                        @else
                                            <span style="color:#6b7280;">{{ $r['new_payment_at'] }}</span>
                                        @endif
                                    </td>
                                    <td style="{{ $cellR }}">
                                        @if (abs($r['diff']) > 0.001)
                                            <span style="color:#6b7280; text-decoration:line-through;">{{ $money($r['old_net']) }}</span>
                                            <span style="font-weight:700;"> → {{ $money($r['new_net']) }}</span>
                                        @else
                                            <span style="color:#6b7280;">{{ $money($r['new_net']) }}</span>
                                        @endif
                                        <span class="sub" style="{{ $sub }} color:{{ abs($r['diff']) <= 0.001 ? '#9ca3af' : ($r['diff'] > 0 ? '#047857' : '#b91c1c') }};">
                                            {{ ($r['diff'] > 0 ? '+' : '') . $money($r['diff']) }}
                                            @if ($r['method_changed'])
                                                · {{ $methodLabel($r['old_method']) }} → {{ $methodLabel($r['new_method']) }}
                                            @endif
                                        </span>
                                    </td>
                                    <td style="{{ $cell }}">
                                        {{ $r['actor'] }}
                                        <span class="sub" style="{{ $sub }}">{{ $r['reason'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            @if ($audit['corrections']['count'] > count($audit['corrections']['rows']))
                                <tr>
                                    <td colspan="4" style="{{ $cell }} color:#6b7280; font-style:italic;">
                                        {{ $t('table.more', ['count' => $audit['corrections']['count'] - count($audit['corrections']['rows'])]) }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                @endif

                {{-- Backdated --}}
                @if ($audit['backdated']['count'] > 0)
                    <h3 style="margin:20px 0 4px 0; font-size:15px; line-height:22px; color:#b45309; font-weight:700;">
                        {{ $t('audit.backdated_title') }} —
                        {{ $t('audit.backdated_summary', ['count' => $audit['backdated']['count'], 'amount' => $money($audit['backdated']['amount']), 'currency' => $cur]) }}
                    </h3>
                    <p style="{{ $desc }}">{{ $t('audit.backdated_desc') }}</p>
                    <table role="presentation" style="{{ $table }}">
                        <thead>
                            <tr>
                                <th style="{{ $th }}">{{ $t('table.customer') }}</th>
                                <th style="{{ $th }}">{{ $t('table.backdated_day') }}</th>
                                <th style="{{ $th }}">{{ $t('table.entered_at') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($audit['backdated']['rows'] as $r)
                                <tr>
                                    <td style="{{ $cell }}">
                                        {{ $r['customer'] }}
                                        <span class="sub" style="{{ $sub }}">{{ $r['contract'] }}</span>
                                    </td>
                                    <td style="{{ $cell }} font-weight:700; color:#b45309;">
                                        {{ \Illuminate\Support\Str::before($r['payment_at'], ' ') }}
                                        <span class="sub" style="{{ $sub }}">{{ $r['payment_at'] }}</span>
                                    </td>
                                    <td style="{{ $cell }} color:#6b7280;">
                                        {{ $r['entered_at'] }}
                                        <span class="sub" style="{{ $sub }} color:#b45309;">
                                            +{{ $r['days_back'] }} {{ $t('table.days_back') }}
                                        </span>
                                    </td>
                                    <td style="{{ $cellR }}">
                                        <span style="font-weight:700;">{{ $money($r['net']) }}</span>
                                        <span class="sub" style="{{ $sub }}">{{ $r['actor'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            @if ($audit['backdated']['count'] > count($audit['backdated']['rows']))
                                <tr>
                                    <td colspan="4" style="{{ $cell }} color:#6b7280; font-style:italic;">
                                        {{ $t('table.more', ['count' => $audit['backdated']['count'] - count($audit['backdated']['rows'])]) }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                @endif

                {{-- Idle branches --}}
                @if (count($audit['idle_branches']) > 0)
                    <h3 style="margin:20px 0 4px 0; font-size:15px; line-height:22px; color:#374151; font-weight:700;">
                        {{ $t('audit.idle_title') }} —
                        {{ $t('audit.idle_summary', ['count' => count($audit['idle_branches'])]) }}
                    </h3>
                    <p style="{{ $desc }}">{{ $t('audit.idle_desc') }}</p>
                    <table role="presentation" style="width:100%; border-collapse:collapse; border:1px solid #e5e7eb; background-color:#f9fafb; margin-bottom:8px;">
                        <tr>
                            <td style="padding:12px 16px; font-size:14px; line-height:22px; color:#374151;">
                                {{ implode(' · ', $audit['idle_branches']) }}
                            </td>
                        </tr>
                    </table>
                @endif

                {{-- ========================== PORTFOLIO ========================== --}}
                <h2 style="{{ $h2 }}">
                    {{ $t('portfolio.title') }}
                    <span style="font-size:13px; font-weight:400; color:#6b7280;">
                        · {{ $t('portfolio.branch_scope', ['count' => $int($pf['total']['branch_count'])]) }}
                    </span>
                </h2>
                <p style="{{ $desc }}">{{ $t('portfolio.desc') }}</p>

                @php
                    // One labelled block per branch rather than a wide grid: every
                    // number keeps its own caption, and two columns fit any screen.
                    $pfCard = function (array $b, bool $isTotal) use ($t, $money, $int, $pct, $cur) {
                        return [
                            'name' => $isTotal ? $t('table.total') : $b['branch'],
                            'rows' => [
                                [$t('portfolio.contracts'), $int($b['credit_count']), ''],
                                [$t('portfolio.total_debt'), $money($b['total_amount']) . ' ' . $cur, 'color:#6b7280;'],
                                [$t('portfolio.collected'), $money($b['paid_amount']) . ' ' . $cur . '  (' . $pct($b['collected_pct']) . ')', 'color:#047857;'],
                                [$t('portfolio.open_balance'), $money($b['open_balance']) . ' ' . $cur, 'font-size:15px;'],
                                [$t('portfolio.overdue_amount'), $money($b['overdue_amount']) . ' ' . $cur . '  (' . $pct($b['overdue_share']) . ')', 'color:#b91c1c;'],
                                [$t('portfolio.overdue_count'), $int($b['overdue_credit_count']), 'color:#b91c1c;'],
                                [$t('portfolio.never_paid'), $int($b['never_paid_count']) . '  (' . $money($b['never_paid_amount']) . ' ' . $cur . ')', 'color:#b45309;'],
                            ],
                        ];
                    };
                @endphp

                {{-- Total first, then the branches behind it. --}}
                @foreach (array_merge([[$pf['total'], true]], array_map(fn ($b) => [$b, false], $pf['rows'])) as [$row, $isTotal])
                    @php $card = $pfCard($row, $isTotal); @endphp

                    <table role="presentation"
                        style="width:100%; border-collapse:collapse; border:1px solid {{ $isTotal ? '#0f172a' : '#e5e7eb' }}; margin-bottom:14px;">
                        <tr>
                            <td colspan="2"
                                style="{{ $cardHead }} background-color:{{ $isTotal ? '#0f172a' : '#f8fafc' }}; color:{{ $isTotal ? '#ffffff' : '#0f172a' }};">
                                {{ $card['name'] }}
                            </td>
                        </tr>
                        @foreach ($card['rows'] as [$rowLabel, $rowValue, $rowStyle])
                            <tr>
                                <td style="{{ $cardKey }} width:58%;">{{ $rowLabel }}</td>
                                <td style="{{ $cardVal }} {{ $rowStyle }}">{{ $rowValue }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endforeach

                @if (count($pf['rows']) === 0)
                    <p style="{{ $desc }}">{{ $t('table.empty') }}</p>
                @endif

                <p style="{{ $desc }}">{{ $t('portfolio.legend') }}</p>

                <p style="margin:24px 0 0 0; font-size:12px; line-height:19px; color:#6b7280;">
                    {{ $t('footer.note') }}
                </p>
            </div>

            {{-- ============================ FOOTER ============================ --}}
            <div style="padding:16px 30px; background-color:#f9fafb; border-top:1px solid #e5e7eb;">
                <p style="margin:0; font-size:12px; line-height:19px; color:#6b7280; text-align:center;">
                    {{ $t('footer.generated') }}
                    &nbsp;·&nbsp;
                    {{ $t('meta.generated_at') }}: {{ $meta['generated_at'] }}
                </p>
            </div>

        </div>
    </div>

</body>

</html>
