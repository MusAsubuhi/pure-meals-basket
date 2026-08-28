<?php

namespace Tests\Unit\Quotation;

use App\Enums\Quotation\QuotationStatus;
use App\Enums\Request\RequestStatus;
use App\Models\Quotation;
use App\Models\Request\Request;
use App\Services\Quotation\QuotationOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private QuotationOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orchestrator = app(QuotationOrchestrator::class);
    }

    /** @test */
    public function create_creates_draft_quotation(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);

        $quotation = $this->orchestrator->create($request);

        $this->assertSame(QuotationStatus::DRAFT, $quotation->status);
        $this->assertSame($request->id, $quotation->request_id);
        $this->assertMatchesRegularExpression('/^QUO-\d{4}-\d{4}$/', $quotation->reference);
        $this->assertCount(1, $quotation->events);
        $this->assertSame('CREATED', $quotation->events->first()->event_type);
    }

    /** @test */
    public function create_throws_for_non_commercial_request(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::DRAFT]);

        $this->expectException(\InvalidArgumentException::class);
        $this->orchestrator->create($request);
    }

    /** @test */
    public function add_item_creates_quotation_item_and_recalculates(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = $this->orchestrator->create($request);

        $item = $this->orchestrator->addItem($quotation, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);

        $quotation->refresh();

        $this->assertSame('Catering', $item->name);
        $this->assertSame('150.000', $item->quantity);
        $this->assertSame('85000.00', $item->unit_price);
        $this->assertSame('12750000.00', $item->subtotal);
        $this->assertSame('12750000.00', $quotation->subtotal);
        $this->assertCount(2, $quotation->events);
    }

    /** @test */
    public function update_item_recalculates_totals(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = $this->orchestrator->create($request);
        $item = $this->orchestrator->addItem($quotation, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);

        $this->orchestrator->updateItem($quotation, $item, [
            'quantity' => 120,
            'unit_price' => 90000,
        ]);

        $quotation->refresh();
        $item->refresh();

        $this->assertSame('120.000', $item->quantity);
        $this->assertSame('90000.00', $item->unit_price);
        $this->assertSame('10800000.00', $item->subtotal);
        $this->assertSame('10800000.00', $quotation->subtotal);
    }

    /** @test */
    public function remove_item_recalculates_totals(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = $this->orchestrator->create($request);
        $item = $this->orchestrator->addItem($quotation, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);

        $this->orchestrator->removeItem($quotation, $item);

        $quotation->refresh();
        $this->assertSame('0.00', $quotation->subtotal);
        $this->assertSame(0, $quotation->items()->count());
    }

    /** @test */
    public function apply_discount_updates_totals(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = $this->orchestrator->create($request);
        $this->orchestrator->addItem($quotation, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);

        $quotation = $this->orchestrator->applyDiscount($quotation, 5000);

        $this->assertSame('12750000.00', $quotation->subtotal);
        $this->assertSame('5000.00', $quotation->discount);
        $this->assertSame('12745000.00', $quotation->total);
    }

    /** @test */
    public function send_transitions_to_sent_and_sets_validity(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = $this->orchestrator->create($request);
        $this->orchestrator->addItem($quotation, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);

        $quotation = $this->orchestrator->send($quotation);

        $this->assertSame(QuotationStatus::SENT, $quotation->status);
        $this->assertNotNull($quotation->sent_at);
        $this->assertNotNull($quotation->valid_until);
        $this->assertTrue($quotation->valid_until->greaterThan(now()));
        $this->assertTrue($quotation->valid_until->lessThanOrEqualTo(now()->addDays(7)));
    }

    /** @test */
    public function send_throws_for_empty_quotation(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = $this->orchestrator->create($request);

        $this->expectException(\InvalidArgumentException::class);
        $this->orchestrator->send($quotation);
    }

    /** @test */
    public function withdraw_transitions_to_withdrawn(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = $this->orchestrator->create($request);
        $this->orchestrator->addItem($quotation, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);
        $this->orchestrator->send($quotation);

        $quotation = $this->orchestrator->withdraw($quotation);

        $this->assertSame(QuotationStatus::WITHDRAWN, $quotation->status);
        $this->assertNotNull($quotation->withdrawn_at);
    }

    /** @test */
    public function decline_transitions_to_declined(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = $this->orchestrator->create($request);
        $this->orchestrator->addItem($quotation, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);
        $this->orchestrator->send($quotation);

        $quotation = $this->orchestrator->decline($quotation);

        $this->assertSame(QuotationStatus::DECLINED, $quotation->status);
        $this->assertNotNull($quotation->declined_at);
    }

    /** @test */
    public function accept_transitions_to_accepted_and_marks_request_ready(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = $this->orchestrator->create($request);
        $this->orchestrator->addItem($quotation, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);
        $this->orchestrator->send($quotation);

        $quotation = $this->orchestrator->accept($quotation);

        $this->assertSame(QuotationStatus::ACCEPTED, $quotation->status);
        $this->assertNotNull($quotation->accepted_at);
        $request->refresh();
        $this->assertSame(RequestStatus::READY_FOR_CHECKOUT, $request->status);
    }

    /** @test */
    public function accept_throws_for_expired_quotation(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = Quotation::factory()->create([
            'request_id' => $request->id,
            'status' => QuotationStatus::SENT,
            'sent_at' => now()->subDays(10),
            'valid_until' => now()->subDays(3),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->orchestrator->accept($quotation);
    }

    /** @test */
    public function expire_transitions_expired_sent_quotation(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quotation = Quotation::factory()->create([
            'request_id' => $request->id,
            'status' => QuotationStatus::SENT,
            'sent_at' => now()->subDays(10),
            'valid_until' => now()->subDays(3),
        ]);

        $quotation = $this->orchestrator->expire($quotation);

        $this->assertSame(QuotationStatus::EXPIRED, $quotation->status);
        $this->assertNotNull($quotation->expired_at);
    }

    /** @test */
    public function replacement_withdraws_original_and_creates_new(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $original = $this->orchestrator->create($request);
        $this->orchestrator->addItem($original, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);
        $this->orchestrator->send($original);

        $replacement = $this->orchestrator->createReplacement($original);

        $original->refresh();
        $this->assertSame(QuotationStatus::WITHDRAWN, $original->status);
        $this->assertNotNull($original->withdrawn_at);

        $this->assertSame(QuotationStatus::DRAFT, $replacement->status);
        $this->assertSame(1, $replacement->items()->count());
        $this->assertSame('Catering', $replacement->items->first()->name);
    }

    /** @test */
    public function send_withdraws_other_sent_quotations(): void
    {
        $request = Request::factory()->create(['status' => RequestStatus::QUOTATION_REQUIRED]);
        $quo1 = $this->orchestrator->create($request);
        $this->orchestrator->addItem($quo1, [
            'name' => 'Catering',
            'quantity' => 150,
            'unit' => 'person',
            'unit_price' => 85000,
        ]);
        $this->orchestrator->send($quo1);

        $quo2 = $this->orchestrator->create($request);
        $this->orchestrator->addItem($quo2, [
            'name' => 'Cake',
            'quantity' => 3,
            'unit' => 'kg',
            'unit_price' => 1000,
        ]);
        $this->orchestrator->send($quo2);

        $quo1->refresh();
        $this->assertSame(QuotationStatus::WITHDRAWN, $quo1->status);
        $this->assertSame(QuotationStatus::SENT, $quo2->status);
    }
}
