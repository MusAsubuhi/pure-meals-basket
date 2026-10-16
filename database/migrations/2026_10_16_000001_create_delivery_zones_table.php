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
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Mombasa Island", "Nairobi CBD"
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Geographic boundary — a comma-separated list of [lat,lng] pairs
            // forming a closed polygon. Stored as JSON so the shape is opaque
            // to MySQL; a dedicated GIS column is overkill for this product.
            $table->json('boundary')->nullable();

            // Flat delivery charge applied to every order whose delivery
            // address falls inside this zone.
            $table->decimal('delivery_charge', 10, 2)->default(0);

            // Minimum order total before delivery is free within this zone.
            $table->decimal('free_delivery_above', 10, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};