<?php

namespace App\Services\Contracts;

use Carbon\Carbon;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Prints a şertnama onto the bank-approved template.
 *
 * The template's pages are imported as they are and the contract's details
 * are written into the blanks on top of them, so the approved wording of
 * page 1 can never change. Page 2 (the hand-over act) also gets the
 * customer's earlier credits below its signatures.
 *
 * Positions are in millimetres from the top-left corner of the template,
 * measured on resources/contract-templates/v1/shertnama.pdf. A new template
 * gets a new version with its own positions; a contract keeps printing with
 * the version it was made on.
 */
class ContractPdfService
{
    public const VERSION = 'v1';

    // Times-like, as the template, and it carries every Turkmen letter.
    private const FONT = 'freeserif';

    private const SIZE = 10;

    // The template is set in 10pt Times: a line's baseline lies 0.891em (3.14 mm) below
    // its top. Writing on that baseline sits on the blank's underscores like typed text.
    private const BASELINE = 3.14;

    // Room left before writing that starts at a blank's left edge, so it does not run into the word before it.
    private const INDENT = 2.0;

    // Baseline of the text in a goods table row, from the row's top.
    private const ROW_BASELINE = 3.1;

    private const MONTHS = [
        1 => 'ýanwar', 'fewral', 'mart', 'aprel', 'maý', 'iýun',
        'iýul', 'awgust', 'sentýabr', 'oktýabr', 'noýabr', 'dekabr',
    ];

    /**
     * The blanks of v1: [x, y, width] of each line to write on.
     */
    private const P1 = [
        'number' => [123.6, 9.2, 40.0],
        'day' => [23.6, 13.3, 5.3],
        'month' => [30.4, 13.3, 22.9],
        'year' => [56.9, 13.3, 7.1],
        'name' => [82.0, 29.5, 118.1],
        'passport_series' => [33.5, 33.6, 15.9],
        'passport_number' => [53.6, 33.6, 148.1],
        'address' => [22.0, 37.6, 179.8],
        'phone' => [76.0, 41.7, 72.3],
        'months' => [145.3, 110.6, 15.9],
        'monthly_amount' => [22.0, 114.7, 26.4],
        'installment_part' => [58.9, 122.8, 54.7],
    ];

    private const P2 = [
        'top_date' => [118.1, 13.3, 40.0],
        'top_number' => [120.7, 17.3, 52.9],
        'act_date' => [38.4, 47.7, 27.0],
        'act_number' => [83.6, 47.7, 42.3],
        'goods_total_manat' => [135.0, 105.8, 54.7],
        'goods_total_tenne' => [32.0, 109.8, 17.6],
        // Goods table: the top of each of its seven rows, and the column edges.
        'goods_rows' => [67.4, 71.7, 75.9, 80.5, 84.7, 88.9, 93.1],
        'goods_columns' => [31.9, 43.8, 97.1, 119.7, 156.0, 194.8],
        // Free space below the stamp, for the earlier credits.
        'history_top' => 174.0,
        'history_bottom' => 287.0,
    ];

    /**
     * History table columns: key, Turkmen heading, width (mm), alignment.
     */
    private const HISTORY_COLUMNS = [
        ['no', 'T/b', 7, 'C'],
        ['contract', 'Şertnama №', 24, 'L'],
        ['date', 'Senesi', 18, 'C'],
        ['amount', 'Möçberi', 18, 'R'],
        ['paid', 'Tölenen', 18, 'R'],
        ['remaining', 'Galyndy', 17, 'R'],
        ['status', 'Ýagdaýy', 16, 'C'],
        ['closed_at', 'Ýapylan senesi', 19, 'C'],
        ['duration', 'Möhleti', 15, 'C'],
        ['overdue', 'Gijikdirme', 17, 'R'],
    ];

    private const HISTORY_ROW_HEIGHT = 5.0;

    public function templatePath(string $version = self::VERSION): string
    {
        return resource_path("contract-templates/{$version}/shertnama.pdf");
    }

    /**
     * @param  array{
     *     number?: ?string, date?: Carbon|string|null,
     *     customer_name?: ?string, passport_series?: ?string, passport_number?: ?string,
     *     address?: ?string, phone?: ?string,
     *     months?: int|string|null, monthly_amount?: string|float|null, installment_part?: ?string,
     *     goods?: list<array{name?: ?string, quantity?: string|int|null, price?: string|float|null, total?: string|float|null}>,
     *     history?: list<array<string, ?string>>,
     * }  $c
     * @param  string|null  $mark  written across every page, e.g. that it is only a draft
     */
    public function render(array $c, ?string $mark = null): string
    {
        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCreator(config('app.name'));
        $pdf->SetTitle('Şertnama ' . ($c['number'] ?? ''));
        $pdf->SetTextColor(0, 0, 0);

        $pdf->setSourceFile($this->templatePath());

        $date = $this->date($c['date'] ?? null);

        $this->templatePage($pdf, 1);
        $this->pageOne($pdf, $c, $date);
        $this->mark($pdf, $mark);

        $this->templatePage($pdf, 2);
        $this->pageTwo($pdf, $c, $date);
        $this->history($pdf, $c['history'] ?? [], $mark);
        $this->mark($pdf, $mark);

        return $pdf->Output('', 'S');
    }

