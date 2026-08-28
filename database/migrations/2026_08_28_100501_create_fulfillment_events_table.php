<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillment_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fulfillment_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_type');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('fulfillment_id')
                ->references('id')
                ->on('fulfillments')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('fulfillment_id');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_events');
    }
};
