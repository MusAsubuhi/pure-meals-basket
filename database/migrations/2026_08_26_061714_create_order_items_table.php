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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // A line may map to a known package or be a custom/requested item not in the catalogue.
            $table->foreignId('service_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->string('unit')->nullable(); // e.g. kg, litres, servings, pieces
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('line_total', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};