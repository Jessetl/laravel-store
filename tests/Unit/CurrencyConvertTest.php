<?php

namespace Tests\Unit;

use App\Models\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyConvertTest extends TestCase
{
    private function currency(string $rate): Currency
    {
        return new Currency(['exchange_rate' => $rate]);
    }

    public function test_same_rate_keeps_amount(): void
    {
        $this->assertSame('10.10', $this->currency('1')->convert('10.10', $this->currency('1')));
    }

    public function test_converts_from_base_currency_rounding_half_up(): void
    {
        // 10.10 × 0.85 = 8.585
        $this->assertSame('8.59', $this->currency('1')->convert('10.10', $this->currency('0.85')));
    }

    public function test_converts_between_two_non_base_currencies(): void
    {
        // 100 EUR ÷ 0.85 × 110 JPY = 12941.176...
        $this->assertSame('12941.18', $this->currency('0.85')->convert('100.00', $this->currency('110')));
    }
}
