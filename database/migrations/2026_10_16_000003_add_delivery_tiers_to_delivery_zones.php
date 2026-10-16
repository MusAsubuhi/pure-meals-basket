<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A zone may charge a flat base fee plus optional surcharges for
     * non-standard delivery types:
     *   - door_to_door: the crew leaves the staging area and delivers
     *     directly to the customer's door.
     *   - beyond_stage: the drop-off point is past the zone's standard
     *     stage / handover point, so extra travel applies.
     *
     * The total delivery fee for an order is base_charge plus whichever
     * surcharges the customer's delivery type triggers.
     */
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->decimal('base_charge', 10, 2)->default(0)
                ->after('delivery_charge');

            $table->decimal('door_to_door_surcharge', 10, 2)->default(0)
                ->after('base_charge');

            $table->decimal('beyond_stage_surcharge', 10, 2)->default(0)
                ->after('door_to_door_surcharge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn([
                'base_charge',
                'door_to_door_surcharge',
                'beyond_stage_surcharge',
            ]);
        });
    }
};