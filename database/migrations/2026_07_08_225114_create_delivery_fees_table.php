<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('fee');
            $table->timestamps();

            $table->unique(['delivery_zone_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_fees');
    }
};
