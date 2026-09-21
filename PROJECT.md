# Project Plan & System Prompt: Custom WooCommerce Build

> Keep this file at the repo root (or as `CLAUDE.md`). Update **Section 12 (Current Phase)** and **Section 13 (Dev Log)** before every new session.
>
> See also **[TODO.md](./TODO.md)** — the client-facing punch list of decisions/access/files we're waiting on. This file (PROJECT.md) is the internal engineering log; TODO.md is the trimmed, plain-language version meant to be forwarded to the client. When a §15 question or a dev-log "still open" item gets resolved here, update TODO.md too.

---

## 1. Project Overview

* **Project Name:** [Insert Project Name] — repo currently `rxShoes`
* **Client:** [Client Name] — via Upwork
* **Target Market:** Australia only — single currency AUD, single language (English). No multi-currency/multi-language needed. Tax: GST **[Guessing: standard 10% GST-inclusive pricing assumed — matches the $ figures shown in the Figma bundle-builder mockup, but still not explicitly client-confirmed in writing]**
* **Product category:** Shoes/footwear, multi-brand (TYR, R.A.D, Inov-8, Strike Mvmnt, Vivobarefoot, NoBull, Puma, Nitro per Figma) — RX Shoe is the retailer, not the manufacturer. Confirm this reading — affects whether "Brand" needs to be a first-class taxonomy/filter.
* **Traffic profile — DECIDED PRIORITY:** ~90% mobile. Build and test mobile-first throughout, not as a responsive afterthought. Site speed is explicitly called out by the client as critical.
* **Core Goal:** A fast, scalable, maintainable WooCommerce store built from scratch from supplied Figma designs. Custom functionality over plugin stacking.
* **Catalog Size:** ~50 core products, 250+ variations. Client will supply an Excel file (names, descriptions, sizes, colours, ~3-5 images/product). See §5 for the import/image pipeline recommendation.
* **Design Source:** Figma — https://www.figma.com/design/0Q5iFcBVniwb0HnBJRXbKc/Untitled (file key `0Q5iFcBVniwb0HnBJRXbKc`). Access via a Figma Personal Access Token, stored locally at repo root in `.figma-token` (gitignored, never commit). Frame inventory and findings logged in §13. Pixel-accurate implementation across desktop / tablet / mobile — but mobile is the priority per the traffic profile above, not an equal third.
* **Post-launch:** Ongoing development and technical support (retainer — terms TBD).

---

## 2. Scope of Work (from client brief)

- [ ] WordPress + WooCommerce build from scratch
- [ ] Figma → live site (custom theme)
- [ ] Product setup: ~50 products / 250+ variations (size, colour, other attributes)
- [ ] Custom product bundles
- [ ] Bundle discounts & promotional pricing rules
- [ ] Custom add-to-cart and purchasing flows
- [ ] Custom checkout flows
- [ ] Payment gateway integrations
- [ ] Shipping and order workflow configuration
- [ ] Customer account functionality
- [ ] Transactional emails + third-party email platform integration
- [ ] Analytics, tracking and conversion integrations
- [ ] Responsive build
- [ ] Performance / page-speed optimisation
- [ ] Clean, scalable backend architecture
- [ ] Testing: products, variations, payments, checkout, orders
- [ ] Launch + post-launch support

---

## 3. Tech Stack & Environment

| Area | Choice |
|---|---|
| WordPress | Latest stable |
| WooCommerce | Latest stable, **HPOS enabled** |
| PHP | 8.2+ |
| Database | MySQL 8 / MariaDB 10.6+ |
| Object cache | Redis |
| Local | [LocalWP / DDEV] |
| Staging / Production | [e.g., Kinsta / Cloudways / Rocket.net] — staging must mirror production |
| Version control | Git — `main` (prod), `staging`, feature branches |
| Dependencies | Composer (PHP), npm (build tooling) |
| Build | [Vite / @wordpress/scripts] |
| Code quality | PHPCS + WPCS, PHPStan (level 5+), ESLint |

### Plugins (keep minimal — every plugin must justify itself)
* **SEO:** [Rank Math / Yoast]
* **Payments:** [WooCommerce Stripe Gateway / WooPayments], [PayPal Payments]
* **Email platform:** [Klaviyo / Mailchimp / other] official integration
* **Analytics:** GA4 + Consent Mode v2 via [GTM], Meta Pixel + Conversions API
* **Caching:** Host-level page cache + Redis object cache [+ WP Rocket / LiteSpeed if needed]
* **Security:** Host WAF / Cloudflare, [Wordfence / Solid Security] if needed
* **SMTP:** [Postmark / SendGrid / SES] for transactional deliverability
* **Backups:** Host daily + off-site

**Rule:** Bundles, discount logic, and purchasing flows are built as custom code — no commercial bundle/discount plugins unless explicitly agreed.

---

## 4. Architecture

### 4.1 Theme
* Custom theme built from Figma: `[project]-theme`
* [Classic PHP templates + minimal block support] — chosen for full control over WooCommerce templates and performance. No page builders.
* Theme = presentation only. **No business logic in the theme.**
* Design tokens from Figma (colours, typography, spacing) → CSS custom properties / `theme.json`. **In progress:** `rx-theme/assets/css/tokens.css`, extracted from the Figma API (not eyeballed), fonts self-hosted.
* **No CSS framework (Bootstrap, etc.) — client asked, declined.** Custom token-driven CSS, mobile-first, matches the "no page builders / minimal deps" principle above and avoids fighting a framework's defaults to hit the bespoke Figma design. See §13 dev log, 2026-09-21.

### 4.2 Functionality Plugin
All business logic lives in a site-specific plugin: `[project]-core`

```
[project]-core/
├── [project]-core.php          # Bootstrap, HPOS compatibility declaration
├── composer.json               # PSR-4 autoload: Project\Core\
├── src/
│   ├── Plugin.php              # Service registration
│   ├── Bundles/                # Custom bundle product type, cart logic
│   ├── Pricing/                # Discount & promo rules engine
│   ├── Cart/                   # Custom add-to-cart flows, AJAX / Store API
│   ├── Checkout/               # Custom checkout fields, steps, validation
│   ├── Orders/                 # Order statuses, workflows, meta
│   ├── Shipping/               # Custom shipping rules (if needed)
│   ├── Accounts/               # My Account endpoints/customisations
│   ├── Emails/                 # Custom WC_Email classes
│   ├── Integrations/           # Email platform, analytics, third-party APIs
│   ├── Admin/                  # Settings pages, admin UI
│   └── REST/                   # Custom REST endpoints
├── templates/                  # Overridable plugin templates
├── assets/                     # Built JS/CSS
└── tests/                      # PHPUnit
```

### 4.3 Core Principles
* Namespaced, class-based, PSR-4 autoloaded code. Prefix for hooks/options/meta: `prj_` [change].
* Declare HPOS compatibility. **Never** read/write order data via `get_post_meta()` or direct `wp_posts` queries — use `wc_get_order()` and CRUD methods.
* Background/heavy work (API syncs, emails to platforms) via **Action Scheduler**, never blocking checkout.
* All third-party API calls: timeouts, retries, logging via `wc_get_logger()`.
* Settings via Options API; API keys via `wp-config.php` constants / env, never in the DB or repo.

---

## 5. Catalog & Variations