    private function templatePage(Fpdi $pdf, int $page): void
    {
        $tpl = $pdf->importPage($page);
        $size = $pdf->getTemplateSize($tpl);

        $pdf->AddPage('P', [$size['width'], $size['height']]);
        $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
    }

    private function pageOne(Fpdi $pdf, array $c, ?Carbon $date): void
    {
        $f = self::P1;

        $this->put($pdf, $f['number'], $c['number'] ?? null, 'L', 11, 'B');

        if ($date) {
            $this->put($pdf, $f['day'], $date->format('d'), 'C');
            $this->put($pdf, $f['month'], self::MONTHS[$date->month], 'C');
            $this->put($pdf, $f['year'], $date->format('y'), 'C');
        }

        $this->put($pdf, $f['name'], $c['customer_name'] ?? null);
        $this->put($pdf, $f['passport_series'], $c['passport_series'] ?? null, 'C');

        // The number line carries on with the address; what does not fit goes to the line below.
        $number = trim((string) ($c['passport_number'] ?? ''));
        $address = trim((string) ($c['address'] ?? ''));
        $head = $number !== '' && $address !== '' ? $number . ', ' . $address : $number . $address;
        [$first, $rest] = $this->split($pdf, $head, $f['passport_number'][2] - self::INDENT);
        $this->put($pdf, $f['passport_number'], $first);
        $this->put($pdf, $f['address'], $rest);

        $this->put($pdf, $f['phone'], $c['phone'] ?? null);

        $this->put($pdf, $f['months'], $this->text($c['months'] ?? null), 'C');
        $this->put($pdf, $f['monthly_amount'], $this->amount($c['monthly_amount'] ?? null), 'C');
        $this->put($pdf, $f['installment_part'], $c['installment_part'] ?? null, 'C');

        // The signature block is left blank, "Satyş hünärmeni" and the buyer's ("ALYJY")
        // column alike: they are written in by hand when the contract is signed.
    }

    private function pageTwo(Fpdi $pdf, array $c, ?Carbon $date): void
    {
        $f = self::P2;
        $day = $date?->format('d.m.Y');

        $this->put($pdf, $f['top_date'], $day, 'C');
        $this->put($pdf, $f['top_number'], $c['number'] ?? null, 'C');
        $this->put($pdf, $f['act_date'], $day, 'C');
        $this->put($pdf, $f['act_number'], $c['number'] ?? null, 'C');

        $goods = array_values(array_filter($c['goods'] ?? [], fn ($g) => trim((string) ($g['name'] ?? '')) !== ''));
        $cols = $f['goods_columns'];
        $sum = 0.0;

        foreach (array_slice($goods, 0, count($f['goods_rows'])) as $i => $g) {
            // put() takes a line's top; this puts the baseline at the row's.
            $y = $f['goods_rows'][$i] + self::ROW_BASELINE - self::BASELINE;
            $total = $g['total'] ?? null;
            if (($total === null || $total === '') && is_numeric($g['price'] ?? null) && is_numeric($g['quantity'] ?? null)) {
                $total = (float) $g['price'] * (float) $g['quantity'];
            }
            $sum += is_numeric($total) ? (float) $total : 0;

            $cells = [(string) ($i + 1), $g['name'] ?? null, $this->text($g['quantity'] ?? null), $this->amount($g['price'] ?? null), $this->amount($total)];
            foreach ($cells as $j => $text) {
                $this->put($pdf, [$cols[$j] + 1, $y, $cols[$j + 1] - $cols[$j] - 2], $text, $j === 1 ? 'L' : 'C', 9);
            }
        }

        if ($goods !== [] && $sum > 0) {
            $manat = (int) floor(round($sum, 2));
            $tenne = (int) round(($sum - $manat) * 100);
            $this->put($pdf, $f['goods_total_manat'], number_format($manat, 0, '.', ' '), 'C');
            $this->put($pdf, $f['goods_total_tenne'], sprintf('%02d', $tenne), 'C');
        }
    }

