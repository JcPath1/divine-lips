"""Mock-ups for a BACK-of-bottle ingredient sticker. Free. Nothing is ordered or sent.
Run: python brand/sticker/make_back_sticker.py

JC (14 Nov 2026): he may keep the round 0.75 in QR sticker on the bottom and add a new ingredient sticker on
the back. The back sticker carries what a round bottom sticker cannot: the gloss name, "Lip Gloss", net
contents, the ingredient list, and the business name and address.

Bottle facts on file: 3.31 in tall, 0.87 in wide, square (TKB picture, on the site); cap 1.5 in (JC measured,
11 Nov 2026), so the clear body is 1.81 in tall. The logo sticker on the front is 11/16 in wide and leaves
0.09 in of bare bottle on each side; this file keeps the same 0.09 in from the bottle's edges.
NOT measured: how round the bottle's corners are, so the truly flat width of a face is not known.

Avery WePrint: safe margin 1/16 in, smallest text 6 pt, 1/16 in bleed on uploads.
The address is read from private/label-address.txt (git-ignored; the GitHub repo is public).

Writes into brand/sticker/back-sticker/ (git-ignored, the files show the address):
  back-sticker-options.pdf     every size tested, the bottle views, and one mock-up per ingredient list
  print-back-<size>-<gloss>.pdf  true-size print files with 1/16 in bleed, for the sizes that reach 6 pt
  measurements.txt
"""
import os
import re

from reportlab.lib.pagesizes import letter
from reportlab.pdfbase.pdfmetrics import stringWidth
from reportlab.pdfgen import canvas

import make_info_sticker as m

OUT = os.path.join(m.HERE, 'back-sticker')
IN = 72.0
SAFE, BLEED, MIN_PT = m.SAFE, m.BLEED, m.MIN_PT
F, FB = m.F, m.FB
BOTTLE_W, BOTTLE_H, CAP_H = 0.87, 3.31, 1.5
BODY_H = BOTTLE_H - CAP_H
EDGE = (BOTTLE_W - 11 / 16) / 2          # bare bottle left at each side by the logo sticker (0.09 in)
GUTTER = 1 / 16                          # clear strip kept on each side of a corner or of the cap seam
LEAD = 1.12

# Long names may break with a hyphen at these points only. The name itself is not changed.
HYPHEN = {'Hydroxyhydrocinnamate': 'Hydroxyhydro|cinnamate', 'Pentaerythrityl': 'Penta|erythrityl',
          'Polyisobutene': 'Poly|isobutene', 'Hydrogenated': 'Hydro|genated', 'Triglyceride': 'Tri|glyceride',
          'Phenoxyethanol': 'Phenoxy|ethanol', 'Terephthalate': 'Tere|phthalate', 'Polyurethane': 'Poly|urethane',
          'Polyethylene': 'Poly|ethylene', 'Fractionated': 'Fraction|ated', 'Tocopheryl': 'Toco|pheryl',
          'Copolymer': 'Co|polymer', 'Flavorings': 'Flavor|ings', 'Ingredients': 'Ingre|dients'}


def back_text(slug, name, text_map):
    return (name + ' Lip Gloss. Net ' + m.NET + '. Ingredients: ' + text_map[slug] +
            ' Glimmer Bliss, ' + ', '.join(m.ADDRESS) + '.')


def tokens(text):
    """[(piece, join)]  join: ' ' = space before, '' = glued (after a slash), '-' = may break here with a hyphen."""
    out = []
    for word in text.split():
        first = True
        for part in re.findall(r'[^/]+/?', word):
            core = re.match(r'[A-Za-z]+', part)
            pieces = [part]
            if core and core.group(0) in HYPHEN:
                a, b = HYPHEN[core.group(0)].split('|')
                pieces = [a, b + part[len(core.group(0)):]]
            for i, piece in enumerate(pieces):
                out.append((piece, ' ' if first else ('-' if i else '')))
                first = False
    return out


def flow(text, boxes, size, font=F):
    """Flows the text through boxes [(x, y_top, w, h)] in points (y measured down from the label's top).
    Returns [(x, baseline_y, line)] or None if it does not all fit."""
    toks, lines, step = tokens(text), [], size * LEAD
    i = 0
    for bx, by, bw, bh in boxes:
        y = 0.0
        while i < len(toks) and y + step <= bh + 1e-6:
            cur = ''
            while i < len(toks):
                piece, join = toks[i]
                trial = cur + (' ' if join == ' ' and cur else '') + piece
                tail = '-' if i + 1 < len(toks) and toks[i + 1][1] == '-' else ''
                if stringWidth(trial + tail, font, size) <= bw:
                    cur = trial
                    i += 1
                else:
                    break
            if not cur:
                return None                      # one piece is wider than the column
            if i < len(toks) and toks[i][1] == '-':
                cur += '-'
            lines.append((bx, by + y + size * 0.86, cur))
            y += step
    return lines if i >= len(toks) else None


