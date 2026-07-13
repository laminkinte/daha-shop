# Daha Shop — Business Strategy: Integrations, Monetization, Funding & Vendor Acquisition

Everything outside the codebase itself: what's technically integrated vs. still open, ways to
make money beyond what's built, realistic funding/sponsorship routes for a Nigerian COD
marketplace with no starting capital, and a concrete plan for signing up the first 100 vendors.

**Honesty check up front**: I can research, draft pitch materials, and help you reason through
these options — I can't submit grant applications, email investors, or register businesses on
your behalf. Everything below is a direction to pursue, not something already done for you.

---

## 1. Integrations — what's live, what's possible

### Already integrated and working
- **Paystack** — vendor subscription payments (test mode; needs live keys to go live).
- **OPay** — vendor subscriptions and prepaid delivery fees (needs a verified OPay Business
  merchant account before going live — see `docs/DEVELOPER_TASKS.md` for the exact steps).
- **SMS (Termii)** — the interface and a stub implementation exist; not yet connected to a real
  account, so no OTP texts actually send yet.

### Worth integrating next, and why
- **A second payment rail alongside OPay for redundancy** — Flutterwave is the other major
  Nigerian gateway; if OPay's merchant approval takes a while, Flutterwave's test mode (like
  Paystack's) is instant, so it could unblock testing the prepaid-delivery-fee flow sooner.
- **Real identity verification** (Smile Identity, Youverify, or Prembly — all Nigeria-focused
  KYC providers) — replaces today's manual admin side-by-side ID/selfie comparison with real
  biometric face-matching and can verify against BVN/NIN. This is the natural next step once
  seller volume makes manual review slow.
- **CAC verification API** — Nigeria's Corporate Affairs Commission has verification tooling
  third parties integrate with; would turn today's free-text CAC number field into something
  actually checked, which matters once you have licensed/registered vendors and not just
  individual sellers.
- **Virtual accounts for vendor payouts** (Paystack/Flutterwave/Monnify all offer these) — instead
  of manual bank transfer payouts, a dedicated virtual account per vendor could automate the
  payout step and give vendors visibility into pending balance in real time.
- **3rd-party logistics** (GIG Logistics, Kwik, Sendbox) — as your own agent network can't reach,
  a logistics API integration extends delivery coverage without hiring more agents directly.

---

## 2. Business ideas — monetization beyond what's built

Two revenue lines already exist: **vendor subscriptions** (recurring, prepaid) and **delivery
fees** (prepaid per order via OPay). Both were deliberately designed so money arrives before you
spend anything servicing that order — keep leaning on that pattern for anything new. Ideas, roughly
ordered by how soon they're realistic:

1. **Commission on top of subscription** — a small percentage per completed sale in addition to
   the flat subscription fee. Common in Jumia-style marketplaces; only add this once vendors are
   seeing enough sales volume that a percentage cut doesn't feel punitive relative to the
   subscription they're already paying.
2. **Featured/promoted listings** — vendors pay extra for placement at the top of a category or
   the homepage. Low-effort to build (a "featured" flag + a small paid-placement queue), and
   doesn't require new payment infrastructure since Paystack/OPay are already wired.
3. **Dispatch-as-a-service** — once your in-house agent/reconciliation system is proven, license
   it out: other small Nigerian e-commerce sites without their own delivery network could pay to
   use your riders/dispatch flow. Turns a cost center into a second product.
4. **BNPL / installment COD for higher-value items** — partner with a Nigerian BNPL provider
   (CredPal, Carbon, PalmCredit) so customers can split payment on big-ticket electronics, which
   also reduces your COD cash-handling risk on the largest, riskiest orders.
5. **Insurance/warranty upsell at checkout** — an insurtech partnership offering extended
   warranty or delivery-damage protection as a paid add-on, commission-based, no capital needed.
6. **Aggregated market-insight data** — once you have real transaction volume, anonymized,
   aggregated trend data (what's selling, where, at what price) has real value to brands and
   manufacturers trying to understand informal Nigerian retail. Long-term idea, needs scale first.
7. **Delivery-speed perks tied to subscription tier** and **property/rental vertical** — both
   already noted in `docs/DEVELOPER_TASKS.md` Tier 3 as discussed-not-built.

---

## 3. Sponsorship opportunities

Realistic sponsors for a stage-appropriate Nigerian COD marketplace, and what they'd actually want
in return:

- **Paystack / OPay / Flutterwave** — payment companies frequently run partner or startup
  programs (transaction fee discounts, co-marketing, sometimes small grants) for platforms
  driving real volume through their rails. Worth approaching once you have live transactions to
  point to, not before — they sponsor traction, not ideas.
- **Telecoms (MTN, Airtel, Glo, 9mobile)** — bulk SMS/data partnerships in exchange for
  case-study/co-marketing rights; MTN in particular runs SME digitization programs periodically.
- **Logistics companies (GIG Logistics, Kwik, Gokada, MAX.ng)** — not sponsorship exactly, but
  revenue-share delivery partnerships to extend coverage beyond your own agents.
- **Bank innovation arms (GTBank, Access, Zenith)** — regularly sponsor fintech/e-commerce
  innovation challenges with cash prizes and mentorship; entering is non-dilutive money plus
  visibility, worth tracking their programs.
- **SMEDAN, LSETF, Bank of Industry** — Nigerian government/parastatal bodies with SME
  digitization support programs; eligibility and programs change often, worth checking current
  offerings directly.
- **Innovation hubs (CcHub Lagos, Founder Institute Lagos, Passion Incubator)** — some offer
  mentorship/visibility sponsorship without taking equity, aimed at productized MVPs like this one.

---

## 4. Funding routes, roughly in order of accessibility

1. **Stay revenue-first as long as possible.** The subscription + prepaid-delivery-fee model is
   already structured so you're not floating anyone else's money — this is your strongest,
   lowest-risk lever and costs no equity or time spent fundraising.
2. **Non-dilutive grants** — Tony Elumelu Foundation's annual Entrepreneurship Programme (seed
   grant + mentorship), Google for Startups Africa, YouWiN (Nigerian federal business plan
   competition), Mastercard Foundation programs. Applications and eligibility windows change
   yearly — check current cycles directly before planning around any one of them.
3. **Accelerators/incubators** — CcHub, Founder Institute Lagos, Ventures Platform Accelerator,
   and Africa-focused cohorts at Techstars/Y Combinator. Typically small equity (roughly 5–10%)
   for a modest check plus structured mentorship — worth it if you want the network and structure,
   not just the cash.
4. **Early-stage African VC funds** — Ventures Platform, Ingressive Capital, Voltron Capital,
   Microtraction (very early, small checks), EchoVC, LoftyInc Capital. Before approaching any of
   these, check their public portfolios for e-commerce/logistics/COD-adjacent investments — a
   fund with no relevant portfolio fit is a wasted pitch.
5. **Revenue-based financing** — once real MRR exists from vendor subscriptions, non-dilutive
   lenders that take a percentage of revenue become viable. Not available before you have revenue
   to underwrite against.
6. **Friends-and-family / small crowdfunding** — realistic for the very earliest cash needs
   (hosting, SMS costs, initial vendor incentives) before any of the above are reachable.

---

## 5. Getting your first 100 vendors registered

This is the part that determines whether any of the above matters — a marketplace with no
sellers has nothing to fund. Concrete, Nigeria-specific approach:

1. **Target sellers who already do COD informally.** Thousands of Nigerian sellers already run
   COD manually through Instagram, WhatsApp Business, and Facebook Marketplace. The behavior
   change you're asking for is small — they already trust the payment model, you're just giving
   them tracking, reconciliation, and reach they don't have today.
2. **Concentrate, don't spread thin.** Launch hyper-focused on one or two categories in one city
   (e.g., Lagos electronics + fashion) rather than trying to cover everything everywhere at once.
   Customers need enough visible variety to trust the platform; that's easier to hit in a narrow
   slice than spread across all categories nationwide from day one.
3. **Go where sellers already cluster.** Computer Village (Ikeja, electronics), Alaba
   International Market (electronics/appliances), Balogun Market/Idumota (Lagos Island, general
   goods/fashion), Aba (fashion manufacturing hub), Onitsha Main Market (general goods) — these
   are dense seller networks where word of mouth travels fast once a few vendors see real sales.
4. **Personally onboard the first 20–30, don't rely on self-serve signup yet.** Help them list
   their first several products, walk them through the dashboard live. This catches real UX
   friction before it costs you at scale, and builds the trust that gets them to bring others in.
5. **Waive the subscription fee for early vendors.** The subscription is a real barrier during the
   trust-building phase — offer the first 100 vendors 2–3 months free, and reintroduce the fee
   once they've seen actual sales come through.
6. **Use the QR code feature to import their existing customers.** Encourage early vendors to put
   their shop QR code in their WhatsApp Business bio, Instagram bio, and on physical packaging —
   this migrates their *existing* customer base onto the platform instead of asking you to find
   customers from scratch for every new vendor.
7. **Referral incentive** once there's a small active base — "bring another vendor, get a month
   free" compounds faster than any single outreach channel.
8. **Find one respected seller per market/association as a visible early adopter.** Nigerian
   market clusters usually have an informal or formal association; one credible, visible success
   story inside that network is worth more than dozens of cold outreach attempts.
9. **Recirculate proof back into the same seller communities.** Short case studies/testimonials
   ("sold out my first batch in 3 days") shared into the WhatsApp/Facebook seller groups sellers
   are already in — meet them where they already spend time, don't ask them to discover you cold.

---

Cross-reference: `docs/PROJECT_STATUS.md` (what's built vs. pending), `docs/DEVELOPER_TASKS.md`
(technical task detail), `docs/REGISTRATION_SECURITY_TASKS.md` and
`docs/PHONE_VERIFICATION_TASKS.md` (security gaps).
