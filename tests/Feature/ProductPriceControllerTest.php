<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceControllerTest extends TestCase
{
    use RefreshDatabase;

    private Currency $usd;

    private Currency $eur;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usd = Currency::factory()->create(['name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1]);
        $this->eur = Currency::factory()->create(['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.85]);
        $this->product = Product::factory()->create(['currency_id' => $this->usd->id, 'price' => '10.10']);
    }

    private function url(): string
    {
        return "/api/v1/products/{$this->product->id}/prices";
    }

    public function test_endpoints_require_token(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
        $this->postJson($this->url(), [])->assertUnauthorized();
    }

    public function test_index_lists_prices_with_converted_price(): void
    {
        $this->signIn();
        $this->product->prices()->create(['currency_id' => $this->eur->id, 'price' => '9.99']);

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', '9.99')
            ->assertJsonPath('data.0.symbol', '€')
            ->assertJsonPath('data.0.convertedPrice', '8.59'); // 10.10 × 0.85 = 8.585 → 8.59
    }

    public function test_index_returns_404_for_missing_or_deleted_product(): void
    {
        $this->signIn();

        $this->getJson('/api/v1/products/999/prices')->assertNotFound();

        $this->product->delete();
        $this->getJson($this->url())->assertNotFound();
    }

    public function test_store_creates_price(): void
    {
        $this->signIn();

        $this->postJson($this->url(), ['currency_id' => $this->eur->id, 'price' => '20.00'])
            ->assertCreated()
            ->assertJsonPath('data.price', '20.00')
            ->assertJsonPath('data.convertedPrice', '8.59');

        $this->assertDatabaseHas('product_prices', [
            'product_id' => $this->product->id,
            'currency_id' => $this->eur->id,
        ]);
    }

    public function test_store_rejects_duplicate_currency_with_409(): void
    {
        $this->signIn();
        $this->product->prices()->create(['currency_id' => $this->eur->id, 'price' => '9.99']);

        $this->postJson($this->url(), ['currency_id' => $this->eur->id, 'price' => '20.00'])
            ->assertConflict()
            ->assertExactJson(['message' => 'The product already has a price for the specified currency.']);
    }

    public function test_store_rejects_product_base_currency(): void
    {
        $this->signIn();

        $this->postJson($this->url(), ['currency_id' => $this->usd->id, 'price' => '20.00'])
            ->assertJsonValidationErrors('currency_id');
    }

    public function test_store_validates_payload(): void
    {
        $this->signIn();

        $this->postJson($this->url(), ['currency_id' => 999, 'price' => '-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['currency_id', 'price']);
    }
}
