<?php

use App\Enums\CatalogStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();

            $table->string('pricing_type'); // PricingType cast in the model
            $table->decimal('base_price', 12, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('minimum_quantity', 10, 3)->nullable();
            $table->decimal('maximum_quantity', 10, 3)->nullable();

            $table->boolean('is_available')->default(true);
            $table->boolean('requires_review')->default(false);
            $table->string('status')->default(CatalogStatus::DRAFT->value);

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
