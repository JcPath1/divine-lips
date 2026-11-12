# TikTok Shop plan: what to do, in order

Written 12 Nov 2026. Rules come from TikTok's own Seller University page "Beauty and Personal Care
Products Requirements" (updated 09/01/2026) and the US cosmetics law (21 U.S.C. § 364h). Links are at
the bottom. Nothing here has been applied for yet.

## How TikTok Shop works

It is a seller account inside TikTok (Seller Center), not something we build. TikTok hosts the product
pages, checkout and payments, videos get a buy button, TikTok takes a cut of each order, and we ship.
Because we make the gloss, TikTok treats us as the **manufacturer**.

## Steps, in order

Each step needs the one before it.

### 1. Write down the recipe for each of the 5 glosses  (JC and Charlie)
Which supplies and how much of each. The supply list is in `brand/ingredients-tkb.md`. Also say whether
anything is bought from a supplier other than TKB Trading.

### 2. Settle the open ingredient questions  (JC, with Claude's help)
- **Glitter:** TKB's page for White Diamond Glitter says the FDA "doesn't formally permit polyester
  glitter in cosmetics" and shows nail use only. TikTok bars FDA-prohibited or restricted ingredients.
  Decide whether to keep it, swap it, or leave that gloss off TikTok.
- **Vitamin E:** some supplies contain it. Fix the brand kit line that says it is "not used" once the
  recipes show which glosses have it.
- **Soy:** the vanilla oil is soybean oil based.
- **Shelf life:** work out a date for the finished gloss. The vanilla oil alone is 6 to 9 months. TikTok
  expects 365 days left on delivery, or the listing must say so before purchase.

### 3. Write the ingredient list for each gloss  (Claude)
In order from most to least, using the names from TKB's pages. Save it in the repo.

### 4. Design and print a label for the tube  (Claude drafts, JC approves)
The bottle has no label today. TikTok's page requires:
- Front: product name and net contents (the tubes are 5 ml).
- Information panel: ingredient list, our name and address, any warnings.
- Expiration date as month/day/year or month/year.
Whoever's name is on the label is the "Responsible Person": they report serious reactions to the FDA and
keep records and safety evidence.

### 5. Confirm the FDA small-business exemption  (JC)
Businesses with under $1,000,000 average yearly US cosmetics sales over the last 3 years are exempt from
FDA facility registration, product listing and the good-manufacturing-practice section. The exemption
does not apply to eye-area, injected, internal-use or 24-hour-plus products.
- Not settled: whether the FDA counts lip products as "internal use". Ask the FDA or read their
  registration and listing guidance before signing anything.
- If exempt, TikTok wants a self-attested document saying so. If not, register with the FDA first.

### 6. Take the product photos  (Claude and JC)
Photos must show the full product and its label, including ingredients, directions and net content.

### 7. Open the Seller Center account  (JC)
In JC's name, with ID and bank details. Check the current fee there: sources say 6%, one report says 8%
since August 2026.

### 8. Apply for the beauty category  (JC, Claude prepares the documents)
In Seller Center's Qualification Center: the exemption attestation or FDA registration, plus the label.
The exact document list only shows when you apply.

### 9. List the products  (Claude writes, JC approves)
From `server/app/catalog.php` so every detail matches glimmerbliss.com. Any "no fragrance" style claim
must match the ingredient list. No whitening, medical or CBD claims.

### 10. Make the TikTok videos  (Claude drafts, JC approves every post)

## What it costs (from seller guides, 12 Nov 2026; not checked on TikTok's own pages)

| Cost | Amount | Required? |
|---|---|---|
| Opening the shop | $0, no setup fee | n/a |
| Monthly or listing fee | $0 | n/a |
| TikTok's cut of each order | 6%, or 8% since August 2026 by one report; includes card processing | Yes, only when we sell |
| Ads | Whatever we choose | No |
| Creator commissions | We set it; 10 to 20% is typical | No |

- On a $15 gloss the cut is about $0.90 at 6% or $1.20 at 8%.
- New US sellers who make a first sale within 60 days are said to get 3% for 30 days.
- Our own videos can carry the buy button for free.
- **Check first in Seller Center:** one source says ship-it-yourself ended for US sellers in March 2026 and
  sellers must use TikTok's shipping labels or warehouse. Other sources do not mention it. If true, it
  changes our shipping cost.
- Costs outside TikTok: printing labels for the tubes.

Cost sources: https://crosslist.com/blog/tiktok-shop-fees ,
https://www.fastmoss.com/blog/tiktok-shop-seller-costs-in-the-us-2026-fees-creator-commissions-fulfillment-returns-real-profit/ ,
https://www.bebolddigital.com/news/tiktok-shop-8-percent-referral-fee ,
https://fitsmallbusiness.com/what-is-tiktok-shop/

## Not confirmed yet

- TikTok's minimum seller age (the account will be JC's either way).
- The current inflation-adjusted figure for the $1,000,000 threshold.
- Whether TikTok or the FDA changed the good-manufacturing-practice deadline (TikTok's page says end of 2025).
- Our own sales total against the threshold.

## Sources

- TikTok, Beauty and Personal Care Products Requirements: https://seller-us.tiktok.com/university/essay?knowledge_id=1789325403522858&lang=en
- TikTok, Guidelines For Listing Beauty & Personal Care Products: https://seller-us.tiktok.com/university/essay?knowledge_id=3026065007494925&lang=en
- TikTok, Your Guide to Category Qualification: https://seller-us.tiktok.com/university/essay?knowledge_id=1707918207813422&lang=en
- TikTok, Expiration Date Labeling and Shelf Life Requirements: https://seller-us.tiktok.com/university/essay?knowledge_id=5544428744673066
- 21 U.S.C. § 364h, small businesses: https://www.law.cornell.edu/uscode/text/21/364h
- FDA guidance, Registration and Listing of Cosmetic Product Facilities and Products: https://www.fda.gov/media/170732/download
