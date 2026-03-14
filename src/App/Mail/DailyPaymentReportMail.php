<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyPaymentReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $report;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function build(): static
    {
        return $this
            ->subject('Daily Payment Report - ' . $this->report['date'])
            ->view('emails.daily_payment_report');
    }
}