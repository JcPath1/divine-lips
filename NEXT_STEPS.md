# Glimmer Bliss — Where We Left Off

**Last worked on: 24 October 2026**
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

## STEP 1 — Point glimmerbliss.com at the site  ← do this first

**You have to do this part.** It needs your Spaceship.com login, and Claude
should not have that.

1. Log in to **Spaceship.com**
2. Find **glimmerbliss.com** and open its **DNS** settings
3. Add **four A records**. Host is `@` (some sites call this "root" or leave it
   blank). Each one points to a different address:

   ```
   185.199.108.153
   185.199.109.153
   185.199.110.153
   185.199.111.153
   ```

4. Add **one CNAME record**:

   - Host: `www`
   - Points to: `jcpath1.github.io`
   - (no repo name on the end, no `https://`, no trailing slash)

5. Save.

These four addresses are GitHub's, taken from their official documentation on
29 July 2026. If a lot of time has passed, have Claude re-check them before you
type them in — GitHub has changed them before.

**Then tell Claude "the DNS is saved."** Claude will:
- add a `CNAME` file to `docs/`
- set the custom domain on GitHub
- turn on the free padlock (HTTPS)
- check the site really loads on glimmerbliss.com

DNS usually takes 15 minutes to a few hours to spread. Sometimes up to a day.
This is normal and there is nothing to fix while you wait.

---

## STEP 2 — Fill in the made-up details  ← before telling anyone the address

Three pages still have a **yellow DRAFT box** at the top and **highlighted
yellow placeholders** in the text. They are live right now and a customer can
read "a grown-up needs to check this."

Pages affected: `docs/shipping.html`, `docs/returns.html`, `docs/faq.html`

**Claude needs these answers from you** (it invented all of them and cannot
guess correctly):

| Thing | Currently says | Your real answer |
|---|---|---|
| Contact email | `hello@glimmerbliss.com` (**made up — appears 7 times**) | ? |
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

## STEP 3 — Taking real money  ← the big one, not started

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

**The Glimmer Kiss video is 12 MB.** Fine on your computer, slow on a phone with
weak signal. Ask Claude to shrink it before you promote the site anywhere.

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
