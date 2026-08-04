<?php

namespace App\Console\Commands;

use App\Mail\DailyPaymentReportMail;
use App\Models\User;
use App\Services\Reports\DailyPaymentReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDailyPaymentReportCommand extends Command
{
    protected $signature = 'report:daily-payments
        {--date= : Report day (Y-m-d), defaults to today. Use to resend a missed day.}
        {--to=* : Send only to these addresses, with the full unrestricted report.}
        {--locale= : Override the mail language (tr, en, ru, tk).}
        {--dry-run : Print what would be sent, without sending anything.}';

    protected $description = 'Send the daily collection report to management and to opted-in users';

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

        $locale = $this->option('locale') ?: null;
        $recipients = $this->recipients();

        if ($recipients->isEmpty()) {
            $this->error('Nobody to send to: DAILY_REPORT_EMAILS is empty and no user has the daily report enabled.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->printPlan($reportService, $date, $recipients);

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        // Recipients that see the same branches share one built report, so a
        // dozen head-office addresses cost a single pass over the data.
        foreach ($recipients->groupBy(fn (array $r) => $this->branchKey($r['branches'])) as $group) {
            $report = $reportService->getReport($date, $group->first()['branches']);

            foreach ($group as $recipient) {
                try {
                    Mail::to($recipient['email'])->send(new DailyPaymentReportMail($report, $locale));
                    $sent++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->error(sprintf('Failed for %s: %s', $recipient['email'], $e->getMessage()));
                    report($e);
                }
            }
        }

        $this->info(sprintf(
            'Daily payment report for %s: %d sent, %d failed.',
            $date->format('d.m.Y'),
            $sent,
            $failed
        ));

        return $failed > 0 && $sent === 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Who gets the mail, and how much of it.
     *
     * The addresses in DAILY_REPORT_EMAILS always get the whole picture. Users
     * ticked on the users page get their own branches only, and fall back to
     * the whole picture when they are not branch-restricted. An address on both
     * lists is sent once, unrestricted.
     *
     * @return Collection<int, array{email: string, branches: string[]|null, label: string}>
     */
    private function recipients(): Collection
    {
        if ($this->option('to')) {
            return collect($this->option('to'))
                ->map(fn ($email) => [
                    'email' => trim((string) $email),
                    'branches' => null,
                    'label' => '--to',
                ])
                ->filter(fn (array $r) => $r['email'] !== '')
                ->values();
        }

        $configured = collect(config('report.daily_report_emails', []))
            ->map(fn ($email) => [
                'email' => trim((string) $email),
                'branches' => null,
                'label' => 'config',
            ])
            ->filter(fn (array $r) => $r['email'] !== '');

        $users = User::query()
            ->where('daily_report', true)
            ->where('status', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => [
                'email' => trim((string) $user->email),
                'branches' => $user->allowedBranches(),
                'label' => 'user #' . $user->id,
            ]);

        return $configured
            ->concat($users)
            ->unique(fn (array $r) => mb_strtolower($r['email']))
            ->values();
    }

    /**
     * @param  string[]|null  $branches
     */
    private function branchKey(?array $branches): string
    {
        if ($branches === null) {
            return '*';
        }

        sort($branches);

        return implode('|', $branches);
    }

    /**
     * @param  Collection<int, array{email: string, branches: string[]|null, label: string}>  $recipients
     */
    private function printPlan(DailyPaymentReportService $service, Carbon $date, Collection $recipients): void
    {
        $this->info('Report date: ' . $date->format('d.m.Y'));
        $this->newLine();

        $rows = [];

        foreach ($recipients->groupBy(fn (array $r) => $this->branchKey($r['branches'])) as $group) {
            $branches = $group->first()['branches'];
            $report = $service->getReport($date, $branches);
            $s = $report['summary'];
            $a = $report['audit'];

            foreach ($group as $recipient) {
                $rows[] = [
                    $recipient['email'],
                    $recipient['label'],
                    $branches === null ? 'ALL' : implode(', ', $branches),
                    $s['tx_count'],
                    number_format($s['net'], 2),
                    $a['voids']['count'] . '/' . $a['corrections']['count'] . '/' . $a['backdated']['count'],
                    number_format($report['portfolio']['open_balance'], 2),
                ];
            }
        }

        $this->table(
            ['Email', 'Source', 'Branches', 'Tx', 'Net', 'Void/Corr/Backdated', 'Open balance'],
            $rows
        );
    }
}
