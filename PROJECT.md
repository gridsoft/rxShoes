# Project Plan & System Prompt: Custom WooCommerce Build

> Keep this file at the repo root (or as `CLAUDE.md`). Update **Section 12 (Current Phase)** and **Section 13 (Dev Log)** before every new session.

---

## 1. Project Overview

* **Project Name:** [Insert Project Name]
* **Client:** [Client Name] — via Upwork
* **Target Market:** [e.g., UK/EU DTC consumers] — currency: [GBP/EUR/USD], tax: [VAT inclusive/exclusive]
* **Core Goal:** A fast, scalable, maintainable WooCommerce store built from scratch from supplied Figma designs. Custom functionality over plugin stacking.
* **Catalog Size:** ~50 core products, 250+ variations
* **Design Source:** Figma — [link]. Pixel-accurate implementation across desktop / tablet / mobile.
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
* Design tokens from Figma (colours, typography, spacing) → CSS custom properties / `theme.json`.

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
* **SKU convention:** [e.g., `PRODUCT-COLOUR-SIZE`]
* **Images:** Per-variation images; WebP/AVIF; defined image sizes matching Figma.

---

## 6. Bundles, Pricing & Discounts

### 6.1 Bundles
* Custom product type: `WC_Product_Prj_Bundle extends WC_Product` (type `prj_bundle`).
* Bundle defines: component products/variations, fixed or customer-selectable options, quantities.
* **Cart representation:** [Parent line + child lines linked by cart item key] OR [single line with component meta] — decide early, affects stock, shipping, refunds, and analytics.
* **Stock:** Components reduce their own stock; bundle availability derived from components.
* **Order data:** Component details stored as order item meta for fulfilment/export.

### 6.2 Pricing Rules Engine
* Rule types: bundle discount (% / fixed), quantity tiers, BOGO, cart-level promos, date-limited promotions, [customer-group pricing?]
* Applied via `woocommerce_before_calculate_totals` (line prices) or cart fees/coupons — **one consistent strategy**, documented here: [decision].
* Rule precedence and stacking with coupons must be defined: [stacking rules].
* Price display: show original vs discounted price consistently on PDP, cart, mini-cart, checkout, emails.
* Admin UI for managing rules without code.

---

## 7. Cart, Add-to-Cart & Checkout

* **Add-to-cart:** AJAX add-to-cart on PDP and archives, side/mini-cart per Figma, cart fragments optimised (or replaced by Store API calls).
* **Custom purchasing flows:** [describe — e.g., build-your-own bundle, upsell step, quick-buy]
* **Cart/Checkout implementation:** [Classic shortcode checkout with hooks] / [Cart & Checkout Blocks with Store API extensions] — decision: [ ] and reason: [ ].
  * Classic = full hook control, easier custom flows.
  * Blocks = future-proof, but custom logic needs `ExtendSchema` / Store API / JS filters.
* **Checkout flow:** [single-page / multi-step], guest checkout [yes/no], custom fields: [list].
* Validation server-side always; client-side only as UX.

---

## 8. Payments, Shipping & Orders

* **Gateways:** [Stripe (cards, Apple Pay, Google Pay), PayPal, Klarna/Clearpay?]
* Test mode on staging; webhooks configured per environment.
* **Shipping:** Zones [list], methods [flat / free over X / carrier rates via ___], bundle shipping rules.
* **Order workflow:** Custom statuses if needed [e.g., `wc-awaiting-fulfilment`], fulfilment/3PL integration: [yes/no — which].
* **Refunds:** Bundle and discounted-item refunds must calculate correctly.

---

## 9. Accounts, Emails & Integrations

* **Customer accounts:** Registration, order history, addresses, [reorder, wishlist, subscriptions?] — styled per Figma.
* **Transactional emails:** Custom-styled WC email templates; custom `WC_Email` classes for new workflows.
* **Email platform:** [Klaviyo / other] — customer sync, placed order, abandoned cart, product viewed events. Consent respected.
* **Analytics:** GA4 enhanced ecommerce (`view_item`, `add_to_cart`, `begin_checkout`, `purchase` with bundle/variant data), Meta Pixel + CAPI with event dedup, [TikTok / Google Ads conversions].
* **Consent:** Cookie banner + Consent Mode v2 (GDPR).
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
* **Immediate Next Steps (blocked until Section 15 is answered):**
  1. [ ] Get Figma link + brand/tokens, fill in Section 1 (project name, client, market/currency/tax) and Section 3 hosting/staging
  2. [ ] Get answers to Section 15 Q1–Q10 (market, bundle model, discount stacking, payment methods, email platform, fulfilment, hosting, subscriptions/wishlist/reviews scope, migration, support terms)
  3. [ ] Once checkout architecture (classic vs. Blocks, §7) is decided, scaffold `Checkout/` and `Cart/` services accordingly

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

---

## 14. QA Checklist (pre-launch)

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

1. Target countries, currency, tax setup?
2. Bundle behaviour: fixed or build-your-own? Mixed variants allowed?
3. Discount stacking: can bundle discounts combine with coupons/promos?
4. Which payment methods are required at launch?
5. Which email platform and which events/flows?
6. Fulfilment: in-house or 3PL/ERP integration?
7. Hosting: client-provided or our recommendation?
8. Subscriptions, wishlists, reviews, loyalty — in scope?
9. Migration from an existing store (products, customers, orders, URLs)?
10. Post-launch support: hours/month and response times?
