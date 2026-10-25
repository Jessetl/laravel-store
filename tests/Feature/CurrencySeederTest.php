<?php

namespace Tests\Feature;

use App\Models\Currency;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_each_currency_once_with_stable_ids(): void
    {
        $this->seed(CurrencySeeder::class);
        $this->seed(CurrencySeeder::class);

        $this->assertSame(
            [1 => 'US Dollar', 2 => 'Euro', 3 => 'British Pound', 4 => 'Japanese Yen', 5 => 'Canadian Dollar'],
            Currency::orderBy('id')->pluck('name', 'id')->all(),
        );
        $this->assertSame('1.000000', Currency::find(1)->exchange_rate);
    }
}
