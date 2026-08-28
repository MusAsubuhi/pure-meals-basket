<?php

namespace Tests\Unit\Order;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Enums\Quotation\QuotationStatus;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\Request\Request;
use App\Services\Order\OrderOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private OrderOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orchestrator = app(OrderOrchestrator::class);
    }

    /** @test */
    public function create_from_accepted_quotation_creates_pending_payment_order(): void
    {
        $request = Request::factory()->create();
        $quotation = Quotation::factory()->create([
            'request_id' => $request->id,
            'status' => QuotationStatus::ACCEPTED,
        ]);
        $quotation->items()->create([
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
            'subtotal' => 12750000,
        ]);
        $quotation->update([
            'subtotal' => 12750000,
            'total' => 12750000,
        ]);

        $order = $this->orchestrator->createFromQuotation($quotation);

        $this->assertSame(OrderStatus::PENDING_PAYMENT, $order->status);
        $this->assertSame(PaymentStatus::UNPAID, $order->payment_status);
        $this->assertSame('12750000.00', $order->subtotal);
        $this->assertSame('12750000.00', $order->total);
        $this->assertSame('12750000.00', $order->payment_required);
        $this->assertSame('0.00', $order->amount_paid);
        $this->assertSame('12750000.00', $order->balance_due);
        $this->assertSame($request->id, $order->request_id);
        $this->assertSame($quotation->id, $order->quotation_id);
        $this->assertCount(1, $order->items);
        $this->assertCount(1, $order->events);
        $this->assertSame('CREATED', $order->events->first()->event_type);
    }

    /** @test */
    public function create_throws_for_non_accepted_quotation(): void
    {
        $request = Request::factory()->create();
        $quotation = Quotation::factory()->create([
            'request_id' => $request->id,
            'status' => QuotationStatus::DRAFT,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->orchestrator->createFromQuotation($quotation);
    }

    /** @test */
    public function create_throws_for_duplicate_order(): void
    {
        $request = Request::factory()->create();
        $quotation = Quotation::factory()->create([
            'request_id' => $request->id,
            'status' => QuotationStatus::ACCEPTED,
        ]);

        $this->orchestrator->createFromQuotation($quotation);

        $this->expectException(\RuntimeException::class);
        $this->orchestrator->createFromQuotation($quotation);
    }

    /** @test */
    public function confirm_after_payment_transitions_to_confirmed(): void
    {
        $order = Order::factory()->pendingPayment()->create([
            'total' => 100000,
            'payment_required' => 30000,
            'amount_paid' => 30000,
        ]);

        $order = $this->orchestrator->confirmAfterPayment($order);

        $this->assertSame(OrderStatus::CONFIRMED, $order->status);
        $this->assertSame(PaymentStatus::PARTIALLY_PAID, $order->payment_status);
    }

    /** @test */
    public function confirm_throws_with_insufficient_payment(): void
    {
        $order = Order::factory()->pendingPayment()->create(['amount_paid' => 10000, 'payment_required' => 30000]);

        $this->expectException(\RuntimeException::class);
        $this->orchestrator->confirmAfterPayment($order);
    }

    /** @test */
    public function cancel_transitions_to_cancelled(): void
    {
        $order = Order::factory()->pendingPayment()->create();

        $order = $this->orchestrator->cancel($order);

        $this->assertSame(OrderStatus::CANCELLED, $order->status);
    }

    /** @test */
    public function cancel_throws_for_confirmed_order(): void
    {
        $order = Order::factory()->confirmed()->create();

        $this->expectException(\RuntimeException::class);
        $this->orchestrator->cancel($order);
    }

    /** @test */
    public function fulfillment_flow_delivery(): void
    {
        $order = Order::factory()->confirmed()->create();

        $order = $this->orchestrator->startPreparing($order);
        $this->assertSame(OrderStatus::PREPARING, $order->status);

        $order = $this->orchestrator->markReady($order);
        $this->assertSame(OrderStatus::READY, $order->status);

        $order->update(['fulfillment_method' => FulfillmentMethod::DELIVERY]);
        $order = $this->orchestrator->dispatch($order);
        $this->assertSame(OrderStatus::OUT_FOR_DELIVERY, $order->status);

        $order = $this->orchestrator->markDelivered($order);
        $this->assertSame(OrderStatus::DELIVERED, $order->status);

        $order = $this->orchestrator->complete($order);
        $this->assertSame(OrderStatus::COMPLETED, $order->status);
    }

    /** @test */
    public function record_payment_updates_amounts_and_status(): void
    {
        $order = Order::factory()->pendingPayment()->create(['total' => 100000, 'payment_required' => 30000]);

        $order = $this->orchestrator->recordPayment($order, 30000);

        $this->assertSame('30000.00', $order->amount_paid);
        $this->assertSame('70000.00', $order->balance_due);
        $this->assertSame(PaymentStatus::PARTIALLY_PAID, $order->payment_status);
    }

    /** @test */
    public function record_full_payment_marks_as_paid(): void
    {
        $order = Order::factory()->pendingPayment()->create(['total' => 100000, 'payment_required' => 100000]);

        $order = $this->orchestrator->recordPayment($order, 100000);

        $this->assertSame('100000.00', $order->amount_paid);
        $this->assertSame('0.00', $order->balance_due);
        $this->assertSame(PaymentStatus::PAID, $order->payment_status);
    }
}
