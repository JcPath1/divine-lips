# Glimmer Bliss — Where We Left Off

**Last worked on: 27 October 2026**
This file is in the repo root, NOT in `docs/`, so it is not published on the website.

---

## Where things are right now

The shop is **live on the internet** at:

**https://jcpath1.github.io/divine-lips/**

It has been live for a while — GitHub Pages was already switched on before this
session, and nobody had noticed. It works today; the only thing missing is your
own domain name in front of it.

The website files live in the **`docs/`** folder. That folder, and only that
folder, is what gets published. Everything else in the repo (the business
spreadsheet, project notes, raw photos) is deliberately **not** served on the
website — this was fixed on 29 July, because the business spreadsheet had been
publicly downloadable.

---

## 🛒 THE REAL SHOP IS BUILT (27 October 2026) — read this first

The shop is moving from GitHub Pages to **Spaceship hosting** (PHP + database +
email, confirmed on Spaceship's hosting page). Built and tested on this computer.
**Not uploaded yet**, because the secrets file is still empty.

**How an order works**
1. Customer: Cart → Checkout → gets an order code like **GB-7K3PQ**.
2. They pay with **Venmo** or **Zelle** and type the code in the payment note.
   (Or **PayPal / card**, which marks the order paid by itself once PayPal keys are in.)
3. They tap "I sent the money". You get an email.
4. You check Venmo/Zelle, then open **glimmerbliss.com/admin/** → **Mark Paid**.
   The customer gets a "payment received" email. Then **Mark Shipped** (with tracking)
   or **Mark Picked Up**, and they get another email.

**Accounts:** no passwords. The customer types their email on **My Orders** and gets
a one-time login link (20 minutes, max 3 per 15 min). The account is made
automatically with their first order. Checkout fills in their last address.

**Promotions:** Admin → Promotions. Free shipping or % off, a minimum order,
an optional code, and optional start/end dates. Each order gets the ONE deal that
saves the most. The deal name and savings are saved on the order and printed on
the invoice email. Starts with "Free shipping on orders over $30" switched on.
The pink bar at the top of the site shows whichever automatic deals are on.

**Where things are**
| | |
|---|---|
| Secrets (passwords, keys) | `private/glimmerbliss-secrets.env` — git-ignored. Claude never prints it. |
| Prices (single source) | `server/app/catalog.php` |
| Server code | `server/app/` → uploads to `glimmerbliss_app/` (outside public_html) |
| API + Admin | `server/public/api/`, `server/public/admin/` |
| Shop pages | `docs/cart.html`, `checkout.html`, `order.html`, `account.html`, `pay-sign.html`, `docs/js/shop.js` |
| Tools | `python tools/glimmer.py check / ftp-test / local / deploy` |

**Tested 27 Oct 2026:** 69 end-to-end checks pass on a local copy (pricing, fake prices
ignored, promos, order emails, I-paid, admin login + lockout, form protection,
mark paid/shipped, login links single-use + rate limited, strangers can't see
orders). 19 Help-assistant questions pass. Phone layout checked at 390px.

**NOT tested yet (need real accounts):**
- Real email sending through the Spaceship mailbox (tested with a fake mailbox only).
- MySQL on the server (tested with SQLite here; the tables are made on first visit).
- **The Venmo "Open Venmo" link with the amount filled in.** Its format
  (`venmo.com/?txn=pay&recipients=…&amount=…&note=…`) is not from Venmo's docs.
  Test it on a phone with the real account. If it doesn't fill in, customers can still
  type it, and the steps on the page tell them what to type.
- PayPal: needs Sandbox keys to test. Card payments without a PayPal account need
  "PayPal Account Optional" turned ON in PayPal settings (PayPal help article).

## Server check (27 Oct 2026, done by Claude)

- Hosting is bought. The shop's web folder is **`glimmerbliss.com`** in the home folder
  (NOT `public_html`). The secrets file now says so (`FTP_WEB_ROOT`).
- PHP 8.2 with everything the shop needs is switched on.
- ✅ The database is made (`(database name: see secrets file)`). Its details are in the secrets file.
- ✅ FTP login `(FTP login: see secrets file)` on `(FTP server: see secrets file)` works (secure),
  and it starts in the home folder, so it can reach `glimmerbliss.com` and `glimmerbliss_app`.
- ✅ Email: sends from `orders@glimmerbliss.com` via Spacemail (`mail.spacemail.com`, port 465 SSL).
  Mailbox login tested OK 27 Oct (nothing sent). Order alerts go to the owner's Gmail.
- ✅ Admin password set. Payment: **Venmo `glimmerbliss`** (a GlimmerBliss Venmo account).
  Zelle skipped: it would show the owner's personal name and phone. PayPal comes later
  (its button already offers Venmo and cards, and marks orders paid automatically).
- ✅ **DEPLOYED 27 Oct 2026.** All 69 files uploaded. glimmerbliss.com ALREADY pointed at the
  hosting (A 66.29.148.164, Spaceship nameservers, Spacemail MX). It's **LIVE at
  https://glimmerbliss.com** with a valid SSL padlock (until 25 Jan 2027, auto-renews).
  Checked: catalog API 200 (MySQL tables made), admin 200, `_boot.php` and `.htaccess`
  blocked (403), http and www both redirect to https://glimmerbliss.com/.
- To upload changes later: `python tools/glimmer.py deploy`. Only changed files are sent.
  Claude's safety check blocks Claude from running it, so type `! python tools/glimmer.py deploy`.
- NOT committed to git yet: pushing master would redeploy the OLD GitHub Pages copy, where
  the cart can't work. Turn off GitHub Pages first, then commit.
- The `orders@glimmerbliss.com` mailbox is Spacemail, not cPanel email, so its
  send settings (SMTP) still need to be looked up.

## Shipping is now $7 flat (27 Oct 2026)

Set in `tools/glimmer.py` (`shipping_cents: 700`, goes into config.php on deploy) and in all page text.
Still free on $35 or more.

## Phone menu (27 Oct 2026)

On phones the menu used to be hidden with no replacement (no way to Home/FAQ/My Orders
except the logo). Now it shows as a small row under the logo on every page (`styles.css`,
`@media (max-width: 768px)`). Checked at 390px.

## 🛍️ Charlie's back end (27 Oct 2026, late) — in Admin (glimmerbliss.com/admin/)

Made for an 11-year-old on a phone: big buttons, few words.
- **🛍️ Sell** — booth screen. Tap a gloss picture once per bottle, tap 💵 Cash or 📱 Venmo.
  Prices INCLUDE tax ($12 is $12); the tax part is worked out and saved. "Oops" takes a sale back.
  💳 Card is OFF until there's a Square / Tap to Pay account (add `'card' => '💳 Card'` back in
  `SHOW_PAY_METHODS`, `server/app/stock.php`).
- **📦 My Gloss** — count of each gloss (Glimmer Kiss with/without glitter counted apart).
  "How many? → Add" after making a batch; − / + to fix. Booth sales and PAID website orders come
  off by themselves (cancelled paid orders go back). ⚠️ at 3 or less. − never goes below 0.
  The website keeps selling at 0 (made to order).
- **🧾 Spent** — what was bought, how much, when.
- **💰 Money** — this month and all time: money in, tax to save for Texas, spent, kept.
- New tables `stock_moves`, `show_sales`, `expenses` are made by themselves on the first visit.
- Tests: `tools/stocktest.py`, `tools/taxtest.py` (run against the local test shop at
  localhost:8080) and `node tools/helptest.js docs/js/main.js`. All pass 27 Oct.
- ⚠️ Not checked: whether Texas allows tax-included prices at a booth, and which local rate
  applies at a show in another city (e.g. Canton). Ask the Comptroller.

## Changes on 27 Oct 2026 (evening) — tax, $35 shipping, all sales final

- **Texas sales tax 8.25%** on Texas orders only (local pickup, or shipping to TX).
  Charged on glosses + shipping after discounts (Texas Comptroller: delivery charges on
  taxable items are taxable). No Texas tax on orders shipped out of state. The rate is
  `TAX_RATE_PERCENT` in the secrets file (default 8.25). **Check your exact rate** with the
  Comptroller's Sales Tax Rate Locator for your address, and get a free **Texas sales tax
  permit** before collecting tax. Tax shows at checkout, on the order page, in emails and in Admin.
- **Free shipping on $35 or more** (before tax). The live database deal updates itself from
  $30 to $35 on the next deploy (only if it's still the untouched original).
- **All sales final:** Returns page rewritten (no returns, refunds or exchanges). Broken or
  wrong items get a replacement (photo within 7 days, no mail-back). FAQ, help bot and
  checkout updated. Help bot also no longer offers Zelle.
- Tested locally: 23 price/tax/promo/order checks + 16 help-bot questions pass, and the old
  database upgraded itself (tax columns added, $30 deal became $35).

## STEP 1 — Fill in the secrets file  ✅ DONE 27 Oct

Open `private/glimmerbliss-secrets.env` in Notepad. Every section says where to
find the value in cPanel. Save it, then tell Claude "secrets file is saved".
**Never paste it into the chat.** Claude runs `python tools/glimmer.py check`
(shows only OK/MISSING), then `ftp-test`, then `deploy`.

## STEP 2 — Point glimmerbliss.com at Spaceship hosting  ✅ already pointed; still turn OFF GitHub Pages

⚠️ The old plan said to add GitHub's four A records. **Don't do that now.** The shop
needs the Spaceship hosting, because GitHub Pages can't run the order system.
Connect the domain to the hosting plan inside Spaceship. Claude has not confirmed
Spaceship's exact clicks, so have Claude walk through it with you. After that,
turn off GitHub Pages so there aren't two copies of the shop.

## STEP 3 — Save your payment QR pictures

- Venmo app → Scan → **My Code** → save the picture as `docs/images/pay/venmo-qr.png`
- Bank app → Zelle → **My QR code** → save as `docs/images/pay/zelle-qr.png`

They then show on the pay page and on the printable sign (`pay-sign.html`, Ctrl+P).

## STEP 4 — Test one real order before telling anyone

Place a $12 pickup order with Venmo, pay it, Mark Paid in Admin, and check that
both emails arrive (and not in spam). In cPanel, open **Email Deliverability**
and fix anything it flags. That's what keeps shop emails out of spam folders.

---

## STEP 5 — Fill in the made-up details  ✅ DONE 27 Oct: JC confirmed the guesses are right; DRAFT boxes and yellow highlights removed

Three pages still have a **yellow DRAFT box** at the top and **highlighted
yellow placeholders** in the text. They are live right now and a customer can
read "a grown-up needs to check this."

Pages affected: `docs/shipping.html`, `docs/returns.html`, `docs/faq.html`

**Claude needs these answers from you** (it invented all of them and cannot
guess correctly):

| Thing | Currently says | Your real answer |
|---|---|---|
| Contact email | ✅ DONE 27 Oct: now `orders@glimmerbliss.com` everywhere (real Spacemail mailbox) | ✅ |
| Days to make an order | 3 to 5 business days | ? |
| Days in the post | 2 to 5 business days | ? |
| Return window | 14 days | ? |
| Where local pickup happens | "a safe public meet-up spot, or our booth at First Monday Trade Days, Canton TX" | ? |
| How fast you reply | 2 business days | ? |
| Shelf life once opened | 12 months | ? |

Give Claude those and it will fill them in and delete the yellow boxes.

⚠️ Also: the **Returns page needs a grown-up to check it against Texas rules**
for selling and returning cosmetics. Claude wrote a sensible generic policy,
not legal advice.

---

## (OLD) Taking real money — superseded by the shop above

**There is no checkout.** "Add to Cart" just counts up a number and pops up a
message saying it's a demo. Nobody can actually buy anything.

Known facts:
- **No payment account details exist anywhere** in this project. Claude searched
  every file, all commits, and the spreadsheet on 29 July. Nothing.
- Put any account keys in the **`private/`** folder — `.gitignore` already
  blocks it, so it can never be committed by accident.
- **Pirate Ship cannot calculate shipping prices for customers.** Confirmed on
  their own support pages: *"Pirate Ship doesn't offer an API"* and *"Pirate Ship
  doesn't affect the shipping rates your eCommerce platform quotes your
  customers in any way."* It is a **label buyer you use after a sale**, and it
  plugs into 14 store platforms including Shopify, Square, Wix and Etsy.

So the order is: pick a store platform → it handles checkout and shipping
quotes → Pirate Ship imports those orders → you buy cheap labels.

Ask Claude for a written comparison of Square vs Shopify vs Etsy when ready.
You already own the domain, which affects the answer.

---

## What changed on 24 October 2026 (Charlie + Dad session)

- **Glimmer Kiss** page has a new **Glitter** choice: "With glitter ✨" / "No glitter". Same $15 price.
- **Sugar Cookie** uses new lighter-brown photos that Dad supplied. The previous
  photos were kept as `docs/images/sugar-cookie_old.jpg` and `sugar-cookie-side_old.jpg`.
- **"True Love" was renamed to "Love"** (same red, same photos — files now
  `products/love.html`, `images/love.jpg`, `images/love-side.jpg`).
- **A new gloss "True Love" (dark red)** was added: `products/true-love.html`, $12.
  ⚠️ Its photos (`images/true-love.jpg`, `true-love-side.jpg`) are **NOT real
  photos** — they are the Love photos with the red darkened by computer. Replace
  them with real photos once a dark red gloss exists.
- Site text now says **six** glosses / **five** main colors (home page, FAQ, help assistant).
- A sharper logo was saved to `LOGO/Glimmer Bliss Logo_sharpened.jpg`. **Not used on the site yet.**

### Open items from this session
- **Home page grid:** it fits 4 glosses per row on big screens and there are now 5,
  so Sugar Cookie sits alone on a second row. Left as-is on purpose; ask Claude
  to change `.product-grid` in `docs/css/styles.css` if you want one row.
- **The cart does not remember choices** (glitter or delivery). It only counts
  items. Needs fixing when real checkout (STEP 3) is built.
- Swap in the sharper logo when ready.

---

## Other things worth knowing

**The repo is PUBLIC.** github.com/JcPath1/divine-lips can be read by anyone.
The website no longer serves the business spreadsheet, but **GitHub still shows
it**. Ask Claude to make the repo private if you would rather it weren't.

**Charlie's photo is not in the repo and never was.** Both copies live in
`private/`, which git ignores. The site uses a hand-drawn cartoon avatar
(`docs/images/charlie-mae-avatar.svg`) instead. Keep it that way — the site is
public and she is a child.

**The Glimmer Kiss video was shrunk 27 Oct 2026: 12.4 MB → 3.1 MB.** It's shown only 205px wide,
so it's now 432×768 (was 720×1280), with no sound track, and its index ("moov") moved to the start
(`-movflags +faststart`) so phones can start playing before the whole file arrives. The 12 MB original
is kept in `private/videos-original/` (git-ignored). For a new video, ask Claude to do the same.

**Three things are parked**, not deleted — they sit inside `<!-- -->` marks in
`docs/index.html` with notes on how to bring them back:
- the rotating photo gallery ad
- the "Getting Your Gloss" shipping section
- the "Follow Us" footer links

**The help assistant is not AI.** It matches keywords to answers written into
`docs/js/main.js` (`HELP_ANSWERS`). No API key, no internet, no cost, and it
cannot invent something wrong. If you edit that list, ask Claude to re-run its
test — there are 22 test questions and they all pass right now.

---

## Quick reference

| | |
|---|---|
| Live site | https://jcpath1.github.io/divine-lips/ |
| Code | https://github.com/JcPath1/divine-lips (public) |
| Domain | glimmerbliss.com, registered at **Spaceship.com** |
| Website files | `docs/` |
| Open locally | double-click `docs/index.html` |
| After any change | hard-refresh with **Ctrl + Shift + R** |
| Glosses | Pink Paradise, Love (red), True Love (dark red), Gloss Angeles, Sugar Cookie — $12. Glimmer Kiss — $15, limited edition (glitter / no glitter) |
| Founder | Charlie Mae (sole founder) |

## Google Search Console
- glimmerbliss.com is verified in Google Search Console (2026-10-28, account fractalsolution369@gmail.com).
- KEEP `docs/google38530a0b0093f57e.html` and the `google-site-verification` meta tag in `docs/index.html`. Deleting them un-verifies the site.
- Sitemap: https://glimmerbliss.com/sitemap.xml (update the <lastmod> dates when pages change).
