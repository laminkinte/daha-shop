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

**2. Real email delivery — done.** `MAIL_MAILER=smtp` via a Gmail App Password
(`MAIL_HOST=smtp.gmail.com`), confirmed working end to end. See `docs/EMAIL_AND_SMS_STRATEGY.md`
for the pros/cons of this setup (free, but per-day sending caps and weaker deliverability than a
dedicated provider like Postmark/SES) and for when it's worth switching.

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

**6. Digital "scan to pay" at delivery, replacing physical cash collection (assigned to
Kambaza2003).** Right now, when an agent marks a `VendorOrder` delivered
(`App\Livewire\Agent\DeliveryDetail::markDelivered()` → `App\Services\VendorOrderService::markDelivered()`,
`app/Services/VendorOrderService.php:69-100`), they type in how much physical cash they collected,
which immediately creates a `CashReconciliation` row (`status: Collected` or `Short`). Later, the
agent hands that cash to the office and someone marks it remitted
(`App\Services\ReconciliationService::remit()`) before the vendor becomes payout-eligible
(`App\Services\PayoutService::generateForVendor()` only pays out orders with
`cashReconciliation.status = Remitted`). The idea: instead of the agent physically collecting cash,
the customer scans a QR code at the doorstep and pays digitally — no cash changes hands, no office
remittance step, and (if wired all the way through) vendors could become payout-eligible the
moment the digital payment clears instead of waiting on a physical cash handoff.

There's already a complete, working blueprint for exactly this kind of "pay right now" flow to
copy: delivery-fee prepayment (item 5 above). `App\Services\DeliveryFeePaymentService::initialize()`
creates a payment record, calls `App\Services\OpayClient::initialize()` for a hosted checkout URL,
and confirmation comes back through two independent, idempotent paths —
`App\Http\Controllers\Storefront\DeliveryFeeCallbackController` (browser redirect back) and
`App\Http\Controllers\Webhooks\OpayWebhookController` (server-to-server webhook,
`routes/web.php:69`, already routes by reference prefix to tell delivery-fee payments apart from
subscription payments). The QR code itself is also a solved problem elsewhere in the app —
`App\Livewire\Vendor\QrCode` already uses the installed `endroid/qr-code` package to turn a URL
into a scannable PNG.

Open questions to settle before writing code (this needs a real design decision, not just wiring):
- **What does the QR code actually encode?** Almost certainly a link to a public, unauthenticated
  payment page for that specific `VendorOrder` (calling `PaymentGatewayClient::initialize()` with
  the item subtotal as the amount) — the customer scans it with their OWN phone and pays with
  their own card/bank app, not the agent's device.
- **Does this replace `CashReconciliation` entirely for digitally-paid orders, or reuse it?** The
  cleanest option is probably: on successful digital payment, mark `VendorOrder.status = Delivered`
  and `cash_collected` to the paid amount, but skip creating a `CashReconciliation` row at all
  (same as how pickup orders already skip it — see `VendorOrderService::markPickedUp()`) — then
  `PayoutService::generateForVendor()` needs a second eligibility path alongside "remitted cash"
  for "digitally paid, no reconciliation needed."
- **Fallback for when the customer can't/won't pay at the door** (no signal, no working payment
  app, wants to stick with cash) — the agent almost certainly still needs a physical-cash option
  to fall back to; this shouldn't be all-or-nothing on day one.
- **Prepay-before-delivery, as a separate but related idea**: letting a customer pay for the item
  cost upfront at checkout instead of at the door (COD stays the default, prepay becomes a choice).
  This is closer to item 5's existing pattern than the QR-at-delivery flow is — the delivery-fee
  prepayment step could arguably be generalized into "prepay delivery fee + optionally item cost
  too" rather than building a whole separate prepay flow from scratch. Worth deciding up front
  whether scan-to-pay-at-delivery and prepay-at-checkout are one feature or two.