* **Product types:** Variable (main), Simple, custom `bundle` type [+ others?]
* **Global attributes:** Size, Colour, [Other] — use global attributes (`pa_*`), not per-product custom attributes, for filtering and consistency.
* **Variation count:** 250+ total — check any single product with >30 variations; raise `woocommerce_ajax_variation_threshold` or implement custom variation loading.
* **Swatches:** Custom colour/size swatches built into the theme per Figma (no swatch plugin unless agreed).
* **Import:** Products imported via CSV / WP-CLI script so the catalog can be rebuilt reproducibly on staging.
* **Free gift-with-purchase (from client, 2026-09-21):** one pair of socks, free, one per order — one-size, gender-neutral. Implement as a real product/SKU auto-added to the cart (forced qty 1, price forced to 0, not user-removable), not a coupon hack — needs its own line item for stock/fulfilment/order-export visibility.
* **SKU convention:** [e.g., `PRODUCT-COLOUR-SIZE`]
* **Images:** Per-variation images; WebP/AVIF; defined image sizes matching Figma.
* **Product data pipeline (client will supply an Excel file — names, descriptions, sizes, colours, ~3-5 images/product):** recommended approach —
  1. Convert the Excel sheet to WooCommerce's standard product-import CSV format (name, SKU, description, attributes, and an `Images` column of pipe-separated URLs) — either the client exports as CSV directly or we do the conversion.
  2. Images: have the client upload the full image set to one shared cloud folder with predictable filenames (`SKU-1.jpg`, `SKU-2.jpg`, …) rather than emailing/attaching them piecemeal — this is what makes bulk import actually fast. WooCommerce's importer pulls images by URL during import, so nobody uploads 150-250 files by hand through wp-admin.
  3. Run the import via WP-CLI (scriptable, repeatable on staging) rather than the admin UI one-off importer, per the Import line above.
  4. To keep the site fast once images are in: serve WebP/AVIF with responsive `srcset` (already planned above), rely on WordPress core's native `loading="lazy"`, and register real image sizes matching the Figma PDP/gallery/thumbnail dimensions instead of shipping oversized originals. Whether that conversion happens at the host/CDN level or via a media-processing step in the import script depends on the hosting choice — still open, see §3/§15 Q7.

---

## 6. Bundles, Pricing & Discounts

### 6.1 Bundles
* **Build vs. buy — DECIDED:** custom product type, not a commercial bundle plugin (e.g. WooCommerce Product Bundles). Client asked for a recommendation; reasoning: (1) §3 already rules out commercial bundle/discount plugins unless explicitly agreed, (2) §8 now requires bundle pricing to react live to the selected payment method at checkout (PayID-only promo, removed + popup if the customer switches to Card/PayPal) — a commercial bundle extension's pricing hooks aren't built for that and fighting a third-party plugin's internals mid-checkout is a maintenance liability on a retainer contract. Custom code keeps the whole pricing → cart → checkout → order-meta chain in one place we control.
* Custom product type: `WC_Product_Prj_Bundle extends WC_Product` (type `prj_bundle`).
* Bundle defines: component products/variations, fixed or customer-selectable options, quantities.
* **Fixed vs. build-your-own — effectively answered:** client gave direct UX direction on 2026-09-21 ("the bundle builder needs to be simple and easy to use, mainly on mobile"), which presupposes the build-your-own bundle builder seen in the Figma "Bundle Builder" frame is real, confirmed scope, not a maybe. Mixed variants (different models/sizes/colours in one bundle) are shown in that frame and not contradicted — treating as confirmed. **Mobile-first is a hard requirement for this specific UI** — the Figma desktop bundle-builder layout (three-column picker + sticky sidebar) will need a genuinely mobile-first design pass, not just a squeezed-down desktop layout, before front-end work starts on it.
* **Cart representation:** [Parent line + child lines linked by cart item key] OR [single line with component meta] — decide early, affects stock, shipping, refunds, and analytics.
* **Stock:** Components reduce their own stock; bundle availability derived from components.
* **Order data:** Component details stored as order item meta for fulfilment/export.

