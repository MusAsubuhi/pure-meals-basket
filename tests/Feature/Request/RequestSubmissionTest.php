<?php

namespace Tests\Feature\Request;

use App\Enums\Request\RequestItemPricingStatus;
use App\Enums\Request\RequestStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Services\Request\RequestOrchestrator;
use App\Services\Pricing\ProductPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class RequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Customer $otherCustomer;
    private Product $product;
    private ProductPricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricing = app(ProductPricingService::class);

        $user = \App\Models\User::factory()->create();
        $this->customer = Customer::factory()->create(['user_id' => $user->id]);
        $this->otherCustomer = Customer::factory()->create();
        $this->product = Product::factory()->create(['base_price' => 1000, 'unit' => 'kg']);

        $this->actingAs($user);
    }

    /** @test */
    public function customer_can_view_request_history(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $orchestrator->createDraftForCustomer($this->customer);

        $response = $this->get('/requests');

        $response->assertStatus(200);
    }

    /** @test */
    public function customer_can_create_draft_and_submit_request(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $request = $orchestrator->createDraftForCustomer($this->customer);

        Session::put('pmb_request_cart', [
            'product:1' => [
                'item_type' => 'product',
                'item_id' => $this->product->id,
                'quantity' => 5.0,
                'option_ids' => [],
            ],
        ]);

        $response = $this->post('/requests/submit', [
            'request_id' => $request->id,
            'event_date' => '2026-12-25',
            'event_time' => '12:00',
            'location' => 'Mombasa Island',
            'notes' => 'Please deliver early.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $request->refresh();
        $this->assertSame(RequestStatus::SUBMITTED, $request->status);
        $this->assertNotNull($request->submitted_at);
        $this->assertCount(1, $request->items);
    }
}
