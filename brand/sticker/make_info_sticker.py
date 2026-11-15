"""Mock-ups for the bottle-BOTTOM info sticker (ingredients + company info). Free. Nothing is ordered or sent.
Run: python brand/sticker/make_info_sticker.py

JC (14 Nov 2026): reuse the 0.75 in sticker that carried the website QR code. The repo has no record of that
sticker's shape, material or print setup (only the QR pictures in "QR Codes/"), so every option is drawn both
ROUND and SQUARE at 0.75 in.

Ingredient text is read from the five gloss pages (docs/products/*.html), so the sticker and the site match.
Nothing is invented: the business address is not on file and is drawn as a clearly marked placeholder.

Avery WePrint facts used (NEXT_STEPS.md): safe margin 1/16 in, smallest text 6 pt, uploads need 1/16 in of
extra page on every side (bleed).

Writes into brand/sticker/info-sticker/:
  info-sticker-options.pdf        one letter page: every option at TRUE SIZE and 5x enlarged. Print at 100%.
  print-<option>-<gloss>.pdf      true-size print files with 1/16 in bleed
  measurements.txt                the measured font sizes
"""
import html
import math
import os
import re

import qrcode
from reportlab.lib.pagesizes import letter
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.pdfmetrics import stringWidth
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.join(HERE, '..', '..')
OUT = os.path.join(HERE, 'info-sticker')
IN = 72.0
SIZE = 0.75            # sticker, inches (JC)
SAFE = 1 / 16          # Avery safe margin
BLEED = 1 / 16         # Avery bleed
MIN_PT = 6.0           # Avery's smallest text
BACK_W, BACK_H = 11 / 16, 1.75   # a label on the clear body: same width as the logo sticker, body is 1.81 in tall

pdfmetrics.registerFont(TTFont('Narrow', 'C:/Windows/Fonts/ARIALN.TTF'))
pdfmetrics.registerFont(TTFont('NarrowB', 'C:/Windows/Fonts/ARIALNB.TTF'))
F, FB = 'Narrow', 'NarrowB'

GLOSSES = [('pink-paradise', 'Pink Paradise'), ('sugar-cookie', 'Sugar Cookie'), ('true-love', 'True Love'),
           ('gloss-angeles', 'Gloss Angeles'), ('glimmer-kiss', 'Glimmer Kiss')]
SITE = 'glimmerbliss.com'
NET = '5 ml'                              # as sold on the site. Weight vs volume is an open question (ingredients-tkb.md)
# The business address for labels (JC, 14 Nov 2026) is kept OUT of this file: the GitHub repo is public.
# It is read from private/label-address.txt (git-ignored): line 1 = street, line 2 = city, state ZIP.
ADDRESS_FILE = os.path.join(ROOT, 'private', 'label-address.txt')
if os.path.exists(ADDRESS_FILE):
    ADDRESS = [ln.strip() for ln in open(ADDRESS_FILE, encoding='utf-8') if ln.strip()]
    ADDRESS_ON_FILE = True
else:
    ADDRESS = ['[STREET ADDRESS]', '[CITY, ST ZIP]']   # placeholder, not a real address
    ADDRESS_ON_FILE = False


def page_ingredients(slug):
    """Returns the ingredient paragraphs of a gloss page as plain text."""
    raw = open(os.path.join(ROOT, 'docs', 'products', slug + '.html'), encoding='utf-8').read()
    paras = re.findall(r'<p class="ingredients-list">(.*?)</p>', raw, re.S)
    if not paras:
        raise SystemExit(slug + ': no ingredients on the page')
    return [html.unescape(re.sub(r'<[^>]+>', '', p)).strip() for p in paras]


def shorten(text):
    """The only cuts made: colour index numbers in brackets. Every ingredient name stays."""
    text = re.sub(r'\s*\(CI [^)]*\)', '', text)
    return re.sub(r'\s+', ' ', text).strip()


FULL = {s: ' '.join(page_ingredients(s)) for s, _ in GLOSSES}
SHORT = {s: shorten(FULL[s]) for s, _ in GLOSSES}


def everything(slug, name, ingredients):
    return ('GLIMMER BLISS ' + name + ' Lip Gloss. Net ' + NET + '. ' + ', '.join(ADDRESS) + '. ' + SITE +
            '. Ingredients: ' + ingredients)


