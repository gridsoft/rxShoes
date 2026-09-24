# Outstanding Items — RX Shoe

A running list of things we need a decision, information, or access from you on. Each item explains *why* it matters, not just what to do — feel free to forward this as-is.

Updated: 2026-09-24

---

## Decisions we need from you

### 1. Hero background photo (and homepage photos generally)
The photo currently on the homepage is a placeholder pulled from the Figma file. We looked into it and it's very likely **AI-generated, not a real photograph** — the layer's name is an AI-style auto-caption ("Athletic training shoes in high performance gym setting"), and other images in the same Figma file carry raw Google-Gemini-style asset IDs as their names. Two consequences: (1) there's no original real photo behind it, so we can't search for "the same one" anywhere, and (2) even if it looked perfect, it likely has no clear commercial licence for a live store. We need a real, licensed photo before launch — either your own brand photography, or a stock photo you license from somewhere like Unsplash or Pexels as a placeholder in the meantime.

### 2. Card payment gateway: Stripe or Hello Clever?
You mentioned you're open to either. Our recommendation is **Stripe**: Hello Clever's own integration documentation doesn't confirm it supports the checkout style we're building (classic WooCommerce checkout, needed for the PayID/PayTo discount logic), and doesn't document how order status or webhooks work. Stripe's WooCommerce integration is the most mature and well-documented option available, and since PayID/PayTo already require custom integration work regardless, there's no downside to also using the safer, better-documented option for card payments. We'd like your explicit go-ahead before building against either.

### 3. Bundle discount percentages — please confirm the real numbers
The Figma file has two different sets of numbers for the same offer: the top banner and homepage hero say **2 pairs = 40% off, 3 pairs = 55% off**, but the bundle-builder page and the actual checkout math both say **2 pairs = 30% off, 3 pairs = 45% off**. You've told us the 2-pair discount should be **35%** — which matches neither set — so we've set the defaults to **35% for 2 pairs and 45% for 3 pairs** (45% is the one the Figma price maths actually works out to). Please confirm both numbers.

The good news: there is now just one place to change them — dashboard → Appearance → Customize → **Bundle Discount** (a "2-pack" box and a "3-pack" box). The homepage hero, the Power Rotation section and its calculator, and the shop product cards all read from those two boxes, so changing a number there updates every mention on the site at once. One thing to know: the shop cards only show the 3-pack price ("As low as … in 3-pack") for now — the 2-pack number is used on the homepage and will drive the cart discount once the bundle builder is built.

### 4. Is the "first 100 customers" PayID offer real?
The checkout design shows a line saying the PayID discount is "limited to the first 100 customers (87/100 remaining)" with a live countdown. We're not sure if that's an actual feature you want (which needs real tracking — a counter of how many people have used the discount, and what happens at 100) or just placeholder text to make the mockup look more urgent. If it's real, it's a meaningful extra piece of work; if not, we'll drop it.

### 5. GST / tax setup
We're assuming standard Australian GST at 10%, included in the displayed prices (this matches the dollar figures shown in the Figma bundle mockup). Please confirm this is correct before we wire up WooCommerce's tax settings for real.

### 6. Fulfilment: in-house or third-party (3PL)?
This is the last unanswered question from the original project brief. It affects how we build the order workflow (e.g. whether orders need to notify a 3PL warehouse automatically) and is worth settling before we get to that part of the build.

