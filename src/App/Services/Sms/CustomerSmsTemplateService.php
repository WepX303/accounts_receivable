<?php

namespace App\Services\Sms;

use App\Models\Credit;

class CustomerSmsTemplateService
{
    public function render(string $template, Credit $credit): string
    {
        $remaining = $credit->local_remaining;

        if ($remaining === null) {
            $remaining = (float) (($credit->amount_local ?? $credit->amount ?? 0) - ($credit->paid_local ?? $credit->paid ?? 0));
            if ($remaining < 0) {
                $remaining = 0;
            }
        }

        $replacements = [
            '{name}' => (string) ($credit->name ?? ''),
            '{phone}' => (string) ($credit->phone ?? ''),
            '{passport}' => (string) ($credit->passport ?? ''),
            '{contract}' => (string) ($credit->contract ?? ''),
            '{branch}' => (string) ($credit->branch ?? ''),
            '{remaining}' => number_format((float) $remaining, 2, '.', ''),
            '{willpaiddate}' => $credit->willpaiddate ? $credit->willpaiddate->format('Y-m-d') : '',
            '{creditdate}' => $credit->date_ ? $credit->date_->format('Y-m-d') : '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}