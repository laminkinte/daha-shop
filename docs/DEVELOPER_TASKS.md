# Next Development Tasks — Daha Shop

This document is a handoff spec for the next round of changes. It assumes familiarity with the
existing codebase (Laravel 12 + Livewire 3 + Tailwind, MySQL). See `README.md` and the code
itself for how the COD order lifecycle, roles, and services are structured before starting.

Four tasks, in the order they should be tackled (later ones build on earlier ones):

1. Rebrand to "Daha Shop"
2. Mobile responsiveness pass
3. Split registration into Customer vs Seller
4. Seller ID verification (national ID card / passport)

---

## 1. Rebrand: "MarketHub NG" → "Daha Shop"

Find every occurrence first:

```bash
grep -rli "markethub" --include="*.php" --include="*.blade.php" --include="*.md" .
```

Update:
- `.env` and `.env.example` — `APP_NAME="Daha Shop"`
- `resources/views/layouts/storefront.blade.php` — the literal `MarketHub <span>NG</span>` logo text in the header
- `resources/views/layouts/dashboard.blade.php` — the sidebar logo text
- `resources/views/layouts/storefront.blade.php` footer copyright line
- `app/Jobs/SendOtpSms.php` — the SMS message text ("Your MarketHub NG confirmation code...")
- `app/Listeners/NotifyVendorOfPayout.php` — the payout SMS message text
- `README.md`

**Leave alone:** `config/markethub.php` and every `config('markethub.xxx')` call throughout the
services (`CheckoutService`, `OtpService`, `VendorOrderService`, etc.). That's just an internal
config namespace key, not user-facing text — renaming it means touching ~10 files for zero visible
change. Only rename it if you want internal consistency; it's cosmetic, not required.

Optional/cosmetic: the demo seeder (`database/seeders/DemoDataSeeder.php`) uses
`@markethub.ng` email addresses for the seeded demo accounts. Fine to leave as-is (it's just
placeholder test data), or update to `@dahashop.ng` if you want the demo data to match visually
when you show it to people.

---

## 2. Mobile Responsiveness Pass

The app was built mobile-first with Tailwind, but a few specific spots need attention. Test at
320px, 375px, and 414px widths using browser devtools' device toolbar.

**Known gaps to fix:**

- `resources/views/layouts/storefront.blade.php` — the search bar is `hidden md:flex`, meaning
  **mobile users currently have no way to search at all**. Add a search icon button (`md:hidden`)
  in the header that toggles a full-width search input below the header row (Alpine `x-show`).
- `resources/views/livewire/storefront/cart.blade.php` — each cart row is a single fixed-width
  flex row (image + name + quantity input + price, several fixed `w-*` classes). On a 320–375px
  screen this can overflow or crowd. Make it stack: `flex-col sm:flex-row` with the price/quantity
  controls moving to their own row on mobile.
