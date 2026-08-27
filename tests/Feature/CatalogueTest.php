<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Enums\PricingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Cakes', 'is_active' => true]);
    }

    protected function makeProduct(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'name' => 'Chocolate Cake',
            'pricing_type' => PricingType::PER_WEIGHT,
            'base_price' => 1000,
            'unit' => 'kg',
            'minimum_quantity' => 1,
            'status' => 'active',
        ], $attributes));
    }

    public function test_catalogue_index_lists_only_active_categories(): void
    {
        Category::create(['name' => 'Hidden', 'is_active' => false]);

        $this->get('/catalogue')
            ->assertOk()
            ->assertSee('Cakes')
            ->assertDontSee('Hidden');
    }

    public function test_category_page_lists_requestable_products_with_prices(): void
    {
        $this->makeProduct(); // active

        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Draft Cake',
            'pricing_type' => PricingType::PER_WEIGHT,
            'base_price' => 900,
            'unit' => 'kg',
            'status' => 'draft', // not requestable
        ]);

        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Unavailable Juice Cake',
            'pricing_type' => PricingType::PER_WEIGHT,
            'base_price' => 800,
            'unit' => 'kg',
            'status' => 'active',
            'is_available' => false,
        ]);

        // Unavailable-but-active items remain listed but flagged
        $response = $this->get(route('catalogue.category', $this->category));

        $response->assertOk()
            ->assertSee('Chocolate Cake')
            ->assertSee('KSh 1,000 / kg')
            ->assertSee('Currently unavailable')
            ->assertDontSee('Draft Cake');
    }

    public function test_product_page_shows_options_and_estimate_markup(): void
    {
        $product = $this->makeProduct();

        $response = $this->get(route('catalogue.show', $product));

        $response->assertOk()
            ->assertSee('Estimated total')
            ->assertSee('quantity', false);
    }

    public function test_quote_endpoint_returns_calculated_total(): void
    {
        $product = $this->makeProduct(['maximum_quantity' => 20]);

        $response = $this->postJson('/catalogue/quote', [
            'type' => 'product',
            'id' => $product->id,
            'quantity' => 3,
        ]);

        $response->assertOk()
            ->assertJson([
                'total' => 3000.0,
                'requires_pmb_quote' => false,
                'quantity' => 3.0,
            ]);
    }

    public function test_quote_endpoint_validates_and_rejects_bad_quantities(): void
    {
        $product = $this->makeProduct();

        $this->postJson('/catalogue/quote', [
            'type' => 'product',
            'id' => $product->id,
            'quantity' => 0.2, // below minimum of 1
        ])->assertStatus(422);
    }

    public function test_quote_endpoint_rejects_unavailable_products(): void
    {
        $product = $this->makeProduct(['status' => 'archived']);

        $this->postJson('/catalogue/quote', [
            'type' => 'product',
            'id' => $product->id,
            'quantity' => 2,
        ])->assertStatus(422);
    }

    public function test_custom_pricing_items_signal_quotation_required(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Custom Wedding Cake',
            'pricing_type' => PricingType::CUSTOM,
            'status' => 'active',
        ]);

        $this->postJson('/catalogue/quote', [
            'type' => 'product',
            'id' => $product->id,
        ])->assertOk()->assertJson([
            'requires_pmb_quote' => true,
            'total' => null,
        ]);
    }

    public function test_admin_products_index_renders_for_an_admin(): void
    {
        // Regression: the PricingType enum passed into the table formatter
        // used to crash str_replace / in_array. Rendering must succeed now.
        $this->makeProduct();

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin = \App\Models\User::factory()->create(['is_superadmin' => true]);
        $admin->assignRole($role);

        $response = $this->actingAs($admin)->get('/admin/catalogue/products');

        $response->assertOk();
    }
}
