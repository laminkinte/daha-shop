<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_order_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('method')->default('qr');
            $table->string('status')->default('pending');
            $table->string('customer_phone')->nullable();
            $table->text('cashier_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_payments');
    }
};