- Any modal (`app/Livewire/Vendor/ProductManager.php`'s form modal, `Admin/AgentManager.php`'s
  onboarding modal, `Admin/DeliveryZoneManager.php`'s modal) — check they scroll on short mobile
  viewports. Add `max-h-[90vh] overflow-y-auto` to the modal panel div if content gets cut off.
- Header icon row (`storefront.blade.php`) — verify the wishlist icon, cart icon, and login/signup
  buttons don't wrap awkwardly at 320px. Reduce `gap-4` to `gap-2 sm:gap-4` if needed.

There's no dedicated visual regression tooling in this repo — verify by hand in a real mobile
browser or devtools, then spot-check the existing Livewire feature tests still pass
(`php artisan test`) since none of this should change component behavior, only markup/classes.

---

## 3. Split Registration: Customer vs Seller

File: `resources/views/livewire/pages/auth/register.blade.php` (a Volt anonymous class component).

Add an account-type toggle at the top of the form — two buttons/tabs, "Register as a Customer" and
"Register as a Seller" — bound to a new public property, e.g. `public string $accountType = 'customer';`.

- **Customer path**: unchanged — name, email, phone, password.
- **Seller path**: same base fields, plus:
  - `business_name` (string, required)
  - `business_address` (string, required)
  - `business_phone` (string, required — can default to the personal phone field)
  - ID document type: `national_id` or `passport` (select)
  - ID document upload (see Task 4 below — the file field lives here, but don't wire up storage
    until you've read Task 4's security note)

On submit:
- Always create the `User` row. Set `role` to `UserRole::Vendor` or `UserRole::Customer` based on
  `$accountType`.
- If seller: also create a `Vendor` row with `status = VendorStatus::Pending` (this already
  matches the existing admin-approval flow in `app/Livewire/Admin/VendorApprovals.php` — sellers
  who register are **not** immediately able to list products; they wait for admin approval, same
  as before, just now admin approval also means "and I reviewed their ID").

Look at how `App\Enums\UserRole` and `App\Models\Vendor` are already used in
`database/seeders/DemoDataSeeder.php` for the shape of a `Vendor::create([...])` call — copy that
pattern.

---

## 4. Seller ID Verification (Manual Upload + Admin Review)

**Decision made for this iteration:** manual document upload reviewed by a human admin — no
third-party KYC API (Smile Identity / VerifyMe / Youverify) integration. That's a larger, paid,
separate piece of work if it's wanted later; the schema below doesn't block adding it.

### Migration

Add two nullable columns to `vendors`:

```php
$table->string('id_document_path')->nullable();
$table->string('id_document_type')->nullable(); // 'national_id' | 'passport'
```

### ⚠️ Security requirement — read this before writing any code

**Do not store the ID document on the `public` disk.** A national ID or passport scan on the
`public` disk sits at a guessable, unauthenticated URL (`/storage/vendor-kyc/xxxx.jpg`) — anyone
with the link can view it, and Laravel's public disk has no access control at all. This is
sensitive PII; treat it accordingly:

- Store it on the **`local`** disk instead: `$this->idDocument->store('vendor-kyc', 'local')`.
  This puts it in `storage/app/private/vendor-kyc/...` (or `storage/app/vendor-kyc/...` depending
  on your `config/filesystems.php` `local` root), which is **not** web-accessible at all.
- Add a new route + controller, gated by `auth` + `role:admin` middleware, that streams the file
  to a logged-in admin on demand:

  ```php
  Route::middleware(['auth', 'role:admin'])->get(
      '/admin/vendors/{vendor}/id-document',
      [AdminVendorDocumentController::class, 'show']
  )->name('admin.vendors.id-document');
  ```

  ```php
  public function show(Vendor $vendor)
  {
      abort_unless($vendor->id_document_path, 404);

      return Storage::disk('local')->response($vendor->id_document_path);
  }
  ```

- In `app/Livewire/Admin/VendorApprovals.php`'s view, add a "View ID Document" link to that route
  next to each pending vendor, so the admin can actually look at it before clicking Approve.
- Double check `storage/app/private` (or wherever `local` disk resolves) is covered by
  `.gitignore` — it already should be via Laravel's default `storage/*` ignore rules, but verify
  nothing under there ever gets committed.

### Testing

Add a test (follow the pattern in `tests/Feature/RoleAccessTest.php`) asserting:
1. A seller can register with an ID upload and ends up `VendorStatus::Pending`.
2. The stored file path is **not** reachable via a plain `GET /storage/...` request (i.e. it's
   actually on the private disk, not public).
3. A non-admin user gets `403` hitting the new `admin.vendors.id-document` route; an admin gets
   the file back.

---

## Suggested Order of Work

1. Rebrand first (quick, no logic changes, easy to verify visually).
2. Mobile pass second (also low-risk, markup-only).
3. Registration split third (adds the `accountType` toggle and Vendor-on-register logic).
4. ID verification last (depends on the registration form already existing from step 3).

Run `php artisan test` after each step — the existing suite (37 tests) should stay green throughout
since none of this changes the core order/checkout/reconciliation logic.
