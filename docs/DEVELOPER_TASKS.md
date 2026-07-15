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

**4. Vendor subscriptions are real code, but running on test/sandbox accounts.** Vendors must now
pay (monthly or annual, `app/Livewire/Vendor/Subscription.php`) before posting/listing new
products, enforced in `app/Livewire/Vendor/ProductManager.php`, and can pay with either **Paystack**
or **OPay** (`App\Enums\PaymentGateway`). Both integrations are fully wired - initialize/create
transaction, verify/query status, webhook signature verification - and tested
(`tests/Feature/VendorSubscriptionTest.php`). To go live:

- **Paystack**: swap the test `PAYSTACK_SECRET_KEY` / `PAYSTACK_PUBLIC_KEY` in `.env` for live
  keys, and register the production webhook URL (`/webhooks/paystack`) in the Paystack dashboard
  under Settings > API Keys & Webhooks.
- **OPay**: requires a verified OPay Business merchant account (business KYC, unlike Paystack's
  instant test mode) before you even get sandbox keys - apply at business.opayweb.com. Once
  approved, set `OPAY_MERCHANT_ID`, `OPAY_PUBLIC_KEY`, `OPAY_SECRET_KEY` in `.env` and flip
  `OPAY_SANDBOX=false` when ready for live traffic. **Important caveat**: `App\Services\OpayClient`
  was built directly from OPay's published API docs (doc.opaycheckout.com), but the unit for the
  `amount.total` field (kobo vs whole naira) could not be confirmed without a live sandbox
  account - verify a real test transaction charges the expected amount before trusting this in
  production.
- Both webhook URLs (`/webhooks/paystack`, `/webhooks/opay`) need to be publicly reachable over
  HTTPS, which only exists once this is deployed somewhere other than localhost.

**5. Delivery fees are prepaid via OPay - same sandbox-account caveat as item 4.** Customers now
pay the delivery-fee portion of an order online via OPay (`app/Services/DeliveryFeePaymentService.php`)
before the order can be OTP-confirmed - only the items themselves stay cash on delivery
(`Order::deliveryFeePaid()`, enforced in `App\Services\OrderService::confirmFromOtp()`). Reuses the
same `App\Services\OpayClient` as vendor subscriptions, and the same production requirements apply:
a verified OPay Business merchant account, and re-verifying the `amount.total` unit against a real
sandbox transaction before trusting it live. Orders with no delivery fee due (all-pickup orders)
skip this step entirely and confirm immediately, same as before this feature existed.

**6. Add more payment gateways - only Paystack and OPay exist today, both used for the same two
things (vendor subscriptions, prepaid delivery fee).** Relying on two gateways is thin,
particularly since OPay requires a verified Business merchant account (real KYC, can take a while
to get approved) before you can even get sandbox keys - if that account approval stalls or OPay
has an outage, vendors currently have exactly one working payment path. Add at least one more
well-known Nigerian gateway; **Flutterwave** is the obvious first pick (Paystack's main
competitor, instant test-mode signup like Paystack rather than OPay's slower business-KYC
process, and it's what most Nigerian founders reach for as a second option). Monnify or
Interswitch/Quickteller are reasonable second additions after that if you want a third.

Before wiring in a new gateway, worth fixing the architecture first rather than bolting on
another branch:

- `App\Services\SubscriptionService::initialize()` and `verifyAndActivate()`
  (`app/Services/SubscriptionService.php`) currently do `if ($gateway === PaymentGateway::Opay) { ... } else { /* assumes Paystack */ }`
  - a third gateway means a third branch, and a fourth means a fourth. Extract a common interface
  first (e.g. `PaymentGatewayClient` with `initialize(...)`, `verifyTransaction(string $reference)`,
  `verifyWebhookSignature(...)`), have `PaystackClient`/`OpayClient` implement it, then have
  `SubscriptionService` resolve the right implementation from `App\Enums\PaymentGateway` (a
  `client()` method on the enum itself, resolving out of the container, is a clean way to do this)
  instead of branching on the gateway type inline.
- Add the new case to `App\Enums\PaymentGateway` (`label()` too).
- New gateway credentials go in `config/services.php` + `.env` (see the existing `paystack`/`opay`
  blocks for the pattern), a new webhook controller under `app/Http/Controllers/Webhooks/`, and a
  route in `routes/web.php` next to `webhooks.paystack`/`webhooks.opay`.
- The gateway-picker UI (`resources/views/livewire/vendor/subscription.blade.php`, the
  `selectedGateway` buttons around line 65) needs a third button, and
  `App\Services\DeliveryFeePaymentService` currently hard-codes `OpayClient` in its constructor -
  decide there whether customers should get a gateway choice for delivery-fee prepayment too, or
  whether that one stays OPay-only by design.
- Follow the `tests/Feature/VendorSubscriptionTest.php` pattern (it already covers both Paystack
  and OPay init/callback/webhook flows) to add equivalent coverage for the new gateway.

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

- **Delivery-speed perks tied to subscription plan.** Vendor subscriptions exist now (see Tier 1,
  item 4), but they're flat pay-to-post gates — the original idea of faster dispatch/payout for
  higher tiers isn't built. Would need a tier concept beyond monthly/annual and changes to
  `App\Services\DeliveryFeeCalculator` / dispatch assignment logic to actually prioritize paid
  vendors.
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
  These are still unverified. Separately, real-browser screenshot testing at 375px and 1280px
  (this round) found and fixed two actual mobile overflow bugs on the admin
  approve/reject-with-reason rows - `resources/views/livewire/admin/vendor-approvals.blade.php`
  (KYC photo retake request) and `resources/views/livewire/admin/product-approvals.blade.php`
  (product rejection) - both now stack input-above-button on narrow screens instead of
  overflowing off-screen. The vendor identity-verification page
  (`resources/views/livewire/vendor/identity-verification.blade.php`) was also checked and is
  clean at both sizes.
- **CAC number verification.** Vendor registration collects an optional CAC (business
  registration) number as free text with no validation against Nigeria's actual CAC registry.
- **A dedicated security review pass** — the app now handles cash reconciliation and KYC
  documents (ID photos, selfies); worth a focused review beyond normal feature testing before
  handling real money/real user documents.

---

Run `php artisan test` after any change — 105 tests currently pass and cover the full order
lifecycle, registration, product moderation, seller verification, vendor subscription (Paystack
and OPay), pickup fulfillment, prepaid delivery-fee, vendor payout-eligibility, and vendor KYC
photo retake-request flows.
