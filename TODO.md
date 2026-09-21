# Outstanding Items — RX Shoe

A running list of things we need a decision, information, or access from you on. Each item explains *why* it matters, not just what to do — feel free to forward this as-is.

Updated: 2026-09-21

---

## Decisions we need from you

### 1. Hero background photo (and homepage photos generally)
The photo currently on the homepage is a placeholder pulled from the Figma file. We looked into it and it's very likely **AI-generated, not a real photograph** — the layer's name is an AI-style auto-caption ("Athletic training shoes in high performance gym setting"), and other images in the same Figma file carry raw Google-Gemini-style asset IDs as their names. Two consequences: (1) there's no original real photo behind it, so we can't search for "the same one" anywhere, and (2) even if it looked perfect, it likely has no clear commercial licence for a live store. We need a real, licensed photo before launch — either your own brand photography, or a stock photo you license from somewhere like Unsplash or Pexels as a placeholder in the meantime.

### 2. Card payment gateway: Stripe or Hello Clever?
You mentioned you're open to either. Our recommendation is **Stripe**: Hello Clever's own integration documentation doesn't confirm it supports the checkout style we're building (classic WooCommerce checkout, needed for the PayID/PayTo discount logic), and doesn't document how order status or webhooks work. Stripe's WooCommerce integration is the most mature and well-documented option available, and since PayID/PayTo already require custom integration work regardless, there's no downside to also using the safer, better-documented option for card payments. We'd like your explicit go-ahead before building against either.

### 3. Bundle discount percentages — please confirm the real numbers
The Figma file has two different sets of numbers for the same offer: the top banner and homepage hero say **2 pairs = 40% off, 3 pairs = 55% off**, but the bundle-builder page and the actual checkout math both say **2 pairs = 30% off, 3 pairs = 45% off**. We've built this as editable text (so whichever is right can be corrected from the dashboard, no developer needed) — but we need to know which set is actually correct before it goes live.

### 4. Is the "first 100 customers" PayID offer real?
The checkout design shows a line saying the PayID discount is "limited to the first 100 customers (87/100 remaining)" with a live countdown. We're not sure if that's an actual feature you want (which needs real tracking — a counter of how many people have used the discount, and what happens at 100) or just placeholder text to make the mockup look more urgent. If it's real, it's a meaningful extra piece of work; if not, we'll drop it.

### 5. GST / tax setup
We're assuming standard Australian GST at 10%, included in the displayed prices (this matches the dollar figures shown in the Figma bundle mockup). Please confirm this is correct before we wire up WooCommerce's tax settings for real.

### 6. Fulfilment: in-house or third-party (3PL)?
This is the last unanswered question from the original project brief. It affects how we build the order workflow (e.g. whether orders need to notify a 3PL warehouse automatically) and is worth settling before we get to that part of the build.

---

## Access / info we need from you

### 7. AzuPay account setup
For PayID this is straightforward, but **PayTo specifically requires "Checkout App V3" to be enabled** on your AzuPay account (per their own documentation) — it won't work otherwise. When you're setting up the AzuPay account, please make sure that's turned on, and send us the API sandbox credentials once you have them so we can start building and testing the payment integration.

### 8. Product catalog (Excel file)
Still waiting on this — product names, descriptions, sizes, colours, and 3-5 images per product. Reminder on the image side: if you can put all the images in one shared folder (Google Drive/Dropbox) with predictable filenames (e.g. `SKU-1.jpg`, `SKU-2.jpg`), we can bulk-import everything in one pass rather than uploading each photo by hand — much faster and repeatable if we ever need to rebuild the catalog on staging.

### 9. Figma account is on the Starter plan — API access keeps running out
To build each page accurately we pull exact colours/spacing/text straight from your Figma file via its API, rather than guessing off a screenshot (guessing caused a real rework earlier — see the hero section history). That API access is tied to your Figma account's plan, and it's currently on **Starter**, which has a low request allowance. We've already hit it once this week, and it doesn't reset for about 4-5 days each time. Since we'll be pulling data like this for every remaining page (not just the homepage — checkout, shop, product pages, the bundle builder), this will likely keep happening and slow the build down unless the Figma account is upgraded to a plan with a higher API limit. Worth doing sooner rather than later if you want steady progress.

---

## Good to know — not urgent, nothing to action yet

- **Production hosting & domain** — you said to use your own staging for now with DNS/production set up later if needed. No action needed until you're ready for that.
- **Post-launch support terms** — you said this isn't something to plan yet. Just flagging it's still an open item for whenever it's useful to revisit.
- **Duplicate Figma frames** — a few frames in the Figma file appear twice with the same name (two "Premium VSL Checkout Experience," two "PayID Exclusive Checkout," two "Mobile VSL Checkout"). Minor, but if one is a leftover/duplicate rather than an intentional A/B variant, it'd be worth tidying up on your end at some point.
