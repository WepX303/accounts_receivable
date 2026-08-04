<?php

namespace App\Console\Commands;

use App\Mail\DailyPaymentReportMail;
use App\Services\Reports\DailyPaymentReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDailyPaymentReportCommand extends Command
{
    protected $signature = 'report:daily-payments
        {--date= : Report day (Y-m-d), defaults to today. Use to resend a missed day.}
        {--to=* : Override the configured recipients.}
        {--locale= : Override the mail language (tr, en, ru, tk).}
        {--dry-run : Render and print the summary without sending anything.}';

    protected $description = 'Send the daily collection report to management by email';

    public function handle(DailyPaymentReportService $reportService): int
    {
        try {
            $date = $this->option('date')
                ? Carbon::parse((string) $this->option('date'))->startOfDay()
                : Carbon::today();
        } catch (Throwable) {
            $this->error('Invalid --date value. Expected format: Y-m-d');

            return self::FAILURE;
        }

        $recipients = $this->option('to') ?: config('report.daily_report_emails', []);
        $locale = $this->option('locale') ?: null;

        $report = $reportService->getReport($date);

        if ($this->option('dry-run')) {
            $this->printSummary($report, $recipients);

            return self::SUCCESS;
        }

        if (empty($recipients)) {
            $this->error('Daily report emails are not configured (DAILY_REPORT_EMAILS).');

            return self::FAILURE;
        }

        $sent = 0;
        $failed = 0;

        // Sent one by one so a single bad address does not drop the whole batch,
        // and so recipients cannot see each other.
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new DailyPaymentReportMail($report, $locale));
                $sent++;
            } catch (Throwable $e) {
                $failed++;
                $this->error(sprintf('Failed for %s: %s', $email, $e->getMessage()));
                report($e);
            }
        }

        $this->info(sprintf(
            'Daily payment report for %s: %d sent, %d failed.',
            $report['meta']['date_label'],
            $sent,
            $failed
        ));

        return $failed > 0 && $sent === 0 ? self::FAILURE : self::SUCCESS;
    }

    private function printSummary(array $report, array $recipients): void
    {
        $s = $report['summary'];
        $a = $report['audit'];

        $this->info('Report date: ' . $report['meta']['date_label']);
        $this->line('Recipients:  ' . (implode(', ', $recipients) ?: '(none configured)'));
        $this->newLine();

        $this->table(['Metric', 'Value'], [
            ['Transactions', $s['tx_count']],
            ['Customers', $s['customer_count']],
            ['Gross', number_format($s['gross'], 2)],
            ['Change', number_format($s['change'], 2)],
            ['Net collected', number_format($s['net'], 2)],
            ['Cash / Card / Phone', sprintf(
                '%s / %s / %s',
                number_format($s['cash'], 2),
                number_format($s['card'], 2),
                number_format($s['phone'], 2)
            )],
            ['MTD net', number_format($report['comparison']['mtd']['net'], 2)],
            ['Voids', $a['voids']['count'] . ' (' . number_format($a['voids']['amount'], 2) . ')'],
            ['Corrections', $a['corrections']['count'] . ' (' . number_format($a['corrections']['diff'], 2) . ')'],
            ['Backdated entries', $a['backdated']['count']],
            ['Branches with no activity', count($a['idle_branches'])],
            ['Open balance', number_format($report['portfolio']['open_balance'], 2)],
            ['Overdue', number_format($report['portfolio']['overdue_amount'], 2)],
        ]);
    }
}
