<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Daily Payment Report</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px; color:#222; background:#f5f6f8; padding:30px;">

    <div
        style="max-width:750px; margin:auto; background:white; padding:30px; border-radius:6px; box-shadow:0 2px 6px rgba(0,0,0,0.05);">

        <h2 style="margin-top:0;">Daily Payment Report</h2>

        <p>
            <strong>Date:</strong> {{ $report['date'] ?? '-' }}
        </p>


        <!-- GENERAL SUMMARY -->

        <h3 style="margin-top:25px;">General Summary</h3>

        <table style="width:100%; border-collapse:collapse; max-width:700px;">
            <tbody>

                <tr>
                    <td style="border:1px solid #ddd; padding:10px; background:#fafafa;">
                        <strong>Total Payment Count</strong>
                    </td>

                    <td style="border:1px solid #ddd; padding:10px; text-align:right;">
                        {{ $report['total_count'] ?? 0 }}
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid #ddd; padding:10px; background:#fafafa;">
                        <strong>Total Amount</strong>
                    </td>

                    <td style="border:1px solid #ddd; padding:10px; text-align:right;">
                        {{ number_format($report['total_amount'] ?? 0, 2) }} TMT
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid #ddd; padding:10px; background:#fafafa;">
                        <strong>Cash Total</strong>
                    </td>

                    <td style="border:1px solid #ddd; padding:10px; text-align:right;">
                        {{ number_format($report['cash_total'] ?? 0, 2) }} TMT
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid #ddd; padding:10px; background:#fafafa;">
                        <strong>Card Total</strong>
                    </td>

                    <td style="border:1px solid #ddd; padding:10px; text-align:right;">
                        {{ number_format($report['card_total'] ?? 0, 2) }} TMT
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid #ddd; padding:10px; background:#fafafa;">
                        <strong>Phone Total</strong>
                    </td>

                    <td style="border:1px solid #ddd; padding:10px; text-align:right;">
                        {{ number_format($report['phone_total'] ?? 0, 2) }} TMT
                    </td>
                </tr>

            </tbody>
        </table>



        <!-- METHOD SUMMARY -->

        <h3 style="margin-top:30px;">Method Summary</h3>

        <table style="width:100%; border-collapse:collapse; max-width:700px;">

            <thead>

                <tr style="background:#f1f3f5;">

                    <th style="border:1px solid #ddd; padding:10px; text-align:left;">
                        Method
                    </th>

                    <th style="border:1px solid #ddd; padding:10px; text-align:right;">
                        Count
                    </th>

                    <th style="border:1px solid #ddd; padding:10px; text-align:right;">
                        Amount
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($report['method_summary'] ?? [] as $method => $summary)
                    <tr>

                        <td style="border:1px solid #ddd; padding:10px;">
                            {{ strtoupper($method) }}
                        </td>

                        <td style="border:1px solid #ddd; padding:10px; text-align:right;">
                            {{ $summary['count'] ?? 0 }}
                        </td>

                        <td style="border:1px solid #ddd; padding:10px; text-align:right;">
                            {{ number_format($summary['amount'] ?? 0, 2) }} TMT
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="3" style="border:1px solid #ddd; padding:12px; text-align:center;">
                            No data
                        </td>
                    </tr>
                @endforelse

            </tbody>

        </table>



        <!-- BRANCH SUMMARY -->

        <h3 style="margin-top:30px;">Branch Summary</h3>

        <table style="width:100%; border-collapse:collapse; max-width:700px;">

            <thead>

                <tr style="background:#f1f3f5;">

                    <th style="border:1px solid #ddd; padding:10px; text-align:left;">
                        Branch
                    </th>

                    <th style="border:1px solid #ddd; padding:10px; text-align:right;">
                        Count
                    </th>

                    <th style="border:1px solid #ddd; padding:10px; text-align:right;">
                        Amount
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($report['branch_summary'] ?? [] as $branch => $summary)
                    <tr>

                        <td style="border:1px solid #ddd; padding:10px;">
                            {{ $branch }}
                        </td>

                        <td style="border:1px solid #ddd; padding:10px; text-align:right;">
                            {{ $summary['count'] ?? 0 }}
                        </td>

                        <td style="border:1px solid #ddd; padding:10px; text-align:right;">
                            {{ number_format($summary['amount'] ?? 0, 2) }} TMT
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="3" style="border:1px solid #ddd; padding:12px; text-align:center;">
                            No data
                        </td>
                    </tr>
                @endforelse

            </tbody>

        </table>


        <p style="margin-top:35px; font-size:12px; color:#888;">
            This report was automatically generated by WepX. 
        </p>
    </div>

</body>

</html>
