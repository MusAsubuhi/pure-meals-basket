<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('method');
            $table->string('provider');
            $table->string('status');
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('KES');
            $table->string('provider_payment_id')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('order_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('reference');
            $table->index('checkout_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
