<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->constrained();
            $table->string('status')->default('pending_confirmation');
            $table->string('confirmation_status')->default('pending_confirmation');
            $table->unsignedBigInteger('items_subtotal');
            $table->unsignedBigInteger('delivery_fee_total');
            $table->unsignedBigInteger('cod_amount_expected');
            $table->unsignedBigInteger('cod_amount_collected')->default(0);
            $table->unsignedTinyInteger('delivery_attempts')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
