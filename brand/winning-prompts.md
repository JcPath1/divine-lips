# Glimmer Bliss winning prompts

Prompts that passed the art director gate. Start every new ad and every new shade from these, so the campaign
looks like one shoot. Add a prompt here only after its picture passed the gate.

## The structure that works (fal-ai/flux-pro/kontext, reference photo = our real bottle)

1. **"Place this exact lip gloss bottle ..."** then where it sits and how (lying flat, standing on a glass block).
2. **The surface and backdrop:** pale blush pink.
3. **The light:** one hard light source, named (direct morning sun, or studio light from the upper left), plus
   what it does: caustics, refraction through the clear bottle, one long sharp shadow.
4. **The sensory detail** (macro scenes): a long wet streak of transparent, colourless gloss with fine
   iridescent sparkle. Always say "transparent, colourless", or the model makes it pink or white.
5. **Proportions, spelled out:** "long and slender, about three and a half times longer than it is wide, with
   the white cap taking up half of its length". Without this the bottle comes out short.
6. **Keep list:** plain white rectangular cap, clear square body, sparkly gloss and white applicator inside,
   exactly as in the reference.
7. **Ban list:** no label, no text, no logo, no extra objects.
8. **Composition:** where the bottle sits and where the empty space is (for type added later in Remotion or HTML).

## What did not work

- "Lying on a wet sheet of clear acrylic" with "one thick glossy smear": the bottle came out short and the smear
  was an opaque white blob with a notch in it. Fixed by asking for a "long wet streak ... dragged across the
  surface", a view "from directly above", and the proportions line.

## 1. Sunlit macro swatch (Glimmer Kiss)

- File: `ads/campaign-test/01-sunlit-macro-swatch-take2-kontext.jpg`
- Model: `fal-ai/flux-pro/kontext`, aspect 3:4, seed 730121162, $0.04
- Source photo: https://glimmerbliss.com/images/glimmer-kiss.jpg

> Place this exact lip gloss bottle lying flat on its side on a pale blush pink surface in hard direct morning sunlight, photographed from directly above. The bottle is long and slender, about three and a half times longer than it is wide, with the white cap taking up half of its length, exactly as in the reference photo. In front of it, one long wet streak of transparent, colourless lip gloss dragged across the surface, glassy and thick, with fine iridescent sparkle suspended in it and bright specular highlights. Macro beauty photography, sharp sun caustics and light refracting through the clear bottle, crisp real shadows from a window frame. Keep the plain white rectangular cap, clear square body, sparkly gloss and white applicator inside exactly as in the reference. No label, no text, no logo, no extra objects. The bottle sits in the upper right, with empty blush space in the lower left.

## 2. High-fashion studio (Glimmer Kiss)

- File: `ads/campaign-test/02-high-fashion-studio-draft-kontext.jpg`
- Model: `fal-ai/flux-pro/kontext`, aspect 3:4, seed 3434079328, $0.04
- Source photo: https://glimmerbliss.com/images/glimmer-kiss.jpg

> Place this exact lip gloss bottle standing upright on a solid block of clear glass, against a seamless pale blush pink backdrop. Hard sculptural studio light from the upper left throws one long sharp shadow, and light refracts through the clear bottle onto the backdrop. Minimal luxury beauty campaign still life. The bottle sits off-centre in the right third of the frame with a large empty area on the left. Keep the bottle's shape, tall proportions, plain white rectangular cap, clear square body and the sparkly gloss and white applicator inside exactly as in the reference photo. No label, no text, no logo, no extra objects.

## For another shade

Swap the source photo and the gloss description. Only Glimmer Kiss has sparkle, so drop "with fine iridescent
sparkle" for the others and name the real colour from the product page (for example "sheer pink gloss").
