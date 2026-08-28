<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paynexus_checkout_sessions', function (Blueprint $table) {
            $table->id();

            // PayNexus identifiers
            $table->unsignedBigInteger('paynexus_session_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->string('reference')->nullable()->index();

            // Status and amounts
            $table->string('status', 20)->default('active')->index();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('KES');

            // Customer information
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_name')->nullable();

            // URLs
            $table->string('success_url')->nullable();
            $table->string('cancel_url')->nullable();

            // Session data
            $table->json('metadata')->nullable();
            $table->datetime('expires_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paynexus_checkout_sessions');
    }
};
