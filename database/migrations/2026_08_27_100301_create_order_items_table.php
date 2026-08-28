<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete();
            $table->string('item_type')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 3)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->json('options')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id');
            $table->index('quotation_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
