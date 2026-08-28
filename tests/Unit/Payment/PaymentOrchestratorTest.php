<?php

namespace Tests\Unit\Payment;

use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Enums\Payment\PaymentMethod;
use App\Enums\Payment\PaymentProvider;
use App\Enums\Payment\PaymentStatus as PaymentPaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Request\Request;
use App\Services\Order\OrderOrchestrator;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentResult;
use App\Services\Payment\PaymentOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private PaymentOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orchestrator = app(PaymentOrchestrator::class);
    }

    /** @test */
    public function initiate_mpesa_creates_pending_payment_and_attempt(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_required' => 1000,
            'amount_paid' => 0,
            'balance_due' => 1000,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('initiateMpesa')
            ->once()
            ->andReturn(PaymentResult::success([
                'status' => PaymentPaymentStatus::PROCESSING->value,
                'provider_payment_id' => 'PNX-123',
                'provider_reference' => 'PNX-REF-123',
                'checkout_request_id' => 'ws_CO_123',
                'message' => 'STK Push sent.',
            ]));

        $orchestrator = new PaymentOrchestrator($gateway, app(OrderOrchestrator::class));
        $payment = $orchestrator->initiateMpesa($order, '0712345678');

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame(PaymentPaymentStatus::PROCESSING, $payment->status);
        $this->assertSame(PaymentMethod::MPESA, $payment->method);
        $this->assertSame(PaymentProvider::PAYNEXUS, $payment->provider);
        $this->assertSame('1000.00', $payment->amount);
        $this->assertSame('ws_CO_123', $payment->checkout_request_id);
        $this->assertSame('PNX-REF-123', $payment->provider_reference);
        $this->assertMatchesRegularExpression('/^PAY-\d{4}-\d{4}$/', $payment->reference);
        $this->assertCount(1, $payment->attempts);
        $this->assertCount(2, $payment->events);
        $this->assertSame('INITIATED', $payment->events->first()->event_type);
    }

    /** @test */
    public function initiate_mpesa_throws_for_non_pending_order(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->confirmed()->forRequest($request)->create();

        $this->expectException(\RuntimeException::class);
        $this->orchestrator->initiateMpesa($order, '0712345678');
    }

    /** @test */
    public function initiate_mpesa_throws_when_no_outstanding_balance(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_required' => 1000,
            'amount_paid' => 1000,
            'balance_due' => 0,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->orchestrator->initiateMpesa($order, '0712345678');
    }

    /** @test */
    public function record_cash_creates_pending_payment(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 2000,
            'discount' => 0,
            'total' => 2000,
            'payment_required' => 2000,
            'amount_paid' => 0,
            'balance_due' => 2000,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $payment = $this->orchestrator->recordCash($order);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame(PaymentPaymentStatus::PENDING, $payment->status);
        $this->assertSame(PaymentMethod::CASH, $payment->method);
        $this->assertSame(PaymentProvider::CASH, $payment->provider);
        $this->assertSame('2000.00', $payment->amount);
        $this->assertCount(1, $payment->events);
        $this->assertSame('INITIATED', $payment->events->first()->event_type);
    }

    /** @test */
    public function confirm_cash_marks_payment_successful_and_confirms_order(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 1500,
            'discount' => 0,
            'total' => 1500,
            'payment_required' => 1500,
            'amount_paid' => 0,
            'balance_due' => 1500,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $payment = $this->orchestrator->recordCash($order);
        $confirmed = $this->orchestrator->confirmCash($payment);

        $this->assertSame(PaymentPaymentStatus::SUCCESS, $confirmed->status);
        $this->assertNotNull($confirmed->paid_at);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
    }

    /** @test */
    public function confirm_cash_throws_for_non_cash_payment(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_required' => 1000,
            'amount_paid' => 0,
            'balance_due' => 1000,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MPESA,
            'provider' => PaymentProvider::PAYNEXUS,
            'status' => PaymentPaymentStatus::PENDING,
            'amount' => 1000,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->orchestrator->confirmCash($payment);
    }

    /** @test */
    public function handle_provider_success_updates_payment_and_confirms_order(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 500,
            'discount' => 0,
            'total' => 500,
            'payment_required' => 500,
            'amount_paid' => 0,
            'balance_due' => 500,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MPESA,
            'provider' => PaymentProvider::PAYNEXUS,
            'status' => PaymentPaymentStatus::PROCESSING,
            'amount' => 500,
            'checkout_request_id' => 'ws_CO_999',
        ]);

        $confirmed = $this->orchestrator->handleProviderSuccess($payment, [
            'provider_payment_id' => 'PNX-999',
            'provider_reference' => 'PNX-REF-999',
        ]);

        $this->assertSame(PaymentPaymentStatus::SUCCESS, $confirmed->status);
        $this->assertSame('PNX-999', $confirmed->provider_payment_id);
        $this->assertSame('PNX-REF-999', $confirmed->provider_reference);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
    }

    /** @test */
    public function handle_provider_failure_marks_payment_failed(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 500,
            'discount' => 0,
            'total' => 500,
            'payment_required' => 500,
            'amount_paid' => 0,
            'balance_due' => 500,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MPESA,
            'provider' => PaymentProvider::PAYNEXUS,
            'status' => PaymentPaymentStatus::PROCESSING,
            'amount' => 500,
        ]);

        $failed = $this->orchestrator->handleProviderFailure($payment, 'Insufficient funds');

        $this->assertSame(PaymentPaymentStatus::FAILED, $failed->status);
        $this->assertSame(OrderStatus::PENDING_PAYMENT, $order->fresh()->status);
    }

    /** @test */
    public function partial_payments_aggregate_and_confirm_order_when_threshold_reached(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_required' => 1000,
            'amount_paid' => 0,
            'balance_due' => 1000,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $payment1 = Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MPESA,
            'provider' => PaymentProvider::PAYNEXUS,
            'status' => PaymentPaymentStatus::SUCCESS,
            'amount' => 400,
            'paid_at' => now(),
        ]);

        $payment2 = Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CASH,
            'provider' => PaymentProvider::CASH,
            'status' => PaymentPaymentStatus::PENDING,
            'amount' => 600,
        ]);

        $this->orchestrator->confirmCash($payment2);

        $this->assertSame(PaymentPaymentStatus::SUCCESS, $payment2->fresh()->status);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
    }
}
