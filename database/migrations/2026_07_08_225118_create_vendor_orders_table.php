<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained();
            $table->foreignId('delivery_agent_id')->nullable()->constrained();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('items_subtotal');
            $table->unsignedBigInteger('delivery_fee');
            $table->unsignedBigInteger('cash_collected')->default(0);
            $table->unsignedTinyInteger('delivery_attempts')->default(0);
            $table->string('failure_reason')->nullable();
            $table->string('proof_of_delivery_path')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_orders');
    }
};
