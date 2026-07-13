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
        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->string('fulfillment_method')->default('delivery')->after('vendor_id');
            $table->timestamp('ready_for_pickup_at')->nullable()->after('packed_at');
            $table->timestamp('picked_up_at')->nullable()->after('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_method', 'ready_for_pickup_at', 'picked_up_at']);
        });
    }
};
