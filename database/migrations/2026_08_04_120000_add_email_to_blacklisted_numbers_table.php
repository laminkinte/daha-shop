<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blacklisted_numbers', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('phone');
        });

        // phone was required (and the only identifier) before email support
        // existed - an entry now only needs at least one of the two, not
        // both, so it can no longer stay NOT NULL.
        Schema::table('blacklisted_numbers', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('blacklisted_numbers', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->string('phone')->nullable(false)->change();
        });
    }
};
