<?php

namespace Tests\Unit\Payments;

use App\Services\Payments\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_reads_amounts_the_way_people_type_them(): void
    {
        $this->assertSame(1234.5, Money::parse('1234.5'));
        $this->assertSame(1234.5, Money::parse('1234,50'));
        $this->assertSame(1234.5, Money::parse('1.234,50'));
        $this->assertSame(1234.5, Money::parse('1,234.50'));
        $this->assertSame(1234.5, Money::parse(' 1 234,50 '));
        $this->assertSame(10.13, Money::parse('10.126'));
        $this->assertSame(0.0, Money::parse(''));
        $this->assertSame(0.0, Money::parse(null));
        $this->assertSame(0.0, Money::parse('abc'));
    }

    public function test_it_formats_with_two_decimals_and_no_grouping(): void
    {
        $this->assertSame('1234.50', Money::format(1234.5));
        $this->assertSame('0.00', Money::format(0));
        $this->assertSame('10.13', Money::format(10.126));
    }
}
