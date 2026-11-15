"""Makes VECTOR versions of the two logos and of the bottle sticker. Free.
Run: python brand/sticker/make_vector.py

StickerGiant (Abbie, 12 Nov 2026) prefers vector files and rich black. There is no vector logo in the repo, so
this traces the two PNG logos (brand/logo/) into outlines with potracer (pip install potracer).

Writes:
  brand/logo/logo-gb.svg, brand/logo/logo-wordmark.svg      traced logos, black
  brand/sticker/glimmer-bliss-bottle-sticker-vector.pdf     the sticker, vector, rich black (CMYK)
  brand/sticker/glimmer-bliss-bottle-sticker-vector.svg     the sticker, vector, black

Sizes and positions come from make_sticker.py, so the two files always match.
"""
import os
import numpy as np
import potrace
from PIL import Image, ImageDraw
from reportlab.pdfgen import canvas
import make_sticker as m

UP = 8                              # trace at 8x the PNG so curves come out smooth
RICH_BLACK = (0.60, 0.40, 0.40, 1.0)  # C M Y K. A common rich black; StickerGiant has not given their own values.


def trace(png):
    """Returns (width, height, curves) in the PNG's own pixel units, y down."""
    im = Image.open(png).convert('RGBA')
    big = im.resize((im.width * UP, im.height * UP), Image.LANCZOS)
    mask = np.asarray(big)[:, :, 3] > 127
    curves = []
    # potracer traces the False pixels, so hand it the mask inverted (checked: the other way outlines the background)
    for c in potrace.Bitmap(~mask).trace(turdsize=4, alphamax=1.0, opticurve=True):
        pts = [('M', (c.start_point.x / UP, c.start_point.y / UP))]
        for s in c.segments:
            if s.is_corner:
                pts.append(('L', (s.c.x / UP, s.c.y / UP)))
                pts.append(('L', (s.end_point.x / UP, s.end_point.y / UP)))
            else:
                pts.append(('C', (s.c1.x / UP, s.c1.y / UP, s.c2.x / UP, s.c2.y / UP,
                                  s.end_point.x / UP, s.end_point.y / UP)))
        curves.append(pts)
    return im.width, im.height, curves


def svg_d(curves):
    out = []
    for pts in curves:
        for op, v in pts:
            out.append(op + ' '.join(f'{n:.3f}' for n in v))
        out.append('Z')
    return ''.join(out)


def pdf_path(c, curves):
    p = c.beginPath()
    for pts in curves:
        for op, v in pts:
            (p.moveTo if op == 'M' else p.lineTo if op == 'L' else p.curveTo)(*v)
        p.close()
    return p


def check(png, curves):
    """Draws the traced outline back to pixels and returns the share of pixels that differ from the PNG."""
    im = Image.open(png).convert('RGBA')
    k = 4
    canvas_ = Image.new('L', (im.width * k, im.height * k), 0)
    d = ImageDraw.Draw(canvas_)
    polys = []
    for pts in curves:
        poly, cur = [], None
        for op, v in pts:
            if op in 'ML':
                cur = v
                poly.append((v[0] * k, v[1] * k))
            else:
                x0, y0 = cur
                x1, y1, x2, y2, x3, y3 = v
                for t in np.linspace(0, 1, 13)[1:]:
                    a, b, cc, dd = (1 - t) ** 3, 3 * t * (1 - t) ** 2, 3 * t * t * (1 - t), t ** 3
                    poly.append(((a * x0 + b * x1 + cc * x2 + dd * x3) * k, (a * y0 + b * y1 + cc * y2 + dd * y3) * k))
                cur = (x3, y3)
        polys.append(poly)
    for poly in polys:                       # even-odd fill: XOR each closed outline
        layer = Image.new('L', canvas_.size, 0)
        ImageDraw.Draw(layer).polygon(poly, fill=255)
        canvas_ = Image.fromarray(np.bitwise_xor(np.asarray(canvas_), np.asarray(layer)))
    got = np.asarray(canvas_.resize(im.size, Image.LANCZOS)) > 127
    want = np.asarray(im)[:, :, 3] > 127
    return (got != want).sum() / want.sum()


