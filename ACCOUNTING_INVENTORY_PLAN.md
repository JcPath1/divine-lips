# Accounting and inventory plan (14 Nov 2026) - PLAN ONLY, nothing built

JC: "It does need some accounting and inventory system. Right now I'm not sure what it's made or sold." Sales
are at shows and on the web; TikTok ads and maybe other channels are next.

The actual figures found (order amounts, stock counts, costs) are kept out of this public repo. They are in
`private/records-audit-2026-11-14.md`.

## 1. What exists today

**A simple system is already built into the shop admin (since 27 Oct 2026) and is almost empty.**
glimmerbliss.com/admin has four screens made for a phone:

- **Sell**: tap a gloss per bottle sold at the booth, then Cash / Venmo / Card. Takes it off the stock count.
- **My Gloss**: count of each gloss. "How many? Add" after a batch. Paid web orders come off by themselves.
- **Spent**: what was bought, how much, when.
- **Money**: money in (web + booth), tax to save, spent, kept. This month and all time.

What the records show (checked read-only on the live admin, 14 Nov 2026):

| Record | State | Gap |
|---|---|---|
| Web orders | A handful since 28 Oct, none marked paid | Not known if they were paid or picked up |
| Booth sales | None recorded, ever | All show sales |
| Stock counts | A count is entered for each gloss | Date and accuracy not known |
| Spending | Nothing entered | All purchases |
| Batches made | No log | How many were ever made |
| Business spreadsheet (May 2026) | A plan with estimates, old flavour list | Not a record |
| Venmo / cash / show tallies | Nothing on file | Everything |
| Costs on file | Sticker order, ad-picture spend, what was bought from TKB (not the amounts) | Hosting, booth fees, Amazon, TKB amounts |

So today nobody can say how many were made, how many sold, what came in, what went out or what is left.

**What the built-in screens cannot do, even if used:** show units sold per gloss; show a history of batches;
tell TikTok Shop or other channels apart (only "web" and "booth"); sort spending into kinds (supplies, labels,
bottles, hosting, show fees, ads); work out cost per gloss or profit per gloss; give a date range or a download.

## 2. Options, smallest to fullest

### Option A - use what is already there (no build, $0)
Charlie or JC taps **Sell** at the booth, **Add** in My Gloss after each batch, **Spent** for each purchase, and
**Mark Paid** on web orders.
- Tells you: stock on hand, money in from web and from booth, total spent, what is left.
- Does not tell you: units sold per gloss, batches made, TikTok or other channels, spending by kind, profit per
  gloss.
- Effort: a habit, about a minute per sale or purchase. Needs a phone signal at the booth.

### Option B - text it to Phronesis, one summary page (recommended; a build, $0 outside cost)
Keep the shop database as the one place the numbers live, so there is only ever one stock count. Add:
1. **Text intake.** JC texts Phronesis in plain words and an agent files it:
   - "made 24 Pink Paradise" -> a batch, adds to stock
   - "sold 4 Pink Paradise at the show, $48 cash" -> a sale on the show channel, takes off stock
   - "sold 2 Glimmer Kiss on TikTok, $30" -> a sale on the TikTok channel
   - "spent $56.50 on bottles at TKB" -> an expense, sorted as bottles
   - "count: 20 Pink Paradise" -> corrects the stock count
   The agent repeats back what it filed, so a wrong entry is caught at once. The booth Sell screen keeps working
   for anyone who would rather tap.
2. **Channel on every sale:** show, web, TikTok Shop, other. Web orders keep coming in by themselves.
3. **Kind on every expense:** supplies, bottles, labels, hosting, show fees, ads, other.
4. **One summary page** in the admin, also sent as a text on request ("how are we doing?"): for each gloss,
   made / sold / in stock; sales by channel; spending by kind; money in, tax set aside, spent, left; cost per
   gloss once supplies are entered.
5. **A download** (CSV) for an accountant.
- Does not tell you: bank or Venmo balances (it only knows what it is told), or TikTok fees unless they are
  entered or read from TikTok's own reports.
- Effort: a build by Claude over one or two sessions, with tests on the local test shop before anything goes
  live. It changes the live order system, so it needs JC's go-ahead before it is deployed.
- TikTok Shop sales: typed in by text at first. If volume grows, read TikTok's order export instead.