def biggest(text, boxes):
    lo, hi = 0.2, 14.0
    for _ in range(40):
        mid = (lo + hi) / 2
        if flow(text, boxes, mid):
            lo = mid
        else:
            hi = mid
    return lo


def one_face(w_in, h_in):
    return [(SAFE * IN, SAFE * IN, (w_in - 2 * SAFE) * IN, (h_in - 2 * SAFE) * IN)]


def full_height():
    """11/16 x 3 in, the size ordered from Avery for the logo. It crosses the cap seam, so the text is set in two
    blocks with a clear strip at the seam (the sticker is cut there, like the logo sticker)."""
    seam = CAP_H - (BOTTLE_H - 3.0) / 2     # same placement as the logo sticker: centered on the bottle's height
    w = (11 / 16 - 2 * SAFE) * IN
    return [(SAFE * IN, SAFE * IN, w, (seam - GUTTER - SAFE) * IN),
            (SAFE * IN, (seam + GUTTER) * IN, w, (3.0 - seam - GUTTER - SAFE) * IN)]


def wrap_faces(n, h_in):
    """One sticker folded around n faces of the body: one text column per face, clear strip at each corner."""
    w_in = n * BOTTLE_W - 2 * EDGE
    boxes = []
    for k in range(n):
        left = max(k * BOTTLE_W - EDGE + GUTTER, SAFE) if k else SAFE
        right = min((k + 1) * BOTTLE_W - EDGE - GUTTER, w_in - SAFE) if k < n - 1 else w_in - SAFE
        boxes.append((left * IN, SAFE * IN, (right - left) * IN, (h_in - 2 * SAFE) * IN))
    return w_in, boxes


W2, B2 = wrap_faces(2, 1.75)


def back_and_side(inset, h_in=1.75):
    """One sticker on the back and one side. It starts `inset` in from the back's far corner, folds over the
    corner between back and side, and stops 0.09 in before the front corner. Column 1 = back, column 2 = side."""
    w_in = 2 * BOTTLE_W - inset - EDGE
    fold = BOTTLE_W - inset
    return w_in, [(SAFE * IN, SAFE * IN, (fold - GUTTER - SAFE) * IN, (h_in - 2 * SAFE) * IN),
                  ((fold + GUTTER) * IN, SAFE * IN, (w_in - SAFE - fold - GUTTER) * IN, (h_in - 2 * SAFE) * IN)]


def smallest_back_and_side():
    """Narrowest back + side sticker on which the longest list reaches 6 pt. None if even 0.03 in is not enough."""
    text = back_text('pink-paradise', 'Pink Paradise', m.SHORT)
    for k in range(9, 2, -1):                       # inset 0.09 in down to 0.03 in
        w_in, boxes = back_and_side(k / 100)
        if flow(text, boxes, MIN_PT):
            return k / 100, w_in, boxes
    return None


BS = smallest_back_and_side()
W3, B3 = wrap_faces(3, 1.75)
CANDIDATES = [   # key, title, width in, height in, boxes, faces covered, note
    ('a', '11/16 x 1.75 in, back of the body', 11 / 16, 1.75, one_face(11 / 16, 1.75), 1, ''),
    ('b', '3/4 x 1.75 in, back of the body', 0.75, 1.75, one_face(0.75, 1.75), 1, ''),
    ('c', '11/16 x 3 in, back, cap + body (the Avery logo size)', 11 / 16, 3.0, full_height(), 1,
     'cut at the cap seam like the logo sticker'),
    ('d', f'{W2:.2f} x 1.75 in, folded around 2 sides of the body', W2, 1.75, B2, 2, 'back + one side'),
]
if BS:
    CANDIDATES.append(('d2', f'{BS[1]:.2f} x 1.75 in, back + one side, {BS[0]:.2f} in from the far corner', BS[1], 1.75,
                       BS[2], 2, 'back + one side'))
