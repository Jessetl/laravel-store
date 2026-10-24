<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $table = 'currencies';

    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'symbol',
        'exchange_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exchange_rate' => 'decimal:6',
    ];

    /**
     * Get the products associated with the currency.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the product prices associated with the currency.
     */
    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Convert an amount from this currency to another currency.
     *
     * @param  string  $amount  The amount to convert.
     * @param  Currency  $to  The target currency.
     * @return string The converted amount.
     */
    public function convert(string $amount, Currency $to): string
    {
        // exchange_rate = unidades de la divisa por 1 de la base → monto ÷ tasa_origen × tasa_destino
        $raw = bcdiv(bcmul($amount, (string) $to->exchange_rate, 10), (string) $this->exchange_rate, 10);

        return bcadd($raw, '0.005', 2);
    }
}
