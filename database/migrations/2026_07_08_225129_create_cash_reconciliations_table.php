<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_agent_id')->constrained();
            $table->foreignId('vendor_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_expected');
            $table->unsignedBigInteger('amount_collected');
            $table->text('denomination_notes')->nullable();
            $table->string('status')->default('collected');
            $table->unsignedBigInteger('remitted_amount')->nullable();
            $table->timestamp('remitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_reconciliations');
    }
};
