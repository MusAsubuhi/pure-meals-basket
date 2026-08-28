<?php

namespace Tests\Unit\Request;

use App\Enums\PricingType;
use App\Enums\Request\RequestItemPricingStatus;
use App\Enums\Request\RequestStatus;
use App\Models\Customer;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\Service;
use App\Services\Pricing\ProductPricingService;
use App\Services\Pricing\TierOverflowException;
use App\Services\Request\RequestOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private RequestOrchestrator $orchestrator;

    private ProductPricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricing = app(ProductPricingService::class);
        $this->orchestrator = new RequestOrchestrator($this->pricing);
    }

    /** @test */
    public function cart_starts_empty(): void
    {
        $this->assertSame([], $this->orchestrator->cart());
    }

    /** @test */
    public function add_to_cart_persists_item(): void
    {
        $product = Product::factory()->create(['base_price' => 1000, 'unit' => 'kg']);

        $this->orchestrator->addToCart('product', $product->id, 5.0, []);

        $cart = $this->orchestrator->cart();
        $this->assertCount(1, $cart);
        $this->assertSame('product:1', array_key_first($cart));
        $this->assertSame('product', $cart['product:1']['item_type']);
        $this->assertSame(5.0, $cart['product:1']['quantity']);
    }

    /** @test */
    public function adding_same_item_overwrites_quantity(): void
    {
        $product = Product::factory()->create();

        $this->orchestrator->addToCart('product', $product->id, 2.0, []);
        $this->orchestrator->addToCart('product', $product->id, 5.0, []);

        $this->assertCount(1, $this->orchestrator->cart());
        $this->assertSame(5.0, $this->orchestrator->cart()['product:1']['quantity']);
    }

    /** @test */
    public function remove_from_cart_deletes_item(): void
    {
        $product = Product::factory()->create();

        $this->orchestrator->addToCart('product', $product->id, 2.0, []);
        $this->orchestrator->removeFromCart('product:1');

        $this->assertSame([], $this->orchestrator->cart());
    }

    /** @test */
    public function clear_cart_empties_cart(): void
    {
        Product::factory()->create();
        Product::factory()->create();

        $this->orchestrator->addToCart('product', 1, 2.0, []);
        $this->orchestrator->addToCart('product', 2, 3.0, []);

        $this->orchestrator->clearCart();

        $this->assertSame([], $this->orchestrator->cart());
    }

    /** @test */
    public function create_draft_generates_reference_and_logs_event(): void
    {
        $customer = Customer::factory()->create();

        $request = $this->orchestrator->createDraftForCustomer($customer);

        $this->assertSame(RequestStatus::DRAFT, $request->status);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{4}$/', $request->reference);
        $this->assertCount(1, $request->events);
        $this->assertSame('REQUEST_CREATED', $request->events->first()->event_type);
    }

    /** @test */
    public function hydrate_request_from_cart_creates_items_and_clears_cart(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->perWeight()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);

        $this->orchestrator->addToCart('product', $product->id, 3.0, []);
        $this->orchestrator->hydrateRequestFromCart($request);

        $request->refresh();
        $this->assertCount(1, $request->items);
        $this->assertSame([], $this->orchestrator->cart());
        $this->assertSame(RequestItemPricingStatus::CALCULATED, $request->items->first()->pricing_status);
    }

    /** @test */
    public function hydrate_request_with_custom_item_sets_quotation_required(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->custom()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);

        $this->orchestrator->addToCart('service', $service->id, 1.0, []);
        $this->orchestrator->hydrateRequestFromCart($request);

        $request->refresh();
        $this->assertSame(RequestStatus::QUOTATION_REQUIRED, $request->status);
        $this->assertSame(RequestItemPricingStatus::QUOTATION_REQUIRED, $request->items->first()->pricing_status);
    }

    /** @test */
    public function hydrate_request_with_mixed_items_sets_quotation_required(): void
    {
        $customer = Customer::factory()->create();
        $fixedProduct = Product::factory()->create(['base_price' => 1500, 'pricing_type' => PricingType::FIXED]);
        $customService = Service::factory()->custom()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);

        $this->orchestrator->addToCart('product', $fixedProduct->id, 1.0, []);
        $this->orchestrator->addToCart('service', $customService->id, 1.0, []);
        $this->orchestrator->hydrateRequestFromCart($request);

        $request->refresh();
        $this->assertSame(RequestStatus::QUOTATION_REQUIRED, $request->status);
        $this->assertCount(2, $request->items);

        $calculated = $request->items->firstWhere('item_type', 'product');
        $this->assertSame(RequestItemPricingStatus::CALCULATED, $calculated->pricing_status);
        $this->assertSame('1500.00', $calculated->unit_price);

        $quoted = $request->items->firstWhere('item_type', 'service');
        $this->assertSame(RequestItemPricingStatus::QUOTATION_REQUIRED, $quoted->pricing_status);
        $this->assertNull($quoted->unit_price);
    }

    /** @test */
    public function submit_request_transitions_draft_to_submitted(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);

        $this->orchestrator->submitRequest($request);

        $request->refresh();
        $this->assertSame(RequestStatus::SUBMITTED, $request->status);
        $this->assertCount(2, $request->events);
        $this->assertSame('REQUEST_SUBMITTED', $request->events->last()->event_type);
    }

    /** @test */
    public function start_review_transitions_submitted_to_under_review(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);
        $this->orchestrator->submitRequest($request);

        $this->orchestrator->startReview($request, 1);

        $request->refresh();
        $this->assertSame(RequestStatus::UNDER_REVIEW, $request->status);
    }

    /** @test */
    public function request_information_transitions_to_needs_information(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);
        $this->orchestrator->submitRequest($request);
        $this->orchestrator->startReview($request, 1);

        $clarification = $this->orchestrator->requestInformation($request, 1, 'What time is delivery?');

        $request->refresh();
        $this->assertSame(RequestStatus::NEEDS_INFORMATION, $request->status);
        $this->assertSame('What time is delivery?', $clarification->question);
        $this->assertFalse($clarification->hasBeenAnswered());
    }

    /** @test */
    public function respond_to_clarification_returns_to_under_review(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);
        $this->orchestrator->submitRequest($request);
        $this->orchestrator->startReview($request, 1);
        $clarification = $this->orchestrator->requestInformation($request, 1, 'Delivery time?');

        $this->orchestrator->respondToClarification($clarification, $customer->id, '12:30 PM');

        $clarification->refresh();
        $request->refresh();
        $this->assertTrue($clarification->hasBeenAnswered());
        $this->assertSame('12:30 PM', $clarification->response);
        $this->assertSame(RequestStatus::UNDER_REVIEW, $request->status);
    }

    /** @test */
    public function mark_ready_for_checkout_transitions_from_under_review(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);
        $this->orchestrator->submitRequest($request);
        $this->orchestrator->startReview($request, 1);

        $this->orchestrator->markReadyForCheckout($request, 1);

        $request->refresh();
        $this->assertSame(RequestStatus::READY_FOR_CHECKOUT, $request->status);
    }

    /** @test */
    public function decline_transitions_to_declined(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);
        $this->orchestrator->submitRequest($request);

        $this->orchestrator->decline($request, 1, 'Out of stock');

        $request->refresh();
        $this->assertSame(RequestStatus::DECLINED, $request->status);
    }

    /** @test */
    public function cancel_allowed_from_draft_and_submitted(): void
    {
        $customer = Customer::Factory()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);

        $this->orchestrator->cancel($request, $customer->id, 'Changed mind');
        $request->refresh();
        $this->assertSame(RequestStatus::CANCELLED, $request->status);
    }

    /** @test */
    public function cancel_not_allowed_from_under_review(): void
    {
        $customer = Customer::factory()->create();
        $request = $this->orchestrator->createDraftForCustomer($customer);
        $this->orchestrator->submitRequest($request);
        $this->orchestrator->startReview($request, 1);

        $this->expectException(\RuntimeException::class);
        $this->orchestrator->cancel($request, $customer->id, 'Changed mind');
    }

    /** @test */
    public function tiered_pricing_selects_correct_bracket(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->perPerson()->create();

        PriceTier::create([
            'priceable_type' => Service::class,
            'priceable_id' => $service->id,
            'min_quantity' => 1,
            'max_quantity' => 50,
            'unit_price' => 700,
            'label' => 'Small',
        ]);
        PriceTier::create([
            'priceable_type' => Service::class,
            'priceable_id' => $service->id,
            'min_quantity' => 51,
            'max_quantity' => 100,
            'unit_price' => 650,
            'label' => 'Medium',
        ]);
        PriceTier::create([
            'priceable_type' => Service::class,
            'priceable_id' => $service->id,
            'min_quantity' => 101,
            'max_quantity' => null,
            'unit_price' => 600,
            'label' => 'Large',
        ]);

        $request = $this->orchestrator->createDraftForCustomer($customer);
        $this->orchestrator->addToCart('service', $service->id, 75.0, []);
        $this->orchestrator->hydrateRequestFromCart($request);

        $request->refresh();
        $this->assertSame('650.00', $request->items->first()->unit_price);
        $this->assertSame('48750.00', $request->items->first()->subtotal);
    }

    /** @test */
    public function tier_overflow_throws_exception(): void
    {
        $customer = Customer::factory()->create();
        $service = Service::factory()->create([
            'name' => 'Event Catering (Tiered)',
            'pricing_type' => PricingType::TIERED,
            'unit' => 'person',
            'base_price' => null,
        ]);

        PriceTier::create([
            'priceable_type' => Service::class,
            'priceable_id' => $service->id,
            'min_quantity' => 1,
            'max_quantity' => 50,
            'unit_price' => 700,
        ]);

        $request = $this->orchestrator->createDraftForCustomer($customer);
        $this->orchestrator->addToCart('service', $service->id, 200.0, []);

        $this->expectException(TierOverflowException::class);
        $this->orchestrator->hydrateRequestFromCart($request);
    }
}