# ---------- layout ----------
def line_width(shape, w, h, y_top, y_bot):
    """Usable width (pt) of a text line whose box spans y_top..y_bot, measured down from the top of the safe area."""
    if shape == 'square':
        return w
    r = w / 2
    far = max(abs(r - y_top), abs(r - y_bot))
    return 0.0 if far >= r else 2 * math.sqrt(r * r - far * far)


def wrap(text, font, size, shape, w, h, lead=1.12):
    """Greedy wrap into the safe area. Returns the lines [(y_baseline_from_top, text)] or None if it does not fit."""
    # long names such as Ethylene/Propylene/Styrene may break after a slash; \x00 marks "no space before this piece"
    words = []
    for word in text.split():
        parts = re.findall(r'[^/]+/?', word)
        words.append(parts[0])
        words.extend('\x00' + p for p in parts[1:])
    lines, y = [], 0.0
    step = size * lead
    while words:
        if y + step > h + 1e-6:
            return None
        avail = line_width(shape, w, h, y, y + step)
        cur = ''
        while words:
            nxt = words[0]
            trial = cur + nxt[1:] if nxt.startswith('\x00') else (cur + ' ' + nxt).strip()
            if stringWidth(trial, font, size) <= avail:
                cur = trial
                words.pop(0)
            else:
                break
        if cur:
            lines.append((y + size * 0.86, cur))
        elif shape == 'square':
            return None                      # a single word is wider than the label
        y += step
    return lines


def biggest(text, font, shape, w, h):
    lo, hi = 0.2, 14.0
    for _ in range(40):
        mid = (lo + hi) / 2
        if wrap(text, font, mid, shape, w, h):
            lo = mid
        else:
            hi = mid
    return lo


def outline(c, shape, w, h, dash=True):
    c.saveState()
    c.setLineWidth(0.3)
    c.setStrokeGray(0.55)
    if dash:
        c.setDash(1.2, 1.2)
    if shape == 'round':
        c.circle(w / 2, h / 2, w / 2, stroke=1, fill=0)
    else:
        c.roundRect(0, 0, w, h, 2.2, stroke=1, fill=0)
    c.restoreState()


def draw_lines(c, lines, font, size, w_full, h_full, safe_w, top):
    """Draws wrapped lines centered. top = distance (pt) from the label's top edge to the top of the text block."""
    c.setFont(font, size)
    for y, t in lines:
        c.drawCentredString(w_full / 2, h_full - top - y, t)


# ---------- the options (each draws one label with its lower-left corner at 0,0, in points) ----------
def opt_everything(c, shape, slug, name, text_map, w=SIZE * IN, h=SIZE * IN):
    safe_w, safe_h = w - 2 * SAFE * IN, h - 2 * SAFE * IN
    text = everything(slug, name, text_map[slug])
    size = biggest(text, F, shape, safe_w, safe_h)
    lines = wrap(text, F, size, shape, safe_w, safe_h)
    c.setFillGray(0)
    draw_lines(c, lines, F, size, w, h, safe_w, SAFE * IN)
    return size


def stacked(c, shape, rows, w=SIZE * IN, h=SIZE * IN):
    """rows = [(text, font, size)]. Draws them centered as a block. Returns False if a row is too wide."""
    lead = 1.14
    total = sum(s * lead for _, _, s in rows)
    safe = w - 2 * SAFE * IN
    y = (h - total) / 2                      # from the label's top edge
    ok = total <= safe + 1e-6
    for text, font, size in rows:
        top_in_safe = y - SAFE * IN
        avail = line_width(shape, safe, safe, top_in_safe, top_in_safe + size * lead)
        if stringWidth(text, font, size) > avail + 1e-6:
            ok = False
        c.setFont(font, size)
        c.setFillGray(0)
        c.drawCentredString(w / 2, h - y - size * 0.86, text)
        y += size * lead
    return ok


def round_rows(name):
    # short rows at the top and bottom, where a circle is narrow. No room for an address.
    return [('Lip Gloss', F, MIN_PT), ('GLIMMER BLISS', FB, MIN_PT), (SITE, F, MIN_PT), (name, F, MIN_PT),
            ('Net ' + NET, F, MIN_PT)]


CARD_W, CARD_H = 3.5, 2.0              # a business-card size insert


