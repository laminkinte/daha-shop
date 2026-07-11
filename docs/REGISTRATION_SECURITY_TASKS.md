# Registration Security Hardening — Handoff Spec

Focused review of the registration flow (`resources/views/livewire/pages/auth/register.blade.php`
and everything it touches) after adding customer/seller account types and seller ID/selfie
verification. These are real gaps I found while building it, not theoretical — ranked by
severity. None of this blocks the feature from working; it's what should be closed before this
touches real user data and real seller applications at any scale.

---

## High priority

**1. No rate limiting on registration.**
There's no throttle on the register endpoint at all. Someone can script mass account creation —
including mass *seller* applications with stolen/AI-generated ID photos, which is a more serious
abuse vector now that registration accepts document uploads. `LoginForm` already has a working
`RateLimiter` pattern (`app/Livewire/Forms/LoginForm.php`) — copy that same approach into the
register Volt component, or add Laravel's `throttle:6,1` middleware to the `register` POST route.

**2. Session fixation on registration.**
`login.blade.php` calls `Session::regenerate()` right after authenticating. The register
component's `register()` method does **not** — it calls `Auth::login($user)` directly with no
session regeneration. This means a session ID fixated before registration could persist through
to the authenticated session afterward. One-line fix: add `Session::regenerate()` right after
`Auth::login($user)` in `register()`, matching what login already does.

**3. Uploaded ID/selfie images are stored as-is, not re-encoded.**
Right now `$this->idDocument->store(...)` and `$this->selfie->store(...)` save the raw uploaded
bytes directly. Two problems with that:
- Phone photos routinely carry EXIF metadata, including **GPS coordinates** of where the photo
  was taken — that's sensitive location data about a seller sitting in a file admins routinely
  open. Strip EXIF before storing.
- Storing raw uploaded bytes trusts that Laravel's `image` validation rule caught everything.
  Re-encoding the image server-side (decode with GD, re-save as a fresh JPEG) is a stronger
  guarantee than MIME-sniffing alone — it means whatever gets stored is *actually* a flat raster
  image, not a polyglot file wearing a `.jpg` extension. The codebase already has GD image
  handling to copy from in `App\Services\ImageClarityChecker` — reuse that pattern: load with
  `imagecreatefromstring()`, re-save with `imagejpeg()`, store the re-saved version instead of
  the original upload.

---

## Medium priority

**4. No bot/spam protection on the registration form.**
No CAPTCHA, no honeypot field, nothing. Combined with #1 (no rate limit), this form is currently
wide open to scripted abuse. Cheapest fix: add an invisible honeypot field (a hidden input real
users never fill in; reject silently if it's non-empty) — zero cost, no third-party account
needed. If more assurance is wanted later, Cloudflare Turnstile has a generous free tier and is
less annoying than reCAPTCHA.

**5. Password policy is Laravel's bare default (8 characters, nothing else).**
`Rules\Password::defaults()` in `register.blade.php` currently enforces the minimum only.
Laravel supports strengthening this in one line —
`Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()` — the `uncompromised()`
check runs the password against the HaveIBeenPwned breached-password API (k-anonymity, no real
password ever leaves the server) for free, no account needed. Worth doing given this app will
hold financial/reconciliation data behind these accounts.

**6. `email_verified_at` / `phone_verified_at` are collected but never enforced.**
Both columns exist and get set on registration (`null` for real users, `now()` only in seed
data), but nothing in the app actually *requires* a verified email or phone before a user can
act — there's no follow-up confirmation step after registration itself. Phone gets implicitly
proven later through OTP at checkout, but email is never confirmed at all. Decide whether this
matters enough to add Laravel's built-in `MustVerifyEmail` flow (mostly already scaffolded by
Breeze, just needs the interface added to the `User` model and the `verified` middleware applied
where it should matter).

---

## Lower priority / worth documenting even if not fixed now

**7. The live-camera requirement for the selfie can't be technically enforced server-side.**
The UI requires the selfie come from `getUserMedia`, not a file upload — but a sufficiently
motivated person could bypass the browser entirely and POST any image directly to the Livewire
upload endpoint. There's no way to cryptographically prove an image came from a live camera
without real liveness-detection (a paid biometric API, same gap already flagged for face
matching in `docs/DEVELOPER_TASKS.md`). Not fixable cheaply — just make sure nobody on the team
assumes the current selfie capture is tamper-proof; it raises the bar for casual abuse, it
doesn't eliminate it.

**8. No disposable-email or duplicate-identity checks.**
No blocklist for temporary/disposable email domains, and no check for the same CAC number or ID
document being reused across multiple seller applications. Lower priority than the above, and
CAC verification specifically is already tracked as its own item in `docs/DEVELOPER_TASKS.md`.

---

## Suggested order

1 and 2 are quick, contained, no new dependencies — do those first. 3 reuses code that already
exists in the project. 4 and 5 are each a few lines. 6 is a bigger decision (whether email
verification should actually gate anything) — worth a short discussion before building it.

Run `php artisan test` after each change — 59 tests currently pass, including the seller
registration and clarity-check flows in `tests/Feature/SellerVerificationTest.php`, which will
catch anything this work accidentally breaks.
