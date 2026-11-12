# Glimmer Bliss Brand Guidelines

**Version 3 (draft for approval) · 11 November 2026**
Version 3 adds the fal.ai production pipeline. Version 2 added JC's Creative Director rules (section 5): no web-template design, texture and sensory focus,
editorial type and layout, the 3-concept pitch, and the self-critique gate.
The look is called **Blush & Black**. Charlie Mae picked it on 10 November 2026.
Machine-readable values are in `brand/design-tokens.json`. Sample ads are in `brand/samples.html` and
`brand/glimmer-kiss.html`.

How to read this file: **measured** means a script or the code gave the number. **judgement** means it is a
design opinion. Nothing here has been tested with real customers yet.

---

## 0. What the audit found

Audited on 11 Nov 2026: the live site (https://glimmerbliss.com), `docs/`, `docs/images/`, `server/app/catalog.php`,
`server/app/posts.php`, and the sample ads in `brand/`. Site screenshots are in `brand/audit/site-2026-11-11/`.

### The brand today has three looks (measured)

| Where | Letters | Colours |
|---|---|---|
| Logo (`docs/images/logo.png`) | Black high-contrast serif monogram with 4-point sparkles | Black on white |
| Website (`docs/css/styles.css`) | Georgia headings, Segoe UI body | Hot pink `#ff69b4`, `#ffb6c1`, `#fff0f5`, `#db7093`, grey `#6b7280` |
| Weekly posts (`server/app/posts.php`) | Poppins | Photo backgrounds (grey-blue) |

The new look uses one set of letters and colours everywhere.

### Checks

| Check | Result | Evidence |
|---|---|---|
| Body text is easy to read | ✓ Pass | Ink `#1a1a1a` on blush `#f7e1e3` = 13.95:1 (measured; needs 4.5:1) |
| Pink headline words | ✓ Pass for big text only | Pop `#e6337f` on blush = 3.27:1 (measured; big text needs 3:1, small text needs 4.5:1) |
| Small pink text and buttons | ✗ Fail with Pop, ✓ pass with Deep Pink | `#c31861` on blush = 4.65:1, white on `#c31861` = 5.80:1 (measured) |
| Current site buttons | ✗ Fail | White on `#ff69b4` = 2.65:1 (measured) |
| Current site grey text on pale pink | ✗ Just under | `#6b7280` on `#fff0f5` = 4.38:1 (measured) |
| Fonts cover English | ✓ Pass | Font audit: Cormorant Garamond and Jost. Jost lacks one rare mark (ʻ), not used in our copy |
| Font licences | ✓ Both are Open Font License on Google Fonts | Not re-checked by script in this run |
| Logo file | ✗ Fail | Only PNG files exist (1256 × 848). No vector (SVG) logo in the repo |
| Not mistaken for a competitor | — Not checked | No competitor colour comparison was run |

### Copy problems found in the code (measured)

1. `server/app/posts.php` line 68 still lists **"Love"**, and line 69 says **"6 handmade"** glosses. Love was
   discontinued. There are 5 glosses. This text can go out in the weekly posts.
2. Every gloss is **vanilla** flavored (`docs/faq.html` line 85, all product pages). Any copy that says
   "flavors" (plural) is wrong.
3. `docs/index.html` meta descriptions say **"age 11"**. Ads and the Instagram bio no longer use her age. The site
   still does. Decide if the site should match.
4. Product pages say **"Moisturizing formula"** and **"Lip-safe ingredients."** The ingredient list is not in the
   repo yet, so these are not confirmed from here.
5. `server/app/posts.php` line 83 says the booth takes **cards**. Not confirmed from here.

### Worth keeping (judgement)

- The GB monogram. It is the most grown-up thing the brand owns, and the sparkles give the whole system its one
  decoration.
- The shade names. Pink Paradise, True Love, Sugar Cookie, Gloss Angeles and Glimmer Kiss are short, warm and
  easy to say.
- The honest "sheer" wear note on every product page.

### Three fixes, most important first

1. **Buttons and small pink text → Deep Pink `#c31861`.** White label goes from 2.65:1 to 5.80:1. Cost: site CSS.
2. **Fix the weekly post text** (remove Love, 6 → 5). Cost: two lines in `posts.php`.
3. **Get a vector logo (SVG).** Needed for labels, the booth banner and small sizes. Cost: redraw or trace once.

---

## 1. Visual aesthetic and mood

**In one sentence:** a real beauty counter, with a wink.

**The one idea (judgement):** everything is calm so the gloss can be the colour. Blush ground, black serif
letters, one bottle. The only loud thing is one line of hot pink italic.

| | Rule |
|---|---|
| Mood words | Clean, sophisticated, a little fun |
| Never | Kiddish, bubbly, rainbow, neon, doodles, cartoon hearts |
| Ground | Blush `#f7e1e3`, flat. White is allowed for the website. **No black backgrounds** (Charlie's rule) |
| Light | Light you can feel: direct flash, hard sun through a window, or sculpted studio light. No coloured gels |
| Texture | Real ones only: wet gloss smears, shine, sparkle, reflections in the acrylic. No fake overlays (paper grain, marble, glitter, blur) |
| Shadow | Real shadows from the photo, or a tight contact shadow at the base. No basic floating drop shadow |
| Decoration | The 4-point sparkle from the logo. Black. At most 2 per layout, one big and one small |
| Space | At least a third of every layout stays empty blush |
| The one gradient | A clear-to-pink bar, used only to explain Glimmer Kiss changing colour |

**Gloss finishes, said truthfully**

| Gloss | Colour (from product pages) | Finish | Sparkle? |
|---|---|---|---|
| Pink Paradise | Sheer pink | High shine | No |
| True Love | Sheer dark red | High shine | No |
| Sugar Cookie | Sheer warm brown | High shine | No |
| Gloss Angeles | Clear | High shine | No |
| Glimmer Kiss ($15, limited) | Clear in the bottle, turns pink on lips in a few seconds | High shine | Yes, fine cosmetic sparkle (or no glitter) |

All are 5 ml, vanilla flavored, and sheer. Regular glosses are $12.

---

## 2. Colour palette

| Role | Name | Hex | Use | Contrast (measured) |
|---|---|---|---|---|
| Background | **Blush** | `#f7e1e3` | Ad and post backgrounds, website section bands | — |
| Primary | **Ink** | `#1a1a1a` | Logo, headlines, body text | 13.95:1 on Blush, 17.40:1 on White |
| Accent | **Pop Pink** | `#e6337f` | One italic line in a headline. **Big text only** | 3.27:1 on Blush, 4.08:1 on White |
| Secondary | **Deep Pink** | `#c31861` | Buttons, links, small pink text, prices | 4.65:1 on Blush, 5.80:1 on White |
| Surface | **White** | `#ffffff` | Website page background, cards, button labels | — |
| Surface, soft | **Petal** | `#fff0f5` | Pale website panels (kept from the current site) | — |
| Text, quiet | **Mauve Grey** | `#5c5356` | Captions, fine print | 5.96:1 on Blush, 7.43:1 on White |

**Where the colours came from.** Ink `#1a1a1a` and Petal `#fff0f5` are already in `docs/css/styles.css`.
Pop Pink is the site's `#ff69b4` made deeper so it holds up next to black. Deep Pink is Pop Pink darkened until
small text passed 4.5:1. Mauve Grey replaces the site's cool blue-grey `#6b7280`, which reads cold on blush
(judgement).

**Rules**

- Pop Pink is for **one** thing per layout. If a second thing wants to be pink, use Ink.
- Pop Pink text must be large: 60 px or more on a 1080 px post, 24 px or more on the website.
- Never put Pop Pink text on Petal or Blush at small sizes. Use Deep Pink.
- Retire `#ff69b4`, `#ffb6c1` and `#db7093` when the site is restyled.

**Shade colours, sampled from the product photos (approximate, for swatch dots only)**

| Gloss | Hex |
|---|---|
| Pink Paradise | `#b34662` |
| True Love | `#63070c` |
| Sugar Cookie | `#8f614c` |
| Gloss Angeles | `#c1c6cd` (clear; this is the bottle) |
| Glimmer Kiss | `#b2a3b7` (clear with sparkle; this is the bottle) |

These are the colour in the bottle. The glosses are sheer, so they look much lighter on lips. Never use these
dots to promise a lip colour.

---

## 3. Typography and hierarchy

| Role | Font | Weight | Why |
|---|---|---|---|
| Display (hooks, headlines) | **Cormorant Garamond** | 600 upright, 500 italic | Matches the high-contrast serif in the logo |
| Text (everything you read) | **Jost** | 400 body, 500 labels, 600 emphasis | Clean, even, easy at small sizes |

Both are free (Open Font License). **Self-host the font files on the website.** The site's security header only
allows fonts from our own server, so a Google Fonts link will be blocked. Poppins (in `server/app/fonts`) is
retired when the Post Maker is restyled.

**Measured warning:** Cormorant Garamond has thin hairlines (thin/thick 0.29). They break up at small sizes.
Never set it below 28 px on a 1080 px post or below 20 px on the website. Never use it for paragraphs.

### Sizes for a 1080 × 1080 post

| Level | Font | Size | Line height | Notes |
|---|---|---|---|---|
| Hook, 3 short lines | Cormorant 600 | 124–135 px | 0.95 | Sentence case |
| Hook, 2 lines | Cormorant 600 | 113–119 px | 0.95 | |
| Hook accent line | Cormorant 500 italic | same as hook | 0.95 | Pop Pink. One line only |
| Tagline | Cormorant 500 italic | 54 px | 1.1 | "From a young entrepreneur who's a" |
| Tagline caps | Jost 600 | 39 px, tracking 0.10 em | — | "LIP GLOSS BOSS", Pop Pink |
| Body | Jost 400 | 37 px | 1.4 | Two or three lines at most |
| Step text | Jost 400 with 600 lead-in | 35 px | 1.3 | For how-it-works posts |
| Footer | Jost 500 caps | 27 px, tracking 0.28 em | — | `GLIMMERBLISS.COM` |

For tall posts (1080 × 1920) and pins (1000 × 1500), keep the same sizes. Add space, not bigger letters.

### Sizes for the website

| Level | Font | Size |
|---|---|---|
| Page headline | Cormorant 600 | 44–56 px desktop, 34–40 px phone |
| Section heading | Cormorant 600 | 30–36 px |
| Body | Jost 400 | 17–18 px, line height 1.6 |
| Small and captions | Jost 400 | 14–15 px, Mauve Grey |
| Buttons | Jost 600 | 16 px, tracking 0.04 em |

### Hierarchy rules

- A hook is **2 to 6 words**. If it needs more, it is body text.
- One hook per layout. One italic pink line per hook.
- Headlines are sentence case. All caps only for the footer and "LIP GLOSS BOSS".
- No bold serif, no outlines, no drop shadows on letters.
- Display type is **huge and tracked tight** (about -0.02 em), with dramatic scale contrast against small text.
- The big display word **layers with the product**: in front of it, behind it, or running off the edge.
- Emoji never go inside the picture. They are fine in captions.

---

## 4. Brand voice and copy rules

**Who is talking:** Charlie Mae, a young entrepreneur who makes every gloss by hand. Confident, warm, a little
cheeky. She talks to you like a friend who knows her stuff.

| We are | We are not |
|---|---|
| Confident | Braggy |
| Warm | Gushy |
| Short and clear | Wordy |
| A little cheeky | Babyish or sarcastic |
| Honest about what the gloss does | Hype |

### Things that must always be true

- The glosses are **sheer**. Never promise lipstick colour or full coverage.
- **Only Glimmer Kiss has sparkle.** Never write glimmer, shimmer, sparkle or glitter about another gloss.
- Glimmer Kiss turns pink in **a few seconds**.
- Glimmer Kiss turns pink **on skin**: lips and the back of a hand (JC confirmed, 11 Nov 2026). On glass,
  acrylic or paper it stays clear. Never show or describe a pink smear on a surface.
- Every gloss is **vanilla**. Say "vanilla flavored", never "flavors".
- There are **5** glosses. Love is discontinued.
- Glimmer Kiss is **not the only** pH gloss on the market. Never write "the only", "the first" or "new invention".
- **Never guess ingredients.** No "vitamin E" (not used), no "natural", "organic", "vegan", "clean" or
  "hypoallergenic" until the ingredient list is in the repo.
- No medical or body claims: plumping, healing, hydrating, long-lasting, 24-hour.
- Charlie's privacy: **first name only.** No age, school, town, or photo in ads. Use "young entrepreneur".
  The booth location may be named.
- JC approves every post before it goes anywhere.

### Banned phrases (they sound like a robot wrote them)

elevate · unleash · unlock · radiant · game-changer · must-have · obsessed · indulge · luxurious · revolutionary ·
effortless · stunning · next level · look no further · say goodbye to · introducing · meet your new favorite ·
transform your · the perfect blend of · whether you're… or… · more than just a · in a world where ·
get ready to · your lips will thank you · trust us · run, don't walk · POV:

Also banned: three exclamation marks in a row, ALL-CAPS sentences, more than 2 emoji in a row, and hashtag
walls. Use 3 to 5 hashtags, at the end of the caption.

### Signature lines

- **From a young entrepreneur who's a LIP GLOSS BOSS** (the tagline)
- Be a Lip Gloss Boss.
- Gloss on. Boss up.
- Dream big. Gloss often.
- Big dreams look good in gloss.
- Boss moves. Glossy lips.

### Hooks by gloss

These are starting points. None has been tested with real customers, so nobody knows yet which sells best.

| Gloss | Hooks |
|---|---|
| Glimmer Kiss | Clear in the bottle. Pink on you. · It turns pink. Just for you. · This gloss turns pink. Here's how. · Your pink. Nobody else's. · One gloss. A different pink on everyone. |
| Pink Paradise | Pretty in Pink Paradise. · A sheer pink for every day. |
| True Love | True Love, at first swipe. · Deep red, worn sheer. |
| Sugar Cookie | Sweet as a fresh-baked cookie. · A sheer warm brown that goes with everything. |
| Gloss Angeles | Clear shine. No colour needed. · A little bit of city shine. |
| Any | Handmade in small batches. · Sheer colour. High shine. Vanilla. |

### Instagram

- The picture carries the hook. The caption carries the facts.
- Caption shape: one line that restates the hook → one or two true facts → where to buy.
- First line under 125 characters, so it shows before "more".
- Teach with swipe posts: one idea per picture, 4 pictures at most.
- End with the link or "link in bio". 3 to 5 hashtags.

Example:
> Clear in the bottle. Pink on you. ✨
> Glimmer Kiss reacts with your lips' natural pH and turns pink in a few seconds. Everyone gets their own shade.
> Handmade by Charlie Mae. $15 at glimmerbliss.com
> #lipgloss #phlipgloss #handmadebeauty #smallbusiness

### X

- One idea, under 200 characters, link at the end.
- No hashtags, or one at most. No emoji strings.
- Plain statements work better than questions here (judgement).

Example:
> This gloss goes on clear and turns pink in a few seconds. Everyone gets a different pink. Handmade by a young
> entrepreneur. glimmerbliss.com

---

## 5. Product photography and layout

### How we show a bottle

1. **Cut it out.** The bottle sits straight on the blush. Cut-outs are in `brand/cutouts/`.
2. **Never put a photo in a frame.** No boxes, cards, circles, ovals, arches or rounded "windows". JC's rule:
   it looks cheap.
3. **One hero bottle per layout,** and never a centred sticker cut-out on a plain flat background. Group shots only for "pick your shade" posts, and then all 5 in one straight
   row, evenly spaced.
4. **Angle:** whatever serves the layout, including lying flat or cropped off the edge.
5. **Size:** big. The bottle may run off the edge of the layout.
6. **Place:** off-centre, always. The display word layers with the bottle. Small text stays clear of it.
7. **Shadow:** a real shadow, or a tight contact shadow at the base. No basic drop shadow, no glow.
8. **Never recolour the gloss.** No filters, no saturation boost. The colour in the ad is the colour in the
   bottle.
9. **For ads, the bottle goes into a scene** made with the production pipeline below. A raw cut-out on a flat
   colour is no longer an ad picture.

### Creative Director rules (JC, 11 Nov 2026). These apply to every Instagram and X ad.

**The bar:** treat every ad like a $50,000 luxury beauty campaign. The mood references are Rhode, Glossier, Dior
Lip Glow and Summer Fridays. They are references for quality only. Never copy their layouts, photos, words or
logos.

#### 1. No web-template design

Never, in any ad or social graphic:

- Flat CSS boxes, cards, panels or card grids.
- A **centred sticker cut-out** of the product on a solid background.
- **Basic drop shadows** under the product or the letters.
- **Buttons drawn into the picture** ("Shop Now!"), Canva-style pills, badges, ribbons, starbursts, stock icons.
- Photo frames and windows, black backgrounds, fake texture overlays.

If a draft could be mistaken for a website banner, it fails.

#### 2. Texture and sensory focus

Lip gloss sells through shine, thickness, light and texture. Every concept must be built around a sensory
detail: a wet gloss smear, a glassy highlight, sun or flash bouncing off the acrylic bottle, a drip on the
applicator, the sparkle inside Glimmer Kiss.

The repo's own photos are straight-on studio shots on a grey-blue backdrop. Sensory scenes are made with the
production pipeline below, starting from those real photos.

#### 3. Editorial type and layout

- **Asymmetric magazine layout.** Nothing centred. Weight on one side, real empty space on the other.
- **Dramatic scale contrast.** One display word or line is enormous. Everything else is small.
- **Tight tracking** on display type.
- **Layered text.** Type runs in front of, behind, or across the product. No stacked website text blocks.

#### 4. Copy

Punchy, sharp, insider beauty talk. Short lines. No generic AI marketing words (elevate, unleash, radiant,
must-have, game-changer, and the rest of the list in section 4). Every claim must still be true.

#### 5. Three-concept pitch (before any final asset)

Pitch three clearly different angles, in words, and let JC pick before anything is rendered or generated:

- **Concept A: High-Fashion Editorial.** Minimal, sculptural, studio light.
- **Concept B: Raw Flash / Paparazzi Candid.** Direct flash, 35mm grain, effortless.
- **Concept C: Sensory Macro / ASMR Texture.** Extreme close-up of the gloss, the sparkle, the applicator.

For each: the hook, what we see, the light, the copy, which photos it needs, and whether those photos exist.
Say which one is strongest and why.

#### 6. Self-critique gate (before JC sees any rendered draft)

1. Render the draft and **look at it**.
2. Score it 1 to 10 on a **"Vogue / Sephora editorial"** scale:
   1–4 is a web banner or template, 5–7 is tidy but could be any brand, 8–10 could run in a magazine.
3. Ask: **"Does this look like a web banner?"** If yes, or if the score is under 8, rewrite the concept and
   render again. Do not just nudge sizes.
4. Check it against the banned list in rule 1. Any hit means a redo.
5. Show JC the final score, the reasons, and how many redo rounds it took.

The score is the agent's own judgement. It is not a measurement and it does not predict sales.

### Production pipeline (JC, 11 Nov 2026). Three steps for every Instagram and X ad.

JC approved AI-made scenes on 11 Nov 2026. This replaces the old "real product photos only" rule **for ads**.
The product pages on the website keep the real, untouched photos, so shoppers see the actual item.

#### Step 1. Reference-based hero photography

- **Never use a raw website cut-out on a flat background** as the ad picture.
- Start from our **real product photo** in the repo (`docs/images/<gloss-id>.jpg`, live at
  `https://glimmerbliss.com/images/<gloss-id>.jpg`).
- Use a fal.ai **image-editing / reference** model to place that real bottle into an editorial studio, a macro
  swatch scene, or a sunlit candid scene, with real-looking glass refraction, shadows and texture.
- Never generate the bottle from text alone. Text-only models are for background plates with no product in them.

#### Step 2. Motion and video

- For Reels and X video: pass the approved hero image to a fal.ai **image-to-video** model for a 5-second clip
  with small, slow motion (light moving across the bottle, a slow push-in, shimmer).
- Then add the type, pacing and sound in **Remotion** (`brand/video/`). Type is never generated by the AI model.

#### Step 3. Art director quality gate

Look at every generated image and clip before JC sees it. **Reject and regenerate** when it:

- looks plasticky, waxy or over-smoothed;
- **changes the product**: wrong proportions, wrong cap, wrong body shape, added label, logo or text, wrong
  number of bottles;
- shows something untrue (see below);
- feels like a generic template or stock picture.

Then run the self-critique gate (rule 6 above). Keep rejected takes out of the review folder or mark them
"rejected", and say how many takes it took.

**Truth rules for generated scenes**

- Glimmer Kiss turns pink **on skin**: on lips, and on the back of a hand (JC confirmed, 11 Nov 2026). On
  glass, acrylic, paper or marble it stays **clear with sparkle**. Never show a pink smear on a surface.
- Our bottle has **no label**. A plain white cap and a clear square body. Any label in a result is a reject.
- Only Glimmer Kiss has sparkle. A smear of any other gloss has none.
- No AI-made people's faces. No picture of Charlie, ever. Never send her photo to fal.
- Every AI picture is saved with a `.json` file next to it that says it is AI-made and records the prompt.

#### Which fal model for which job (IDs and prices checked 11 Nov 2026; check again before a big run)

| Job | Model ID | Price | Notes |
|---|---|---|---|
| Hero scene from our photo, draft | `fal-ai/flux-pro/kontext` | $0.04 per image | Draft here first. Aspect 3:4, 9:16, 16:9, 1:1 (no 4:5) |
| Hero scene, higher quality | `fal-ai/flux-pro/kontext/max` | $0.08 per image | Not tried yet |
| Hero scene, final | `fal-ai/nano-banana-pro/edit` | $0.15 per image (4K is double) | Has 4:5. Not tried yet |
| Background plates with no product | `fal-ai/flux-2-pro` | from $0.03 per megapixel | Text-to-image. The ID is `flux-2-pro`, not `flux-2/pro` |
| Cheap plate drafts | `fal-ai/flux/schnell`, `fal-ai/flux/dev` | $0.003 and $0.025 per megapixel | Text-only. They cannot use our photo, so they cannot draft a hero shot |
| Video, 5 seconds | `fal-ai/kling-video/v2.1/pro/image-to-video` | $0.49 for 5 s, then $0.098 per extra second | Not tried yet |
| Video, cheaper | `fal-ai/wan-i2v` | $0.20 at 480p, $0.40 at 720p | The ID is `wan-i2v`. `fal-ai/wan-2.1/image-to-video` does not exist |

Before using a model that is not yet in `tools/fal_generate.py`, read its schema at
`https://fal.ai/models/<model-id>/llms.txt` and add it to the tool. Never guess parameters.

#### Keeping the cost down

- **Draft cheap, finish high.** Test angles and prompts on `fal-ai/flux-pro/kontext` ($0.04). Run the winning
  prompt on a dearer model only if the draft is not good enough.
- **One image per call.** Look at it before making another.
- **Never re-run a call that failed in an unclear way.** A second call is a second charge.
- **Video is the expensive step.** Only animate a hero image that JC has already approved.
- Tell JC the total spent at the end of every job.

#### Winning prompts

When a result nails the look, save its prompt structure in `brand/winning-prompts.md`, with the model, the
aspect ratio and the file it made. Start every new ad, and every new shade, from those prompts, so the whole
campaign looks like one shoot.

### Shooting new photos

The current photos are on a grey-blue backdrop. The cut-outs work, but better source photos will make them
cleaner.

- **Macro texture (most wanted):** a thick smear of each gloss on white paper or glass, the wand pulling out of
  the bottle, a drip on the applicator. As close as the phone will focus. One set for every gloss.
- **Flash shots:** the bottle in a hand, on a vanity, in a makeup bag. Phone flash on, room lights low.
- **Sunlit shots:** bottles on a bathroom counter or windowsill in direct sun, so the acrylic throws real
  reflections and shadows.
- **Clean shots for cut-outs:** plain white or blush paper curved up behind the bottle, window light from one side.
- Angles: straight on, and a three-quarter view. Same height and distance for every gloss.
- Wipe fingerprints off the bottle and cap before every shot.
- Fill the frame with the bottle. Leave a little space all around for cutting out.
- **Most wanted:** a real swatch of each gloss on skin, and Glimmer Kiss before and after it turns pink. The
  coloured dots in the how-it-works post are drawings, and a real photo will teach it better.

### Logo use

- Black on Blush or White only. Never recolour it, stretch it, add a shadow or put it on a photo.
- Keep clear space around it equal to the height of the "G".
- Smallest size: 120 px wide on screen. Below that, use the GB monogram without the words.
- A vector (SVG) version is still needed (see section 0).

---

## 6. Not done yet

- The live website, the weekly Post Maker and the labels still use the old look. Nothing has been changed.
- No vector logo.
- No competitor colour check.
- Print values (CMYK, Pantone) for labels and the booth banner are not set.
- No dark mode. The brand has no black backgrounds, so none is planned.

## Logo on the bottle: the locked position (JC, 11 Nov 2026; Charlie confirmed the centered spot the same day)

One rule for every picture and video, now and later. The numbers are in `brand/design-tokens.json` under
`bottle-logo`. **Never place the logo by eye.** Always use `python tools/bottle_logo.py` (free).

| Part | Rule |
|---|---|
| GLIMMER BLISS | Centered on the clear bottle, both ways. It is **64%** as long as the clear bottle, so there is the **same clear gap (18%) under the cap and at the bottom**. |
| GB | Centered on the white cap, both ways. **Stands upright:** it sits across the cap, a quarter turn from GLIMMER BLISS, top of the letters toward the end of the cap. Its width is **38%** of the cap's length (JC and Charlie, 11 Nov). |
| Direction | **GLIMMER BLISS always reads from the bottom of the bottle toward the cap.** GB stands upright on the cap. Every ad, picture and video, whatever way the bottle is lying (JC, 11 Nov). The bottle is never turned to fix the logo. |
| Which side | The front face (the wide, bright side), not the middle of the whole bottle outline. |

How to use it: find three points on the center line of the front face (where the cap starts, where the cap
meets the clear bottle, and the bottom of the bottle). Give them to the tool. It writes the plate.
Check: the two clear gaps must match within 1% of the bottle length.

Before this rule the gap under the cap was 3%, 11%, 15% and 20% in four set-ups (measured).
When the printed labels arrive, measure a real bottle and change the numbers to match it.

## Layout: the phi grid (JC, 11 Nov 2026)

Nothing sits in the middle. Place things with phi (the golden ratio, 1.618). Numbers are in
`brand/design-tokens.json` under `layout-phi`.

**What a phi grid is.** Two lines across and two lines down, like the rule of thirds, but the lines sit at
**38.2% and 61.8%** of the frame, a little closer to the middle than thirds (33% and 67%). Photographers and
designers put the main subject on a line or where two lines cross.

| Frame | Lines down the page (x) | Lines across the page (y) |
|---|---|---|
| 1080 x 1920 (Reels, Stories) | 413 and 667 | 733 and 1187 |
| 1080 x 1350 (feed) | 413 and 667 | 516 and 834 |
| 1600 x 900 (X) | 611 and 989 | 344 and 556 |

**Our rules**

1. The bottle's center line, or its logo, sits on a phi line. Never on the middle of the frame.
2. Words go on the other side from the bottle. Line up a text edge or a baseline with a phi line.
3. Type sizes step by phi. Small words x 2.618 = headline. Headline x 2.618 = the big word. Example: 40, 104, 272.
4. When two things share a space, split it 61.8 / 38.2, not half and half.
5. If the bottle in a fal picture is too central, enlarge and shift the whole picture in Remotion (the `stage`
   prop) until the bottle's center line is on a phi line. Free, no new clip.

**Be honest about it.** A phi grid is a placing guide. It does not prove a layout is beautiful, and designers
disagree about how much it matters. Still look at the result and judge it.

Worked example: `ReelStudio` in `brand/video/src/Reels.tsx`. Bottle center line at x = 667, the baseline of
"pink." at y = 733, the last line of small words near y = 1187, type sizes 40 / 104 / 272.
