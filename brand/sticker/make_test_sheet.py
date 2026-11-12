"""Makes a US Letter test sheet of the bottle sticker at ACTUAL SIZE, to print at home, cut out and hold on a
bottle. Free. Run: python brand/sticker/make_test_sheet.py   (print at 100%, not "fit to page")"""
import os
from reportlab.pdfgen import canvas
from reportlab.lib.utils import ImageReader
import make_sticker as m

IN = 72
out = os.path.join(m.HERE, 'glimmer-bliss-sticker-test-sheet.pdf')
c = canvas.Canvas(out, pagesize=(8.5 * IN, 11 * IN))
c.setTitle('Glimmer Bliss bottle sticker test sheet')
c.setFont('Helvetica-Bold', 13)
c.drawString(0.75 * IN, 10.3 * IN, 'Glimmer Bliss bottle sticker: actual size test sheet')
c.setFont('Helvetica', 10)
c.drawString(0.75 * IN, 10.08 * IN, 'Print at 100% ("Actual size"). Do NOT use "Fit to page". Each sticker is 11/16 x 3 in.')
c.drawString(0.75 * IN, 9.9 * IN, 'Check: the bar below must measure exactly 3 inches with a ruler. Then cut on the gray lines.')
c.setLineWidth(1)
y = 9.62 * IN
c.line(0.75 * IN, y, 3.75 * IN, y)
for i in range(4):
    c.line((0.75 + i) * IN, y - 5, (0.75 + i) * IN, y + 5)
    c.setFont('Helvetica', 7)
    c.drawString((0.75 + i) * IN + 2, y + 4, f'{i} in')
gb, wm = ImageReader(m.gb), ImageReader(m.wm)
c.setStrokeGray(0.6)
c.setLineWidth(0.4)
for row, top in enumerate((9.2, 5.6)):          # top edge of each row, inches from the page bottom
    for col in range(7):
        x = 0.75 + col * (m.W_IN + 0.33)
        c.rect(x * IN, (top - m.H_IN) * IN, m.W_IN * IN, m.H_IN * IN)
        for im, w, h, yc in ((gb, m.GB_W, m.GB_H, m.GB_Y), (wm, m.WM_W, m.WM_LEN, m.WM_Y)):
            c.drawImage(im, (x + (m.W_IN - w) / 2) * IN, (top - yc - h / 2) * IN, w * IN, h * IN, mask='auto')
c.setFillGray(0.3)
c.setFont('Helvetica', 9)
c.drawString(0.75 * IN, 2.2 * IN, 'Tip: print on clear sticker paper or plain paper. On the bottle, GB goes on the white cap.')
c.drawString(0.75 * IN, 2.02 * IN, f'The cap seam falls {m.SEAM:.2f} in below the top of the sticker (cap 1.5 in, sticker centered on a 3.31 in bottle).')
c.save()
print(out)
