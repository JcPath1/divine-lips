# Organic social plan (saved 11 November 2026)

JC asked for these notes to be saved so they are not lost. **This is the next part of the project.**
Part 1 is what Claude checked. Part 2 is JC's notes, saved as he pasted them.

This file is in the repo root, NOT in `docs/`, so it is not published on the website.

---

## Part 1. What Claude checked on 11 Nov 2026

### What is real

| Thing in the notes | Checked | Result |
|---|---|---|
| `coreyhaines31/marketingskills` | GitHub + `npx skills add ... --list` | Real. MIT licence, about 59,600 stars. It has **52 skills**, mostly for software companies |
| Skill names | Same listing | The notes say `social-content`, `launch-strategy`, `referral-program`. The real names are **`social`**, **`launch`**, **`referrals`**. `copywriting` is right |
| `@playwright/mcp` | npm | Real (version 0.0.85) |
| `@getlatedev/mcp` (Late) | npm | **Not found (404).** The install command in the notes will fail as written. Late's real MCP install steps need to come from Late's own website |
| Late "free, 20 posts a month" | Not checked | Unknown. Check on Late's site before relying on it |
| Postiz | Not checked | Unknown |

### Skills worth installing for a lip gloss shop

`social`, `copywriting`, `launch`, `influencer-marketing`, `referrals`. The other 47 are for things like
software pricing, paywalls and sales teams, and would only clutter the skill list.

### Things in the notes that break our own rules

These must be fixed before any of it is used. See `brand/brand-guidelines.md`, section 4.

1. **Made-up product claims.** "Glass-lip formula that doesn't stick to your hair", "ingredient/hydration
   breakdown", "vanilla peptide lip treatment", "clean girl". We do not know the ingredients yet, there is no
   peptide, and "hydrating" and "clean" are banned until the ingredient list is in. Our true facts: sheer, high
   shine, vanilla flavored, 5 ml, not sticky, handmade in small batches, Glimmer Kiss turns pink on skin.
2. **"15% founder code."** No such code exists. It would have to be made first in Admin → Promotions, and JC
   decides if he wants one.
3. **Auto-posting.** Our rule: JC approves every post before it goes anywhere. A scheduler is fine only if
   nothing is queued until JC has said yes to that post.
4. **"Founder build-in-public" posts.** Charlie's privacy rule still applies: first name only, no age, school,
   town or photo.
5. **Accounts.** Instagram, TikTok, X and Pinterest need an adult's account (13+). They must be in JC's name.
6. **Messaging creators.** Fine as drafts for JC to send himself. Claude does not send DMs.
7. **Reading TikTok / Instagram with a robot browser** (Playwright). Those sites' rules limit automated
   scraping. Ask JC before doing this, and prefer looking by hand.
8. **"Comment GLOSS and I'll DM you."** Sending those DMs automatically needs another tool, which may not be
   free. Without one, JC would answer each comment by hand.
9. **"Tracks what converts."** Nothing is set up to measure that yet.

### What already exists for this

- The ad agent: `.claude/agents/glimmer-ads.md`.
- The brand kit: `brand/brand-guidelines.md`, `brand/design-tokens.json`, `brand/winning-prompts.md`.
- fal picture and video tools: `tools/fal_generate.py`, `tools/fal_video.py`.
- Remotion video project: `brand/video/`.
- The first approved ad and its video: `ads/campaign-test/01-sunlit-macro-instagram-4x5.png` and
  `01-sunlit-macro-video-4x5.mp4`.
- The older weekly Post Maker on the server (`server/app/posts.php`), which emails JC three posts a week.

### Suggested order

1. Install the five skills.
2. Fix the prompt below so it only uses true facts, then build "organic batch 1" as ready-to-post folders in
   `ads/organic-batch-1/`. JC posts by hand at first.
3. Only after a few weeks of posting by hand, decide on a scheduler (Late, Postiz, or none).

### The prompt, corrected for our rules

```text
Update our advertising agent so it also works as a zero-budget organic social engine for Instagram, X, TikTok
and Pinterest, using the `social` skill and brand/brand-guidelines.md.

Build a weekly batching workflow where, from one gloss, you make:
1. 3 video concepts in 9:16 (fal.ai visuals + Remotion text hooks) for Instagram Reels, TikTok and X video.
2. 1 four-slide Instagram carousel plus a Pinterest pin set, using our fal.ai campaign pictures.
3. 3 native X posts (a beauty tip, a texture close-up, and a founder-story angle that keeps Charlie's privacy
   rules) written to start replies, not to look like ads.
4. Captions for each platform with searchable keywords. Only true facts from the repo. No ingredient,
   hydration, peptide or "clean" claims. No discount code unless JC has made one.

Make a 3-day organic launch batch for Glimmer Kiss and save the schedule and assets in ads/organic-batch-1/.
Tell me the fal cost before making any picture or video. Do not post or schedule anything.
```

---

## Part 2. JC's notes, as pasted on 11 Nov 2026

