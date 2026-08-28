<?php

namespace Tests\Feature\Request;

use App\Enums\PricingType;
use App\Enums\Request\RequestItemPricingStatus;
use App\Models\Customer;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\Pricing\ProductPricingService;
use App\Services\Request\RequestOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private ProductPricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricing = app(ProductPricingService::class);

        $user = User::factory()->create();
        $this->customer = Customer::factory()->create(['user_id' => $user->id]);
    }

    /** @test */
    public function fixed_price_is_snapshot_correctly(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $product = Product::factory()->create([
            'name' => 'Celebration Box',
            'base_price' => 1500,
            'pricing_type' => PricingType::FIXED,
            'unit' => null,
            'minimum_quantity' => null,
            'maximum_quantity' => null,
        ]);
        $request = $orchestrator->createDraftForCustomer($this->customer);
        $orchestrator->addToCart('product', $product->id, 1.0, []);
        $orchestrator->hydrateRequestFromCart($request);

        $request->refresh();
        $item = $request->items->first();

        $this->assertSame('1500.00', $item->unit_price);
        $this->assertSame('1500.00', $item->subtotal);
        $this->assertSame(RequestItemPricingStatus::CALCULATED, $item->pricing_status);
    }

    /** @test */
    public function per_weight_pricing_snapshot_correctly(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $product = Product::factory()->create([
            'name' => 'Chocolate Cake',
            'base_price' => 1000,
            'pricing_type' => PricingType::PER_WEIGHT,
            'unit' => 'kg',
            'minimum_quantity' => 1,
            'maximum_quantity' => 20,
        ]);
        $request = $orchestrator->createDraftForCustomer($this->customer);
        $orchestrator->addToCart('product', $product->id, 5.0, []);
        $orchestrator->hydrateRequestFromCart($request);

        $request->refresh();
        $item = $request->items->first();

        $this->assertSame('1000.00', $item->unit_price);
        $this->assertSame('5000.00', $item->subtotal);
        $this->assertSame(RequestItemPricingStatus::CALCULATED, $item->pricing_status);
    }

    /** @test */
    public function tiered_pricing_snapshot_correctly(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $service = Service::factory()->create([
            'name' => 'Event Catering (Tiered)',
            'pricing_type' => PricingType::TIERED,
            'unit' => 'person',
        ]);

        PriceTier::create([
            'priceable_type' => Service::class,
            'priceable_id' => $service->id,
            'min_quantity' => 1,
            'max_quantity' => 50,
            'unit_price' => 700,
        ]);
        PriceTier::create([
            'priceable_type' => Service::class,
            'priceable_id' => $service->id,
            'min_quantity' => 51,
            'max_quantity' => 100,
            'unit_price' => 650,
        ]);

        $request = $orchestrator->createDraftForCustomer($this->customer);
        $orchestrator->addToCart('service', $service->id, 75.0, []);
        $orchestrator->hydrateRequestFromCart($request);

        $request->refresh();
        $item = $request->items->first();

        $this->assertSame('650.00', $item->unit_price);
        $this->assertSame('48750.00', $item->subtotal);
        $this->assertSame(RequestItemPricingStatus::CALCULATED, $item->pricing_status);
    }

    /** @test */
    public function custom_pricing_sets_quotation_required(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $service = Service::factory()->create([
            'name' => 'Full Wedding Catering',
            'pricing_type' => PricingType::CUSTOM,
            'requires_review' => true,
        ]);
        $request = $orchestrator->createDraftForCustomer($this->customer);
        $orchestrator->addToCart('service', $service->id, 1.0, []);
        $orchestrator->hydrateRequestFromCart($request);

        $request->refresh();
        $item = $request->items->first();

        $this->assertNull($item->unit_price);
        $this->assertNull($item->subtotal);
        $this->assertSame(RequestItemPricingStatus::QUOTATION_REQUIRED, $item->pricing_status);
    }
}
