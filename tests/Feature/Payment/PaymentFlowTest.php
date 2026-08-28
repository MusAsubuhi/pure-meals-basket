<?php

namespace Tests\Feature\Payment;

use App\Enums\Order\PaymentStatus;
use App\Enums\Payment\PaymentStatus as PaymentPaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Request\Request;
use App\Models\User;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function customer_can_view_payment_page_for_order(): void
    {
        $request = Request::factory()->create(['customer_id' => $this->customer->id]);
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_required' => 1000,
            'amount_paid' => 0,
            'balance_due' => 1000,
            'payment_status' => PaymentStatus::UNPAID,
            'customer_name' => $this->user->name,
            'customer_phone' => $this->user->email,
        ]);

        $response = $this->get(route('payments.index', $order));

        $response->assertStatus(200);
        $response->assertSee('Payments');
        $response->assertSee($order->reference);
        $response->assertSee('Outstanding balance');
    }

    /** @test */
    public function customer_can_initiate_mpesa_payment(): void
    {
        $request = Request::factory()->create(['customer_id' => $this->customer->id]);
        $order = Order::factory()->pendingPayment()->forRequest($request)->create([
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_required' => 1000,
            'amount_paid' => 0,
            'balance_due' => 1000,
            'payment_status' => PaymentStatus::UNPAID,
            'customer_name' => $this->user->name,
            'customer_phone' => $this->user->email,
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

        $this->app->instance(PaymentGateway::class, $gateway);

        $response = $this->post(route('payments.mpesa', $order), [
            'phone' => '0712345678',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_attempts', 1);
        $this->assertDatabaseCount('payment_events', 2);
    }

    /** @test */
    public function guest_cannot_access_payment_page(): void
    {
        $request = Request::factory()->create();
        $order = Order::factory()->pendingPayment()->forRequest($request)->create();

        $response = $this->get(route('payments.index', $order));

        $response->assertStatus(403);
    }
}