    /**
     * The customer's earlier credits, under the act; carried on to further pages when long.
     */
    private function history(Fpdi $pdf, array $rows, ?string $mark): void
    {
        $f = self::P2;
        $x0 = 32.0;
        $y = $f['history_top'];

        $title = 'Alyjynyň öňki karzlary';
        $pdf->SetFont(self::FONT, 'B', 10);
        $pdf->SetXY($x0, $y);
        $pdf->Cell(0, 5, $title, 0, 1, 'L');
        $y += 6;

        if ($rows === []) {
            $pdf->SetFont(self::FONT, '', 9);
            $pdf->SetXY($x0, $y);
            $pdf->Cell(0, 5, 'Öňki karz ýok.', 0, 1, 'L');

            return;
        }

        $header = function (float $y) use ($pdf, $x0): float {
            $pdf->SetFont(self::FONT, 'B', 7.5);
            $pdf->SetFillColor(235, 235, 235);
            $x = $x0;
            foreach (self::HISTORY_COLUMNS as [, $label, $w]) {
                $pdf->SetXY($x, $y);
                $pdf->Cell($w, self::HISTORY_ROW_HEIGHT, $label, 1, 0, 'C', true, '', 1);
                $x += $w;
            }

            return $y + self::HISTORY_ROW_HEIGHT;
        };

        $y = $header($y);

        foreach (array_values($rows) as $i => $row) {
            if ($y + self::HISTORY_ROW_HEIGHT > $f['history_bottom']) {
                $this->mark($pdf, $mark);
                $pdf->AddPage('P', 'A4');
                $pdf->SetFont(self::FONT, 'B', 10);
                $pdf->SetXY($x0, 15);
                $pdf->Cell(0, 5, $title . ' (dowamy)', 0, 1, 'L');
                $y = $header(21);
            }

            $pdf->SetFont(self::FONT, '', 7.5);
            $x = $x0;
            foreach (self::HISTORY_COLUMNS as [$key, , $w, $align]) {
                $text = $key === 'no' ? (string) ($i + 1) : (string) ($row[$key] ?? '');
                $pdf->SetXY($x, $y);
                $pdf->Cell($w, self::HISTORY_ROW_HEIGHT, $text, 1, 0, $align, false, '', 1);
                $x += $w;
            }
            $y += self::HISTORY_ROW_HEIGHT;
        }
    }

    private function mark(Fpdi $pdf, ?string $mark): void
    {
        if ($mark === null || $mark === '') {
            return;
        }

        $pdf->StartTransform();
        $pdf->SetFont(self::FONT, 'B', 70);
        $pdf->SetTextColor(200, 200, 200);
        $pdf->setAlpha(0.35);
        $pdf->Rotate(45, 105, 148);
        $width = $pdf->GetStringWidth($mark);
        $pdf->Text(105 - $width / 2, 140, $mark);
        $pdf->setAlpha(1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->StopTransform();
    }

    /**
     * Writes one value onto a blank, shrinking it to fit the blank's width.
     *
     * @param  array{0: float, 1: float, 2: float}  $at
     */
    private function put(Fpdi $pdf, array $at, ?string $text, string $align = 'L', float $size = self::SIZE, string $style = ''): void
    {
        $text = trim((string) $text);
        if ($text === '') {
            return;
        }

        [$x, $y, $w] = $at;

        if ($align === 'L') {
            $x += self::INDENT;
            $w -= self::INDENT;
        }

        $pdf->SetFont(self::FONT, $style, $size);
        while ($size > 6 && $pdf->GetStringWidth($text) > $w) {
            $size -= 0.5;
            $pdf->SetFont(self::FONT, $style, $size);
        }

        // Placed by its baseline ('L'), the same as the template's own text on that line.
        // Stretch 1: squeezed horizontally only if it still does not fit.
        $pdf->SetXY($x, $y + self::BASELINE);
        $pdf->Cell($w, 0, $text, 0, 0, $align, false, '', 1, false, 'L', 'B');
    }

    /**
     * Splits text at a word boundary so the first part fits the width.
     *
     * @return array{0: string, 1: string}
     */
    private function split(Fpdi $pdf, string $text, float $width, float $size = self::SIZE): array
    {
        $pdf->SetFont(self::FONT, '', $size);

        $first = '';
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $i => $word) {
            $try = $first === '' ? $word : $first . ' ' . $word;
            if ($first !== '' && $pdf->GetStringWidth($try) > $width) {
                return [$first, implode(' ', array_slice($words, $i))];
            }
            $first = $try;
        }

        return [$first, ''];
    }

    private function date(Carbon|string|null $date): ?Carbon
    {
        if ($date === null || $date === '') {
            return null;
        }

        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    private function amount(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        if (! is_numeric($v)) {
            return (string) $v;
        }

        $n = round((float) $v, 2);

        // Whole manats as on the paper contracts; tenge only when there are any.
        return floor($n) == $n
            ? number_format($n, 0, '.', ' ')
            : number_format($n, 2, '.', ' ');
    }

    private function text(mixed $v): ?string
    {
        return $v === null ? null : trim((string) $v);
    }
}
