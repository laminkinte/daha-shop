# Email & SMS Strategy — Why We Built It This Way

## The win, in one sentence

We now send real email for free, and we cut SMS (the notification channel that actually costs
money) from 10 of our 16 notification types — without losing any coverage that matters, because
the 10 we dropped all land on people who already check their dashboard as part of their job.

---

## Part 1 — Email: Gmail SMTP instead of a paid API

### What we did

Real email delivery is live, using Gmail's own SMTP server (`smtp.gmail.com`) authenticated with a
**Google App Password** — a 16-character code you generate once from a Google Account's security
settings. `.env` holds the address, the app password, and `smtp.gmail.com:587`; Laravel's built-in
`Mail` facade does the rest.

### Why App Password instead of OAuth2

Google gives you two ways to authenticate against Gmail:

| | App Password | OAuth2 (XOAUTH2) |
|---|---|---|
| Setup | Generate a code once, paste into `.env` | Register an OAuth client, build a consent flow, store a refresh token, add an OAuth2 mail transport package |
| Requires | 2-Step Verification enabled | Gmail API enabled on a Google Cloud project, a one-time browser consent screen |
| Ongoing maintenance | None — the code doesn't expire | Refresh tokens can be revoked; needs monitoring |
| Cost/time to build | ~5 minutes | Hours, plus a real consent-flow UI |

We don't have the funding or the need yet for the more "correct" OAuth2 approach — it exists mainly
for apps that send email *as* many different end users (e.g. a Gmail add-on), which isn't our
situation. App Password gets us 100% of the functionality for a fraction of the effort.

### Pros of this approach

- **Free.** No monthly bill, no per-email cost, no credit card on file with a third party.
- **Fast to set up and already verified working** — a real email round-tripped through it during
  build.
- **No new vendor relationship** — one less account/contract/API key to manage while pre-revenue.
- **Zero code complexity** — it's a `.env` change, not a library.

### Cons — and what would make us outgrow it

- **Gmail's own sending limits apply.** A regular Gmail account is capped at roughly 500
  recipients/day; a Google Workspace account raises that to ~2,000/day. Once order volume pushes
  us near that, emails will start silently failing/bouncing.
- **Deliverability is weaker than a dedicated transactional provider.** Gmail SMTP has no delivery
  analytics (opens, bounces, spam complaints), no dedicated sending IP, and mail *from* a
  `@gmail.com`/personal-looking address reads less professionally to customers than
  `noreply@dahashop.com` would, and is more likely to land in spam as volume grows.
- **No domain reputation of our own.** SPF/DKIM are Google's, not ours — we can't build up "this
  domain is trustworthy" signal the way we could with our own sending domain.
- **Single point of failure with no SLA.** If Google's automated abuse detection ever flags the
  sending pattern (a real risk once volume climbs — this is exactly the kind of automated,
  templated, recurring send that spam filters are built to catch), the account can be locked with
  no support escalation path, since we're not a paying customer.
- **Technically outside the intended use case.** Google's consumer terms are written for a human
  sending personal mail, not an application sending automated transactional email. It works today
  at our volume; it isn't what Gmail is *for*.

**The upgrade path is trivial when we need it**: swap `MAIL_MAILER`/`MAIL_HOST` in `.env` for
Postmark, Amazon SES, or Resend (all already referenced in `config/services.php` from earlier
planning) — no application code changes required, since everything goes through Laravel's `Mail`
facade already. This is a "good enough for now, upgrade when it matters" decision, not a permanent
architecture choice.

---

## Part 2 — SMS: why we cut it from 10 of 16 notification types

### The cost problem

SMS costs real money per message (via Termii, already integrated). Every one of our 16
notification types used to fire SMS *and* email. A single order lifecycle alone could trigger 6+
SMS. At any real volume, SMS is the line item that scales with the business — email and in-app
don't cost anything extra per message.

### The rule we applied

**Does this person need to be interrupted right now, or will they see it next time they check
their dashboard anyway?**

**Kept on SMS** (reaches someone who may not be looking at anything right now):
- OTP codes — security-critical, non-negotiable
- Order confirmed — the customer just paid, may not check email
- Order rejected / cancelled — a definitive "this isn't happening," the customer needs to know
- Delivery failed — time-sensitive, the customer needs to act
- KYC photo retake request — blocks the vendor from selling, and it's rare enough that cost isn't
  a factor

