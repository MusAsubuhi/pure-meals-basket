<?php

namespace Tests\Feature\Fulfillment;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Enums\Quotation\QuotationStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Request\Request;
use App\Models\User;
use App\Services\Fulfillment\FulfillmentOrchestrator;
use Database\Factories\QuotationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FulfillmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_can_view_own_fulfillment(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $user->customer()->save($customer);

        $order = $this->createOrderForCustomer($customer);
        $fulfillment = app(FulfillmentOrchestrator::class)->createFromOrder($order);

        $response = $this->actingAs($user)->get(route('fulfillments.show', $fulfillment));

        $response->assertOk();
        $response->assertSee($order->reference);
    }

    /** @test */
    public function customer_cannot_view_other_customer_fulfillment(): void
    {
        $customerA = Customer::factory()->create();
        $userA = User::factory()->create();
        $userA->customer()->save($customerA);

        $customerB = Customer::factory()->create();
        $userB = User::factory()->create();
        $userB->customer()->save($customerB);

        $order = $this->createOrderForCustomer($customerB);
        $fulfillment = app(FulfillmentOrchestrator::class)->createFromOrder($order);

        $response = $this->actingAs($userA)->get(route('fulfillments.show', $fulfillment));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_view_any_fulfillment(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['is_superadmin' => true]);
        $admin->assignRole($role);

        $customer = Customer::factory()->create();
        $order = $this->createOrderForCustomer($customer);
        $fulfillment = app(FulfillmentOrchestrator::class)->createFromOrder($order);

        $response = $this->actingAs($admin)->get(route('fulfillments.show', $fulfillment));

        $response->assertOk();
    }

    /** @test */
    public function guest_cannot_view_fulfillments(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createOrderForCustomer($customer);
        $fulfillment = app(FulfillmentOrchestrator::class)->createFromOrder($order);

        $response = $this->get(route('fulfillments.show', $fulfillment));

        $response->assertRedirect(route('login'));
    }

    private function createOrderForCustomer(Customer $customer): Order
    {
        $request = Request::factory()->create(['customer_id' => $customer->id]);
        $quotation = QuotationFactory::new()->create([
            'request_id' => $request->id,
            'status' => QuotationStatus::ACCEPTED,
        ]);

        return Order::factory()->create([
            'request_id' => $request->id,
            'quotation_id' => $quotation->id,
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
            'amount_paid' => 100000,
            'payment_required' => 100000,
            'total' => 100000,
            'fulfillment_method' => FulfillmentMethod::DELIVERY,
        ]);
    }
}
