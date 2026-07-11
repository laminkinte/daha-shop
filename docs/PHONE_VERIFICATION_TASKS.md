# Phone/PIN Registration + Verification Popup — Handoff Spec

The phone number + 4-digit PIN registration option and the mandatory phone-verification popup
are live (`app/Livewire/PhoneVerificationPrompt.php`, the `registrationMethod` toggle in
`resources/views/livewire/pages/auth/register.blade.php`, differentiated rate limiting in
`app/Livewire/Forms/LoginForm.php`). The core flow works and is tested
(`tests/Feature/PhoneRegistrationTest.php`), but it is not production-ready. These are the real
gaps found while building it, ranked by severity — not theoretical.

---

## High priority

**1. No SMS actually gets delivered.**
`SendOtpSms` currently goes through `LogSmsGateway`, which just writes the code to
`storage/logs/laravel.log`. Every OTP — registration, resend, checkout confirmation — is silent
in production right now. This has to be wired to a real gateway (Termii is the stub already
scaffolded at `app/Services/Sms/TermiiSmsGateway.php`) with a real account and API key before this
feature means anything to a real user. This was already flagged in `docs/DEVELOPER_TASKS.md`
Tier 1, but it's worth restating here because the verification popup makes the gap visible on
literally every new signup now, not just at checkout.

**2. The "Resend code" button has no rate limit.**
`PhoneVerificationPrompt::resend()` (`app/Livewire/PhoneVerificationPrompt.php`) calls
`OtpService::generate()` directly with no throttle. Once SMS delivery is real (#1), a user — or
anyone who gets their hands on that button — can hammer it and rack up SMS costs, or spam a phone
number that isn't even theirs (the popup is shown to whoever is logged in, but nothing stops
scripting repeated `resend` calls against the same session). Add a per-user cooldown, e.g.
`RateLimiter::attempt('otp-resend:'.$user->id, 3, fn() => ..., decaySeconds: 60)`, matching the
pattern already used in `LoginForm`.

**3. Forgot-password is silently broken for PIN accounts.**
Phone/PIN accounts get a synthetic email (`{phone}@phone.dahashop.internal`, see
`register.blade.php`) so `users.email` stays unique/NOT NULL. But Breeze's password-reset flow
(`resources/views/livewire/pages/auth/forgot-password.blade.php`) emails a reset link — which for
these accounts goes to an address nobody reads. A PIN account that forgets its PIN currently has
**no recovery path at all**. This needs an SMS-based "forgot PIN" flow: send an OTP to the phone,
let them set a new PIN on successful verification, bypassing the email-link mechanism entirely
for `uses_pin = true` accounts.

---

## Medium priority

**4. Unverified accounts are never actually restricted from anything.**
The popup is dismissible ("Remind me later") indefinitely, and nothing server-side checks
`isPhoneVerified()` before allowing checkout, vendor product listing, or payouts. Right now
"pending" is cosmetic — a user can dismiss the popup once and never verify, forever. Decide which
actions should actually be gated behind verification (checkout is the obvious first candidate,
since COD relies on a working phone number to reach the customer) and add a middleware or
in-component check, not just the reminder UI.

**5. No format validation/normalization on the phone number.**
`register.blade.php` validates `phone` as `required|string|max:20|unique` — nothing enforces a
real Nigerian number shape. `+2348031234567`, `08031234567`, and `803 123 4567` for the same
underlying number would all pass validation as three different unique rows, defeating the
`unique` check and letting one person register multiple times. Normalize to a single canonical
format (e.g. `+234XXXXXXXXXX`) before saving and before every `unique` check.

**6. Registration itself still has no rate limit** (cross-reference:
`docs/REGISTRATION_SECURITY_TASKS.md` #1 — still open, and now more relevant). Phone+PIN signup
is an even cheaper abuse path than the original email flow: no inbox needed at all, just a phone
number string, since nothing currently proves the phone number is reachable *before* the account
is created. Closing #1 in that doc also closes this.

---

## Lower priority / worth documenting even if not fixed now

**7. Admins have no visibility into pending/unverified accounts.**
There's no admin-side list of unverified users, no way to see how many signups never complete
verification, and no manual override to verify or flag a suspicious one. Not urgent, but worth a
small admin widget once the above are done.

**8. Only phone/SMS verification exists — the original ask mentioned "email or SMS."**
Right now every account (email-registered or not) is verified by phone OTP only; there's no
email-based verification path or fallback if SMS delivery (#1) is down or misconfigured. Worth a
short discussion on whether email verification should be a genuine parallel path (relevant to
`docs/REGISTRATION_SECURITY_TASKS.md` #6, which already flags that `email_verified_at` is
collected but never enforced) or whether phone-only is fine long-term.

---

## Suggested order

1 blocks everything else from being real — do it first. 2 and 3 are both small, contained, and
sit directly on top of the SMS gateway from #1. 4 and 5 are judgment calls worth a short team
discussion before building (which actions to gate, what "canonical phone format" means for
Nigerian numbers specifically — MTN/Glo/Airtel/9mobile prefixes, local vs +234 vs 0-prefixed).
6 is already scoped in the other doc. 7 and 8 can wait.

Run `php artisan test` after each change — 68 tests currently pass, including
`tests/Feature/PhoneRegistrationTest.php`, which covers both registration paths, popup visibility,
correct/incorrect code handling, dismissal, and PIN vs password rate limiting, and will catch
regressions from this work.
