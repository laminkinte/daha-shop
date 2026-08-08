<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the is_super_admin boolean with a distinct 'super_admin' role
 * value, so super-admin status is encoded in `role` itself instead of a
 * separate flag on top of role=admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'admin')
            ->where('is_super_admin', true)
            ->update(['role' => 'super_admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('role');
        });

        DB::table('users')->where('role', 'super_admin')->update(['is_super_admin' => true]);

        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['role' => 'admin']);
    }
};
