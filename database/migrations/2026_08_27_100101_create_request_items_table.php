<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('request_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained()->cascadeOnDelete();
            $table->string('item_type'); // 'product' || 'service'
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot fields
            $table->string('name'); // product/service name at time of request
            $table->decimal('quantity', 10, 3)->default(1);
            $table->string('unit')->nullable(); // e.g. kg, litre, person
            $table->json('options')->nullable(); // array of option value IDs selected
            $table->string('pricing_type')->nullable(); // snapshot of ProductPricingType
            $table->string('pricing_status')->nullable(); // RequestItemPricingStatus
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->json('pricing_breakdown')->nullable(); // full calculation trace
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['request_id', 'item_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_items');
    }
};
