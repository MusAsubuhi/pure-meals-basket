<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paynexus_receipts', function (Blueprint $table) {
            $table->id();

            // PayNexus identifiers
            $table->unsignedBigInteger('paynexus_receipt_id')->nullable()->index();
            $table->string('receipt_number')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable()->index();

            // Receipt details
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('KES');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable()->index();

            // Customer information
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();

            // Receipt items
            $table->json('items')->nullable();

            // Tracking
            $table->datetime('sent_at')->nullable();

            // Extra data
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paynexus_receipts');
    }
};