**7. Add more payment gateways - status update.** The old inline
`if ($gateway === PaymentGateway::Opay) { ... } else { /* assumes Paystack */ }` branching in
`SubscriptionService::initialize()` is gone - it now returns a normalized
`['type' => 'redirect', 'url' => ...]` or `['type' => 'virtual_account', 'account_number' => ...]`
array (see `app/Services/SubscriptionService.php`), so adding another gateway no longer means
another special case in the caller. Current state per gateway:

- **Paystack, OPay** - unchanged, real, working (with OPay's existing amount-unit caveat above).
- **Monnify** (the actual payment-gateway product behind "MoniePoint" - MoniePoint itself has no
  merchant API) - `app/Services/MonnifyClient.php` is wired in and live in the vendor subscription
  gateway picker. **Built from general knowledge of Monnify's typical API shape, NOT fetched live
  documentation** (docs.monnify.com/developers.monnify.com is a JS-rendered SPA that couldn't be
  read at the time this was written) - same caveat class as OPay's own unverified amount unit.
  Before trusting this in production: get a real Monnify sandbox account, verify the OAuth
  token-login endpoint/field names, the init-transaction request/response shape, and especially
  the webhook signature hash formula in `MonnifyClient::verifyWebhookSignature()` (the field
  concatenation order used there is a best guess).
- **PalmPay** (`app/Services/PalmPayClient.php`) - architecture-only stub, every method throws.
  Docs: `docs.palmpay.com`, business/merchant portal `business.palmpay.com`, payin product
  `palmpay.com/business/payin`. Known from public search only: the API uses `PaymentNotification`/
  `QueryTxnStatus` endpoints with encrypted request/response payloads - the encryption scheme,
  endpoint paths, and signature header are NOT known. Not added to the vendor-facing gateway
  picker (`resources/views/livewire/vendor/subscription.blade.php`) on purpose - don't expose a
  payment option that's guaranteed to error. Add it there once the client is real and verified.
- **Kuda** (`app/Services/KudaClient.php`) - architecture-only stub, every method throws. Docs:
  `developer.kuda.com`, `docs.kuda.com`, `business-support.kuda.com`. Architecturally different
  from the others: Kuda's Business API collects payment via a dynamically-generated virtual
  account number the customer bank-transfers into, not a hosted-checkout redirect -
  `SubscriptionService::initialize()` already has a `virtual_account` branch and
  `Subscription.php`/`subscription.blade.php` already render a "transfer to this account" panel
  for that case, so the UI/service plumbing is ready; only `KudaClient`'s actual HTTP calls need
  building once real API docs/sandbox access exist. Also not on the vendor-facing picker yet.
- All three new enum cases (`App\Enums\PaymentGateway::Monnify/PalmPay/Kuda`), `config/services.php`
  blocks, and (for Monnify) the webhook route (`POST /webhooks/monnify`) are already in place -
  see `tests/Feature/VendorSubscriptionTest.php` (Monnify init/callback/webhook coverage) and
  `tests/Feature/PaymentGatewayStubsTest.php` (proves PalmPay/Kuda fail loudly rather than
  silently) for the patterns to extend once PalmPay/Kuda are implemented for real.
- `App\Services\DeliveryFeePaymentService` still hard-codes `OpayClient` directly - untouched by
  this round, still an open decision whether delivery-fee prepayment should also become
  gateway-selectable.
- Flutterwave and Interswitch/Quickteller remain reasonable further additions if you want a
  fourth/fifth option - same pattern (implement `PaymentGatewayClient`, wire into
  `PaymentGatewayManager`, add config + webhook route) applies.

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

Run `php artisan test` after any change — 174 tests currently pass and cover the full order
lifecycle, registration, product moderation, seller verification, vendor subscription (Paystack,
OPay, and Monnify - see `PaymentGatewayStubsTest` for the PalmPay/Kuda stub-safety coverage),
pickup fulfillment, prepaid delivery-fee, vendor payout-eligibility, vendor KYC photo
retake-request flows, the sub-admin permission system + audit trail (`AdminPermissionsTest`,
`AdminAuditLogTest`), admin-created vendor/agent/admin accounts, and the reporting
dashboard/business settings/CSV export suite (`AdminDashboardReportingTest`, `BusinessSettingsTest`,
`AdminExportTest`, `PayoutOverviewTest`).
