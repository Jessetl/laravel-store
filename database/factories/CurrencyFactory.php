<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Currency::class;

    public const CURRENCIES = [
        ['name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1.00],
        ['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.85],
        ['name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => 0.75],
        ['name' => 'Japanese Yen', 'symbol' => '¥', 'exchange_rate' => 110.00],
        ['name' => 'Canadian Dollar', 'symbol' => '$', 'exchange_rate' => 1.25],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return fake()->randomElement(self::CURRENCIES);
    }
}
