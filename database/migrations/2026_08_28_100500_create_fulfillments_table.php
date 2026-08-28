<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->string('method');
            $table->string('status');

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('service_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('delivery_address')->nullable();
            $table->string('delivery_contact_name')->nullable();
            $table->string('delivery_contact_phone')->nullable();

            $table->text('collection_notes')->nullable();
            $table->text('service_location')->nullable();
            $table->text('service_notes')->nullable();

            $table->string('recipient_name')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('method');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillments');
    }
};
