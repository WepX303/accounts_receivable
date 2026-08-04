@php
    $t = fn (string $key, array $r = []) => __('emails/daily_payment_report.' . $key, $r);

    $meta = $report['meta'];
    $s = $report['summary'];
    $cmp = $report['comparison'];
    $audit = $report['audit'];
    $sch = $report['schedule'];
    $portfolio = $report['portfolio'];
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
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">

    <div style="width:100%; background-color:#f3f4f6; padding:28px 12px;">
        <div style="max-width:860px; margin:0 auto; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">

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

            <div style="padding:26px 30px 32px 30px;">

                {{-- ============================= HERO ============================= --}}
                <table role="presentation" style="width:100%; border-collapse:collapse; background-color:#0b3b2e; border-radius:8px; margin-bottom:22px;">
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="margin:0; font-size:13px; line-height:18px; color:#a7f3d0; text-transform:uppercase; letter-spacing:0.5px;">
                                {{ $t('hero.net_collected') }}
                            </p>
                            <p style="margin:6px 0 0 0; font-size:34px; line-height:42px; color:#ffffff; font-weight:700;">
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
                            <td style="{{ $cell }} font-weight:700;">{{ $t('comparison.mtd', ['days' => $cmp['mtd']['days_elapsed']]) }}</td>
                            <td style="{{ $cellR }} font-weight:700;">{{ $money($cmp['mtd']['net']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }} color:#6b7280;">{{ $int($cmp['mtd']['tx_count']) }} {{ $t('hero.transactions') }}</td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }}">{{ $t('comparison.mtd_daily_avg') }}</td>
                            <td style="{{ $cellR }}">{{ $money($cmp['mtd']['daily_avg']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }} color:#9ca3af;">—</td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }}">{{ $t('comparison.prev_month_day', ['label' => $cmp['prev_month_day']['label']]) }}</td>
                            <td style="{{ $cellR }}">{{ $money($cmp['prev_month_day']['net']) }} {{ $cur }}</td>
                            <td style="{{ $cellR }}">{!! $delta($cmp['prev_month_day']['net_delta_pct']) !!}</td>
                        </tr>
                    </tbody>
                </table>

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
                <table role="presentation" style="{{ $table }}">
                    <thead>
                        <tr>
                            <th style="{{ $th }}">{{ $t('table.branch') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.count') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.gross') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.change') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.net') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.cash') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.card') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.phone') }}</th>
                            <th style="{{ $thR }}">{{ $t('table.share') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['branches'] as $b)
                            <tr>
                                <td style="{{ $cell }} font-weight:600;">{{ $b['branch'] }}</td>
                                <td style="{{ $cellR }}">{{ $int($b['tx_count']) }}</td>
                                <td style="{{ $cellR }}">{{ $money($b['gross']) }}</td>
                                <td style="{{ $cellR }}">{{ $money($b['change']) }}</td>
                                <td style="{{ $cellR }} font-weight:700;">{{ $money($b['net']) }}</td>
                                <td style="{{ $cellR }}">{{ $money($b['cash']) }}</td>
                                <td style="{{ $cellR }}">{{ $money($b['card']) }}</td>
                                <td style="{{ $cellR }}">{{ $money($b['phone']) }}</td>
                                <td style="{{ $cellR }} color:#6b7280;">{{ $pct($b['share']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="{{ $cell }} text-align:center; color:#6b7280;">{{ $t('table.empty') }}</td>
                            </tr>
                        @endforelse
                        @if (count($report['branches']) > 0)
                            <tr>
                                <td style="{{ $totalCell }}">{{ $t('table.total') }}</td>
                                <td style="{{ $totalCellR }}">{{ $int($s['tx_count']) }}</td>
                                <td style="{{ $totalCellR }}">{{ $money($s['gross']) }}</td>
                                <td style="{{ $totalCellR }}">{{ $money($s['change']) }}</td>
                                <td style="{{ $totalCellR }}">{{ $money($s['net']) }}</td>
                                <td style="{{ $totalCellR }}">{{ $money($s['cash']) }}</td>
                                <td style="{{ $totalCellR }}">{{ $money($s['card']) }}</td>
                                <td style="{{ $totalCellR }}">{{ $money($s['phone']) }}</td>
                                <td style="{{ $totalCellR }}">100.0%</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

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
                                <th style="{{ $th }}">{{ $t('table.contract') }}</th>
                                <th style="{{ $th }}">{{ $t('table.branch') }}</th>
                                <th style="{{ $th }}">{{ $t('table.method') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.time') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.net') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['top_payments'] as $p)
                                <tr>
                                    <td style="{{ $cell }}">{{ $p['customer'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $p['contract'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $p['branch'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $methodLabel($p['method']) }}</td>
                                    <td style="{{ $cellR }} color:#6b7280;">{{ $p['time'] }}</td>
                                    <td style="{{ $cellR }} font-weight:700;">{{ $money($p['net']) }} {{ $cur }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- =========================== SCHEDULE =========================== --}}
                <h2 style="{{ $h2 }}">{{ $t('schedule.title') }}</h2>
                <p style="{{ $desc }}">{{ $t('schedule.desc') }}</p>

                <h3 style="margin:16px 0 6px 0; font-size:15px; line-height:22px; color:#0f172a; font-weight:700;">
                    {{ $t('schedule.due_title') }}
                </h3>
                <table role="presentation" style="{{ $table }}">
                    <tr>
                        <td style="{{ $label }} width:60%;">{{ $t('schedule.due_credits') }}</td>
                        <td style="{{ $cellR }} font-weight:700;">{{ $int($sch['due']['credit_count']) }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('schedule.due_expected') }}</td>
                        <td style="{{ $cellR }}">{{ $money($sch['due']['expected']) }} {{ $cur }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }} background-color:#ecfdf5;">{{ $t('schedule.due_paid_credits') }}</td>
                        <td style="{{ $cellR }} background-color:#ecfdf5; font-weight:700; color:#047857;">
                            {{ $int($sch['due']['paid_credit_count']) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('schedule.due_paid_payments') }}</td>
                        <td style="{{ $cellR }}">{{ $int($sch['due']['paid_payment_count']) }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }} background-color:#ecfdf5;">{{ $t('schedule.due_paid_amount') }}</td>
                        <td style="{{ $cellR }} background-color:#ecfdf5; font-weight:700; color:#047857;">
                            {{ $money($sch['due']['paid_amount']) }} {{ $cur }}
                        </td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('schedule.due_unpaid_credits') }}</td>
                        <td style="{{ $cellR }} color:#b91c1c; font-weight:700;">{{ $int($sch['due']['unpaid_credit_count']) }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }} background-color:#fef2f2;">{{ $t('schedule.due_missing') }}</td>
                        <td style="{{ $cellR }} background-color:#fef2f2; font-weight:700; color:#b91c1c;">
                            {{ $money($sch['due']['missing']) }} {{ $cur }}
                        </td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('schedule.due_rate') }}</td>
                        <td style="{{ $cellR }} font-weight:700;">{{ $pct($sch['due']['rate']) }}</td>
                    </tr>
                </table>

                <h3 style="margin:22px 0 4px 0; font-size:15px; line-height:22px; color:#0f172a; font-weight:700;">
                    {{ $t('schedule.not_due_title') }}
                </h3>
                <p style="{{ $desc }}">{{ $t('schedule.not_due_desc') }}</p>
                <table role="presentation" style="{{ $table }}">
                    <tr>
                        <td style="{{ $label }} width:60%;">{{ $t('schedule.not_due_credits') }}</td>
                        <td style="{{ $cellR }} font-weight:700;">{{ $int($sch['not_due']['credit_count']) }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('schedule.not_due_payments') }}</td>
                        <td style="{{ $cellR }}">{{ $int($sch['not_due']['payment_count']) }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('schedule.not_due_amount') }}</td>
                        <td style="{{ $cellR }} font-weight:700;">{{ $money($sch['not_due']['amount']) }} {{ $cur }}</td>
                    </tr>
                </table>

                <table role="presentation" style="{{ $table }}">
                    <thead>
                        <tr>
                            <th style="{{ $th }}">&nbsp;</th>
                            <th style="{{ $thR }}">{{ $t('schedule.not_due_credits') }}</th>
                            <th style="{{ $thR }}">{{ $t('schedule.not_due_payments') }}</th>
                            <th style="{{ $thR }}">{{ $t('schedule.not_due_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="{{ $cell }}">
                                <span style="font-weight:700; color:#047857;">{{ $t('schedule.early_title') }}</span>
                                <span style="display:block; font-size:12px; line-height:17px; color:#6b7280;">{{ $t('schedule.early_desc') }}</span>
                            </td>
                            <td style="{{ $cellR }}">{{ $int($sch['not_due']['early']['credit_count']) }}</td>
                            <td style="{{ $cellR }}">{{ $int($sch['not_due']['early']['payment_count']) }}</td>
                            <td style="{{ $cellR }} font-weight:700; color:#047857;">{{ $money($sch['not_due']['early']['amount']) }} {{ $cur }}</td>
                        </tr>
                        <tr>
                            <td style="{{ $cell }}">
                                <span style="font-weight:700; color:#b45309;">{{ $t('schedule.late_title') }}</span>
                                <span style="display:block; font-size:12px; line-height:17px; color:#6b7280;">{{ $t('schedule.late_desc') }}</span>
                            </td>
                            <td style="{{ $cellR }}">{{ $int($sch['not_due']['late']['credit_count']) }}</td>
                            <td style="{{ $cellR }}">{{ $int($sch['not_due']['late']['payment_count']) }}</td>
                            <td style="{{ $cellR }} font-weight:700; color:#b45309;">{{ $money($sch['not_due']['late']['amount']) }} {{ $cur }}</td>
                        </tr>
                    </tbody>
                </table>

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
                                <th style="{{ $th }}">{{ $t('table.contract') }}</th>
                                <th style="{{ $th }}">{{ $t('table.branch') }}</th>
                                <th style="{{ $th }}">{{ $t('table.payment_at') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.amount') }}</th>
                                <th style="{{ $th }}">{{ $t('table.actor') }}</th>
                                <th style="{{ $th }}">{{ $t('table.reason') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($audit['voids']['rows'] as $r)
                                <tr>
                                    <td style="{{ $cell }}">{{ $r['customer'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $r['contract'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $r['branch'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $r['payment_at'] }}</td>
                                    <td style="{{ $cellR }} color:#b91c1c; font-weight:700;">− {{ $money($r['net']) }}</td>
                                    <td style="{{ $cell }}">{{ $r['actor'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $r['reason'] }}</td>
                                </tr>
                            @endforeach
                            @if ($audit['voids']['count'] > count($audit['voids']['rows']))
                                <tr>
                                    <td colspan="7" style="{{ $cell }} color:#6b7280; font-style:italic;">
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
                                <th style="{{ $th }}">{{ $t('table.contract') }}</th>
                                <th style="{{ $th }}">{{ $t('table.payment_date') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.amount_change') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.diff') }}</th>
                                <th style="{{ $th }}">{{ $t('table.method_change') }}</th>
                                <th style="{{ $th }}">{{ $t('table.actor') }}</th>
                                <th style="{{ $th }}">{{ $t('table.reason') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($audit['corrections']['rows'] as $r)
                                <tr>
                                    <td style="{{ $cell }}">{{ $r['customer'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $r['contract'] }}</td>
                                    <td style="{{ $cell }} white-space:nowrap;">
                                        @if ($r['date_moved'])
                                            <span style="color:#6b7280; text-decoration:line-through;">{{ $r['old_payment_at'] }}</span>
                                            <span style="color:#b45309; font-weight:700;"> → {{ $r['new_payment_at'] }}</span>
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
                                    </td>
                                    <td style="{{ $cellR }} color:{{ abs($r['diff']) <= 0.001 ? '#6b7280' : ($r['diff'] > 0 ? '#047857' : '#b91c1c') }}; font-weight:700;">
                                        {{ ($r['diff'] > 0 ? '+' : '') . $money($r['diff']) }}
                                    </td>
                                    <td style="{{ $cell }} color:#6b7280; white-space:nowrap;">
                                        @if ($r['method_changed'])
                                            <span style="text-decoration:line-through;">{{ $methodLabel($r['old_method']) }}</span>
                                            <span style="color:#b45309; font-weight:700;"> → {{ $methodLabel($r['new_method']) }}</span>
                                        @else
                                            {{ $methodLabel($r['new_method']) }}
                                        @endif
                                    </td>
                                    <td style="{{ $cell }}">{{ $r['actor'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $r['reason'] }}</td>
                                </tr>
                            @endforeach
                            @if ($audit['corrections']['count'] > count($audit['corrections']['rows']))
                                <tr>
                                    <td colspan="8" style="{{ $cell }} color:#6b7280; font-style:italic;">
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
                                <th style="{{ $th }}">{{ $t('table.contract') }}</th>
                                <th style="{{ $th }}">{{ $t('table.backdated_day') }}</th>
                                <th style="{{ $th }}">{{ $t('table.payment_at') }}</th>
                                <th style="{{ $th }}">{{ $t('table.entered_at') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.days_back') }}</th>
                                <th style="{{ $thR }}">{{ $t('table.amount') }}</th>
                                <th style="{{ $th }}">{{ $t('table.actor') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($audit['backdated']['rows'] as $r)
                                <tr>
                                    <td style="{{ $cell }}">{{ $r['customer'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $r['contract'] }}</td>
                                    <td style="{{ $cell }} font-weight:700; color:#b45309; white-space:nowrap;">
                                        {{ \Illuminate\Support\Str::before($r['payment_at'], ' ') }}
                                    </td>
                                    <td style="{{ $cell }}">{{ $r['payment_at'] }}</td>
                                    <td style="{{ $cell }} color:#6b7280;">{{ $r['entered_at'] }}</td>
                                    <td style="{{ $cellR }} color:#b45309; font-weight:700;">{{ $r['days_back'] }}</td>
                                    <td style="{{ $cellR }}">{{ $money($r['net']) }}</td>
                                    <td style="{{ $cell }}">{{ $r['actor'] }}</td>
                                </tr>
                            @endforeach
                            @if ($audit['backdated']['count'] > count($audit['backdated']['rows']))
                                <tr>
                                    <td colspan="8" style="{{ $cell }} color:#6b7280; font-style:italic;">
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
                <h2 style="{{ $h2 }}">{{ $t('portfolio.title') }}</h2>
                <p style="{{ $desc }}">{{ $t('portfolio.desc') }}</p>
                <table role="presentation" style="{{ $table }}">
                    <tr>
                        <td style="{{ $label }} width:60%;">{{ $t('portfolio.open_credits') }}</td>
                        <td style="{{ $cellR }}">{{ $int($portfolio['credit_count']) }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('portfolio.open_balance') }}</td>
                        <td style="{{ $cellR }} font-weight:700;">{{ $money($portfolio['open_balance']) }} {{ $cur }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('portfolio.overdue_credits') }}</td>
                        <td style="{{ $cellR }}">{{ $int($portfolio['overdue_credit_count']) }}</td>
                    </tr>
                    <tr>
                        <td style="{{ $label }} background-color:#fef2f2;">{{ $t('portfolio.overdue_amount') }}</td>
                        <td style="{{ $cellR }} background-color:#fef2f2; font-weight:700; color:#b91c1c;">
                            {{ $money($portfolio['overdue_amount']) }} {{ $cur }}
                        </td>
                    </tr>
                    <tr>
                        <td style="{{ $label }}">{{ $t('portfolio.overdue_share') }}</td>
                        <td style="{{ $cellR }} font-weight:700; color:#b91c1c;">{{ $pct($portfolio['overdue_share']) }}</td>
                    </tr>
                </table>

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
