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
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('customer_account_id')->constrained('customer_accounts')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_base', 12, 2)->nullable();
            $table->enum('type', ['credit', 'debit']);
            $table->string('description')->nullable();
            $table->string('reference')->nullable()->unique();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('preferred_currency_id')->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }
};