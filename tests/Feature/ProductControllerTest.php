<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Laptop',
            'description' => '14 pulgadas',
            'price' => '10.50',
            'currency_id' => Currency::factory()->create()->id,
            'tax_cost' => '1.68',
            'manufacturing_cost' => '6.00',
        ], $overrides);
    }

    public function test_endpoints_require_token(): void
    {
        $product = Product::factory()->create();

        $this->getJson('/api/v1/products')->assertUnauthorized();
        $this->postJson('/api/v1/products', [])->assertUnauthorized();
        $this->getJson("/api/v1/products/{$product->id}")->assertUnauthorized();
        $this->putJson("/api/v1/products/{$product->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/v1/products/{$product->id}")->assertUnauthorized();
    }

    public function test_index_paginates_latest_first(): void
    {
        $this->signIn();
        $products = Product::factory()->count(3)->create();

        $this->getJson('/api/v1/products?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $products->last()->id)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_index_rejects_invalid_per_page(): void
    {
        $this->signIn();

        $this->getJson('/api/v1/products?per_page=0')->assertJsonValidationErrors('per_page');
        $this->getJson('/api/v1/products?per_page=101')->assertJsonValidationErrors('per_page');
    }

    public function test_store_creates_product(): void
    {
        $this->signIn();

        $response = $this->postJson('/api/v1/products', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Laptop')
            ->assertJsonPath('data.price', '10.50');

        $id = $response->json('data.id');
        $response->assertHeader('Location', route('products.show', $id));
        $this->assertDatabaseHas('products', ['id' => $id, 'name' => 'Laptop']);
    }

    public function test_store_requires_description(): void
    {
        $this->signIn();

        $this->postJson('/api/v1/products', $this->payload(['description' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('description');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_store_requires_fields(): void
    {
        $this->signIn();

        $this->postJson('/api/v1/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost']);
    }

    public function test_store_rejects_invalid_amounts_and_currency(): void
    {
        $this->signIn();

        $this->postJson('/api/v1/products', $this->payload([
            'price' => '-1',
            'tax_cost' => '1.234',
            'manufacturing_cost' => '10000000000',
            'currency_id' => 999,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price', 'tax_cost', 'manufacturing_cost', 'currency_id']);
    }

    public function test_show_returns_product_with_prices(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $product->prices()->create(['currency_id' => Currency::factory()->create()->id, 'price' => '9.99']);

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonCount(1, 'data.prices');
    }

    public function test_show_returns_404_for_missing_product(): void
    {
        $this->signIn();

        $this->getJson('/api/v1/products/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'The requested resource was not found.']);
    }

    public function test_unknown_url_returns_404_with_route_message(): void
    {
        $this->signIn();

        $this->getJson('/api/v1/prodcts')
            ->assertNotFound()
            ->assertExactJson(['message' => 'The requested URL was not found on the server. If you entered the URL manually please check your spelling and try again.']);
    }

    public function test_update_changes_only_sent_fields(): void
    {
        $this->signIn();
        $product = Product::factory()->create(['price' => '100.00']);

        $this->putJson("/api/v1/products/{$product->id}", ['name' => 'Nuevo nombre'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo nombre')
            ->assertJsonPath('data.price', '100.00');
    }

    public function test_update_rejects_negative_price(): void
    {
        $this->signIn();
        $product = Product::factory()->create();

        $this->putJson("/api/v1/products/{$product->id}", ['price' => -5])
            ->assertJsonValidationErrors('price');
    }

    public function test_update_rejects_more_than_two_decimals_without_rounding(): void
    {
        $this->signIn();
        $product = Product::factory()->create(['price' => '100.00']);

        $this->putJson("/api/v1/products/{$product->id}", [
            'price' => '1.239',
            'tax_cost' => '1.239',
            'manufacturing_cost' => '1.239',
        ])->assertJsonValidationErrors(['price', 'tax_cost', 'manufacturing_cost']);

        $this->assertSame('100.00', $product->fresh()->price);
    }

    public function test_update_rejects_amounts_over_column_limit(): void
    {
        $this->signIn();
        $product = Product::factory()->create();

        // decimal(12,2) admite hasta 9999999999.99; más que eso sería un 500 en MySQL
        $this->putJson("/api/v1/products/{$product->id}", [
            'price' => '10000000000',
            'tax_cost' => '10000000000',
            'manufacturing_cost' => '10000000000',
        ])->assertJsonValidationErrors(['price', 'tax_cost', 'manufacturing_cost']);
    }

    public function test_update_accepts_amount_at_column_limit(): void
    {
        $this->signIn();
        $product = Product::factory()->create();

        $this->putJson("/api/v1/products/{$product->id}", ['price' => '9999999999.99'])
            ->assertOk()
            ->assertJsonPath('data.price', '9999999999.99');
    }

    public function test_update_rejects_unknown_currency(): void
    {
        $this->signIn();
        $product = Product::factory()->create();

        $this->putJson("/api/v1/products/{$product->id}", ['currency_id' => 999])
            ->assertJsonValidationErrors('currency_id');
    }

    public function test_update_rejects_base_currency_that_already_has_a_price(): void
    {
        $this->signIn();
        $usd = Currency::factory()->create();
        $eur = Currency::factory()->create();
        $product = Product::factory()->create(['currency_id' => $usd->id]);
        $product->prices()->create(['currency_id' => $eur->id, 'price' => '90.00']);

        $this->putJson("/api/v1/products/{$product->id}", ['currency_id' => $eur->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.currency_id.0', 'The product already has a registered price in this currency.');

        $this->assertSame($usd->id, $product->fresh()->currency_id);
    }

    public function test_update_allows_base_currency_without_registered_price(): void
    {
        $this->signIn();
        $product = Product::factory()->create();
        $product->prices()->create(['currency_id' => Currency::factory()->create()->id, 'price' => '90.00']);
        $other = Currency::factory()->create();

        $this->putJson("/api/v1/products/{$product->id}", ['currency_id' => $other->id])->assertOk();

        $this->assertSame($other->id, $product->fresh()->currency_id);
    }

    public function test_destroy_soft_deletes_product(): void
    {
        $this->signIn();
        $product = Product::factory()->create();

        $this->deleteJson("/api/v1/products/{$product->id}")->assertNoContent();

        $this->assertSoftDeleted($product);
        $this->getJson("/api/v1/products/{$product->id}")->assertNotFound();
    }
}
