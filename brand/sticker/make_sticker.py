"""Makes the clear bottle sticker print file. Free. Run: python brand/sticker/make_sticker.py

One sticker, 11/16 x 3 in, black print on clear. It goes down the side of the bottle from top to bottom and is
then cut at the seam where the cap meets the bottle (JC, 11 Nov 2026). So it carries two labels:
GB (upright) for the cap, GLIMMER BLISS (reads from the bottom toward the cap) for the clear bottle.

The cap is 1.5 in (JC measured a real bottle). JC does not want a cut line drawn on the proof.
The sticker is placed centered on the bottle's height (0.155 in of bare bottle at the top and at the bottom).
"""
import os
from PIL import Image, ImageDraw, ImageFont
from reportlab.pdfgen import canvas
from reportlab.lib.utils import ImageReader

HERE = os.path.dirname(os.path.abspath(__file__))
LOGO = os.path.join(HERE, '..', 'logo')
W_IN, H_IN = 11 / 16, 3.0          # sticker
BOTTLE_IN = 3.31                   # bottle height (measured, on the site)
CAP_IN = 1.5                       # top of the white cap to the seam, measured by JC on a real bottle, 11 Nov 2026
BODY_IN = BOTTLE_IN - CAP_IN
TOP_GAP = (BOTTLE_IN - H_IN) / 2   # sticker centered on the bottle's height
SEAM = CAP_IN - TOP_GAP            # seam, measured from the top of the sticker
GB_W = 0.50                        # in. Brand rule is 38% of the cap (0.61 in); made smaller to stay off the edge
WM_LEN = 0.64 * BODY_IN            # brand rule: 64% of the clear bottle
GB_Y = SEAM - CAP_IN / 2           # center of the cap
WM_Y = SEAM + BODY_IN / 2          # center of the clear bottle

gb = Image.open(os.path.join(LOGO, 'logo-gb.png'))
wm = Image.open(os.path.join(LOGO, 'logo-wordmark.png')).rotate(90, expand=True)  # reads bottom to top
GB_H = GB_W * gb.height / gb.width
WM_W = WM_LEN * wm.width / wm.height


def pdf(path):
    c = canvas.Canvas(path, pagesize=(W_IN * 72, H_IN * 72))
    for im, w, h, y in ((gb, GB_W, GB_H, GB_Y), (wm, WM_W, WM_LEN, WM_Y)):
        c.drawImage(ImageReader(im), (W_IN - w) / 2 * 72, (H_IN - y - h / 2) * 72, w * 72, h * 72, mask='auto')
    c.save()


def proof(path, dpi=600, pad=1.1):
    s = lambda v: int(round(v * dpi))
    im = Image.new('RGB', (s(W_IN + 2 * pad + 1.2), s(H_IN + 2 * 0.45)), 'white')
    ox, oy = s(pad), s(0.45)
    d = ImageDraw.Draw(im)
    for src, w, h, y in ((gb, GB_W, GB_H, GB_Y), (wm, WM_W, WM_LEN, WM_Y)):
        r = src.resize((s(w), s(h)), Image.LANCZOS)
        im.paste(r, (ox + s((W_IN - w) / 2), oy + s(y - h / 2)), r)
    d.rectangle([ox, oy, ox + s(W_IN), oy + s(H_IN)], outline=(230, 51, 127), width=4)
    f = ImageFont.truetype('arial.ttf', s(0.085))
    edge = (W_IN - GB_W) / 2
    notes = [(0.0, 'Sticker: 11/16 x 3 in, clear, black print'),
             (GB_Y, f'GB: {GB_W:.2f} in wide, {edge:.3f} in from each side edge'),
             (WM_Y, f'GLIMMER BLISS: {WM_LEN:.2f} in long, {WM_W:.2f} in tall'),
             (H_IN, 'The pink outline is the sticker edge. It does not print.')]
    for y, t in notes:
        d.text((ox + s(W_IN + 0.33), oy + s(y) - s(0.05)), t, fill=(26, 26, 26), font=f)
    im.save(path, dpi=(dpi, dpi))


if __name__ == '__main__':
    pdf(os.path.join(HERE, 'glimmer-bliss-bottle-sticker-print.pdf'))
    proof(os.path.join(HERE, 'glimmer-bliss-bottle-sticker-proof-v2.png'))
    print(f'seam {SEAM:.3f} in from top; GB {GB_W:.2f} x {GB_H:.2f} in, center {GB_Y:.3f}; '
          f'GLIMMER BLISS {WM_LEN:.3f} x {WM_W:.3f} in, center {WM_Y:.3f}; GB edge gap {(W_IN-GB_W)/2:.3f} in')
