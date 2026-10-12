<?php

namespace App\Services\Payments;

class Money
{
    /**
     * Reads an amount as a person types it: "1234.5", "1 234,50", "1.234,50".
     */
    public static function parse($v): float
    {
        $s = trim((string) $v);
        if ($s === '') {
            return 0.0;
        }

        $s = str_replace([' ', "\u{00A0}"], '', $s);

        $hasDot = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');

            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } else {
            if ($hasComma && ! $hasDot) {
                $s = str_replace(',', '.', $s);
            }
        }

        $n = (float) $s;
        if (! is_finite($n)) {
            return 0.0;
        }

        return round($n, 2);
    }

    public static function format(float $n): string
    {
        return number_format(round($n, 2), 2, '.', '');
    }
}
