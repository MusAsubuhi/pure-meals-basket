<?php

namespace App\Console\Commands;

use App\Enums\Quotation\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireQuotations extends Command
{
    protected $signature = 'quotations:expire';
    protected $description = 'Expire sent quotations that have passed their validity date';

    public function handle(): void
    {
        $expired = Quotation::where('status', QuotationStatus::SENT)
            ->where('valid_until', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired quotations found.');
            return;
        }

        DB::transaction(function () use ($expired) {
            foreach ($expired as $quotation) {
                $quotation->update([
                    'status' => QuotationStatus::EXPIRED,
                    'expired_at' => now(),
                ]);
                $quotation->logEvent('EXPIRED', 'Quotation expired automatically.');
            }
        });

        $this->info("Expired {$expired->count()} quotation(s).");
    }
}
