# Test / Demo Credentials

All accounts below come from `database/seeders/DemoDataSeeder.php` and only exist after running:

```
php artisan migrate:fresh --seed
```

**Local development only.** Every password is the literal string `password`. Never run this
seeder against a production database, and never reuse these values for a real account.

---

## Admin

| Field | Value |
|---|---|
| Name | Daha Shop Admin |
| Email | `admin@dahashop.ng` |
| Phone | `+2348010000001` |
| Password | `password` |

---

## Vendors (sellers)

| Name | Shop | Email | Phone | Password |
|---|---|---|---|---|
| Chinedu Okafor | TechHub Electronics (`techhub-electronics`) | `vendor1@dahashop.ng` | `+2348010000002` | `password` |
| Ada's Fashion House | Ada's Fashion House (`adas-fashion-house`) | `vendor2@dahashop.ng` | `+2348010000003` | `password` |
| Emeka Nwosu | MegaMart General Store (`megamart-general-store`) | `vendor3@dahashop.ng` | `+2348010000007` | `password` |

All three vendors are pre-approved (`status: approved`), so they can log in and manage products/orders immediately — no admin approval step needed in the seeded data.

---

## Delivery agent

| Field | Value |
|---|---|
| Name | Ibrahim Musa |
| Email | `agent1@dahashop.ng` |
| Phone | `+2348010000004` |
| Password | `password` |
| Zone | Ikeja, Lagos (motorcycle) |

---

## Customers

| Name | Email | Phone | Password |
|---|---|---|---|
| Bisi Adewale | `customer1@dahashop.ng` | `+2348010000005` | `password` |
| Tunde Bakare | `customer2@dahashop.ng` | `+2348010000006` | `password` |

Bisi Adewale has a default saved address in Surulere, Lagos; Tunde Bakare has none.

---

## Notes

- All seeded accounts have `email_verified_at` and `phone_verified_at` already set to `now()`, so none of them will see the phone-verification popup (`docs/PHONE_VERIFICATION_TASKS.md`) — that only appears for accounts created through the real registration form.
- To test the phone+PIN registration path or the verification popup itself, register a **new** account through `/register` rather than using one of the seeded logins above.
- Demo products are seeded for all three vendors (electronics for TechHub, clothing/shoes for Ada's Fashion House, laptops/gaming/books/sports/car accessories for MegaMart).
