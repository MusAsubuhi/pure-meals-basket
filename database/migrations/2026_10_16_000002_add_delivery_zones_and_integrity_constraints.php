<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add delivery_zone_id to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_zone_id')
                ->nullable()
                ->after('fulfillment_method')
                ->constrained('delivery_zones')
                ->nullOnDelete();
        });

        // 2. Add delivery_zone_id to fulfillments
        Schema::table('fulfillments', function (Blueprint $table) {
            $table->foreignId('delivery_zone_id')
                ->nullable()
                ->after('order_id')
                ->constrained('delivery_zones')
                ->nullOnDelete();
        });

        // 3. Enforce one order per quotation (unique constraint).
        //    quotation_id is already nullable; the unique index allows NULLs
        //    (multiple orders without a quotation).
        Schema::table('orders', function (Blueprint $table) {
            $table->unique('quotation_id', 'orders_quotation_id_unique');
        });

        // 4. Enforce one order per request (unique constraint).
        Schema::table('orders', function (Blueprint $table) {
            $table->unique('request_id', 'orders_request_id_unique');
        });

        // 5. Ensure request_items has exactly one of product_id or service_id.
        //    MySQL 8.0.16+ treats booleans as integers, so the two tests can be
        //    added together. Postgres cannot add booleans; num_nonnulls() counts
        //    the non-null arguments and is equivalent.
        $exactlyOne = DB::getDriverName() === 'pgsql'
            ? 'num_nonnulls(product_id, service_id) = 1'
            : '(product_id IS NOT NULL) + (service_id IS NOT NULL) = 1';

        DB::statement(
            'ALTER TABLE request_items ADD CONSTRAINT request_items_product_or_service '.
            "CHECK ({$exactlyOne})"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE request_items DROP CONSTRAINT request_items_product_or_service');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_request_id_unique');
            $table->dropUnique('orders_quotation_id_unique');
            $table->dropForeign(['delivery_zone_id']);
            $table->dropColumn('delivery_zone_id');
        });

        Schema::table('fulfillments', function (Blueprint $table) {
            $table->dropForeign(['delivery_zone_id']);
            $table->dropColumn('delivery_zone_id');
        });
    }
};