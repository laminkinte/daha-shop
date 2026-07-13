# Phone/PIN Registration — Remaining Gaps to Assign

The phone number + 4-digit PIN registration option and the mandatory phone-verification popup
are live (`app/Livewire/PhoneVerificationPrompt.php`, the `registrationMethod` toggle in
`resources/views/livewire/pages/auth/register.blade.php`, differentiated rate limiting in
`app/Livewire/Forms/LoginForm.php`) and tested (`tests/Feature/PhoneRegistrationTest.php`). These
are the specific gaps assigned for this handoff — real issues found while building it, not
theoretical.

---

## High priority

**1. ~~Forgot-password is silently broken for PIN accounts.~~ RESOLVED.**
Built by Kambaza2003 (`resources/views/livewire/pages/auth/forgot-pin.blade.php`, `pin.request`
route): sends an OTP (purpose `pin_reset`) to the phone, verifies it, then updates the password
column directly for `uses_pin = true` accounts. Verified compatible with the rest of the app in a
separate clean-clone check — merges cleanly, doesn't break any of the 93 existing tests, and a
live end-to-end run (send code → reset → log in with the new PIN) actually works. One gap it
still has: `sendOtp()` has no rate limit, so it inherits the same "no rate limiting" issue as item
4 below — worth closing both together.

---

## Medium priority

**2. Unverified accounts are never actually restricted from anything.**
The popup is dismissible ("Remind me later") indefinitely, and nothing server-side checks
`isPhoneVerified()` before allowing checkout, vendor product listing, or payouts. Right now
"pending" is cosmetic — a user can dismiss the popup once and never verify, forever. Decide which
actions should actually be gated behind verification (checkout is the obvious first candidate,
since COD relies on a working phone number to reach the customer) and add a middleware or
in-component check, not just the reminder UI.

**3. No format validation/normalization on the phone number.**
`register.blade.php` validates `phone` as `required|string|max:20|unique` — nothing enforces a
real Nigerian number shape. `+2348031234567`, `08031234567`, and `803 123 4567` for the same
underlying number would all pass validation as three different unique rows, defeating the
`unique` check and letting one person register multiple times. Normalize to a single canonical
format (e.g. `+234XXXXXXXXXX`) before saving and before every `unique` check.

**4. Registration itself still has no rate limit** (cross-reference:
`docs/REGISTRATION_SECURITY_TASKS.md` #1 — still open, and now more relevant). Phone+PIN signup
is an even cheaper abuse path than the original email flow: no inbox needed at all, just a phone
number string, since nothing currently proves the phone number is reachable *before* the account
is created. Closing #1 in that doc also closes this.

---

## Lower priority / worth documenting even if not fixed now

**5. Admins have no visibility into pending/unverified accounts.**
There's no admin-side list of unverified users, no way to see how many signups never complete
verification, and no manual override to verify or flag a suspicious one. Not urgent, but worth a
small admin widget once the above are done.

**6. Only phone/SMS verification exists — the original ask mentioned "email or SMS."**
Right now every account (email-registered or not) is verified by phone OTP only; there's no
email-based verification path or fallback. Worth a short discussion on whether email verification
should be a genuine parallel path (relevant to `docs/REGISTRATION_SECURITY_TASKS.md` #6, which
already flags that `email_verified_at` is collected but never enforced) or whether phone-only is
fine long-term.

---

## Suggested order

1 is the most user-facing broken path (a locked-out user has zero recovery today) — do it first.
2 and 3 are judgment calls worth a short team discussion before building (which actions to gate,
what "canonical phone format" means for Nigerian numbers specifically — MTN/Glo/Airtel/9mobile
prefixes, local vs +234 vs 0-prefixed). 4 is already scoped in the other doc. 5 and 6 can wait.

Run `php artisan test` after each change — 68 tests currently pass, including
`tests/Feature/PhoneRegistrationTest.php`, which covers both registration paths, popup visibility,
correct/incorrect code handling, dismissal, and PIN vs password rate limiting, and will catch
regressions from this work.
