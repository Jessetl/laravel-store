<?php

namespace Database\Seeders;

use App\Models\Currency;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Siembra cada divisa de la lista fija una sola vez, en orden: en una base
     * nueva los IDs quedan 1 USD, 2 EUR, 3 GBP, 4 JPY y 5 CAD. Es idempotente.
     */
    public function run(): void
    {
        foreach (CurrencyFactory::CURRENCIES as $currency) {
            Currency::updateOrCreate(['name' => $currency['name']], $currency);
        }
    }
}
