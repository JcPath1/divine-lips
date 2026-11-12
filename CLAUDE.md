# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

## Project Overview

# GlimmerBliss - Custom Lip Gloss Business

## Overview
Online lip gloss business run by two young entrepreneurs (age 10). Handmade, custom lip glosses with creative flavors and premium packaging.

## Founders
- **Charlie** - Co-founder
- **Martina** - Co-founder
- **JC** - Dad/Business Helper

## Background
Charlie and Martina bonded over doing makeup and hair at school. Both are into skincare and facial products. They decided to start an online business selling something fun - custom lip glosses! The brand is **GlimmerBliss** (GlimmerBliss.com).

## Tech Stack (Planned)
- Platform: TBD (Shopify, Etsy, or simple site)
- Social Media: TBD (with parental supervision)

## Product Line
See GLIMMER_BLISS_PROJECT_OUTLINE.md for full details.

## Ads, social graphics and video scripts (MANDATORY)

Whenever you make an ad, a social graphic or a video script for Instagram or X, you MUST follow
`.claude/agents/glimmer-ads.md`. In short:

1. **Read the brand kit first:** `brand/brand-guidelines.md` and `brand/design-tokens.json`. Use its exact
   colors, fonts and voice. Obey its "must always be true" and banned-phrase lists.
2. **Invoke the `frontend-design:frontend-design` skill.** Where its general advice and the brand kit disagree,
   the brand kit wins.
3. **Use real assets and real facts from this repo:** `brand/cutouts/`, `docs/images/`,
   `server/app/catalog.php`, `docs/products/*.html`. Never invent a product detail.
4. **Format for the platform:** Instagram feed 4:5 (1080 × 1350), Stories and Reels 9:16 (1080 × 1920),
   X 16:9 (1600 × 900) with copy under 200 characters.
5. **Creative Director rules (section 5 of the brand kit):**
   - **No web-template design.** No flat CSS boxes, basic drop shadows, centred sticker cut-outs, or buttons
     drawn into the picture ("Shop Now"). Treat every ad like a $50,000 luxury beauty campaign.
   - **Texture and sensory focus.** Build every concept around shine, thickness, light and texture.
   - **Editorial type and layout.** Asymmetric, dramatic scale contrast, tight tracking, type layered with the
     product.
   - **No cliché AI words** (elevate, unleash, radiant, must-have, game-changer). Punchy insider beauty copy,
     and every claim true.
6. **Pitch 3 concepts before any final asset:** A High-Fashion Editorial, B Raw Flash / Paparazzi Candid,
   C Sensory Macro / ASMR Texture. Let JC pick.
7. **Self-critique gate:** look at every rendered draft, score it 1 to 10 on a "Vogue / Sephora editorial"
   scale, and redo anything under 8 or anything that looks like a web banner.

## Production pipeline for every Instagram and X ad (JC, 11 Nov 2026)

Full rules, model IDs and prices: "Production pipeline" in `brand/brand-guidelines.md`.

1. **Reference-based hero photography.** Never use a raw website cut-out on a flat background. Take our real
   product photo from the repo and use a fal.ai image-editing model (`fal-ai/flux-pro/kontext` to draft,
   `fal-ai/nano-banana-pro/edit` for finals) to place the actual bottle into an editorial studio, macro swatch
   or sunlit candid scene. Run it with `python tools/fal_generate.py`. Never generate the bottle from text alone.
2. **Motion and video.** For Reels and X video, pass the approved hero image to a fal.ai image-to-video model
   (`fal-ai/kling-video/v2.1/pro/image-to-video` or `fal-ai/wan-i2v`) for a 5-second clip, then add type,
   pacing and sound in Remotion (`brand/video/`).
3. **Art director quality gate.** Look at every result. Reject and regenerate anything plasticky, anything that
   changes the product (proportions, cap, body, added label or text), anything untrue, or anything that feels
   like a template.

**Truth rules:** Glimmer Kiss is clear with sparkle on any surface and pink only on skin (lips, back of a
hand). The bottle has no label. No AI faces. Never send Charlie's photo to fal.

**Cost rules:** draft on `fal-ai/flux-pro/kontext` ($0.04) first; one image per call; never re-run a call that
failed in an unclear way; animate only a hero image JC approved; report the total spent. Video costs the most
($0.20 to $0.49 a clip), so say the price before making one.

**Winning prompts:** start from `brand/winning-prompts.md`, and add to it whenever a result passes the gate.

The fal key is in `private/fal.env`. Never print it. Review pictures go in `ads/`.


Save drafts in `brand/ads/`. **JC approves every post before it goes anywhere.** Never post, schedule or send
an ad yourself.
