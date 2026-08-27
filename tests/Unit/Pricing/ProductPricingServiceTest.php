<?php

namespace Tests\Unit\Pricing;

use App\Enums\PricingType;
use App\Models\Category;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\Service;
use App\Services\Pricing\InvalidQuantityException;
use App\Services\Pricing\PricingException;
use App\Services\Pricing\ProductPricingService;
use App\Services\Pricing\TierOverflowException;
use App\Services\Pricing\UnavailableItemException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductPricingService $service;
    protected Category $cakes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductPricingService();

        $this->cakes = Category::create(['name' => 'Cakes', 'is_active' => true]);
    }

    protected function makeProduct(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $this->cakes->id,
            'name' => 'Chocolate Cake',
            'pricing_type' => PricingType::PER_WEIGHT->value,
            'base_price' => 1000,
            'unit' => 'kg',
            'minimum_quantity' => 1,
            'maximum_quantity' => 20,
            'status' => 'active',
        ], $attributes));
    }

    // Worked example: cake (per-weight + option modifiers)

    public function test_cake_3kg_with_fondant_and_premium_costs_4800(): void
    {
        $product = $this->makeProduct();
        [$frosting, $decoration] = $this->makeCakeOptions($product);

        $quote = $this->service->quote($product, 3, [
            $frosting['fondant']->id,     // +800
            $decoration['premium']->id,   // +1,000
        ]);

        $this->assertSame(3000.0, $quote->subtotal);   // 3 × 1000
        $this->assertSame(1800.0, $quote->option_total);
        $this->assertSame(4800.0, $quote->total);
        $this->assertFalse($quote->requires_pmb_quote);
    }

    public function test_per_weight_calculates_simple_quantities(): void
    {
        $product = $this->makeProduct();

        $this->assertSame(2000.0, $this->service->quote($product, 2)->total);
        $this->assertSame(10000.0, $this->service->quote($product, 10)->total);
    }

    public function test_included_option_is_listed_in_breakdown_with_zero_modifier(): void
    {
        $product = $this->makeProduct();
        [$frosting] = $this->makeCakeOptions($product);

        $quote = $this->service->quote($product, 3, [$frosting['buttercream']->id]); // +0

        $this->assertSame(3000.0, $quote->total);
        $this->assertSame(0.0, $quote->option_total);
        $this->assertNotEmpty($quote->breakdown);
    }

    // Quantity bounds

    public function test_quantity_below_minimum_throws(): void
    {
        $product = $this->makeProduct(); // min 1 kg

        $this->expectException(InvalidQuantityException::class);
        $this->service->quote($product, 0.5);
    }

    public function test_quantity_above_maximum_throws(): void
    {
        $product = $this->makeProduct(); // max 20 kg

        try {
            $this->service->quote($product, 25);
            $this->fail('Expected InvalidQuantityException.');
        } catch (InvalidQuantityException $e) {
            $this->assertStringContainsString('between 1 and 20', $e->getMessage());
        }
    }

    public function test_null_or_non_positive_quantity_throws(): void
    {
        $product = $this->makeProduct();

        $this->expectException(InvalidQuantityException::class);
        $this->service->quote($product, null);
    }

    // Other pricing types

    public function test_per_unit_pricing(): void
    {
        $samosa = Product::create([
            'category_id' => $this->cakes->id,
            'name' => 'Samosa',
            'pricing_type' => PricingType::PER_UNIT->value,
            'base_price' => 50,
            'unit' => 'piece',
            'minimum_quantity' => 5,
            'status' => 'active',
        ]);

        $this->assertSame(600.0, $this->service->quote($samosa, 12)->total);
    }

    public function test_fixed_pricing_ignores_quantity(): void
    {
        $box = Product::create([
            'category_id' => $this->cakes->id,
            'name' => 'Celebration Box',
            'pricing_type' => PricingType::FIXED->value,
            'base_price' => 1500,
            'status' => 'active',
        ]);

        $quote = $this->service->quote($box, 99);

        $this->assertSame(1500.0, $quote->total);
        $this->assertNull($quote->quantity);
    }

    public function test_custom_pricing_returns_no_numeric_price(): void
    {
        $cake = Product::create([
            'category_id' => $this->cakes->id,
            'name' => 'Custom Wedding Cake',
            'pricing_type' => PricingType::CUSTOM->value,
            'status' => 'active',
        ]);

        $quote = $this->service->quote($cake);

        $this->assertTrue($quote->requires_pmb_quote);
        $this->assertNull($quote->total);
        $this->assertNull($quote->unit_price);
    }

    public function test_per_volume_juice(): void
    {
        $juice = Service::create([ // services support the same rules
            'name' => 'Passion Juice',
            'pricing_type' => PricingType::PER_VOLUME->value,
            'base_price' => 300,
            'unit' => 'litre',
            'minimum_quantity' => 5,
            'status' => 'active',
        ]);

        $this->assertSame(6000.0, $this->service->quote($juice, 20)->total);
    }

    public function test_per_person_catering_requires_review(): void
    {
        $catering = Service::create([
            'name' => 'Catering Package A',
            'pricing_type' => PricingType::PER_PERSON->value,
            'base_price' => 650,
            'unit' => 'person',
            'minimum_quantity' => 30,
            'requires_review' => true,
            'status' => 'active',
        ]);

        $quote = $this->service->quote($catering, 150);

        $this->assertSame(97500.0, $quote->total);       // 150 × 650
        $this->assertTrue($quote->requires_pmb_quote);   // estimate → PMB review
    }

    // Tiered pricing

    protected function makeTieredCatering(): Service
    {
        $service = Service::create([
            'name' => 'Tiered Catering',
            'pricing_type' => PricingType::TIERED->value,
            'unit' => 'person',
            'status' => 'active',
        ]);

        foreach ([[1, 50, 700], [51, 100, 650], [101, 300, 600]] as [$min, $max, $price]) {
            PriceTier::create([
                'priceable_type' => Service::class,
                'priceable_id' => $service->id,
                'min_quantity' => $min,
                'max_quantity' => $max,
                'unit_price' => $price,
            ]);
        }

        return $service;
    }

    public function test_tiered_boundaries(): void
    {
        $service = $this->makeTieredCatering();

        $this->assertSame(700.0, $this->service->quote($service, 50)->unit_price);
        $this->assertSame(650.0, $this->service->quote($service, 51)->unit_price);
        $this->assertSame(650.0, $this->service->quote($service, 100)->unit_price);
        $this->assertSame(600.0, $this->service->quote($service, 101)->unit_price);
        $this->assertSame(180000.0, $this->service->quote($service, 300)->total); // 300 × 600
    }

    public function test_tiered_overflow_requires_quotation(): void
    {
        $service = $this->makeTieredCatering();

        try {
            $this->service->quote($service, 301);
            $this->fail('Expected TierOverflowException.');
        } catch (TierOverflowException $e) {
            $this->assertSame(300.0, $e->highest_bracket_max);
        }
    }

    // Availability & integrity guards

    public function test_inactive_product_is_not_requestable(): void
    {
        $product = $this->makeProduct(['status' => 'inactive']);

        $this->expectException(UnavailableItemException::class);
        $this->service->quote($product, 2);
    }

    public function test_unavailable_product_is_not_requestable(): void
    {
        $product = $this->makeProduct(['is_available' => false]);

        $this->expectException(UnavailableItemException::class);
        $this->service->quote($product, 2);
    }

    public function test_product_of_inactive_category_is_not_requestable(): void
    {
        $inactiveCategory = Category::create(['name' => 'Old Items', 'is_active' => false]);
        $product = Product::create([
            'category_id' => $inactiveCategory->id,
            'name' => 'Legacy Cake',
            'pricing_type' => PricingType::PER_WEIGHT->value,
            'base_price' => 900,
            'unit' => 'kg',
            'status' => 'active',
        ]);

        $this->expectException(UnavailableItemException::class);
        $this->service->quote($product, 2);
    }

    public function test_foreign_option_value_is_rejected(): void
    {
        $productA = $this->makeProduct();
        $productB = $this->makeProduct(['name' => 'Vanilla Cake']);

        [$frostingB] = $this->makeCakeOptions($productB); // options of product B

        $this->expectException(PricingException::class);
        $this->service->quote($productA, 3, [$frostingB['fondant']->id]);
    }

    public function test_unavailable_option_value_is_rejected(): void
    {
        $product = $this->makeProduct();
        [, $decoration] = $this->makeCakeOptions($product);

        $unavailable = ProductOptionValue::create([
            'product_option_id' => $decoration['option']->id,
            'name' => 'Retired Gold Leaf',
            'price_modifier' => 5000,
            'is_available' => false,
        ]);

        $this->expectException(PricingException::class);
        $this->service->quote($product, 3, [$unavailable->id]);
    }
    // Helpers

    /**
     * Cake options per the catalogue spec:
     * Frosting: Buttercream +0 / Fondant +800 · Decoration: Standard +0 / Premium +1,000.
     */
    protected function makeCakeOptions(Product $product): array
    {
        $frosting = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Frosting',
            'type' => 'select',
            'is_required' => true,
        ]);
        $decoration = ProductOption::create([
            'product_id' => $product->id,
            'name' => 'Decoration',
            'type' => 'select',
        ]);

        $buttercream = ProductOptionValue::create([
            'product_option_id' => $frosting->id, 'name' => 'Buttercream', 'price_modifier' => 0, 'sort_order' => 1,
        ]);
        $fondant = ProductOptionValue::create([
            'product_option_id' => $frosting->id, 'name' => 'Fondant', 'price_modifier' => 800, 'sort_order' => 2,
        ]);
        $standard = ProductOptionValue::create([
            'product_option_id' => $decoration->id, 'name' => 'Standard', 'price_modifier' => 0, 'sort_order' => 1,
        ]);
        $premium = ProductOptionValue::create([
            'product_option_id' => $decoration->id, 'name' => 'Premium', 'price_modifier' => 1000, 'sort_order' => 2,
        ]);

        return [
            ['option' => $frosting, 'buttercream' => $buttercream, 'fondant' => $fondant],
            ['option' => $decoration, 'standard' => $standard, 'premium' => $premium],
        ];
    }
}
