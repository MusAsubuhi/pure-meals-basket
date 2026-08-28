<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status');
            $table->string('payment_status');
            $table->string('fulfillment_method')->nullable();
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            $table->text('location')->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('payment_required', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('request_id');
            $table->index('quotation_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
