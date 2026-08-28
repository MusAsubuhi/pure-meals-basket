<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained()->cascadeOnDelete();
            $table->string('attempt_reference')->nullable();
            $table->string('provider');
            $table->string('status');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->dateTime('initiated_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('payment_id');
            $table->index('checkout_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
