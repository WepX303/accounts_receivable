<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyPaymentReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $report;

    public function __construct(array $report, ?string $locale = null)
    {
        $this->report = $report;

        // Rendering happens outside the request cycle (scheduler / queue), so the
        // language cannot be inherited from the session. Mailable::locale() wraps
        // both the subject and the view in the chosen locale.
        $this->locale($locale ?: (string) config('report.daily_report_locale', config('app.locale')));
    }

    public function build(): static
    {
        $summary = $this->report['summary'] ?? [];

        $subject = __('emails/daily_payment_report.subject', [
            'date' => $this->report['meta']['date_label'] ?? '-',
            'net' => number_format((float) ($summary['net'] ?? 0), 2),
            'currency' => $this->report['meta']['currency'] ?? 'TMT',
        ]);

        return $this
            ->subject($subject)
            ->view('emails.daily_payment_report');
    }
}