**Moved to email + a new in-app notification, SMS dropped** (the recipient already lives in a
dashboard as part of their job, or it's an FYI, not a definitive outcome):
- Vendor: cash collected, cash remitted, payout paid, product approved/rejected, subscription
  activated/expired
- Agent: delivery assigned
- Customer: vendor accepted the order (they already know it's confirmed — this is just a progress
  ping), one item in a multi-vendor order was rejected and refunded (less severe than the whole
  order failing)

This is the same split professional logistics/fintech platforms use for the same reason: SMS for
anything a consumer might miss because they're not in the app; in-app + email/push for anyone
whose job already has them checking a screen regularly.

### What "in-app notification" means technically

We added a notification bell (top-right of every dashboard page) backed by Laravel's built-in
database notification channel — a `notifications` table, one reusable `App\Notifications\InAppAlert`
class (title/message/link), and a small Livewire component (`NotificationBell`) with an unread
badge, a dropdown, and mark-read/mark-all-read actions. It costs nothing per notification — it's
just a database row.

### Pros of this split

- **Directly cuts the recurring cost that scales with order volume**, without touching the
  channels that don't cost anything extra.
- **No coverage gap for anything urgent** — everything time-sensitive or a definitive outcome
  stayed on SMS.
- **Vendors/agents get a persistent, glanceable history** (the notification bell) that SMS never
  gave them — arguably better UX than a text they have to remember or search for later.

### Cons / things worth watching

- **Phone/PIN accounts have no email fallback.** Their email column is a synthetic placeholder
  (see `User::hasRealEmail()`), so for the 10 "dropped" notification types, a phone/PIN vendor or
  agent *only* sees them in the in-app bell — no push, no SMS, no email. If they don't log in for a
  while, they won't know a product was rejected or a payout landed until they do. Worth revisiting
  if phone/PIN accounts turn out to check the dashboard less often than email accounts.
- **No push notifications yet.** The "in-app" tier still requires opening the dashboard to see
  anything new — there's no way to alert someone the moment something happens without SMS or a
  native app with push. If we ever build a mobile app, push would let us drop SMS even further at
  zero marginal cost.
- **We're trusting our own judgment on the split**, not user data. Once we have real usage, it's
  worth checking whether vendors are actually missing anything important, or whether the split
  could be tightened further.

---

## Part 3 — the bonus find: every notification was firing twice

While building this, we added the first *exact-count* test assertion this codebase has ever had
(`Bus::assertDispatchedTimes(...)` — earlier tests only checked "was this sent at all," never "how
many times"). It failed immediately: every SMS and every email had been dispatching **twice** since
the very first notification listener was ever added to this app.

**Root cause**: Laravel 12's `Application::configure()` (in `bootstrap/app.php`) registers its own
automatic event-discovery provider by default — completely independent of this app's own
`App\Providers\EventServiceProvider`, which has its own manual listener list. Both providers were
registering the same listener classes, so every event fired its listener twice: two SMS, two
emails, for every single order/vendor notification, for as long as this feature has existed.

**Fix**: one line, `->withEvents(discover: false)` in `bootstrap/app.php`, so only the explicit,
manually-reviewed listener list in `EventServiceProvider` runs.

This means the SMS cost problem we just fixed was actually **worse than it looked** going into
this work — real spend (once we're on a paid SMS plan) would have been double what the notification
list implied. Combined with dropping SMS from 10 notification types, actual SMS volume going
forward should land somewhere around **5x lower** than what an un-audited version of this app would
have cost: half the dispatch count (bug fix) × roughly a third of the notification types still
using SMS (the cost split above).

---

## Quick reference: current state

| Channel | Cost | Used for |
|---|---|---|
| SMS (Termii) | Per-message, real cost | OTP, order confirmed/rejected/cancelled, delivery failed, KYC retake |
| Email (Gmail SMTP) | Free (App Password) | All 16 notification types |
| In-app (dashboard bell) | Free (database row) | The 10 vendor/agent/FYI notification types |

Run `php artisan test` any time to confirm this is all still true — the exact-count assertions in
`tests/Feature/OrderLifecycleTest.php` and `tests/Feature/AdditionalNotificationsTest.php` will fail
loudly if SMS ever creeps back onto a dropped notification type, or if the double-dispatch bug ever
resurfaces.