### 6.2 Pricing Rules Engine
* Rule types: bundle discount (% / fixed), quantity tiers, BOGO, cart-level promos, date-limited promotions, [customer-group pricing?]
* Applied via `woocommerce_before_calculate_totals` (line prices) or cart fees/coupons — **one consistent strategy**, documented here: [decision].
* **Coupon stacking:** allowed in principle (bundle discounts + coupons can combine) but **explicitly low priority — not in MVP scope**. Build the rule-evaluation logic so a stacking rule can be added later without a rewrite (don't hard-code "bundle discount is the only discount").
* **Payment-method-conditional promotions — SCOPE FINALISED (2026-09-21, supersedes the earlier PayPal/Card wording):** the bundle discount applies **only** when paying via **PayID or PayTo** (both via AzuPay — see §8). Any other payment method (Stripe card, or anything else later) must strip the discount, show a clear warning explaining why, and actively encourage switching back to PayID/PayTo. Client was explicit: *"If they use any other payment gateway or payment options, I don't want them to be able to get this [discount]."* This is a hard business rule, not a UX nicety — the gateway-eligibility check belongs server-side in the pricing calculation itself (`woocommerce_before_calculate_totals`), not just in the UI, so it can't be bypassed by tampering with the client-side request.
* **Discount tier numbers still unconfirmed:** see the 30%/45% vs. 40%/55% inconsistency noted under §13 dev log — not yet resolved by the client, don't hardcode either.
* Price display: show original vs discounted price consistently on PDP, cart, mini-cart, checkout, emails.
* Admin UI for managing rules without code.

---

## 7. Cart, Add-to-Cart & Checkout

* **Add-to-cart:** AJAX add-to-cart on PDP and archives, side/mini-cart per Figma, cart fragments optimised (or replaced by Store API calls).
* **Custom purchasing flows:** [describe — e.g., build-your-own bundle, upsell step, quick-buy]
* **Cart/Checkout implementation — DECIDED (2026-09-21): classic shortcode checkout with hooks.** Reason: the payment-method-conditional promotion (PayID-only discount, live re-price + popup if switched to Card/PayPal) is straightforward via `woocommerce_checkout_update_order_review` / `woocommerce_before_calculate_totals`; the same behaviour on Blocks needs a custom Store API `ExtendSchema` extension plus React-side UI work — more build, less mature pattern for this exact case. Accepted risk: WooCommerce's ecosystem direction favours Blocks long-term, so this may need revisiting/migrating later in the retainer.
* **Checkout flow:** [single-page / multi-step], guest checkout [yes/no], custom fields: [list].
* **Checkout video/message (client, 2026-09-21):** a short video or clear message at checkout reminding customers the discount only applies via PayID/PayTo — matches the "60 seconds before you checkout" video already shown in the Figma checkout mockup, now confirmed as a real requirement, not mockup flavour. Homepage also gets a main sales video at the top (explains the "VSL" frame naming); product pages may get short videos too.
* **Video delivery — flag, not yet decided:** with 90% mobile traffic and speed as an explicit priority, these videos must not be naive autoplaying embeds — recommend click-to-play (thumbnail + play button, which is what the Figma checkout mockup already shows) with `preload="metadata"`, and hosting via a proper video host/CDN (not raw files served from WP uploads) once hosting (§15 Q7) is settled.
* Validation server-side always; client-side only as UX.

---

## 8. Payments, Shipping & Orders

* **Gateways — FINALISED (2026-09-21), supersedes the earlier Card/PayPal/PayID line and the Figma checkout mockup (which shows Apple Pay/Google Pay/PayPal/Afterpay/Zip — none of that is current scope; the mockup is stale on this point):**
  * **PayID + PayTo, via AzuPay.** No official WooCommerce plugin — confirmed by reading AzuPay's API docs (`developer.azupay.com.au`). This is a raw REST API (`POST /paymentRequest`), so both become custom `rx-core` gateway classes (`azupay_payid`, `azupay_payto`), distinguished by the response's `settledBy` field (`PayID` / `PayTo` / `BSBAndAccountNumber`). Auth is a secret key in the `Authorization` header.
  * **Card, via Stripe** (my recommendation, see below) rather than Hello Clever.
  * **Explicitly excluded at launch:** PayPal, Afterpay, Zip — despite appearing in the Figma checkout frame.
  * **Card gateway decision — Stripe over Hello Clever:** client asked for a recommendation between the two. Hello Clever's own WooCommerce integration docs don't state whether it supports classic checkout, webhook/order-status handling, or hook depth — all undocumented. Since PayID/PayTo already require one fully custom integration, adding a second under-documented one (Hello Clever) is pure risk for no benefit — the discount-eligibility check treats "any non-PayID/PayTo method" identically regardless of which processor it is. Stripe's official WooCommerce plugin is the most mature classic-checkout integration available, with full hook support and mature webhook handling. Client indicated openness to this exact trade (drop Hello Clever for more control via Stripe) — recommendation, not yet a client-confirmed final decision.
  * **Payment confirmation is asynchronous, not instant.** AzuPay's `paymentRequest` has a `WAITING` → `COMPLETE` (or `EXPIRED`/`RETURN_*`) status lifecycle, confirmed via a webhook (`paymentNotificationEndpointUrl`) that AzuPay retries up to 45× at 20s intervals until it gets an HTTP 200 — our webhook receiver must be idempotent. Model this the way WooCommerce's built-in BACS gateway does: order created in an `on-hold`/custom `wc-awaiting-payment` status, moved to `processing`/`completed` only once the webhook confirms.
  * **Unpaid-order → Omnisend workflow — now has a clear owner:** WooCommerce owns payment/order state (via the AzuPay webhook above); an order-status-change event is what triggers Omnisend's email + SMS reminder flow, not the reverse. Still to confirm: whether that's a webhook to Omnisend, or Omnisend's own WooCommerce integration polling/listening for status changes — research Omnisend's native WC integration before building a custom pusher.
  * **Discount enforcement must be server-side, not just UI:** the bundle discount is only valid for `azupay_payid` / `azupay_payto` as the selected `payment_method` — this check belongs in the cart total calculation itself (`woocommerce_before_calculate_totals`), so it can't be bypassed by tampering with the checkout request. See §6.2.
* Test mode on staging; webhooks configured per environment.
* **Shipping:** Zones [list], methods [flat / free over X / carrier rates via ___], bundle shipping rules.
* **Order workflow:** Custom statuses if needed [e.g., `wc-awaiting-fulfilment`], fulfilment/3PL integration: **still open — §15 Q6 not yet answered.**
* **Refunds:** Bundle and discounted-item refunds must calculate correctly.

---

## 9. Accounts, Emails & Integrations

* **Customer accounts:** Registration, order history, addresses, [reorder, wishlist, subscriptions?] — styled per Figma.
* **Transactional emails:** Custom-styled WC email templates; custom `WC_Email` classes for new workflows.
* **Email platform — DECIDED: Omnisend** (was placeholder). Client confirmed via correspondence, plus a specific automation requirement: unpaid-PayID orders trigger an Omnisend email + SMS workflow prompting payment (see §8). Standard events still apply: customer sync, placed order, abandoned cart, product viewed. Consent respected.
* **Analytics:** GA4 enhanced ecommerce (`view_item`, `add_to_cart`, `begin_checkout`, `purchase` with bundle/variant data), Meta Pixel + CAPI with event dedup, [TikTok / Google Ads conversions].
* **Consent:** Market is Australia, not EU/UK — Consent Mode v2/GDPR banner as written here is the wrong default. Needs an actual decision against the Australian Privacy Act (and any state-based SMS/email marketing consent rules relevant to the Omnisend SMS flow), not GDPR boilerplate. Flagging, not deciding — not asked yet.
* **Other APIs:** [ERP / inventory / reviews / 3PL]

---

## 10. Performance & Security Targets

**Performance**
* Core Web Vitals green on mobile: LCP < 2.5s, INP < 200ms, CLS < 0.1
* No jQuery on the front end where avoidable; conditional loading of scripts/styles (WooCommerce assets only on shop pages).
* Page cache for anonymous users; cart/checkout/account excluded.
* Redis object cache, optimised autoload options, transients for expensive queries.
* Images: responsive `srcset`, lazy loading, preloaded LCP image.

**Security**
* Sanitize input, escape output, nonces + capability checks on every form/AJAX/REST endpoint.
* `$wpdb->prepare()` for any raw SQL.
* No secrets in repo. 2FA for admins. Least-privilege user roles.
* XML-RPC disabled, file editing disabled (`DISALLOW_FILE_EDIT`).

---

## 11. Claude's Role & Rules

You are a **senior WordPress core developer and WooCommerce architect** working on this project. Follow these rules:

1. **Security first:** Sanitize input (`sanitize_text_field()`, `absint()`, etc.), escape output (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`), verify nonces and capabilities on every form, AJAX handler and REST endpoint.
2. **WooCommerce CRUD + HPOS:** Use `wc_get_product()`, `wc_get_order()`, getters/setters and data stores. Never access order data through post meta or raw SQL on `wp_posts`.
3. **Hooks over template overrides:** Prefer actions/filters. Override a template only when unavoidable, and note it in the Dev Log (overrides need checking on every WC update).
4. **Business logic in `[project]-core`, presentation in the theme.** Never modify WP/WC core or third-party plugin files.
5. **Code style:** WordPress Coding Standards, namespaced PSR-4 classes, typed PHP 8.2 code, docblocks on public methods. Prefix everything with `prj_`.
6. **Performance:** No queries in loops, no unbounded `WP_Query` (`posts_per_page => -1`), cache expensive results, enqueue assets conditionally.
7. **Bundles & pricing:** Any change to pricing/bundle logic must consider cart, mini-cart, checkout, order totals, emails, refunds, stock and analytics events.
8. **Integrations:** External calls must be non-blocking for checkout (Action Scheduler), with timeouts, error handling and logging.
9. **Be explicit:** When a decision is needed, state the options briefly, recommend one, and say why. Flag risks (update compatibility, performance, edge cases).
10. **Output:** Complete, working code with file paths. Say where each snippet goes. Include test steps for anything touching cart/checkout/payments.

---

## 12. Current Implementation Phase

> *Update before each new session.*

* **Current Focus:** Milestone 1 scaffold complete. Blocked on Section 15 (client brief) before any bundle/pricing/checkout/payment code is written — do not guess these.
* **Completed:**
  * [x] Local Git repo initialised (`main` branch; WP core/contrib excluded via `.gitignore`, only `rx-theme` + `rx-core` versioned)
  * [x] WordPress + WooCommerce install, HPOS enabled (confirmed via `woocommerce_custom_orders_table_enabled = yes`)
  * [x] Theme scaffold — `rx-theme` (style.css, header/footer/index, functions.php; WC default styles disabled, Figma design tokens not yet applied — no Figma link supplied)
  * [x] `rx-core` plugin scaffold — namespaced `RX\Core\`, PSR-4 via Composer, HPOS compatibility declared, `Plugin`/`Service` bootstrap, empty `src/{Bundles,Pricing,Cart,Checkout,Orders,Shipping,Accounts,Emails,Integrations,Admin,REST}` dirs ready for real services, PHPCS (WPCS) + PHPStan (level 5) configured and passing clean
  * [x] Both activated on the `RX` install and smoke-tested (front end 200, admin login redirect 302, no fatals)
* **Environment note:** WAMP's active CLI/Apache PHP defaults to 7.4.33; this project targets 8.2+ per §3. Scaffold work used `C:\wamp64\bin\php\php8.2.13\php.exe` directly. **Switch WAMP to PHP 8.2 (tray icon → PHP → Version) before browsing the site or running `composer`/`wp` without an explicit binary path**, or typed code here will fatal.
* **Update (2026-09-21, later):** Section 15 is now answered except fulfilment (Q6). Figma access working (API token). Checkout architecture decided (classic). Homepage build started: `front-page.php` + `template-parts/front-page/` (one file per Figma-named section), Hero is the first section built — content pulled from the Customizer (`inc/customizer.php`, `RX Homepage` panel), not hardcoded, specifically so the still-unconfirmed tier discount % can be corrected from the dashboard without a deploy. 4 dev/test products exist for building/testing against (see §13, §14 — must be wiped before launch).
* **Update (2026-09-21, later still):** Figma's API is rate-limited (Starter-plan quota, ~4.6 day reset — see §13 dev log) and the client doesn't control the file's owning team, so it can't just be upgraded on demand. Worked around it for Category Cards by pixel-sampling the already-cached full-resolution `home.png` screenshot instead — every colour needed turned out to already exist in `tokens.css`, so no new API calls were actually required. Category Cards is now fully styled and verified via a live screenshot. This "sample the cached screenshot" approach is the fallback for any section while the rate limit is up; a fresh API pull is still preferred when available.
* **Immediate Next Steps:**
  1. [ ] Remaining homepage sections, in Figma order: Best Sellers grid, Educational comparison, Shop by Brand, Community Rotations — API pull if the rate limit has cleared, cached-screenshot/client-export pixel-sampling if not
  2. [ ] Fulfilment answer (Section 15 Q6) — only open original-brief question left
  3. [ ] Full-width vs. contained layout — new open question, see TODO.md #7
  4. [ ] Real product catalog once the client's Excel file arrives (§5 import pipeline)
  5. [ ] Real (non-screenshot-crop) category photos before launch — current ones bake in the Figma mockup's own badge text (see §13 dev log)
  6. [ ] Product card + its three admin controls are built (§13). Still to do around it: the Shop page chrome (heading/breadcrumb/result count/sorting are unstyled WooCommerce defaults — needs its own pass against the Figma "Shop Collection" frame); homepage Section 4 (Best Sellers) should reuse this card, not rebuild it; the real bundle discount calculation from cart contents (Milestone 4) — the card only *displays* a tier % today

### Milestones
| # | Milestone | Status |
|---|---|---|
| 1 | Setup, architecture, theme scaffold | [x] |
| 2 | Catalog, attributes, variations, import | [ ] |
| 3 | Figma build — global, home, archive, PDP | [ ] |
| 4 | Bundles + pricing rules engine | [ ] |
| 5 | Cart, add-to-cart flows, checkout | [ ] |
| 6 | Payments, shipping, order workflows | [ ] |
| 7 | Accounts, emails, email platform | [ ] |
| 8 | Analytics, tracking, consent | [ ] |
| 9 | Performance, security, QA | [ ] |
| 10 | Launch + hypercare | [ ] |

---

## 13. Development Log & Decisions

| Date | Type | Entry |
|---|---|---|
| [YYYY-MM-DD] | Decision | [e.g., Classic checkout chosen over Blocks — custom multi-step flow needs full hook control] |
| [YYYY-MM-DD] | Template override | [e.g., `single-product/add-to-cart/variable.php` — custom swatches] |
| [YYYY-MM-DD] | Issue | [problem → fix] |
| 2026-09-21 | Decision | Project slug `rx`, prefix `rx_core` for hooks/functions/vars, `RX\Core` namespace, constants `RX_CORE_*` — inferred from folder/DB name (`rx`), not client-supplied. Cheap to rename now (nothing built on top yet); revisit once a real project/client name exists. |
| 2026-09-21 | Decision | `.gitignore` excludes WP core and third-party plugins/themes (WooCommerce, default themes) — only `rx-theme` and `rx-core` are versioned. Revisit if the team wants full-core versioning instead. |
| 2026-09-21 | Issue | WAMP's default CLI/Apache PHP is 7.4.33; this plan targets 8.2+. PHP 8.2.13 is installed under WAMP but not selected. Composer/WP-CLI must be invoked via the explicit 8.2 binary until WAMP is switched over. |
| 2026-09-21 | Milestone | Milestone 1 (setup/architecture/scaffold) done: git init, `rx-theme` scaffold, `rx-core` plugin scaffold (HPOS-declared, PSR-4, PHPCS/WPCS + PHPStan lvl 5 clean), both activated and smoke-tested with no fatals. Milestones 2+ (catalog, bundles, pricing, checkout, payments, etc.) are blocked on Section 15 client answers — no business logic invented ahead of real requirements. |
| 2026-09-21 | Decision | Repo pushed to `https://github.com/gridsoft/rxShoes.git` (`main`). Only `rx-theme`/`rx-core`/`PROJECT.md` are tracked, per the existing `.gitignore` — no WP core, no WooCommerce, no default themes pushed. |
| 2026-09-21 | Answered | Client answered several §15 questions: target market AU/AUD (Q1), bundles built as custom code not a plugin (Q2, platform half only — config half still open), coupon stacking allowed but low priority (Q3), payment methods Card/PayPal/PayID (Q4, PayID's PSP still unnamed), email/SMS platform is Omnisend with an unpaid-PayID nudge workflow (Q5). See §1, §6, §8, §9, §15 for detail. |
| 2026-09-21 | Requirement | New, not in the original scope list: promotions can be tied to payment method (PayID-only discount), with live removal + a popup notice if the customer switches to Card/PayPal at checkout. This is a hard input into the still-undecided §7 checkout architecture (classic vs. Blocks) — flagged to the client as a decision needed before Cart/Checkout services are built. |
| 2026-09-21 | Decision | §7 checkout architecture: classic shortcode checkout, confirmed. See §7 for reasoning and accepted risk. |
| 2026-09-21 | Design access | Figma access via a client-supplied Personal Access Token (read-only scope — client has viewer access, not ownership). Stored locally at repo root in `.figma-token`, gitignored, confirmed excluded from git before use. |
| 2026-09-21 | Design findings | Read the Figma file via the REST API (12 frames: Home, Shop, PDP, Bundle Builder, 2× Premium VSL Checkout, 2× PayID Exclusive Checkout, 2× Mobile VSL Checkout, Wordmark). Extracted exact colours/fonts (not eyeballed): brand blue `#0066FF`, ink `#111111`, lime accent `#AAFD30`; type is Barlow Condensed (display/headings, weights 500-900) + Inter (body, 400-700) — both self-hosted as woff2 in `rx-theme/assets/fonts`, latin subset only. "VSL" = video sales letter; the checkout frames embed a literal 60-second pitch video, matching the client's later-confirmed checkout-video requirement. Frame exports saved to `design-reference/` (not committed — reference only, regenerable from the API). |
| 2026-09-21 | Issue (design vs. reality) | The Figma checkout mockup shows Apple Pay/Google Pay/PayPal/Afterpay/Zip as payment options and a "first 100 customers" PayID scarcity counter. Flagged both to the client before building anything against them. The payment-method list was subsequently overridden by the client's direct instruction (see next entry) — PayPal/Afterpay/Zip are NOT in scope. The "first 100 customers" counter was not addressed by the client's reply and remains an open question — treat as mockup flavour text, not a requirement, unless the client confirms otherwise. |
| 2026-09-21 | Requirement | Client finalised payment scope directly (overriding the Figma mockup and the earlier Card/PayPal/PayID note): **PayID + PayTo via AzuPay**, **Card via Stripe** (client open to Hello Clever too — see recommendation below), **no PayPal/Afterpay/Zip at launch**. Also confirmed: free one-size/gender-neutral sock SKU (1 per order, auto-added), Omnisend for email+SMS, a homepage sales video + optional PDP videos + a checkout PayID/PayTo reminder video, ~90% mobile traffic with mobile-first + site speed as hard priorities, and that the bundle discount must be strictly gated to PayID/PayTo only (server-side, not just UI) with a warning + encouragement-to-switch-back on any other method. Full detail in §1, §5, §6, §7, §8. |
| 2026-09-21 | Recommendation | Read AzuPay's (`developer.azupay.com.au`) and Hello Clever's (`docs.helloclever.co`) integration docs before advising. AzuPay: raw REST API, no WC plugin — confirms PayID/PayTo need a custom `rx-core` gateway either way, webhook-confirmed (`paymentNotificationEndpointUrl`, retried up to 45×), `settledBy` field distinguishes PayID vs PayTo vs bank transfer. Hello Clever: WC plugin exists but its own docs don't document classic-checkout support, webhooks, or hook depth. Recommended **Stripe over Hello Clever** for card — see §8 for full reasoning. Not yet a client-confirmed final decision, just a recommendation made when asked. |
| 2026-09-21 | Recommendation | Client asked for advice on the fastest way to get ~50 products × 3-5 images into the catalog without slowing the site down. Recommended: Excel → WooCommerce CSV import format, images uploaded to one shared cloud folder with predictable filenames so the importer pulls them by URL instead of manual one-by-one uploads, run via WP-CLI (scriptable/repeatable per §5's existing Import line), WebP/AVIF + native lazy-loading + correctly-sized image registrations on the output side. See §5. |
| 2026-09-21 | Answered | Client answered remaining §15 questions in one pass: hosting is the client's own staging for now (Q7), no subscriptions/wishlists/reviews/loyalty (Q8), no existing site to migrate (Q9), post-launch support terms deliberately deferred (Q10). Only Q6 (fulfilment: in-house vs. 3PL) remains open. |
| 2026-09-21 | Milestone | `rx-theme` wired up for real: design tokens (`assets/css/tokens.css`) extracted from Figma via the API — exact colours (`#0066FF` blue, `#111111` ink, `#AAFD30` lime accent) and type (Barlow Condensed 500-900 + Inter 400-700, self-hosted woff2, latin subset only, no Google Fonts CDN request). `header.php`/`footer.php` rebuilt with the confirmed global nav/logo/footer structure — footer payment badges intentionally show PayID/PayTo/Stripe, NOT the stale Figma mockup's Stripe/Apple Pay/Afterpay/Zip. Base CSS in `style.css` is genuinely mobile-first (phone-width base styles, `min-width` media queries add desktop), per the client's explicit mobile-first/speed priority. Theme-specific `phpcs.xml.dist` added (prefix `rx_theme`, separate from the plugin's `rx_core`); both PHPCS and a live smoke test pass clean. |
| 2026-09-21 | Issue | The homepage returned 200 but rendered none of the theme's markup — turned out to be **WooCommerce's own "Coming Soon" mode** (`woocommerce_coming_soon` option, defaults to `yes` on a fresh WC install), which replaces the entire front end with WC's own "coming soon" block template regardless of active theme, before any theme template ever runs. Not a bug in `rx-theme`. Fixed via `wp option update woocommerce_coming_soon no`. **Worth remembering:** if the site ever appears to ignore theme changes entirely, check this setting (Settings > Site Visibility in wp-admin) before assuming a code problem. |
| 2026-09-21 | Recommendation | Client asked whether to use Bootstrap for responsive design. Recommended against it: PROJECT.md already rules out heavy front-end dependencies in favour of custom templates for performance (§3/§4.1/§10), the Figma tokens are already wired into a lean custom mobile-first CSS system, and Bootstrap's own grid/component defaults would need overriding throughout to match the bespoke design rather than saving work. Flexbox/Grid + the existing token system already covers what Bootstrap would be used for. |
| 2026-09-21 | Dev/test data | Created 4 real WooCommerce variable products for dev/testing at the client's request ("we will clean them after"): R.A.D ONE V2 (#13), TYR L-1 Lifter (#25), Inov-8 F-Fly Hybrid Run (#48), Strike Mvmnt Haze Trainer (#55) — 41 variations total across global `pa_size`/`pa_colour` attributes (per §5's "global attributes, not per-product custom" rule), real stock, and real product photos exported directly from the Figma file's "Best Sellers Product Grid" section (individual image nodes, not full-page screenshots). Prices match the Figma mockup ($229/$299/$219/$210 AUD). Names/descriptions are placeholder copy, clearly marked `[DEV/TEST PRODUCT]` in the description and tagged `dev-test-data`. **To remove them all before launch:** `wp post delete $(wp post list --post_type=product --product_tag=dev-test-data --field=ID) --force` (tested, confirmed this returns exactly the 4 IDs). Creation script kept at `design-reference/products/create_test_products.php` (gitignored, not shipped — it's throwaway tooling, not part of the theme/plugin). |
| 2026-09-21 | Issue | Creating these test products surfaced a real store-config bug: WooCommerce's currency was `EUR` and default country `AT` (Austria) — leftover installer defaults, never set for this AU-only project. Prices were literally displaying in Euros with European `229,00` formatting. Fixed: `woocommerce_currency` → `AUD`, `woocommerce_default_country` → `AU`, decimal separator → `.`, thousand separator → `,`. This would have been very easy to miss until someone eyeballed a live price. |
| 2026-09-21 | Milestone | Homepage build started, split into `template-parts/front-page/` (one file per Figma-named section) rendered from a new `front-page.php`. Hero is the first section. Per client instruction, all hero copy is dashboard-editable (Appearance > Customize > RX Homepage > Hero) via the core Customizer API — deliberately not ACF/a fields plugin, per §3's "plugins must justify themselves" rule; a handful of scalar fields didn't justify one. This also resolves the earlier-flagged 30%/45% vs. 40%/55% tier-discount inconsistency at the architecture level: since the two tier badges are now editable text, whoever confirms the real numbers fixes them from the dashboard, no code change needed. |
| 2026-09-21 | Issue | First version of the Customizer wiring silently rendered every field empty. Root cause: `get_theme_mod( $id )` does **not** automatically use the `'default'` passed to `add_setting()` — that default only pre-fills the Customizer UI, never a normal front-end `get_theme_mod()` call without an explicit second argument. Fixed by centralising field defaults in `rx_theme_hero_fields()` and routing every template/partial read through a new `rx_theme_get_mod()` wrapper (in `inc/template-tags.php`) instead of raw `get_theme_mod()`. **Worth remembering for every future Customizer field:** always read through a defaults-aware wrapper, never call `get_theme_mod()` bare. |
| 2026-09-21 | Issue | Hero background image is a placeholder sideloaded from the Figma mockup's stock/AI-generated photo (same caveat as the dev/test product photos — not a licensed asset). Set as the default via `set_theme_mod()` so the section is visually testable; the Customizer control's own description also warns to replace it before launch. |
| 2026-09-21 | Issue | Client flagged the first hero pass as not matching the design at all — colours/gradients missing. Root cause: it was built from screenshot recollection, not the actual node data, and got the scheme backwards — dark full-bleed overlay + white text, when the real design (pulled precisely from Figma node 1:2842 via the API) is a **light** hero: white section background, full-bleed photo with a white-to-transparent gradient fading in **left-to-right** (not a dark top-to-bottom vignette), dark ink text throughout. Also missing entirely: the black eyebrow pill badge (lime icon+text), the white "Bundle Offer Callout Box" card (red "TIER 1" + dark text; lime "MAX VALUE" + blue text), and the footnote line under it. Rebuilt hero.php and the hero CSS in style.css from the exact extracted fills/gradient-stops/type, added the two missing Customizer fields (`rx_hero_tier_1_flag`, `rx_hero_tier_note`). Verified against a real headless-Chrome screenshot compared side-by-side with the Figma export, not just by reading the code. **Lesson for every future section:** pull exact node-level fills/gradients/effects for that specific section before writing CSS — a whole-frame colour palette isn't enough to reconstruct one section correctly. |
| 2026-09-21 | Issue | Client reported the hero background photo looks pixelated. Investigated properly (pixel-cropped the in-focus foreground subject at native 2560x1440 resolution and again at a 4x/5120px Figma re-export) — the shoes in sharp focus are genuinely crisp at both; the soft area is the background people, which is intentional shallow depth-of-field in the photo's own composition, not a resolution bug. Also confirmed the theme is serving the full original file, not a smaller WP-generated thumbnail. Separately, the node's name ("Athletic training shoes in high performance gym setting" — an AI-caption style description) and sibling images in the same Figma file carrying raw Gemini-style asset IDs as names are strong evidence this is an **AI-generated placeholder, not a real photograph** — meaning there is no original to find via reverse image search, and (regardless of the pixelation question) it has no clear commercial licence for production use. This was already flagged as launch-blocking; the client's report just confirms it needs replacing with a real licensed photo sooner rather than later, not a CSS/implementation fix. |
| 2026-09-21 | Milestone | Built the Category Cards section (Figma: "Section - 2. CATEGORY CARDS SECTION (Strictly: Men, Women, Unisex)"). Deliberately built the query/logic layer first, while waiting on a Figma API rate limit for this section's exact styling data — the two are genuinely independent. The three cards pull live from the Men/Women/Unisex product categories (name, product count, link) rather than being hardcoded or duplicated into Customizer fields, fixed order per the Figma section's own "Strictly: Men, Women, Unisex" note. Category images use WooCommerce's own native "Thumbnail" field (term meta `thumbnail_id` — same mechanism WC core's `woocommerce_subcategory_thumbnail()` uses) — no custom admin UI needed for that part, it already exists under Products > Categories. Added one new admin field on that same screen, "Shop label" (`rx_shop_label` term meta), because a plain category name can't reliably produce correct possessive grammar for the card copy ("Shop Men's Shoes" vs "Shop Unisex Shoes" — Unisex takes no apostrophe-s) — verified end-to-end via WP-CLI (`rx_theme_category_shop_label()`) and confirmed on the live rendered page. Section eyebrow/heading/description are Customizer fields, same pattern as Hero; the Customizer field-registration loop was factored out into a shared `rx_theme_register_fields()` helper since this is now the second section using it. **Styling is deliberately neutral/layout-only** — colours, badge treatment, and exact image aspect ratio are not guessed; they land once this section's Figma node data is actually pulled. |
| 2026-09-21 | Issue | Caught a nonce bug before it shipped: WordPress uses a fixed `'add-tag'` nonce action for the "Add category" admin form but a per-term `'update-tag_' . $term_id` action for the "Edit category" form — a single save handler checking the same nonce action for both `created_product_cat` and `edited_product_cat` would silently fail on new-category creation. Split into two handlers, one per hook, each with the correct nonce check. |
| 2026-09-21 | Issue | **Figma API is now rate-limited until ~2026-09-26 01:39 UTC (~4.6 days).** Checked the actual `429` response headers (not just the body) — `Retry-After: 396689`, `x-figma-plan-tier: starter`, `x-figma-rate-limit-type: low`. This isn't a short burst throttle, it's the connected Figma account's **Starter-plan API quota**, exhausted by this session's usage (repeated per-section node fetches, image exports, and one full-file fetch). Since the rest of this build needs the same per-section "pull exact node data before styling" treatment for every remaining homepage section plus the other pages (Bundle Builder, Shop, PDP, checkout variants), this will very likely recur unless the Figma account is upgraded off Starter — flagged to the client in TODO.md. Until it clears: work that doesn't need new Figma pulls continues (query logic, WooCommerce admin/data work); anything needing exact colours/layout for a not-yet-pulled section waits or proceeds only with the client's explicit OK on a lower-confidence approach (e.g. colour-sampling the already-downloaded home.png screenshot) — not silently guessed from memory, per the Hero lesson above. |
| 2026-09-21 | Recommendation | Client asked whether buying cheaper Figma "Dev seats" ($12-15/mo) would lift the rate limit. Checked Figma's own rate-limit docs and forum reports before answering (not from memory, given it's a real purchase decision): Dev seats and Full seats have **identical** API rate limits — the limit is set by the **plan tier of the team that owns the file** (Starter/Professional/Organization/Enterprise), not by any individual's seat type. Confirmed by Figma's own forum: a Full seat on a Professional plan still gets Starter-level limits if the file itself lives in a Starter team. Since the client doesn't own/administer this file's team, a seat purchase on their own account likely wouldn't change anything — whoever administers the file's actual team would need to upgrade it, or the file would need to move into a team the client controls and pays for. |
| 2026-09-21 | Milestone | Category Cards fully styled, without waiting out the Figma rate limit — pixel-sampled hex values from a full-resolution crop of the already-cached `home.png` screenshot (downloaded before the limit hit) instead of a fresh API pull. Every colour needed (`#0066FF` blue, `#111111` ink, `#555` grey, `#AAFD30` lime) turned out to already match existing `tokens.css` values — same design system as Hero, confirmed rather than assumed. Corrected a structural assumption in the process: cards are full-bleed photo with a badge (top-left, black for Men/Women, **blue for Unisex** — confirmed by sampling, not a generalisable rule) and a bottom gradient text overlay, not the separate photo-then-body-section layout the first pass (built before this data was available) assumed. Header is a 2-column layout at desktop (heading left, description right-aligned), not stacked. Added a second per-category admin field, "Badge label" (`rx_badge_label`), alongside the existing "Shop label" — the small on-photo kicker text ("Men's Performance", "Unisex Series") isn't derivable from either the category name or the shop label by one consistent rule. Refactored both the new field and the existing one onto a shared `rx_theme_category_text_fields()` definition array (same single-source-of-truth pattern as the Customizer fields) instead of duplicating add/edit/save handlers per field. Verified live via a real screenshot compared against the Figma reference crop. **Noted, not fixed:** the Figma copy itself is inconsistent — "EXPLORE ALL MEN'S", "EXPLORE ALL WOMEN'S", but "EXPLORE UNISEX" (no "ALL"). Built as a uniform "Explore All {label}" pattern for all three rather than replicate the inconsistency — flag if the client wants it to match exactly. |
| 2026-09-21 | Dev/test data | Category card photos for Men/Women/Unisex are cropped directly from the cached `home.png` Figma screenshot (`design-reference/homepage/cat-*.png`, sideloaded via `design-reference/homepage/set_category_data.php`, gitignored/not shipped). Same "not a licensed asset" caveat as the hero photo, plus a visible dev-only artifact: because the source crop is a flattened screenshot, the Figma mockup's own badge text is baked into the image *and* rendered again by the real CSS badge on top — looks like a duplicate label. Cosmetic only, resolves itself once real category photography replaces these crops; not a bug in the badge/overlay CSS itself. |
| 2026-09-21 | Issue | Client caught a real site-wide layout gap: no section had a page-width cap matching Figma's 1280px frame, so every section just stretched to fill the actual browser viewport — meaning every measurement (card widths, spacing) ran bigger than Figma on any screen wider than 1280px. Worse, each section's horizontal padding had been chosen independently and didn't match the others (header stuck at 16px even on desktop, category-cards at 48px, hero at 48px, footer untouched) — even after adding a width cap per-section, content wouldn't have aligned on the same left/right edges the way it does in Figma. Fixed properly: `--rx-container-max: 1280px` and `--rx-container-pad-desktop: 2.5em` (40px — the one value actually measured, from Hero's own inner content container in the original node dump) added to `tokens.css` as shared tokens, applied identically to `.rx-header__row`, `.rx-category-cards`, `.rx-footer__inner` (new wrapper — footer's dark background stays full-bleed while its content gets capped, added in `footer.php`), and `.rx-hero` itself (capped whole-section, matching Figma exactly — the photo genuinely doesn't extend past 1280px in the source design either, so on very wide screens the hero now shows flat background beside it rather than a full-bleed photo). Verified with a fresh screenshot: logo, hero heading, "Discover by Fit & Gender," and the category cards all now start at the same x-position; re-measured card width at 384-385px, matching Figma almost exactly (previously 432px, wrongly inflated by the missing cap). **Applies to every future section too** — use `--rx-container-max` + `--rx-container-pad-desktop` from day one, don't reinvent per-section padding. |
| 2026-09-21 | Decision | New open question, added to TODO.md: should the site be full-width or contained (capped at 1280px, current implementation) on screens wider than the Figma frame? A fixed-width mockup doesn't answer this by itself — flagged for explicit client sign-off rather than assumed, even though the container fix above defaults to contained for now. |
| 2026-09-21 | Milestone | Built the Power Rotation section (Figma: "Build Your 3-Stage Power Rotation") — 3 illustrative pricing tiers plus an example calculator box, the most content-heavy homepage section so far (48 Customizer settings total across all sections now). Colours/copy verified against a clean export the client supplied directly (better quality than the cached `home.png` crop used for Category Cards), cross-checked with pixel-sampling — real per-tier detail confirmed this way: tier 2's number badge is blue while tier 1/3 stay ink, tier 3's number text is lime (not white), and the "30% Tier Active"/"Best Value" tags are low-opacity tints of the existing red/lime tokens, not new colours. Kept as flat Customizer fields (not a repeater/CPT) — same reasoning as Category Cards: exactly 3 fixed tiers, not a variable list. Refactored `rx_theme_customize_partials()` to auto-derive its selective-refresh list from the field-definition functions instead of a hand-maintained ID array — at 48 settings and growing, that would have drifted out of sync fast. **Explicitly did not make the 2-pair/3-pair toggle interactive** — no real pricing/bundle rules engine exists yet (Milestone 4, not started), so it renders Figma's default (3-pair selected) state as static content rather than fake interactivity against fake numbers. Same 30/45 vs 40/55 tier-percentage inconsistency as Hero recurs here (toggle labels say -40%/-55%, tier cards and the computed total both say 30%/45%) — logged, not silently resolved. Verified live via screenshot against the client's reference export. |
| 2026-09-21 | Template override | `wp-content/themes/rx-theme/woocommerce/content-product.php` — the shop-loop product card, based on core WooCommerce template **version 9.4.0**. Done at the client's explicit request ("use a template from WooCommerce and customise it"), which is the exception §11 rule 3 allows. The default loop hooks (`woocommerce_before_shop_loop_item`, `..._title`, etc.) are intentionally **not fired** — their callbacks would duplicate the markup — nothing in this project hooks them. **Re-check on every WooCommerce update:** diff against `plugins/woocommerce/templates/content-product.php` and port relevant changes (WooCommerce > Status flags an outdated `@version`). |
| 2026-09-21 | Milestone | Built the single-product loop card, measured from the client's reference image by pixel-sampling (image area `#F5F5F5` ~288×220, badges stacked and stretched to equal width at 20px tall, body copy `#555`, lime star, red "as low as", 30px rotation row, 42px `#0066FF` button) — all colours map to existing tokens except the `#F5F5F5` surface, now `--rx-color-surface`. Verified on `/shop/` at 1440px and at a true 390px (see testing note below) and PHPCS clean across the theme. Category badge = the product's first non-default category name; colour pill = count of `pa_colour` terms; price = active price (variable products: lowest variation) with the "As low as X in 3-pack" line computed from a discount % (below); "Best for:" line reuses the product **short description** (assumption — say if it should be its own field); grid is 1 col <40em, 2 cols 40–62em, 4 cols desktop (mobile column count is an assumption until Figma's mobile frames are read). **Bundle vs. normal buy:** every product renders the bundle card for now, as agreed; the branch is `rx_theme_product_is_bundle_eligible()` (filter `rx_theme_product_is_bundle_eligible`, default `true`) — when the admin checkbox is built in rx-core it hooks that filter and the template needs no change. Non-bundle products get the same button shape in black with WooCommerce's normal behaviour (AJAX add-to-cart for simple products, "Select options" for variable) — branch verified by forcing the filter false. **Bundle button behaviour:** with no bundle engine yet (Milestone 4) it links to the product page rather than adding straight to the cart, which would be wrong. **Discount %:** `rx_theme_bundle_max_discount_percent()` (filter, default **45**) — 45 because the Figma card's own maths ($199 → $109.45) is exactly 45% off, but the tier numbers are still the unresolved 30/45 vs 40/55 question, and this is a pricing rule that belongs in the rx-core pricing engine via that filter, not the theme. **"Adds into Rotation: Pair 3 (+45% tier)" row is static placeholder text** — which pair slot a product fills depends on what's already in the customer's rotation, which needs the bundle builder; flagged in the template. **New admin field: "Type label"** (`_rx_type_label`, General tab of the product data box — the "NIKE PERFORMANCE" kicker), saved through the CRUD object via `woocommerce_admin_process_product_object`; kept in the theme because it's presentation copy (same call as the category "Shop label"/"Badge label"), whereas the bundle-eligible checkbox is a business rule and goes in rx-core. Field render, sanitising (tags stripped, trimmed) and persistence verified via WP-CLI. Dev/test products got type labels and their short descriptions' `·` changed to `•` to match the design. |
| 2026-09-21 | Issue | **Star ratings on the card are invented.** Reviews are out of scope (§15 Q8), so the "★ 4.8 (142)" line uses `rx_theme_product_placeholder_rating()` — a stable pseudo-random value derived from the product ID (doesn't change between loads), purely so the design can be built and judged. Showing fabricated ratings on a live store is misleading (and a consumer-law risk in Australia) — must be replaced with real data or removed before launch; added to §14 and TODO.md. |
| 2026-09-21 | Decision | **Product card follow-up — supersedes parts of the "Milestone: single-product loop card" entry above** (that entry describes the first pass; read this for current behaviour). (1) **One button per card, always WooCommerce's standard add-to-cart.** Bundle-eligible → blue "Add to bundle"; not eligible → black "Add to basket". The client's own resolution: "Add to bundle" is just add-to-basket under another name, and the discount is calculated later from what's in the cart (2–3 items), *not* from which button was pressed — so there is deliberately nothing bundle-specific in the link. (An intermediate version showed both buttons on eligible products; they did the identical thing, which is what made the client drop the second.) Variable products (all current ones — sizes) still go to the product page, standard WooCommerce behaviour, because a size must be chosen first. This replaces the earlier "Add to bundle links to the product page" note. (2) **"Eligible for bundle" is now a real checkbox** on the product General tab, owned by rx-core (`RX\Core\Bundles\BundleEligibility`, meta `_rx_bundle_eligible` = yes/no, saved via the CRUD object). It answers the theme's `rx_theme_product_is_bundle_eligible` filter, whose default is now **false** (was true) — anything not ticked, or with rx-core inactive, gets the plain button. `Plugin::boot()` now builds its services from `default_services()`. (3) **"Best for" is a per-product textarea** (`_rx_best_for`, General tab), no longer the short description. Built as a textarea, not a WordPress taxonomy, because a taxonomy is a set of reusable terms and can't be a textarea; the trade-off is that shoppers can't filter the shop by "best for" — that would need a real taxonomy. One item per line is joined with " • ". (4) **Discount % is now a Customizer setting** (Appearance > Customize > Bundle Discount), default 45, clamped 0–100. Registered with `'type' => 'option'`, so it lives in `wp_options` as `rx_bundle_max_discount_percent`, **not** a theme_mod — a theme_mod belongs to one theme and would silently reset a pricing number on a theme switch, and rx-core can read the option later without depending on the theme. The homepage tier text (Hero/Power Rotation) is separate editable copy and does **not** follow this setting, so the two can drift — see TODO.md #3. (5) **Tooling:** added `php-stubs/woocommerce-stubs` (dev) and pointed `phpstan.neon.dist` at it — PHPStan level 5 had only passed before because no WooCommerce code existed; the fix is the stubs, not a lower level. Verified: ticked → 1 button (bundle), unticked and never-set → 1 button (basket) with badge/as-low-as/rotation hidden; discount option 50 → card shows $114.50 and +50%; sanitiser clamps 150→100, −5→0; both admin fields render and save (checkbox absent from POST → `no`); PHPCS clean on theme and core, PHPStan clean. **Dev data:** all four test products got `_rx_best_for` and were ticked bundle-eligible; the **R.A.D ONE V2 is left unticked on purpose** as a visible demo of the non-eligible card. |
| 2026-09-21 | Testing note | **Headless Chrome cannot render narrower than 500px** (`--window-size=390` still yields `innerWidth=500`, so the screenshot is a 500px layout cropped to 390 — it will look like right-edge overflow when there is none). To test phone widths, load the page inside a fixed-width `<iframe>` in a wrapper HTML file and screenshot that. Found because a "cut-off" mobile shot was investigated rather than trusted. |
| 2026-09-21 | Note | A real menu named "main" (Men / Women / Unisex) has been created in wp-admin and assigned to the Primary location, so the header now shows that instead of the theme's 5-item fallback (Men/Women/Unisex/Brands/Sale). Not a bug — just why the header nav differs from earlier screenshots. |
| 2026-09-21 | Issue | **The product-card admin fields were invisible on every real product — client caught it; my earlier "verified" claim was wrong.** The "Type label", "Best for" and "Eligible for bundle" fields were added to the product data box's **General** tab, but WooCommerce **hides the General tab for variable products** (its content is all simple-product pricing) — and every shoe here is variable. So they only appeared on simple products, which don't exist in this catalogue. The earlier check ("field renders") called the hook from WP-CLI, which proves the markup is produced and says nothing about whether a browser shows it. **Fix:** a dedicated **"Shop card"** tab in the product data box (`RX\Core\Admin\ProductCardTab`, no `show_if`/`hide_if` classes, shown for every type, priority 65 so it doesn't become the default tab), with all three fields inside it. Fields plug in via the `rx_core_product_card_fields` action. **Ownership consolidated in rx-core:** the two copy fields moved out of the theme into `RX\Core\Admin\ProductCardCopyFields` (theme's `inc/product-fields.php` deleted) so one plugin owns the tab; the theme just reads the `_rx_type_label` / `_rx_best_for` / `_rx_bundle_eligible` meta keys. This reverses the earlier "Type label lives in the theme as presentation copy" call — the theme-owned field UI could never share a tab with a core-owned one without a fragile dependency. Trade-off accepted: with rx-core inactive those fields have no admin UI (the saved data and the card still work). **Verified properly this time, in a real browser as an administrator:** on a variable product the tab is present, the three fields are hidden until it's opened and displayed after; and a genuine click-**Update** round trip persisted (untick + edited "best for" saved and reloaded, then restored through the UI). PHPCS + PHPStan clean. |
| 2026-09-21 | Testing note | **How to test wp-admin screens for real** (WP-CLI can't tell you whether something is *visible*): `npm i puppeteer-core` in a scratch folder outside the repo, mint a short-lived admin session with `WP_Session_Tokens::get_instance($id)->create()` + `wp_generate_auth_cookie(..., 'auth' / 'logged_in', $token)` (cookie names `AUTH_COOKIE` / `LOGGED_IN_COOKIE`), drive the installed Chrome, and read `offsetParent !== null` for "is it actually displayed". **Never call `destroy_all()` before creating that session** — it logs the real user out of their own browser; destroy only your own token afterwards. (I did call `destroy_all()` once while doing this and logged the user out of wp-admin; they need to log in again.) Also: native Windows PHP needs `C:/...` paths, not `/c/...`, in `file_get_contents` etc. |

---

## 14. QA Checklist (pre-launch)

- [ ] **Remove placeholder star ratings** from the product card (`rx_theme_product_placeholder_rating()` in `inc/woocommerce.php`) — replace with real data or delete the star line. Invented ratings must not go live.
- [ ] **Remove dev/test products** (R.A.D ONE V2, TYR L-1 Lifter, Inov-8 F-Fly, Strike Mvmnt Haze — created 2026-09-21, see §13):
  `wp post delete $(wp post list --post_type=product --product_tag=dev-test-data --field=ID) --force`
- [ ] Every product/variation: price, stock, images, add-to-cart
- [ ] Bundles: all configurations, discounts, stock deduction, order meta, refunds
- [ ] Promo rules: stacking, coupons, edge cases (qty 0, out-of-stock component, expired promo)
- [ ] Checkout: guest / logged in, all shipping zones, tax, all gateways (success, failure, 3DS, webhooks)
- [ ] Emails: all transactional emails render correctly in major clients
- [ ] Integrations: email platform events, GA4 / Meta purchase events (no duplicates)
- [ ] Responsive: desktop / tablet / mobile vs Figma
- [ ] Core Web Vitals on key templates (home, archive, PDP, cart, checkout)
- [ ] Security: user roles, 2FA, backups tested (restore!)
- [ ] 301 redirects (if migrating), sitemap, robots, schema

---

## 15. Open Questions for Client

1. ~~Target countries, currency, tax setup?~~ **Answered:** Australia only, AUD, single language. Tax (GST) treatment still needs explicit confirmation — see §1.
2. ~~Bundle behaviour: fixed or build-your-own? Mixed variants allowed?~~ **Effectively answered:** build-your-own bundle builder confirmed real (client gave direct UX/mobile direction on it) — see §6.1.
3. ~~Discount stacking: can bundle discounts combine with coupons/promos?~~ **Answered:** yes in principle, but explicitly low priority / not MVP. See §6.2.
4. ~~Which payment methods are required at launch?~~ **Answered:** PayID + PayTo (via AzuPay), Card (via Stripe, recommended). No PayPal/Afterpay/Zip at launch. See §8.
5. ~~Which email platform and which events/flows?~~ **Answered:** Omnisend, including an unpaid-order nudge workflow (email + SMS) triggered off WooCommerce order-status changes. See §9.
6. Fulfilment: in-house or 3PL/ERP integration? — **still open, only unanswered question left in this list.**
7. ~~Hosting: client-provided or our recommendation?~~ **Answered:** client's own staging environment for now, full access available; production/DNS to be set up later if/when needed. Not blocking current work.
8. ~~Subscriptions, wishlists, reviews, loyalty — in scope?~~ **Answered: not in scope.**
9. ~~Migration from an existing store (products, customers, orders, URLs)?~~ **Answered: N/A — no existing site.**
10. ~~Post-launch support: hours/month and response times?~~ **Answered: deferred** — client doesn't want to plan this now.
