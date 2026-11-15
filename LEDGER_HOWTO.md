# Text ledger: how to file an entry (for Phronesis and for Claude)

Built 14 Nov 2026. **LIVE on glimmerbliss.com since 15 Nov 2026** (JC approved 14 Nov, 11:43 PM).
Code: `tools/ledger.py` (the tool), `server/app/ledger.php` and `server/public/api/ledger.php` (the shop side),
`tools/ledgertest.py` (tests).

JC or Charlie sends a short message. Phronesis hands it to the Glimmer Bliss agent as a task. The agent runs one
command and sends back, word for word, what the tool printed.

## How Phronesis should word the task

For an entry:

> Ledger entry from **Charlie**: "sold 4 Pink Paradise at the show, $48 cash"

For the summary:

> Ledger summary for **Jc**

Always give the sender's name and the message exactly as it was typed. One thing per message.

## What the agent runs

    python tools/ledger.py say "<the message, unchanged>" --by <sender>
    python tools/ledger.py summary
    python tools/ledger.py recent          (the last 20 entries with their numbers)

Then reply with the tool's output, unchanged. Do not fix the message, fill in a missing part, or try again with
different words. If the tool prints `NOT RECORDED`, send its questions back so Phronesis can ask the sender.

Exit code 0 = recorded. 2 = not recorded (unclear or refused). 1 = something broke: report the error.

## Messages it understands

| Start with | Example | Must include |
|---|---|---|
| made | `made 24 Pink Paradise` | a number before each gloss name |
| sold | `sold 4 Pink Paradise at the show, $48 cash` | number + gloss, where, how paid, the total with a $ |
| spent | `spent $56.50 on bottles` | one $ amount, what it was, a kind word |
| donated | `donated: 2 oz squalane oil from Grandma` | what it was, a kind word. Saved at no cost. |
| count | `count: 30 Pink Paradise, 12 True Love` | the number on the shelf for each gloss named |
| undo | `undo 12` | the entry number from the read-back |
| summary | `summary` or `how are we doing?` | - |

- **Glosses:** Pink Paradise, True Love, Gloss Angeles, Sugar Cookie, Glimmer Kiss. Glimmer Kiss must say
  `with glitter` or `no glitter`.
- **Where:** show (also booth, market, Canton), web, TikTok, other.
- **How paid:** cash, Venmo, card, Zelle, PayPal. A TikTok sale needs no pay word.
- **Total:** `$48`, or `$12 each`.
- **Kind of cost:** supplies, bottles, labels, hosting, show fees (or booth), ads, shipping, other.
- **Date:** today unless the message says `yesterday`, `on Nov 13` or `on 2026-11-13`.

## Rules the tool keeps

- **It never guesses.** A missing gloss, number, place, pay type, amount or kind stops the entry and is asked.
- **Stock never goes below zero.** A sale of more than is on the shelf is refused and reported. Fix the count
  first, or record the batch.
- **Nothing is deleted.** `undo 12` adds a new entry that reverses entry 12. Both stay in the history. An entry
  can be undone once.
- **Every entry keeps** who sent it, when, and the words they sent.
- **One stock count.** Text entries, the booth Sell screen and paid website orders all move the same count.
- **Tax:** a show or web sale treats the total as including Texas sales tax, the same as the booth Sell screen.
  TikTok and other sales are saved with no tax worked out. Whether that is right is a question for an accountant.
- **Website orders are not typed in.** They come in by themselves once marked paid in the admin.

## Test copy or live shop

`private/ledger-target.txt` now contains `live`, so `tools/ledger.py` files entries in the **live shop**. To use the
test copy instead, add `--target local` right after `ledger.py`. Every reply from the test copy starts with `[TEST COPY on this computer - not the live shop]`.
To run the test copy: `python tools/glimmer.py local`, start the php command it prints, then
`python tools/ledgertest.py`.

## Live setup (done 15 Nov 2026)

- The door is `https://glimmerbliss.com/api/ledger.php`. It only answers a request that carries the secret
  `LEDGER_TOKEN` (64 random characters) kept in `private/glimmerbliss-secrets.env`, which git never uploads.
  A wrong token gets "Not allowed" (checked), and 10 wrong tries from one place lock it for 15 minutes.
- Never print the token, and never put it in a task, a report or the log.
- Backups: `python tools/ledger.py backup` saves a full copy of the live records to `private/backups/`. The tool
  also does this by itself before the first live entry of each day. First one: 15 Nov 2026, before any entry.
- Go-live proof: entries 1 and 2 in the live history are a test count ("34 True Love", already 34) and its undo,
  sent as "Claude (go-live test)". Stock and totals were identical before and after.
- Not entered yet: the show sales and past Venmo sales. JC has not confirmed those lists
  (`private/past-records-2026-11-14.md`).
