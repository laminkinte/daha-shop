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
        Schema::table('vendor_subscriptions', function (Blueprint $table) {
            $table->string('gateway')->default('paystack')->after('vendor_id');
            $table->renameColumn('paystack_reference', 'reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_subscriptions', function (Blueprint $table) {
            $table->dropColumn('gateway');
            $table->renameColumn('reference', 'paystack_reference');
        });
    }
};