def opt_card(c, slug, name, text_map, w=CARD_W * IN, h=CARD_H * IN):
    """Header: brand left, gloss + net contents right. Ingredients in the middle. Name, address, website on the bottom line."""
    m = 0.15 * IN
    c.setFillGray(0)
    c.setFont(FB, 11)
    c.drawString(m, h - m - 9, 'GLIMMER BLISS')
    c.setFont(F, 9)
    c.drawRightString(w - m, h - m - 9, name + ' Lip Gloss   Net ' + NET)
    foot = 'Glimmer Bliss, ' + ', '.join(ADDRESS) + '   ' + SITE
    fsize = 7.5
    assert stringWidth(foot, F, fsize) <= w - 2 * m, 'card footer too wide'
    c.setFont(F, fsize)
    c.drawString(m, m, foot)
    text = 'Ingredients: ' + text_map[slug]
    top = m + 20
    room = h - top - m - fsize - 6
    size = min(biggest(text, F, 'square', w - 2 * m, room), 9.0)
    c.setFont(F, size)
    for y, t in wrap(text, F, size, 'square', w - 2 * m, room):
        c.drawString(m, h - top - y, t)
    return size


def company_rows(name, with_address=True):
    rows = [('GLIMMER BLISS', FB, MIN_PT), (name, F, MIN_PT), ('Lip Gloss  Net ' + NET, F, MIN_PT)]
    if with_address:
        rows += [(a, F, MIN_PT) for a in ADDRESS]
    rows.append((SITE, F, MIN_PT))
    return rows


def qr_matrix(url):
    q = qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_M, border=0, box_size=1)
    q.add_data(url)
    q.make(fit=True)
    return q.get_matrix(), q.version


def gloss_url(slug):
    return 'https://' + SITE + '/products/' + slug + '.html'


def opt_qr(c, shape, slug, name, w=SIZE * IN, h=SIZE * IN):
    """Brand name + gloss name + net contents, and a QR code to that gloss's page. Returns (module_mm, version)."""
    m, version = qr_matrix(gloss_url(slug))
    n = len(m)
    quiet = 2                                           # modules of white kept around the code
    text_h = MIN_PT * 1.14 * 2
    if shape == 'square':
        side = h - 2 * SAFE * IN - text_h - 1.0          # code + quiet zone
    else:
        # largest square that fits under two text lines inside the round safe area
        r = w / 2 - SAFE * IN
        side = 0.0
        for k in range(400, 0, -1):
            s = k / 400 * 2 * r
            top = r - (text_h * 0.55)                    # code starts just under the text block
            bottom = top - s
            if abs(bottom) < r and math.hypot(s / 2, bottom) <= r and math.hypot(s / 2, top) <= r:
                side = s
                break
    mod = side / (n + 2 * quiet)
    code = mod * n
    c.setFillGray(0)
    if shape == 'square':
        y_text = h - SAFE * IN
        c.setFont(FB, MIN_PT)
        c.drawCentredString(w / 2, y_text - MIN_PT * 0.86, 'GLIMMER BLISS')
        c.setFont(F, MIN_PT)
        c.drawCentredString(w / 2, y_text - MIN_PT * 1.14 - MIN_PT * 0.86, name + '  ' + NET)
        x0, y0 = (w - code) / 2, SAFE * IN + mod * quiet
    else:
        r = w / 2 - SAFE * IN
        top = h / 2 + r - (text_h * 0.55)
        c.setFont(FB, 5.0)
        c.drawCentredString(w / 2, h / 2 + r - 5.0 * 0.86 - 1.2, 'GLIMMER BLISS')
        x0, y0 = (w - code) / 2, top - mod * quiet - code
        c.setFont(F, 4.2)
        c.drawCentredString(w / 2, y0 - mod * quiet - 4.0, name + ' ' + NET)
    for row in range(n):
        for col in range(n):
            if m[row][col]:
                c.rect(x0 + col * mod, y0 + (n - 1 - row) * mod, mod + 0.02, mod + 0.02, stroke=0, fill=1)
    return mod / IN * 25.4, version


def opt_back(c, slug, name, text_map, w=BACK_W * IN, h=BACK_H * IN):
    """A second label for the clear body of the bottle: everything, as large as it will go."""
    safe_w, safe_h = w - 2 * SAFE * IN, h - 2 * SAFE * IN
    text = everything(slug, name, text_map[slug])
    size = biggest(text, F, 'square', safe_w, safe_h)
    lines = wrap(text, F, size, 'square', safe_w, safe_h)
    c.setFillGray(0)
    c.setFont(F, size)
    for y, t in lines:
        c.drawString(SAFE * IN, h - SAFE * IN - y, t)
    return size


