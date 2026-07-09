<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->foreignId('vendor_payout_id')->nullable()->after('delivery_agent_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_payout_id');
        });
    }
};
