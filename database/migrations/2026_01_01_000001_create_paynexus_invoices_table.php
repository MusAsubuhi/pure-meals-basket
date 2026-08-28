<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paynexus_invoices', function (Blueprint $table) {
            $table->id();

            // PayNexus identifiers
            $table->unsignedBigInteger('paynexus_invoice_id')->nullable()->index();
            $table->string('invoice_number')->nullable()->index();
            $table->string('reference')->nullable()->index();

            // Status and amounts
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('KES');
            $table->decimal('tax_amount', 14, 2)->nullable();
            $table->decimal('total_amount', 14, 2)->nullable();

            // Dates
            $table->datetime('due_date')->nullable();
            $table->datetime('sent_at')->nullable();
            $table->datetime('paid_at')->nullable();

            // Customer information
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('billing_address')->nullable();

            // Invoice details
            $table->json('line_items')->nullable();
            $table->text('notes')->nullable();

            // Extra data
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paynexus_invoices');
    }
};
