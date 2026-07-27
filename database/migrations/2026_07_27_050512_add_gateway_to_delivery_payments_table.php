<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_payments', function (Blueprint $table) {
            $table->string('gateway')->default('opay')->after('vendor_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_payments', function (Blueprint $table) {
            $table->dropColumn('gateway');
        });
    }
};
