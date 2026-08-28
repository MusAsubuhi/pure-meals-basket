<?php

namespace Tests\Feature\Request;

use App\Enums\Request\RequestStatus;
use App\Models\Customer;
use App\Models\Request\Request;
use App\Models\Request\RequestClarification;
use App\Services\Request\RequestOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClarificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Request $request;
    private RequestClarification $clarification;

    protected function setUp(): void
    {
        parent::setUp();

        $user = \App\Models\User::factory()->create();
        $this->customer = Customer::factory()->create(['user_id' => $user->id]);
        $orchestrator = app(RequestOrchestrator::class);
        $this->request = $orchestrator->createDraftForCustomer($this->customer);
        $orchestrator->submitRequest($this->request);
        $orchestrator->startReview($this->request, 1);
    }

    /** @test */
    public function staff_can_request_information(): void
    {
        $orchestrator = app(RequestOrchestrator::class);

        $this->clarification = $orchestrator->requestInformation($this->request, 1, 'What time is delivery?');

        $this->request->refresh();

        $this->assertSame(RequestStatus::NEEDS_INFORMATION, $this->request->status);
        $this->assertSame('What time is delivery?', $this->clarification->question);
        $this->assertFalse($this->clarification->hasBeenAnswered());
        $this->assertCount(4, $this->request->events);
    }

    /** @test */
    public function customer_can_respond_to_clarification(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $this->clarification = $orchestrator->requestInformation($this->request, 1, 'Delivery time?');

        $this->actingAs($this->customer->user);
        $response = $this->post("/requests/{$this->clarification->id}/respond", [
            'response' => '12:30 PM',
        ]);

        $response->assertSessionHas('success');

        $this->clarification->refresh();
        $this->request->refresh();

        $this->assertTrue($this->clarification->hasBeenAnswered());
        $this->assertSame('12:30 PM', $this->clarification->response);
        $this->assertSame(RequestStatus::UNDER_REVIEW, $this->request->status);
    }

    /** @test */
    public function customer_cannot_respond_twice(): void
    {
        $orchestrator = app(RequestOrchestrator::class);
        $clarification = $orchestrator->requestInformation($this->request, 1, 'Delivery time?');
        $orchestrator->respondToClarification($clarification, $this->customer->id, '12:30 PM');

        $this->actingAs($this->customer->user);
        $response = $this->post("/requests/{$clarification->id}/respond", [
            'response' => 'Actually 1:00 PM',
        ]);

        $response->assertSessionHas('error');
    }
}
