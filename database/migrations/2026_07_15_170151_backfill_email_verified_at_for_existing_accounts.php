<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Email verification enforcement (User::implements MustVerifyEmail) is being
 * turned on for the first time - without this, every account that registered
 * before today would suddenly be locked out of /dashboard by the `verified`
 * middleware the next time they visit, since their email_verified_at was
 * never set (nothing previously enforced or requested verification).
 * Grandfather every existing real-email account in as already verified;
 * only new registrations from here on actually need to click the link.
 * Phone/PIN accounts are unaffected either way - see User::hasVerifiedEmail().
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('uses_pin', false)
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Not reversible: we can no longer tell which of these rows were
        // genuinely verified before this migration ran versus backfilled.
    }
};