CANDIDATES.append(('e', f'{W3:.2f} x 1.75 in, folded around 3 sides of the body', W3, 1.75, B3, 3, 'back + both sides'))
LISTS = [('pink-paradise', 'Pink Paradise'), ('true-love', 'True Love'), ('gloss-angeles', 'Gloss Angeles'),
         ('glimmer-kiss', 'Glimmer Kiss')]       # Sugar Cookie has the same list as Pink Paradise


def draw_label(c, w_in, h_in, boxes, text, size, edge=True):
    if edge:
        c.saveState()
        c.setLineWidth(0.3)
        c.setStrokeGray(0.55)
        c.setDash(1.2, 1.2)
        c.roundRect(0, 0, w_in * IN, h_in * IN, 2.2, stroke=1, fill=0)
        c.restoreState()
    c.setFillGray(0)
    c.setFont(F, size)
    for x, y, line in flow(text, boxes, size):
        c.drawString(x, h_in * IN - y, line)


def bottle(c, x, y, label=None, title=''):
    """One face of the bottle at true size, lower-left at x, y (points). label = (w_in, h_in, draw, top_in)."""
    c.saveState()
    c.translate(x, y)
    c.setLineWidth(0.5)
    c.setStrokeGray(0.35)
    c.setFillGray(0.93)
    c.rect(0, 0, BOTTLE_W * IN, BODY_H * IN, stroke=1, fill=1)                    # clear body, shown grey
    c.setFillGray(1)
    c.rect(0, BODY_H * IN, BOTTLE_W * IN, CAP_H * IN, stroke=1, fill=1)           # white cap
    if label:
        w_in, h_in, draw, top_in = label
        c.saveState()
        c.translate((BOTTLE_W - w_in) / 2 * IN, (BOTTLE_H - top_in - h_in) * IN)
        c.setFillGray(1)
        c.rect(0, 0, w_in * IN, h_in * IN, stroke=0, fill=1)
        draw(c)
        c.restoreState()
    c.setFillGray(0)
    c.setFont(F, 7.5)
    c.drawCentredString(BOTTLE_W * IN / 2, -11, title)
    c.restoreState()


def bottom_view(c, x, y, draw_round, title):
    c.saveState()
    c.translate(x, y)
    c.setLineWidth(0.5)
    c.setStrokeGray(0.35)
    c.setFillGray(0.93)
    c.rect(0, 0, BOTTLE_W * IN, BOTTLE_W * IN, stroke=1, fill=1)
    off = (BOTTLE_W - m.SIZE) / 2 * IN
    c.translate(off, off)
    c.setFillGray(1)
    c.circle(m.SIZE * IN / 2, m.SIZE * IN / 2, m.SIZE * IN / 2, stroke=1, fill=1)
    draw_round(c)
    c.setFillGray(0)
    c.setFont(F, 7.5)
    c.drawCentredString(m.SIZE * IN / 2, -off - 11, title)
    c.restoreState()


def old_qr(c):
    """Stand-in for the existing round QR sticker: a code for https://glimmerbliss.com (what the pictures in
    'QR Codes/' scan to). The real sticker's artwork is not on file."""
    mat, _ = m.qr_matrix('https://' + m.SITE)
    n = len(mat)
    side = m.SIZE * IN * 0.60
    mod = side / n
    x0 = y0 = (m.SIZE * IN - side) / 2
    c.setFillGray(0)
    for r in range(n):
        for k in range(n):
            if mat[r][k]:
                c.rect(x0 + k * mod, y0 + (n - 1 - r) * mod, mod + 0.02, mod + 0.02, stroke=0, fill=1)


