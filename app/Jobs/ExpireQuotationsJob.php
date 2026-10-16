<?php

namespace App\Jobs;

use App\Models\Quotation;
use App\Enums\Quotation\QuotationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Marks quotations past their valid_until date as expired.
 *
 * Sent quotations that were never accepted are withdrawn automatically.
 * Accepted quotations that already spawned an order are unaffected.
 */
class ExpireQuotationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();

        Quotation::query()
            ->where('status', QuotationStatus::SENT)
            ->where('valid_until', '<', $now)
            ->limit(500)
            ->get()
            ->each(function (Quotation $quotation) {
                $quotation->update([
                    'status' => QuotationStatus::EXPIRED,
                ]);

                $quotation->logEvent('EXPIRED', 'Quotation expired after valid_until date.');
            });
    }
}