> You can automate organic posting and cross-platform distribution across Instagram, X, TikTok, and Pinterest
> for $0 by connecting free scheduling MCP servers and open-source marketing skills to Claude Code.
> Instead of paying for Meta or X ad spend right away, you can turn your upgraded CC ad agent into a full
> organic social media engine that batches daily Reels, carousels, and viral X threads, schedules them
> automatically, and tracks what converts.

### 1. Free Extensions & MCP Servers for Zero-Cost Distribution

You can plug these tools into Claude Code so it handles publishing and growth strategy without paid ad budgets:

- **Late MCP Server (`@getlatedev/mcp`).** Late offers a free tier (up to 20 posts/month across connected
  profiles) and an official MCP server. Once connected to Claude Code, CC can schedule and publish your fal.ai
  images and Remotion videos directly to Instagram, X, TikTok, Threads, LinkedIn, and Pinterest from your
  terminal.
- **Postiz Self-Hosted or Open-Source Social Schedulers.** If you want unlimited posts for $0, Postiz is an
  open-source social media scheduler with AI and MCP support that you can run locally on your machine to queue
  posts across Instagram, X, TikTok, and YouTube Shorts without monthly SaaS fees.
- **Playwright MCP (Free Trend & Viral Hook Scraper).** You can add Microsoft's free `@playwright/mcp` to
  Claude Code so CC can open a headless browser, inspect trending beauty hashtags on TikTok/Instagram/X,
  analyze what organic lip gloss videos are getting high engagement right now, and model your next batch of
  posts after proven formats.
- **Open-Source Growth & Social Skills (`coreyhaines31/marketingskills`).** Installing Corey Haines' free
  Marketing Skills gives CC dedicated playbooks for social-content, copywriting, launch-strategy, and
  referral-program, teaching it how to turn a single fal.ai product shoot into a week's worth of
  platform-native posts.

### 2. High-ROI Zero-Budget Growth Plays for a Lip Gloss Brand

Organic beauty brands grow fastest by pairing high-aesthetic visuals with specific engagement triggers rather
than generic product promos:

- **The "1-to-7 Content Multiplier" Pipeline.** Have CC take one fal.ai hero campaign shoot and automatically
  repurpose it into 7 organic assets:
  1. Instagram Reel / TikTok / YouTube Short (9:16): A 6-second Remotion video with a bold hook ("The glass-lip
     formula that doesn't stick to your hair in the wind") + macro gloss motion.
  2. Instagram Carousel (4:5): Slide 1 = editorial fal.ai cover shot; Slide 2 = close-up swatch texture;
     Slide 3 = ingredient/hydration breakdown; Slide 4 = shade comparison.
  3. X (Twitter) Founder/Brand Build-in-Public Post: Behind-the-scenes look at formulating the shade, packaging
     design, or testing swatches. Founder-led beauty stories routinely outperform polished ads on X.
  4. Pinterest Idea Pins: Vertical fal.ai aesthetic shots tagged with searchable beauty terms ("clean girl lip
     combo," "non-sticky clear lip gloss," "vanilla peptide lip treatment"), which drive free search traffic
     for months.
- **Micro-Creator & PR Seeding Outreach.** Have CC draft personalized, non-spammy DM scripts to send to
  micro-creators (1,000–15,000 followers) offering a free gloss PR box in exchange for an honest swatch video,
  costing you only product and shipping instead of ad spend.
- **Comment-to-DM Funnel Hooks.** End your organic captions with a keyword trigger (e.g., "Comment GLOSS and
  I'll DM you our shade-finder guide + a 15% founder code"), which boosts algorithm reach on Instagram without
  paying for boosted posts.

### 3. Install the Free Marketing Skills & Scheduler Right Now

1. Install the Free Marketing & Social Content Skills:
   `npx skills add coreyhaines31/marketingskills`
2. Optional: Connect Late MCP (Free 20 Posts/Mo Across IG, X, TikTok, Pinterest). Sign up for a free API key at
   getlate.dev, connect your social accounts, and run:
   `claude mcp add late -e LATE_API_KEY="YOUR_LATE_API_KEY" -- npx -y @getlatedev/mcp`

### 4. Prompt to Turn Your Ad Agent into a Zero-Budget Organic Content Engine

```text
Update our advertising agent so it also operates as a zero-budget organic social growth engine for Instagram,
X, TikTok, and Pinterest using our `social-content` skill and `/brand/brand-guidelines.md`.

Build a weekly content batching workflow where, from a single product shade, you generate:
1. 3 scroll-stopping 9:16 video concepts (using fal.ai visuals + Remotion text hooks) tailored for Instagram
   Reels, TikTok, and X video.
2. 1 high-save 4-slide Instagram Carousel + Pinterest Pin set using our fal.ai campaign photography.
3. 3 native X posts (mixing beauty tips, texture/ingredient breakdowns, and founder story angles) designed to
   spark replies without looking like ads.
4. Platform-specific captions with comment-trigger CTAs and searchable keywords.

Generate a 3-day organic launch batch for our flagship lip gloss right now and save the full schedule and
assets into `/ads/organic-batch-1/`.
```

If you want to automate the posting side too, decide:
- Which social platforms to prioritize first (Instagram, X, TikTok, or Pinterest)
- Whether to connect Late's free 20-post/month MCP or keep CC exporting ready-to-post folders for manual upload