def main():
    os.makedirs(OUT, exist_ok=True)
    slug0, name0 = LISTS[0]
    notes = ['Back-of-bottle ingredient sticker. Bottle on file: 0.87 in wide, 3.31 in tall, cap 1.5 in, body 1.81 in.',
             'Text: gloss name + "Lip Gloss" + net contents + ingredients + business name and address.',
             'Font Arial Narrow, line spacing 1.12. Avery: safe margin 1/16 in, smallest text 6 pt.',
             'Long names may break at a slash or with a hyphen (Hydroxyhydro-cinnamate). No name is shortened.',
             'Address: ' + ('from private/label-address.txt' if m.ADDRESS_ON_FILE else 'PLACEHOLDER'), '',
             'Largest font size that fits (colour numbers cut / colour numbers kept):']
    sizes = {}
    for key, title, w_in, h_in, boxes, faces, note in CANDIDATES:
        notes.append(f'  {key}. {title}')
        for slug, name in m.GLOSSES:
            s_short = biggest(back_text(slug, name, m.SHORT), boxes)
            s_full = biggest(back_text(slug, name, m.FULL), boxes)
            sizes[key, slug] = (s_short, s_full)
            notes.append(f'       {name.ljust(14)} {s_short:.2f} pt / {s_full:.2f} pt')
    notes.append('')
    notes.append('Smallest size where every gloss reaches 6 pt:')
    ok_short = [k for k, *_ in CANDIDATES if all(sizes[k, s][0] >= MIN_PT for s, _ in m.GLOSSES)]
    ok_full = [k for k, *_ in CANDIDATES if all(sizes[k, s][1] >= MIN_PT for s, _ in m.GLOSSES)]
    notes.append('  colour numbers cut:  ' + (', '.join(ok_short) or 'none of these'))
    notes.append('  colour numbers kept: ' + (', '.join(ok_full) or 'none of these'))
    by_key = {c[0]: c for c in CANDIDATES}

    files = 0
    for key, title, w_in, h_in, boxes, faces, note in CANDIDATES:
        for slug, name in m.GLOSSES:
            if sizes[key, slug][0] < MIN_PT:
                continue                      # no print file for a size this gloss cannot use at 6 pt
            path = os.path.join(OUT, f'print-back-{key}-{w_in:.2f}x{h_in:.2f}in-{slug}.pdf')
            c = canvas.Canvas(path, pagesize=((w_in + 2 * BLEED) * IN, (h_in + 2 * BLEED) * IN))
            c.translate(BLEED * IN, BLEED * IN)
            draw_label(c, w_in, h_in, boxes, back_text(slug, name, m.SHORT), MIN_PT, edge=False)
            c.save()
            files += 1

    W, H = letter
    c = canvas.Canvas(os.path.join(OUT, 'back-sticker-options.pdf'), pagesize=letter)
    c.setTitle('Glimmer Bliss back-of-bottle ingredient sticker, true size')

    def head(title, sub):
        c.setFillGray(0)
        c.setFont(FB, 14)
        c.drawString(0.6 * IN, H - 0.6 * IN, title)
        c.setFont(F, 9)
        for i, line in enumerate(sub):
            c.drawString(0.6 * IN, H - 0.8 * IN - i * 11, line)
        c.setLineWidth(1)
        c.line(6.9 * IN, H - 0.45 * IN, 7.9 * IN, H - 0.45 * IN)
        c.line(6.9 * IN, H - 0.41 * IN, 6.9 * IN, H - 0.49 * IN)
        c.line(7.9 * IN, H - 0.41 * IN, 7.9 * IN, H - 0.49 * IN)
        c.setFont(F, 7)
        c.drawCentredString(7.4 * IN, H - 0.39 * IN, '1 inch')

    # page 1: every size tested, true size, Pink Paradise
    head('Back sticker: every size tested, TRUE SIZE (' + name0 + ', the longest list)',
         ['Print at 100% ("Actual size"); the bar must measure 1 inch. Each label is drawn at the largest type that fits.',
          'Avery\'s smallest type is 6 pt. Dashed line = sticker edge. Ingredient order is still a draft.'])
    x, top = 0.6 * IN, H - 1.45 * IN
    for key, title, w_in, h_in, boxes, faces, note in CANDIDATES:
        if x + w_in * IN > W - 0.5 * IN:
            x, top = 0.6 * IN, top - 3.75 * IN
        s = sizes[key, slug0][0]
        verdict = 'OK' if all(sizes[key, sl][0] >= MIN_PT for sl, _ in m.GLOSSES) else 'too small'
        c.saveState()
        c.translate(x, top - 0.3 * IN - h_in * IN)
        draw_label(c, w_in, h_in, boxes, back_text(slug0, name0, m.SHORT), s)
        c.restoreState()
        c.setFont(FB, 9)
        c.drawString(x, top, key + '.  ' + f'{s:.1f} pt  ' + verdict)
        c.setFont(F, 7.5)
        c.drawString(x, top - 0.15 * IN, f'{w_in:.2f} x {h_in:.2f} in, {faces} side' + ('s' if faces > 1 else ''))
        x += w_in * IN + 0.3 * IN
    c.showPage()

    # page 2: the bottle
    pick = ok_short[0] if ok_short else None
    if pick:
        _, title, w_in, h_in, boxes, faces, note = by_key[pick]
        head('How the bottle would look, TRUE SIZE (' + name0 + ')',
             ['Back sticker: option ' + pick + ', ' + title + ', 6 pt. Grey = clear body (gloss shows through), white = cap.',
              'The bottom sticker is 0.75 in round on the 0.87 in bottom. The old QR sticker\'s real artwork is not on file; '
              'a code for glimmerbliss.com stands in for it.'])
        text = back_text(slug0, name0, m.SHORT)
        y0 = H - 1.5 * IN - BOTTLE_H * IN
        bottle(c, 0.7 * IN, y0, None, 'FRONT: logo sticker (not drawn)')
        names = ['SIDE', 'BACK', 'OTHER SIDE'] if faces == 3 else ['BACK', 'SIDE']
        x = 2.3 * IN
        for k in range(faces):
            bx, by, bw, bh = boxes[k]
            col = [(SAFE * IN if faces > 1 else bx, by, bw, bh)]
            shown_w = bw / IN + 2 * SAFE if faces > 1 else w_in

            def draw(cc, k=k, col=col, shown_w=shown_w):
                lines = flow(text, boxes, MIN_PT)
                cc.setFillGray(0)
                cc.setFont(F, MIN_PT)
                for lx, ly, line in lines:
                    if abs(lx - boxes[k][0]) < 0.01:
                        cc.drawString(col[0][0], h_in * IN - ly, line)
            bottle(c, x, y0, (shown_w, h_in, draw, CAP_H + (BODY_H - h_in) / 2), names[k] + ': new sticker')
            x += 1.6 * IN
        bottom_view(c, 0.7 * IN, y0 - 1.6 * IN, old_qr, 'BOTTOM, choice 1: the old round QR sticker')
        bottom_view(c, 3.4 * IN, y0 - 1.6 * IN, lambda cc: m.stacked(cc, 'round', m.round_rows(name0)),
                    'BOTTOM, choice 2: round info sticker')
        c.saveState()
        c.translate(5.6 * IN, y0 - 1.6 * IN - 0.9 * IN)
        c.scale(3, 3)
        c.setLineWidth(0.2)
        c.circle(m.SIZE * IN / 2, m.SIZE * IN / 2, m.SIZE * IN / 2, stroke=1, fill=0)
        m.stacked(c, 'round', m.round_rows(name0))
        c.restoreState()
        c.setFont(F, 7.5)
        c.drawString(5.6 * IN, y0 - 1.6 * IN - 0.9 * IN - 11, 'round info sticker, 3x larger')
        c.showPage()

        # one page per ingredient list: true size + 3x
        for slug, name in LISTS:
            same = ' (Sugar Cookie has the same list)' if slug == 'pink-paradise' else ''
            head('Back sticker, option ' + pick + ': ' + name + same,
                 [title + ', 6 pt. Left: TRUE SIZE. Right: the same art 3x larger.',
                  'Cut from the web list: the colour numbers (CI ...) and the allergy note. Nothing else.'])
            t = back_text(slug, name, m.SHORT)
            c.saveState()
            c.translate(0.6 * IN, H - 1.4 * IN - h_in * IN)
            draw_label(c, w_in, h_in, boxes, t, MIN_PT)
            c.restoreState()
            k = min(3.0, (W - 1.2 * IN) / (w_in * IN), (H - 4.2 * IN) / (h_in * IN))
            c.saveState()
            c.translate(0.6 * IN, H - 1.7 * IN - h_in * IN - h_in * IN * k)
            c.scale(k, k)
            draw_label(c, w_in, h_in, boxes, t, MIN_PT)
            c.restoreState()
            c.setFont(F, 8)
            c.drawString(0.6 * IN, 0.5 * IN, f'Enlarged {k:.1f}x. Largest type this list could use on this size: {sizes[pick, slug][0]:.1f} pt.')
            c.showPage()
    head('Measurements', [])
    y = H - 1.0 * IN
    c.setFont(FB, 10)
    c.drawString(0.6 * IN, y, 'Measurements')
    c.setFont('Courier', 6.2)
    for i, line in enumerate(notes):
        c.drawString(0.6 * IN, y - 12 - i * 7.3, line)

    c.save()
    open(os.path.join(OUT, 'measurements.txt'), 'w', encoding='utf-8').write('\n'.join(notes) + '\n')
    print('\n'.join(notes))
    print(files, 'print files; pick =', pick)


if __name__ == '__main__':
    main()
