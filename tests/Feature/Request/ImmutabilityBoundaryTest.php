<?php

namespace Tests\Feature\Request;

use App\Enums\Request\RequestStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\Request\RequestOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImmutabilityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Customer $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->customer = Customer::factory()->create(['user_id' => $user->id]);
        $this->otherCustomer = Customer::factory()->create();
    }

    /** @test */
    public function customer_cannot_modify_submitted_request(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $request = $orchestrator->createDraftForCustomer($this->customer);
        $orchestrator->submitRequest($request);

        $this->actingAs($this->customer->user);

        $response = $this->post('/requests/submit', [
            'request_id' => $request->id,
            'event_date' => '2026-12-26',
            'event_time' => '13:00',
            'location' => 'New Location',
            'notes' => 'Updated notes',
        ]);

        $response->assertSessionHas('error');

        $request->refresh();
        $this->assertSame(RequestStatus::SUBMITTED, $request->status);
    }

    /** @test */
    public function customer_cannot_access_other_customer_request(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $request = $orchestrator->createDraftForCustomer($this->customer);

        $this->actingAs($this->otherCustomer->user);

        $response = $this->get("/requests/{$request->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function request_items_are_immutable_after_submission(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $product = Product::factory()->create(['base_price' => 1000]);
        $request = $orchestrator->createDraftForCustomer($this->customer);
        $orchestrator->addToCart('product', $product->id, 5.0, []);
        $orchestrator->hydrateRequestFromCart($request);
        $orchestrator->submitRequest($request);

        $request->refresh();
        $originalItem = $request->items->first();
        $originalSubtotal = $originalItem->subtotal;

        $product->update(['base_price' => 500]);

        $orchestrator->calculateRequestTotals($request);

        $request->refresh();
        $this->assertSame($originalSubtotal, $request->items->first()->subtotal);
    }
}
