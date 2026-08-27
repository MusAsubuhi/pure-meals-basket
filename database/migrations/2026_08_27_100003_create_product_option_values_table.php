<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained('product_options')->onDelete('cascade');

            // e.g. Fondant. The "value" column holds an optional machine value.
            $table->string('name');
            $table->string('value')->nullable();

            // Additive price modifier: Buttercream +0, Fondant +800 ...
            // Later requirement types (multipliers) can extend this schema.
            $table->decimal('price_modifier', 12, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};
