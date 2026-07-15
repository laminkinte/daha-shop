<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->text('id_document_rejection_reason')->nullable()->after('id_document_path');
            $table->text('selfie_rejection_reason')->nullable()->after('selfie_path');
            $table->foreignId('reviewed_by')->nullable()->after('selfie_rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['id_document_rejection_reason', 'selfie_rejection_reason', 'reviewed_at']);
        });
    }
};
