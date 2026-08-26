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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            // A visitor can place an order without an account, so the link to a customer is optional.
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            // The service this order is for; links to the catalogue of services (seeded by
            // ServiceCategorySeeder to match the three services shown on the welcome page).
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            // Denormalised contact details captured from the enquiry/order form.
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            // pending, confirmed, in_preparation, out_for_delivery, completed, cancelled
            $table->string('status')->default('pending');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('referral_source')->nullable();
            // Free-form service-specific request details: occasion, flavours, sizes, notes, etc.
            $table->json('request_details')->nullable();
            $table->decimal('total', 12, 2)->default(0.00);
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};