def height_needed(text, size, w_in):
    """Height (in) a label w_in wide needs to hold the text at this size."""
    h = 0.2
    while not wrap(text, F, size, 'square', (w_in - 2 * SAFE) * IN, (h - 2 * SAFE) * IN):
        h += 0.01
        if h > 30:
            return None                      # one word is wider than the label at this size
    return h


# ---------- output ----------
def print_file(name, draw, w_in=SIZE, h_in=SIZE):
    path = os.path.join(OUT, name)
    c = canvas.Canvas(path, pagesize=((w_in + 2 * BLEED) * IN, (h_in + 2 * BLEED) * IN))
    c.setTitle(name)
    c.translate(BLEED * IN, BLEED * IN)
    draw(c)
    c.save()
    return path


def main():
    os.makedirs(OUT, exist_ok=True)
    notes = []
    longest = max(GLOSSES, key=lambda g: len(FULL[g[0]]))
    notes.append('Longest ingredient list: ' + longest[1] + ' (' + str(len(FULL[longest[0]])) + ' characters; ' +
                 str(len(SHORT[longest[0]])) + ' after cutting the colour numbers)')
    notes.append('Sticker 0.75 in, safe area 0.625 in (Avery safe margin 1/16 in). Avery smallest text: 6 pt. Font: Arial Narrow.')
    notes.append('')
    notes.append('Font size needed to fit name + company info + ingredients on the 0.75 in sticker:')
    scratch = canvas.Canvas(os.path.join(OUT, '_scratch.pdf'))
    for slug, name in GLOSSES:
        row = '  ' + name.ljust(14)
        for label, tm in (('full', FULL), ('shortened', SHORT)):
            for shape in ('square', 'round'):
                row += f'{label} {shape}: {opt_everything(scratch, shape, slug, name, tm):.2f} pt   '
        notes.append(row)
    notes.append('')
    notes.append('Label on the clear body, 11/16 x 1.75 in, everything (shortened list): font size that fits')
    for slug, name in GLOSSES:
        size = opt_back(scratch, slug, name, SHORT)
        need = height_needed(everything(slug, name, SHORT[slug]), MIN_PT, BACK_W)
        tail = (f'At 6 pt an 11/16 in wide label would have to be {need:.2f} in tall.' if need else
                'At 6 pt it cannot be set on an 11/16 in wide label: one ingredient name is wider than the label.')
        notes.append(f'  {name.ljust(14)} {size:.2f} pt.  ' + tail)
    notes.append('')
    for w_in in (1.5, 2.0, 2.5):
        worst = max(height_needed(everything(s, n, SHORT[s]), MIN_PT, w_in) for s, n in GLOSSES)
        notes.append(f'  At 6 pt, a label {w_in} in wide needs to be {worst:.2f} in tall for the longest list.')
    notes.append('')
    notes.append('Company-info-only sticker at 6 pt (does every row fit inside the safe area?):')
    for shape in ('square', 'round'):
        for addr in (True, False):
            ok = all(stacked(scratch, shape, company_rows(n, addr)) for _, n in GLOSSES)
            notes.append(f'  {shape}, 6 rows {"with" if addr else "without"} the 2 address lines: {"fits" if ok else "DOES NOT FIT"}')
    ok = all(stacked(scratch, 'round', round_rows(n)) for _, n in GLOSSES)
    notes.append(f'  round, 5 rows (Lip Gloss / GLIMMER BLISS / website / gloss name / Net 5 ml): {"fits" if ok else "DOES NOT FIT"}')
    safe_pt = (SIZE - 2 * SAFE) * IN
    notes.append(f'  Address: {"from private/label-address.txt" if ADDRESS_ON_FILE else "PLACEHOLDER, none on file"}. '
                 f'Safe width is {safe_pt:.1f} pt. Width of each line at 6 pt:')
    for line in ADDRESS:
        wpt = stringWidth(line, F, MIN_PT)
        notes.append(f'    "{line}": {wpt:.1f} pt ({"fits" if wpt <= safe_pt else "TOO WIDE"}, {safe_pt - wpt:+.1f} pt to spare)')
    rows_h = len(company_rows('x', True)) * MIN_PT * 1.14
    notes.append(f'    6 rows at 6 pt are {rows_h:.1f} pt tall; safe height is {safe_pt:.1f} pt.')
    notes.append('')
    notes.append('Ingredients card, 3.5 x 2 in: font size of the full ingredient list (capped at 9 pt)')
    for slug, name in GLOSSES:
        notes.append(f'  {name.ljust(14)} {opt_card(scratch, slug, name, FULL):.2f} pt')
    notes.append('')
    notes.append('QR option (code links to the gloss page, error correction M):')
    for shape in ('square', 'round'):
        for slug, name in GLOSSES:
            mm, ver = opt_qr(scratch, shape, slug, name)
            notes.append(f'  {shape} {name.ljust(14)} QR version {ver}, one dot = {mm:.3f} mm   {gloss_url(slug)}')

    # print files, true size with bleed
    files = []
    for slug, name in GLOSSES:
        for shape in ('square', 'round'):
            files.append(print_file(f'print-1-everything-{shape}-{slug}.pdf',
                                    lambda c, sh=shape, s=slug, n=name: opt_everything(c, sh, s, n, SHORT)))
            files.append(print_file(f'print-3-qr-{shape}-{slug}.pdf',
                                    lambda c, sh=shape, s=slug, n=name: opt_qr(c, sh, s, n)))
        files.append(print_file(f'print-2-company-info-square-{slug}.pdf',
                                lambda c, n=name: stacked(c, 'square', company_rows(n, True))))
        files.append(print_file(f'print-2-company-info-round-{slug}.pdf',
                                lambda c, n=name: stacked(c, 'round', round_rows(n))))
        files.append(print_file(f'print-5-ingredients-card-{slug}.pdf',
                                lambda c, s=slug, n=name: opt_card(c, s, n, FULL), CARD_W, CARD_H))
        files.append(print_file(f'print-4-body-label-{slug}.pdf',
                                lambda c, s=slug, n=name: opt_back(c, s, n, SHORT), BACK_W, BACK_H))

    # overview sheet
    slug, name = longest
    path = os.path.join(OUT, 'info-sticker-options.pdf')
    c = canvas.Canvas(path, pagesize=letter)
    c.setTitle('Glimmer Bliss bottom sticker options, true size')
    W, H = letter
    c.setFont(FB, 14)
    c.drawString(0.6 * IN, H - 0.6 * IN, 'Glimmer Bliss bottom sticker, 0.75 in: options (' + name + ' shown, the longest list)')
    c.setFont(F, 9)
    c.drawString(0.6 * IN, H - 0.8 * IN, 'Print at 100% ("Actual size"). The bar on the right must measure exactly 1 inch. '
                 'Small drawings are TRUE SIZE; big ones are the same art 3x larger.')
    c.drawString(0.6 * IN, H - 0.95 * IN, 'Dashed line = sticker edge. Ingredient order is still a draft. '
                 + ('' if ADDRESS_ON_FILE else 'The address in [brackets] is a placeholder: none on file.'))
    c.setLineWidth(1)
    c.line(6.9 * IN, H - 1.12 * IN, 7.9 * IN, H - 1.12 * IN)
    c.line(6.9 * IN, H - 1.08 * IN, 6.9 * IN, H - 1.16 * IN)
    c.line(7.9 * IN, H - 1.08 * IN, 7.9 * IN, H - 1.16 * IN)
    c.setFont(F, 7)
    c.drawCentredString(7.4 * IN, H - 1.06 * IN, '1 inch')

    K = 3.0                                            # enlargement on the sheet

    def cell(x, y, title, sub, draw, shape, w_in=SIZE, h_in=SIZE):
        c.setFillGray(0)
        c.setFont(FB, 9.5)
        c.drawString(x, y + 12, title)
        c.setFont(F, 8)
        for i, line in enumerate(sub):
            c.drawString(x, y - i * 9.5, line)
        yy = y - len(sub) * 9.5 - h_in * IN * K - 4
        c.saveState()                                   # true size
        c.translate(x, yy + (h_in * IN * K - h_in * IN))
        outline(c, shape, w_in * IN, h_in * IN)
        draw(c)
        c.restoreState()
        c.saveState()                                   # enlarged
        c.translate(x + w_in * IN + 14, yy)
        c.scale(K, K)
        outline(c, shape, w_in * IN, h_in * IN)
        draw(c)
        c.restoreState()

    s1 = opt_everything(scratch, 'square', slug, name, SHORT)
    s1r = opt_everything(scratch, 'round', slug, name, SHORT)
    mm_s, ver = opt_qr(scratch, 'square', slug, name)
    mm_r, _ = opt_qr(scratch, 'round', slug, name)
    sb = opt_back(scratch, slug, name, SHORT)
    col = [0.6 * IN, 4.4 * IN]
    row = [H - 1.45 * IN, H - 4.45 * IN, H - 7.45 * IN]
    cell(col[0], row[0], '1. Everything on the sticker, square', [f'Text has to shrink to {s1:.1f} pt. Not readable.'],
         lambda cc: opt_everything(cc, 'square', slug, name, SHORT), 'square')
    cell(col[1], row[0], '1. Everything on the sticker, round', [f'Text has to shrink to {s1r:.1f} pt. Not readable.'],
         lambda cc: opt_everything(cc, 'round', slug, name, SHORT), 'round')
    cell(col[0], row[1], '2. Company info only, square, 6 pt', ['No ingredients (card on page 2, or website). Address fits at 6 pt.'],
         lambda cc: stacked(cc, 'square', company_rows(name, True)), 'square')
    cell(col[1], row[1], '2. Company info only, round, 6 pt', ['No ingredients. No room for an address on a round one.'],
         lambda cc: stacked(cc, 'round', round_rows(name)), 'round')
    cell(col[0], row[2], '3. Name + QR code to the ingredients, square', [f'One QR dot = {mm_s:.2f} mm. No address.'],
         lambda cc: opt_qr(cc, 'square', slug, name), 'square')
    cell(col[1], row[2], '3. Name + QR code to the ingredients, round', [f'One QR dot = {mm_r:.2f} mm. No address.'],
         lambda cc: opt_qr(cc, 'round', slug, name), 'round')
    c.showPage()

    sc = opt_card(scratch, slug, name, FULL)
    c.setFont(FB, 14)
    c.drawString(4.2 * IN, H - 0.7 * IN, '5. Ingredients card, 3.5 x 2 in, true size')
    c.setFont(F, 9)
    c.drawString(4.2 * IN, H - 0.9 * IN, f'Full list with the colour numbers, at {sc:.1f} pt. Goes in the bag or box.')
    c.saveState()
    c.translate(4.2 * IN, H - 1.2 * IN - CARD_H * IN)
    outline(c, 'square', CARD_W * IN, CARD_H * IN)
    opt_card(c, slug, name, FULL)
    c.restoreState()
    c.setFillGray(0)
    c.setFont(FB, 14)
    c.drawString(0.6 * IN, H - 0.7 * IN, '4. Label on the clear body (' + name + ')')
    c.setFont(F, 9)
    c.drawString(0.6 * IN, H - 0.9 * IN, f'11/16 x 1.75 in. Fits only at {sb:.1f} pt (Avery minimum 6 pt). True size, then 3x.')
    c.saveState()
    c.translate(0.6 * IN, H - 1.2 * IN - BACK_H * IN)
    outline(c, 'square', BACK_W * IN, BACK_H * IN)
    opt_back(c, slug, name, SHORT)
    c.restoreState()
    c.saveState()
    c.translate(1.5 * IN, H - 1.2 * IN - BACK_H * IN * 3)
    c.scale(3, 3)
    outline(c, 'square', BACK_W * IN, BACK_H * IN)
    opt_back(c, slug, name, SHORT)
    c.restoreState()
    y = H - 1.2 * IN - BACK_H * IN * 3 - 0.35 * IN
    c.setFont(FB, 10)
    c.drawString(0.6 * IN, y, 'Measurements')
    c.setFont('Courier', 5.6)
    for i, line in enumerate(notes):
        c.drawString(0.6 * IN, y - 12 - i * 6.6, line)
    c.save()

    open(os.path.join(OUT, 'measurements.txt'), 'w', encoding='utf-8').write('\n'.join(notes) + '\n')
    os.remove(os.path.join(OUT, '_scratch.pdf')) if os.path.exists(os.path.join(OUT, '_scratch.pdf')) else None
    print('\n'.join(notes))
    print(path)
    print(len(files), 'print files')


if __name__ == '__main__':
    main()
