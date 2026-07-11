# Next Development Tasks — Daha Shop

Previous round (rebrand, mobile search fix, customer/seller registration split, seller ID
verification) is done and merged. This is the next batch, grouped by priority.

---

## Tier 1 — Things that are currently faked and need to become real

**1. Real SMS gateway.** OTP codes and order-status messages currently just get written to
`storage/logs/laravel.log` (`App\Services\Sms\LogSmsGateway`) because there's no real gateway
account. `App\Services\Sms\TermiiSmsGateway` already exists and implements the same interface —
someone needs to create a Termii (or similar) account, add the API key to `.env`
(`MARKETHUB_SMS_GATEWAY=termii`, `TERMII_API_KEY`, `TERMII_SENDER_ID`), and confirm real OTP texts
actually arrive on a real Nigerian number.

**2. Real email delivery.** `MAIL_MAILER=log` — password reset and verification emails aren't
being sent anywhere. Needs a real provider (Postmark/SES/Resend are already referenced in
`config/services.php`) wired up in `.env` and tested end to end.

**3. Camera capture on real devices.** The ID/selfie capture (`getUserMedia` + Livewire's JS
upload API, in `resources/views/components/camera-capture.blade.php`) was built and tested in a
desktop browser context only. It needs a human to actually test on real Android and iPhone
hardware — iOS Safari in particular has known quirks with camera permissions and autoplay that
don't show up until you test on a real device.

---

## Tier 2 — Explicitly deferred from the original build, still deferred

- **CSV/Excel bulk product upload** for vendors (currently one-at-a-time only, in
  `app/Livewire/Vendor/ProductManager.php`).
- **Fraud scoring beyond the basic blacklist.** Right now checkout only blocks phone numbers an
  admin has manually blacklisted. A real scoring system (e.g. flag customers with N recent
  rejected/cancelled orders) doesn't exist yet.
- **3rd-party logistics integration** (GIG Logistics, Kwik, Sendbox) as an alternative to the
  in-house agent-dispatch flow that exists today.
- **Paystack/Flutterwave prepaid option.** COD is the only payment method. The schema doesn't
  hard-code COD-only assumptions, so this is additive, not a rewrite.

---

## Tier 3 — Business ideas discussed, not yet built

- **Vendor subscription tiers** tied to delivery service level (faster dispatch/payout for paid
  tiers) — discussed as the recommended monetization model, not implemented.
- **Real biometric face-matching** (Smile Identity / AWS Rekognition / similar) to replace the
  current manual admin side-by-side photo comparison in Admin → Vendors. Needs a paid account;
  the capture/storage layer is already built to slot this in later.
- **Property/house rental vertical** — discussed as a possible expansion reusing the existing
  Product/Vendor pattern (a "Property" listing shaped like a "Product," a landlord like a
  vendor). Not started.

---

## Tier 4 — Polish and hardening

- **Remaining mobile responsiveness gaps**: cart row layout may still crowd on very narrow
  (320–375px) screens, and the three modal-based forms (vendor product form, admin agent
  onboarding, admin delivery zone) should be checked for scroll behavior on short viewports.
  Only the missing-mobile-search-bar item was actually fixed; these others were flagged earlier
  but never verified.
- **CAC number verification.** Vendor registration collects an optional CAC (business
  registration) number as free text with no validation against Nigeria's actual CAC registry.
- **A dedicated security review pass** — the app now handles cash reconciliation and KYC
  documents (ID photos, selfies); worth a focused review beyond normal feature testing before
  handling real money/real user documents.

---

Run `php artisan test` after any change — 57 tests currently pass and cover the full order
lifecycle, registration, product moderation, and seller verification flows.