### Option C - outside tools (fullest; monthly fees likely, not checked)
A point-of-sale app for the booth (for example Square) plus bookkeeping software (for example Wave or
QuickBooks), fed by bank and Venmo.
- Tells you the most for taxes: bank-matched income and spending, proper reports.
- Costs: prices and card fees were NOT checked for this plan. Check them before choosing.
- Effort: new accounts in JC's name, the catalog set up again in each tool, Charlie learning a second app, and
  stock counted in two places (the website and the booth app) unless they are connected.

### Recommendation
**Start Option A habits now and build Option B.** Option A costs nothing and stops the gap growing this
weekend. Option B fits how JC works (short texts from a phone), keeps one stock count, and adds the channel
split needed before ad money is spent. Option C is worth a look only if an accountant asks for it.

## 3. What is needed from JC to start

1. **A stock count today** of each gloss (Glimmer Kiss with and without glitter counted apart), to check the
   counts already in My Gloss.
2. **The open web orders:** were they paid and picked up? Then Mark Paid or Cancel each one.
3. **Past sales, if any record exists:** Venmo history, cash notes, show dates, how many sold. If there is no
   record, say so and the books start from today.
4. **What has been spent:** TKB orders (Claude can read the two order emails if JC says yes), Amazon, stickers,
   hosting and domain, booth fees, ad tools.
5. **How many batches have been made**, roughly, and when.
6. **Is anyone selling for you on commission?** The May spreadsheet planned for it.
7. **Who enters booth sales:** Charlie on the Sell screen, JC by text, or both.
8. **An accountant, if there is one**, and what they want to see.

## 4. Sales ideas

None of these is a forecast. The business has no sales history on record to judge them by.

The four Phronesis floated:

- **(a) Show sales by text.** Fits. It is the heart of Option B. The Sell screen already does the same by
  tapping, so nothing is needed to start today.
- **(b) A discount code per channel or ad.** Fits, and mostly works already: the shop takes promo codes and
  saves which one each order used. Limits: it only counts buyers who type the code, and each code costs margin.
  Better as a pair: a tagged link per ad that is saved on the order (small build, no discount) plus a code where
  a link cannot go (a printed card, a creator's video).
- **(c) Sets.** Fits. Free shipping at $35 already pushes web buyers toward three glosses. The site can do
  "percent off when the order is at least $X" today; "pick any 3 for $X" and a set button on the booth Sell
  screen need a small build. Set the price after cost per gloss is known; the May spreadsheet's bundle prices
  were guesses.
- **(d) Reorder card with a QR code.** Fits. The QR codes and the review page exist. One card could carry the
  ingredients on one side (already drawn in the sticker work) and "reorder / leave a review" on the other.
  Needs a print run and JC's pick of what the QR opens.

Five more, best first:

1. **Take cards on the website.** Web checkout is Venmo only today, and the web orders on file never reached
   "paid". The PayPal button (cards, pays and marks the order by itself) is already built and only needs JC's
   PayPal keys. Whether payment is why those orders stalled is not proven.
2. **Holiday gift sets, now.** A 3-pack as a stocking stuffer for the November and December shows and the
   site, with Glimmer Kiss in it. Needs the set price from (c) and a simple band or bag.
3. **Lead TikTok with Glimmer Kiss.** Clear in the bottle, pink on the lips is something video shows well, and
   three finished videos and the ad system are already in the project. Put a tagged link or code on each one
   (b) so the first ad dollars can be measured.
4. **Collect a name and email or phone at the booth** for restock and limited-batch news. The shop has almost
   no customers on file, while the booth meets buyers face to face. Needs a sign-up sheet or QR and a parent's
   OK on how the list is used.
5. **Ask every buyer for a review with the card.** There are very few reviews. They show on each gloss page and
   in Google's product data.

Later, once the label is settled: shops or salons selling on consignment, which the May spreadsheet planned for.

## 5. To check with an accountant (not advice)

- Sales tax on booth sales: whether tax-included prices are allowed, and which local rate applies at a show in
  another city. Already an open question in `NEXT_STEPS.md`.
- What records to keep and for how long (receipts, cash sales, Venmo).
- How to count the cost of glosses made but not yet sold.
- How income is reported when the seller is a minor and a parent holds the accounts.
- What TikTok Shop reports to the tax office about sales made there.
