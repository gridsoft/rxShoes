# Project Plan & System Prompt: Custom WooCommerce Build

> Keep this file at the repo root (or as `CLAUDE.md`). Update **Section 12 (Current Phase)** and **Section 13 (Dev Log)** before every new session.

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
* **Immediate Next Steps:**
  1. [ ] Remaining homepage sections, in Figma order: Category Cards, Power Rotation tiers, Best Sellers grid, Educational comparison, Shop by Brand, Community Rotations
  2. [ ] Fulfilment answer (Section 15 Q6) — only open client question left
  3. [ ] Real product catalog once the client's Excel file arrives (§5 import pipeline)

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

---

## 14. QA Checklist (pre-launch)

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
