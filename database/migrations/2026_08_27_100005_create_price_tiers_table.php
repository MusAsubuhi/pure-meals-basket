<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();

            // Tiered pricing brackets attach to products or services
            $table->morphs('priceable'); // priceable_type + priceable_id indexed

            // Quantities are in the item's own unit (persons, litres...).
            // A null max_quantity means "and above" (top bracket).
            $table->decimal('min_quantity', 10, 3);
            $table->decimal('max_quantity', 10, 3)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->string('label')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
