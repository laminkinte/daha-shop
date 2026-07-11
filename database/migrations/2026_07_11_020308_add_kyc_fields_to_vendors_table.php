<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('id_document_type')->nullable()->after('cac_number');
            $table->string('id_document_path')->nullable()->after('id_document_type');
            $table->string('selfie_path')->nullable()->after('id_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['id_document_type', 'id_document_path', 'selfie_path']);
        });
    }
};