if __name__ == '__main__':
    gb_png, wm_png = os.path.join(m.LOGO, 'logo-gb.png'), os.path.join(m.LOGO, 'logo-wordmark.png')
    gw, gh, gcur = trace(gb_png)
    ww, wh, wcur = trace(wm_png)
    for name, w, h, cur in (('logo-gb.svg', gw, gh, gcur), ('logo-wordmark.svg', ww, wh, wcur)):
        with open(os.path.join(m.LOGO, name), 'w') as f:
            f.write(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}">'
                    f'<path fill="#000" fill-rule="evenodd" d="{svg_d(cur)}"/></svg>\n')

    IN = 72
    gk = m.GB_W * IN / gw                       # points per logo pixel
    wk = m.WM_LEN * IN / ww
    gx, gy = (m.W_IN - m.GB_W) / 2, m.GB_Y - m.GB_H / 2          # top-left of GB, inches from the sticker's top-left
    wx, wy = (m.W_IN - m.WM_W) / 2, m.WM_Y - m.WM_LEN / 2        # top-left of the turned wordmark

    def sticker_pdf(name, bleed=0.0, gb_w=m.GB_W):
        """bleed = extra clear page on every side, in inches. The art stays the same size, centered.
        gb_w = GB width in inches (it stays centered on the cap)."""
        gk = gb_w * IN / gw
        gx, gy = (m.W_IN - gb_w) / 2, m.GB_Y - gb_w * gh / gw / 2
        out = os.path.join(m.HERE, name)
        c = canvas.Canvas(out, pagesize=((m.W_IN + 2 * bleed) * IN, (m.H_IN + 2 * bleed) * IN))
        c.setTitle('Glimmer Bliss bottle sticker, 11/16 x 3 in, vector')
        c.setFillColorCMYK(*RICH_BLACK)
        c.translate(bleed * IN, bleed * IN)
        c.saveState()                                # GB, upright
        c.transform(gk, 0, 0, -gk, gx * IN, (m.H_IN - gy) * IN)
        c.drawPath(pdf_path(c, gcur), fill=1, stroke=0, fillMode=0)
        c.restoreState()
        c.saveState()                                # GLIMMER BLISS, reads from the bottom toward the cap
        c.transform(0, wk, wk, 0, wx * IN, (m.H_IN - wy - m.WM_LEN) * IN)
        c.drawPath(pdf_path(c, wcur), fill=1, stroke=0, fillMode=0)
        c.restoreState()
        c.save()
        return out

    out = sticker_pdf('glimmer-bliss-bottle-sticker-vector.pdf')
    # Avery WePrint stretches an upload to its bleed line, 1/16 in outside the label on every side (seen in their
    # designer, 12 Nov 2026: the plain file came out 1.18x too big). This copy has that 1/16 in built in.
    # Avery's safe margin is 1/16 in (Jeremy, 12 Nov 2026), so GB could be 9/16 in wide there (JC: make it bigger).
    # At the full 9/16 it sat on the safe line in their designer, so it is 0.53 in: about 0.08 in clear each side.
    sticker_pdf('glimmer-bliss-bottle-sticker-avery.pdf', bleed=1 / 16, gb_w=0.53)

    gs, ws = m.GB_W / gw, m.WM_LEN / ww          # inches per logo pixel
    with open(os.path.join(m.HERE, 'glimmer-bliss-bottle-sticker-vector.svg'), 'w') as f:
        f.write(f'<svg xmlns="http://www.w3.org/2000/svg" width="{m.W_IN}in" height="{m.H_IN}in" '
                f'viewBox="0 0 {m.W_IN} {m.H_IN}">'
                f'<path fill="#000" fill-rule="evenodd" transform="translate({gx:.5f} {gy:.5f}) scale({gs:.7f})" d="{svg_d(gcur)}"/>'
                f'<path fill="#000" fill-rule="evenodd" transform="matrix(0 {-ws:.7f} {ws:.7f} 0 {wx:.5f} {wy + m.WM_LEN:.5f})" d="{svg_d(wcur)}"/>'
                f'</svg>\n')
    print(f'GB: {len(gcur)} outlines, {check(gb_png, gcur):.2%} of pixels differ from the PNG')
    print(f'GLIMMER BLISS: {len(wcur)} outlines, {check(wm_png, wcur):.2%} of pixels differ from the PNG')
    print(out)
