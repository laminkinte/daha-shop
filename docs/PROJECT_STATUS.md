# Daha Shop — Project Status

A single point-in-time summary of what's actually built and working versus what's still
incomplete. For full detail on any pending item, see the referenced doc — this file summarizes,
it doesn't replace them.

---

## ✅ Completed and working

### Storefront (customer-facing)
- Product catalog with category chips, search, sort, pagination.
- Product detail page, cart, wishlist.
- **Checkout** with a real address book, and a **per-vendor delivery method choice**: deliver to
  address, or pick up from the vendor directly (multi-vendor carts can mix both).
- **Delivery fees are paid online via OPay** at checkout, before the order is OTP-confirmed —
  only the items themselves stay cash on delivery. Orders with no delivery fee (all-pickup) skip
  this step entirely.
- OTP-based order confirmation (SMS code sent, verified before a vendor can act on the order).
- Order tracking with a real per-vendor status timeline (different step sequence for pickup vs.
  delivery), order history, reviews.
- Per-vendor public shop pages + a shareable QR code that links only to that vendor's storefront.

### Registration & authentication
- Customer/seller registration split, with two sign-up methods: email + password, **or** phone
  number + 4-digit PIN (with differentiated, stricter rate-limiting on PIN login since a 4-digit
  PIN is a weaker credential).
- Every new account is "pending" until phone-verified via OTP, with an in-app popup prompting
  completion (dismissible, not yet enforced beyond the reminder — see gaps below).
- Seller registration requires a real ID document + a live-camera selfie, with **real** image
  blur/clarity detection (Laplacian variance, not a placeholder check) rejecting unusable photos.
  Admin reviews ID + selfie side by side to approve/suspend vendors.
- **Forgot-password** (email accounts) and **forgot-PIN** (phone accounts, OTP-based reset) both
  work — the PIN-reset flow was built and verified compatible in this session (see "Recently
  merged from a co-developer" below).

### Vendor side
- Dashboard with real stats (products, pending orders, delivered/picked-up this month, pending
  payout).
- Product manager with a moderation queue (draft → pending review → published/rejected by admin).
- Order manager: accept/reject, pack, and — for pickup orders — mark ready for pickup, then
  confirm picked-up with cash collected.
- Payout history.
- Shop QR code page.
- **Vendor subscriptions**: vendors must pay (monthly ₦5,000 or annual ₦50,000) before posting or
  listing new products, payable via **either Paystack or OPay** — both fully wired (initialize,
  verify, webhook signature verification), not stubs.

### Admin side
- Dashboard, vendor approvals (with ID/selfie review), product approvals, order overview.
- Dispatch board (assigns agents to packed **delivery** orders only — pickup orders correctly
  excluded, since they never need an agent).
- Cash reconciliation dashboard.
- **Vendor payouts now correctly require the agent's cash to be remitted first** — a real gap
  (paying a vendor before the platform actually had the cash in hand) was found and fixed this
  session.
- Delivery agent manager, delivery zone manager, phone blacklist.

### Delivery agent app
- Assigned deliveries, delivery detail (cash collection — now correctly just the items total
  since delivery fee is prepaid — proof-of-delivery photo, failure reporting), cash remittance.

### Platform / cross-cutting
- Full Nigerian geography seeded (37 states incl. FCT, 774 LGAs).
- Money handled as integer kobo everywhere; enums for every status field.
- Modern, consistent UI (rounded-xl cards, color-coded status pills, icon-badged stats, hover
  states) across every storefront/vendor/admin/agent page — this was a dedicated pass to fix
  inconsistent/dated styling app-wide.
- **93 automated tests passing**, covering the full order lifecycle, registration (both methods),
  product moderation, seller verification, vendor subscriptions (both gateways), pickup
  fulfillment, prepaid delivery fees, and payout eligibility.

### Recently merged from a co-developer
- **Forgot-PIN flow** (Kambaza2003) — resolves the "PIN accounts had no recovery path" gap.
  Verified in this session: merges cleanly, doesn't break any existing test, and a live
  end-to-end check (send code → reset PIN → log in with the new PIN) actually works. One gap it
  still has: no rate limit on sending the reset code (same class of issue as item 4 below).

---

## ⚠️ Known-faked or needs real credentials before production

Full detail in `docs/DEVELOPER_TASKS.md` Tier 1. Summary:

1. **SMS gateway** — OTP/order-status texts only get logged (`LogSmsGateway`), not actually sent.
   Needs a real Termii (or similar) account.
2. **Email delivery** — `MAIL_MAILER=log`, nothing is actually emailed. Needs a real provider.
3. **Camera capture** — built and tested in desktop browsers only; needs real Android/iPhone
   device testing (iOS Safari has known camera-permission quirks).
4. **Vendor subscriptions (Paystack + OPay)** — fully coded and tested, but running on
   test/sandbox keys. Paystack just needs live keys swapped in; **OPay requires a verified OPay
   Business merchant account (real business KYC)** before you even get sandbox keys, and the
   `amount.total` unit (kobo vs. naira) needs verifying against one real sandbox transaction
   before trusting it live.
5. **Delivery-fee OPay payments** — same OPay caveat as #4, since it reuses the same client.

## 🚧 Explicitly deferred (not started)

Full detail in `docs/DEVELOPER_TASKS.md` Tier 2–3. Summary: CSV bulk product upload, real fraud
scoring (beyond the manual blacklist), 3rd-party logistics integration, delivery-speed perks tied
to subscription tier, real biometric face-matching (vs. today's manual admin photo comparison),
and a possible property/rental vertical.

## 🔒 Security/hardening gaps flagged but not fixed

Full detail in `docs/REGISTRATION_SECURITY_TASKS.md` and `docs/PHONE_VERIFICATION_TASKS.md`.
Summary of what's still open:

- **No rate limiting on registration itself** (email or phone/PIN) — the single most repeated gap
  across every review pass. Also blocks the OTP-reset endpoints (forgot-password, forgot-PIN)
  from being spam-proof.
- Session fixation on registration (missing `Session::regenerate()`).
- Uploaded ID/selfie images stored as-is — no EXIF stripping, no server-side re-encoding.
- No bot/spam protection (CAPTCHA/honeypot) on registration.
- Default Laravel password policy only (no `mixedCase()`/`uncompromised()` strengthening).
- `email_verified_at`/`phone_verified_at` are collected but not enforced anywhere beyond the
  dismissible reminder popup — a user can ignore verification forever.
- No phone number format normalization (`+234...`, `0...`, and spaced variants can all register as
  "different" numbers).
- No admin visibility into pending/unverified accounts.
- No CAC (business registration number) validation against Nigeria's actual registry.

## 📋 Business ideas discussed but not built

- Delivery-speed perks tied to subscription plan (currently flat pay-to-post, no tiering).
- Property/house-rental vertical (reusing the Product/Vendor pattern).

---

Run `php artisan test` after any change — 93 tests currently pass. See `docs/TEST_CREDENTIALS.md`
for seeded demo logins.