### 7. Should the site be full-width or contained (capped at a fixed width, like it is now)?
Figma's file is a fixed-width mockup (1280px), which doesn't by itself tell us what should happen on screens wider than that — a big desktop monitor or ultrawide display. Two options: **contained** (what we've built so far) keeps everything at a fixed max width and centers it, so on a wide screen you see the design plus plain background on either side, same as most e-commerce sites; **full-width** stretches sections (and their photos) to fill the entire screen on anything wider than the design, which can look great with big photography but changes proportions Figma didn't specify and needs a decision on how each section should stretch. This affects the whole site, not one section, so worth settling now before more pages are built on top of whichever choice we make.

### 8. Star ratings on product cards — real reviews, or drop them?
The product card design has a star rating line ("★ 4.8 (142)"). You told us reviews are out of scope, so there's no real rating data behind it — for now we're showing made-up numbers (stable per product, so they don't jump around) purely so the card can be built and judged. **These can't go live**: displaying invented ratings on a real store is misleading to customers and carries consumer-law risk in Australia. Two options: drop the star line from the cards, or bring reviews into scope so the numbers are real (a real feature, with moderation and so on). Either is fine; we just need to know which before launch.

### 9. Imported product descriptions contain another store's text and images
While building the product-page feature tiles we noticed some imported product descriptions were copied from **The WOD Life (TWL)**'s website, and still carry their store-specific text — for example "This item is sent by a marketplace partner", "This item is excluded from free gift and **TWL App** offers", "will arrive separately from the rest of your order", "1-3 business days to despatch from the warehouse", "cannot be sent to customers outside of Australia". The size-guide images in some descriptions also carry the **TWL logo**. None of that is true for your store (your shipping, offers and dispatch are your own), and showing another retailer's branding on your product pages isn't something to launch with. Two ways to fix it: send your own descriptions and size guides with the product catalog (item 11), or we strip those lines and the TWL images from the current descriptions and you supply replacement size guides. Either way, we need to know which before launch.

---

## Access / info we need from you

### 10. AzuPay account setup
For PayID this is straightforward, but **PayTo specifically requires "Checkout App V3" to be enabled** on your AzuPay account (per their own documentation) — it won't work otherwise. When you're setting up the AzuPay account, please make sure that's turned on, and send us the API sandbox credentials once you have them so we can start building and testing the payment integration.

### 11. Product catalog (Excel file)
Still waiting on this — product names, descriptions, sizes, colours, and 3-5 images per product. Reminder on the image side: if you can put all the images in one shared folder (Google Drive/Dropbox) with predictable filenames (e.g. `SKU-1.jpg`, `SKU-2.jpg`), we can bulk-import everything in one pass rather than uploading each photo by hand — much faster and repeatable if we ever need to rebuild the catalog on staging.

### 12. Figma file access keeps running out — needs whoever owns the file's team
To build each page accurately we pull exact colours/spacing/text straight from your Figma file via its API, rather than guessing off a screenshot (guessing caused a real rework earlier — see the hero section history). That access runs out fast and takes 4-5 days to reset each time — we've hit it twice already. We checked: this **isn't fixed by buying yourself a cheaper Figma seat** (we looked into the $12-15/mo Dev seat option specifically — it has the same limit as a full seat). What actually controls it is the *plan* of whichever Figma team the file itself lives in, and you mentioned you don't know who owns that. So the actual next step is finding out who does — likely whoever built the mockup — and either asking them to upgrade that team's plan, or having the file duplicated/moved into a Figma team you control and pay for. Until that's sorted, we can keep working around it using cached screenshots (slower and slightly less precise, but workable), which is what we did for the section built most recently.

---

## Good to know — not urgent, nothing to action yet

- **Production hosting & domain** — you said to use your own staging for now with DNS/production set up later if needed. No action needed until you're ready for that.
- **Post-launch support terms** — you said this isn't something to plan yet. Just flagging it's still an open item for whenever it's useful to revisit.
- **Duplicate Figma frames** — a few frames in the Figma file appear twice with the same name (two "Premium VSL Checkout Experience," two "PayID Exclusive Checkout," two "Mobile VSL Checkout"). Minor, but if one is a leftover/duplicate rather than an intentional A/B variant, it'd be worth tidying up on your end at some point.
- **New "Key features" tab on each product** — the three icon tiles under the product photos (e.g. "Olympic stable — Solid TPU heel wedge") are filled in per product in the admin: Products → edit a product → Product data → **Key features**. Up to three tiles, each a short title, one line and an icon. The tab lists suggestions pulled from that product's description with a "Use" button, but they need a human edit (the descriptions weren't written in that shape). Products without any tiles simply don't show the row. One product (TYR RD-1X Runner) is filled in as an example — change or clear it as you like.
