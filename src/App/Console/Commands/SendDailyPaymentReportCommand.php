<?php

namespace App\Console\Commands;

use App\Mail\DailyPaymentReportMail;
use App\Services\Reports\DailyPaymentReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;


class SendDailyPaymentReportCommand extends Command
{
    protected $signature = 'report:daily-payments';
    protected $description = 'Send daily payment summary report by email';

    public function handle(DailyPaymentReportService $reportService): int
    {
        $emails = config('report.daily_report_emails', []);

        if (empty($emails)) {
            $this->error('Daily report emails are not configured.');
            return self::FAILURE;
        }

        $report = $reportService->getTodayReport();

        // Mail::to($emails)->send(new DailyPaymentReportMail($report));
        
        foreach ($emails as $email) {
            Mail::to($email)
                ->send(new DailyPaymentReportMail($report));
        }

        $this->info('Daily payment report sent successfully.');

        return self::SUCCESS;
    }
}
