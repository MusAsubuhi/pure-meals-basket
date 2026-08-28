<?php

namespace Tests\Unit\Fulfillment;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\FulfillmentStatus;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Enums\Quotation\QuotationStatus;
use App\Models\Fulfillment\Fulfillment;
use App\Models\Order;
use App\Models\Request\Request;
use App\Services\Fulfillment\Exceptions\FulfillmentAlreadyExists;
use App\Services\Fulfillment\Exceptions\InvalidFulfillmentMethod;
use App\Services\Fulfillment\Exceptions\InvalidFulfillmentTransition;
use App\Services\Fulfillment\Exceptions\PaymentNotConfirmed;
use App\Services\Fulfillment\FulfillmentOrchestrator;
use Database\Factories\QuotationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FulfillmentOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private FulfillmentOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orchestrator = app(FulfillmentOrchestrator::class);
    }

    private function createConfirmedOrder(array $overrides = []): Order
    {
        $request = Request::factory()->create();
        $quotation = QuotationFactory::new()->create([
            'request_id' => $request->id,
            'status' => QuotationStatus::ACCEPTED,
        ]);

        $order = Order::factory()->create(array_merge([
            'request_id' => $request->id,
            'quotation_id' => $quotation->id,
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
            'amount_paid' => 100000,
            'payment_required' => 100000,
            'total' => 100000,
            'fulfillment_method' => FulfillmentMethod::DELIVERY,
        ], $overrides));

        return $order;
    }

    /** @test */
    public function confirmed_order_creates_fulfillment(): void
    {
        $order = $this->createConfirmedOrder();

        $fulfillment = $this->orchestrator->createFromOrder($order);

        $this->assertInstanceOf(Fulfillment::class, $fulfillment);
        $this->assertSame($order->id, $fulfillment->order_id);
        $this->assertSame(FulfillmentStatus::PENDING, $fulfillment->status);
        $this->assertSame(FulfillmentMethod::DELIVERY, $fulfillment->method);
        $this->assertCount(1, $fulfillment->events);
        $this->assertSame('FULFILLMENT_CREATED', $fulfillment->events->last()->event_type);
    }

    /** @test */
    public function unpaid_order_rejected_for_fulfillment(): void
    {
        $order = $this->createConfirmedOrder([
            'amount_paid' => 50000,
            'payment_required' => 100000,
        ]);

        $this->expectException(PaymentNotConfirmed::class);
        $this->orchestrator->createFromOrder($order);
    }

    /** @test */
    public function duplicate_fulfillment_rejected(): void
    {
        $order = $this->createConfirmedOrder();

        $this->orchestrator->createFromOrder($order);

        $this->expectException(FulfillmentAlreadyExists::class);
        $this->orchestrator->createFromOrder($order);
    }

    /** @test */
    public function pending_to_preparing(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);

        $fulfillment = $this->orchestrator->startPreparing($fulfillment);

        $this->assertSame(FulfillmentStatus::PREPARING, $fulfillment->status);
        $this->assertNotNull($fulfillment->started_at);
        $this->assertCount(2, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'PREPARATION_STARTED'));
    }

    /** @test */
    public function invalid_preparation_transition_rejected(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);

        $this->expectException(InvalidFulfillmentTransition::class);
        $this->orchestrator->startPreparing($fulfillment);
    }

    /** @test */
    public function preparing_to_ready(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);

        $fulfillment = $this->orchestrator->markReady($fulfillment);

        $this->assertSame(FulfillmentStatus::READY, $fulfillment->status);
        $this->assertNotNull($fulfillment->ready_at);
        $this->assertCount(3, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'ORDER_READY'));
    }

    /** @test */
    public function cannot_skip_preparation(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);

        $this->expectException(InvalidFulfillmentTransition::class);
        $this->orchestrator->markReady($fulfillment);
    }

    /** @test */
    public function delivery_ready_to_out_for_delivery(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);

        $fulfillment = $this->orchestrator->dispatch($fulfillment);

        $this->assertSame(FulfillmentStatus::OUT_FOR_DELIVERY, $fulfillment->status);
        $this->assertNotNull($fulfillment->dispatched_at);
        $this->assertCount(4, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'DISPATCHED'));
    }

    /** @test */
    public function only_delivery_can_dispatch(): void
    {
        $order = $this->createConfirmedOrder(['fulfillment_method' => FulfillmentMethod::CUSTOMER_COLLECTION]);
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);

        $this->expectException(InvalidFulfillmentMethod::class);
        $this->orchestrator->dispatch($fulfillment);
    }

    /** @test */
    public function out_for_delivery_to_delivered(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);
        $this->orchestrator->dispatch($fulfillment);

        $fulfillment = $this->orchestrator->markDelivered($fulfillment, 'John Doe');

        $this->assertSame(FulfillmentStatus::DELIVERED, $fulfillment->status);
        $this->assertNotNull($fulfillment->delivered_at);
        $this->assertSame('John Doe', $fulfillment->recipient_name);
        $this->assertCount(5, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'DELIVERED'));
    }

    /** @test */
    public function collection_ready_to_collected(): void
    {
        $order = $this->createConfirmedOrder(['fulfillment_method' => FulfillmentMethod::CUSTOMER_COLLECTION]);
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);

        $fulfillment = $this->orchestrator->markCollected($fulfillment, 'Jane Doe');

        $this->assertSame(FulfillmentStatus::COLLECTED, $fulfillment->status);
        $this->assertNotNull($fulfillment->collected_at);
        $this->assertSame('Jane Doe', $fulfillment->recipient_name);
        $this->assertCount(4, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'COLLECTED'));
    }

    /** @test */
    public function collection_cannot_dispatch(): void
    {
        $order = $this->createConfirmedOrder(['fulfillment_method' => FulfillmentMethod::CUSTOMER_COLLECTION]);
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);

        $this->expectException(InvalidFulfillmentMethod::class);
        $this->orchestrator->dispatch($fulfillment);
    }

    /** @test */
    public function on_site_ready_to_service_in_progress(): void
    {
        $order = $this->createConfirmedOrder(['fulfillment_method' => FulfillmentMethod::ON_SITE_SERVICE]);
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);

        $fulfillment = $this->orchestrator->startService($fulfillment);

        $this->assertSame(FulfillmentStatus::SERVICE_IN_PROGRESS, $fulfillment->status);
        $this->assertNotNull($fulfillment->service_started_at);
        $this->assertCount(4, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'SERVICE_STARTED'));
    }

    /** @test */
    public function on_site_service_to_completed(): void
    {
        $order = $this->createConfirmedOrder(['fulfillment_method' => FulfillmentMethod::ON_SITE_SERVICE]);
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);
        $this->orchestrator->startService($fulfillment);

        $fulfillment = $this->orchestrator->complete($fulfillment);

        $this->assertSame(FulfillmentStatus::COMPLETED, $fulfillment->status);
        $this->assertNotNull($fulfillment->completed_at);
        $this->assertCount(5, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'FULFILLMENT_COMPLETED'));
    }

    /** @test */
    public function out_for_delivery_to_failed(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);
        $this->orchestrator->dispatch($fulfillment);

        $fulfillment = $this->orchestrator->markDeliveryFailed($fulfillment, 'Customer not available');

        $this->assertSame(FulfillmentStatus::DELIVERY_FAILED, $fulfillment->status);
        $this->assertSame('Customer not available', $fulfillment->failure_reason);
        $this->assertCount(5, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'DELIVERY_FAILED'));
    }

    /** @test */
    public function delivery_failure_requires_reason(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);
        $this->orchestrator->dispatch($fulfillment);

        $this->expectException(\InvalidArgumentException::class);
        $this->orchestrator->markDeliveryFailed($fulfillment, '');
    }

    /** @test */
    public function retry_delivery_returns_to_ready(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);
        $this->orchestrator->dispatch($fulfillment);
        $this->orchestrator->markDeliveryFailed($fulfillment, 'Customer not available');

        $fulfillment = $this->orchestrator->retryDelivery($fulfillment);

        $this->assertSame(FulfillmentStatus::READY, $fulfillment->status);
        $this->assertNull($fulfillment->failure_reason);
        $this->assertCount(6, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'RETRY_INITIATED'));
    }

    /** @test */
    public function delivery_can_complete_after_delivered(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);
        $this->orchestrator->dispatch($fulfillment);
        $this->orchestrator->markDelivered($fulfillment);

        $fulfillment = $this->orchestrator->complete($fulfillment);

        $this->assertSame(FulfillmentStatus::COMPLETED, $fulfillment->status);
    }

    /** @test */
    public function collection_can_complete_after_collected(): void
    {
        $order = $this->createConfirmedOrder(['fulfillment_method' => FulfillmentMethod::CUSTOMER_COLLECTION]);
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);
        $this->orchestrator->markCollected($fulfillment);

        $fulfillment = $this->orchestrator->complete($fulfillment);

        $this->assertSame(FulfillmentStatus::COMPLETED, $fulfillment->status);
    }

    /** @test */
    public function on_site_can_complete_after_service(): void
    {
        $order = $this->createConfirmedOrder(['fulfillment_method' => FulfillmentMethod::ON_SITE_SERVICE]);
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);
        $this->orchestrator->startService($fulfillment);

        $fulfillment = $this->orchestrator->complete($fulfillment);

        $this->assertSame(FulfillmentStatus::COMPLETED, $fulfillment->status);
    }

    /** @test */
    public function valid_states_can_cancel(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);

        $fulfillment = $this->orchestrator->cancel($fulfillment);

        $this->assertSame(FulfillmentStatus::CANCELLED, $fulfillment->status);
        $this->assertCount(2, $fulfillment->events);
        $this->assertTrue($fulfillment->events->contains('event_type', 'FULFILLMENT_CANCELLED'));
    }

    /** @test */
    public function completed_cannot_cancel(): void
    {
        $order = $this->createConfirmedOrder();
        $fulfillment = $this->orchestrator->createFromOrder($order);
        $this->orchestrator->startPreparing($fulfillment);
        $this->orchestrator->markReady($fulfillment);
        $this->orchestrator->dispatch($fulfillment);
        $this->orchestrator->markDelivered($fulfillment);
        $this->orchestrator->complete($fulfillment);

        $this->expectException(InvalidFulfillmentTransition::class);
        $this->orchestrator->cancel($fulfillment);
    }
}
