<?php

namespace Tests\Feature\Request;

use App\Enums\Request\RequestItemPricingStatus;
use App\Enums\Request\RequestStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\Service;
use App\Services\Request\RequestOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MixedRequestTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Product $fixedProduct;
    private Product $weightProduct;
    private Service $tieredService;
    private Service $customService;

    protected function setUp(): void
    {
        parent::setUp();

        $user = \App\Models\User::factory()->create();
        $this->customer = Customer::factory()->create(['user_id' => $user->id]);

        $this->fixedProduct = Product::factory()->create([
            'name' => 'Celebration Box',
            'base_price' => 1500,
            'pricing_type' => \App\Enums\PricingType::FIXED,
            'unit' => null,
            'minimum_quantity' => null,
            'maximum_quantity' => null,
        ]);

        $this->weightProduct = Product::factory()->create([
            'name' => 'Chocolate Cake',
            'base_price' => 1000,
            'pricing_type' => \App\Enums\PricingType::PER_WEIGHT,
            'unit' => 'kg',
            'minimum_quantity' => 1,
            'maximum_quantity' => 20,
        ]);

        $this->tieredService = Service::factory()->create([
            'name' => 'Event Catering (Tiered)',
            'pricing_type' => \App\Enums\PricingType::TIERED,
            'unit' => 'person',
        ]);

        $this->customService = Service::factory()->create([
            'name' => 'Full Wedding Catering',
            'pricing_type' => \App\Enums\PricingType::CUSTOM,
            'requires_review' => true,
        ]);

        PriceTier::create([
            'priceable_type' => Service::class,
            'priceable_id' => $this->tieredService->id,
            'min_quantity' => 1,
            'max_quantity' => 50,
            'unit_price' => 700,
        ]);
        PriceTier::create([
            'priceable_type' => Service::class,
            'priceable_id' => $this->tieredService->id,
            'min_quantity' => 51,
            'max_quantity' => null,
            'unit_price' => 600,
        ]);
    }

    /** @test */
    public function mixed_request_sets_quotation_required_when_any_item_needs_quote(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $request = $orchestrator->createDraftForCustomer($this->customer);

        $orchestrator->addToCart('product', $this->fixedProduct->id, 1.0, []);
        $orchestrator->addToCart('product', $this->weightProduct->id, 5.0, []);
        $orchestrator->addToCart('service', $this->customService->id, 1.0, []);
        $orchestrator->hydrateRequestFromCart($request);

        $request->refresh();

        $this->assertSame(RequestStatus::QUOTATION_REQUIRED, $request->status);
        $this->assertCount(3, $request->items);

        $fixed = $request->items->firstWhere('name', 'Celebration Box');
        $this->assertSame(RequestItemPricingStatus::CALCULATED, $fixed->pricing_status);
        $this->assertSame('1500.00', $fixed->unit_price);

        $weight = $request->items->firstWhere('name', 'Chocolate Cake');
        $this->assertSame(RequestItemPricingStatus::CALCULATED, $weight->pricing_status);
        $this->assertSame('5000.00', $weight->subtotal);

        $custom = $request->items->firstWhere('name', 'Full Wedding Catering');
        $this->assertSame(RequestItemPricingStatus::QUOTATION_REQUIRED, $custom->pricing_status);
        $this->assertNull($custom->unit_price);
    }

    /** @test */
    public function all_calculated_items_keep_draft_status(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $request = $orchestrator->createDraftForCustomer($this->customer);

        $orchestrator->addToCart('product', $this->fixedProduct->id, 1.0, []);
        $orchestrator->addToCart('product', $this->weightProduct->id, 5.0, []);
        $orchestrator->hydrateRequestFromCart($request);

        $request->refresh();

        $this->assertSame(RequestStatus::DRAFT, $request->status);
        $this->assertCount(2, $request->items);
    }

    /** @test */
    public function tiered_service_prices_correctly_in_mixed_request(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $request = $orchestrator->createDraftForCustomer($this->customer);

        $orchestrator->addToCart('service', $this->tieredService->id, 75.0, []);
        $orchestrator->hydrateRequestFromCart($request);

        $request->refresh();
        $item = $request->items->first();

        $this->assertSame('600.00', $item->unit_price);
        $this->assertSame('45000.00', $item->subtotal);
    }
}
