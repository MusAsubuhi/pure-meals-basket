<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Records which delivery tier the customer selected for an order:
     * standard (zone base charge), door_to_door (base + door-to-door
     * surcharge), or beyond_stage (base + beyond-stage surcharge).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_type')->nullable()->after('delivery_zone_id');
        });

        Schema::table('fulfillments', function (Blueprint $table) {
            $table->string('delivery_type')->nullable()->after('delivery_zone_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivery_type');
        });

        Schema::table('fulfillments', function (Blueprint $table) {
            $table->dropColumn('delivery_type');
        });
    }
};