<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Credit Collection Report</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">

    <div style="width:100%; background-color:#f3f4f6; padding:32px 16px;">
        <div
            style="max-width:820px; margin:0 auto; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">

            <!-- Header -->
            <div style="background-color:#0f172a; padding:28px 32px;">
                <h1 style="margin:0; font-size:24px; line-height:32px; color:#ffffff; font-weight:700;">
                    Daily Credit Collection Report
                </h1>
                <p style="margin:8px 0 0 0; font-size:14px; line-height:22px; color:#cbd5e1;">
                    Summary of collected credit payments for the selected reporting date.
                </p>
            </div>

            <!-- Body -->
            <div style="padding:32px;">

                <!-- Report Meta -->
                <table role="presentation" style="width:100%; border-collapse:collapse; margin-bottom:28px;">
                    <tbody>
                        <tr>
                            <td style="padding:0; font-size:14px; line-height:22px; color:#4b5563;">
                                <strong style="color:#111827;">Report Date:</strong>
                                {{ $report['date'] ?? '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- General Summary -->
                <h2 style="margin:0 0 14px 0; font-size:18px; line-height:26px; color:#111827; font-weight:700;">
                    General Summary
                </h2>

                <table role="presentation"
                    style="width:100%; border-collapse:collapse; margin-bottom:30px; border:1px solid #e5e7eb;">
                    <tbody>
                        <tr>
                            <td
                                style="width:65%; padding:12px 16px; border:1px solid #e5e7eb; background-color:#f9fafb; font-size:14px; line-height:22px; font-weight:600; color:#111827;">
                                Total Collection Count
                            </td>
                            <td
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                {{ $report['total_count'] ?? 0 }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="padding:12px 16px; border:1px solid #e5e7eb; background-color:#f9fafb; font-size:14px; line-height:22px; font-weight:600; color:#111827;">
                                Total Collected Amount
                            </td>
                            <td
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:15px; line-height:22px; font-weight:700; color:#111827;">
                                {{ number_format($report['total_amount'] ?? 0, 2) }} TMT
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="padding:12px 16px; border:1px solid #e5e7eb; background-color:#f9fafb; font-size:14px; line-height:22px; font-weight:600; color:#111827;">
                                Collected by Cash
                            </td>
                            <td
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                {{ number_format($report['cash_total'] ?? 0, 2) }} TMT
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="padding:12px 16px; border:1px solid #e5e7eb; background-color:#f9fafb; font-size:14px; line-height:22px; font-weight:600; color:#111827;">
                                Collected by Card
                            </td>
                            <td
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                {{ number_format($report['card_total'] ?? 0, 2) }} TMT
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="padding:12px 16px; border:1px solid #e5e7eb; background-color:#f9fafb; font-size:14px; line-height:22px; font-weight:600; color:#111827;">
                                Collected by Phone
                            </td>
                            <td
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                {{ number_format($report['phone_total'] ?? 0, 2) }} TMT
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Payment Method Summary -->
                {{-- <h2 style="margin:0 0 14px 0; font-size:18px; line-height:26px; color:#111827; font-weight:700;">
                    Collection by Payment Method
                </h2>

                <table role="presentation"
                    style="width:100%; border-collapse:collapse; margin-bottom:30px; border:1px solid #e5e7eb;">
                    <thead>
                        <tr style="background-color:#f8fafc;">
                            <th
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:left; font-size:14px; line-height:22px; font-weight:700; color:#111827;">
                                Payment Method
                            </th>
                            <th
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; font-weight:700; color:#111827;">
                                Count
                            </th>
                            <th
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; font-weight:700; color:#111827;">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($report['method_summary'] ?? []) as $method => $summary)
                            <tr>
                                <td
                                    style="padding:12px 16px; border:1px solid #e5e7eb; font-size:14px; line-height:22px; color:#111827;">
                                    {{ ucfirst($method) }}
                                </td>
                                <td
                                    style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                    {{ $summary['count'] ?? 0 }}
                                </td>
                                <td
                                    style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                    {{ number_format($summary['amount'] ?? 0, 2) }} TMT
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3"
                                    style="padding:14px 16px; border:1px solid #e5e7eb; text-align:center; font-size:14px; line-height:22px; color:#6b7280;">
                                    No payment method data available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table> --}}

                <!-- Payment Method Summary -->
                <h2 style="margin:0 0 14px 0; font-size:18px; line-height:26px; color:#111827; font-weight:700;">
                    Collection by Payment Method
                </h2>

                <table role="presentation"
                    style="width:100%; border-collapse:collapse; margin-bottom:30px; border:1px solid #e5e7eb;">
                    <thead>
                        <tr style="background-color:#f8fafc;">
                            <th
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:left; font-size:14px; line-height:22px; font-weight:700; color:#111827;">
                                Payment Method
                            </th>
                            <th
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; font-weight:700; color:#111827;">
                                Count
                            </th>
                            <th
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; font-weight:700; color:#111827;">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $methods = ['cash', 'card', 'phone'];
                        @endphp

                        @foreach ($methods as $method)
                            <tr>
                                <td
                                    style="padding:12px 16px; border:1px solid #e5e7eb; font-size:14px; line-height:22px; color:#111827;">
                                    {{ ucfirst($method) }}
                                </td>
                                <td
                                    style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                    {{ $report['method_summary'][$method]['count'] ?? 0 }}
                                </td>
                                <td
                                    style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                    {{ number_format($report['method_summary'][$method]['amount'] ?? 0, 2) }} TMT
                                </td>
                            </tr>
                        @endforeach

                        @if (empty($report['method_summary']))
                            <tr>
                                <td colspan="3"
                                    style="padding:14px 16px; border:1px solid #e5e7eb; text-align:center; font-size:14px; line-height:22px; color:#6b7280;">
                                    No payment method data available.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                <!-- Branch Summary -->
                <h2 style="margin:0 0 14px 0; font-size:18px; line-height:26px; color:#111827; font-weight:700;">
                    Branch Collection Summary
                </h2>

                <table role="presentation" style="width:100%; border-collapse:collapse; border:1px solid #e5e7eb;">
                    <thead>
                        <tr style="background-color:#f8fafc;">
                            <th
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:left; font-size:14px; line-height:22px; font-weight:700; color:#111827;">
                                Branch
                            </th>
                            <th
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; font-weight:700; color:#111827;">
                                Count
                            </th>
                            <th
                                style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; font-weight:700; color:#111827;">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($report['branch_summary'] ?? []) as $branch => $summary)
                            <tr>
                                <td
                                    style="padding:12px 16px; border:1px solid #e5e7eb; font-size:14px; line-height:22px; color:#111827;">
                                    {{ $branch }}
                                </td>
                                <td
                                    style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                    {{ $summary['count'] ?? 0 }}
                                </td>
                                <td
                                    style="padding:12px 16px; border:1px solid #e5e7eb; text-align:right; font-size:14px; line-height:22px; color:#111827;">
                                    {{ number_format($summary['amount'] ?? 0, 2) }} TMT
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3"
                                    style="padding:14px 16px; border:1px solid #e5e7eb; text-align:center; font-size:14px; line-height:22px; color:#6b7280;">
                                    No branch data available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

            <!-- Footer -->
            <div style="padding:18px 32px; background-color:#f9fafb; border-top:1px solid #e5e7eb;">
                <p style="margin:0; font-size:12px; line-height:20px; color:#6b7280; text-align:center;">
                    This report was generated automatically by WepX system. Please do not reply to this email.
                </p>
            </div>

        </div>
    </div>

</body>

</html>
