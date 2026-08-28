<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paynexus_payments', function (Blueprint $table) {
            $table->id();

            // PayNexus identifiers
            $table->unsignedBigInteger('paynexus_payment_id')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->string('checkout_request_id')->nullable()->index();
            $table->string('merchant_request_id')->nullable()->index();
            $table->string('transaction_id')->nullable()->index();

            // Payment details
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('KES');
            $table->string('phone', 20)->nullable();
            $table->string('description')->nullable();
            $table->string('account_reference', 50)->nullable();

            // Status tracking
            $table->string('status', 20)->default('pending')->index();
            $table->string('provider_reference')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('payer_name')->nullable();

            // Verification fields (for reconciling with payment provider)
            $table->decimal('verified_amount', 10, 2)->nullable();
            $table->string('verified_phone')->nullable();
            $table->datetime('verified_date')->nullable();
            $table->string('verification_method')->nullable();

            // Confirmation fields
            $table->string('user_message')->nullable();
            $table->boolean('retry_possible')->default(false);
            $table->boolean('confirmed_manually')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirmed_by')->nullable();

            // Polymorphic relation (link to Order, Invoice, etc.)
            $table->nullableMorphs('payable');

            // Extra data
            $table->json('metadata')->nullable();
            $table->string('idempotency_key')->nullable()->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paynexus_payments');
    }
};
