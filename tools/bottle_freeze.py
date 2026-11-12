"""Make a "freeze patch": a still picture of the bottle's front face with soft edges. Free.

Laid over a fal video clip in Remotion (the `freeze` prop), it stops the glitter inside the bottle from flowing
and shows the logo in the locked position, without buying a new clip.

Usage:
  python tools/bottle_freeze.py <plate.png> <out.png> --cap-top x,y --bottom x,y --half-width N
         [--inset 6] [--feather 3] [--check frame1.png frame2.png ...]

--cap-top and --bottom are the two ends of the center line of the front face (same points as bottle_logo.py).
--half-width is half the width of the front face in pixels.
--check prints how much the clip changes inside the patch area between the given frames. A big number means the
light moves across the bottle and a frozen bottle may look wrong: look at the result before using it.
"""
import argparse
import math

import numpy as np
from PIL import Image, ImageDraw, ImageFilter


def pt(text):
    x, y = text.split(',')
    return float(x), float(y)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('plate')
    ap.add_argument('out')
    ap.add_argument('--cap-top', required=True, type=pt)
    ap.add_argument('--bottom', required=True, type=pt)
    ap.add_argument('--half-width', required=True, type=float)
    ap.add_argument('--inset', type=float, default=6)
    ap.add_argument('--feather', type=float, default=3)
    ap.add_argument('--check', nargs='*', default=[])
    a = ap.parse_args()

    (x0, y0), (x1, y1) = a.cap_top, a.bottom
    length = math.dist((x0, y0), (x1, y1))
    dx, dy = (x1 - x0) / length, (y1 - y0) / length
    nx, ny = -dy, dx
    hw = a.half_width - a.inset
    s0, s1 = a.inset, length - a.inset
    corners = [(x0 + dx * s + nx * c, y0 + dy * s + ny * c)
               for s, c in ((s0, -hw), (s0, hw), (s1, hw), (s1, -hw))]

    plate = Image.open(a.plate).convert('RGBA')
    mask = Image.new('L', plate.size, 0)
    ImageDraw.Draw(mask).polygon(corners, fill=255)
    soft = mask.filter(ImageFilter.GaussianBlur(a.feather))
    plate.putalpha(soft)
    plate.save(a.out)
    print('saved', a.out)

    if len(a.check) > 1:
        inside = np.asarray(mask) > 0
        frames = [np.asarray(Image.open(f).convert('L').resize(plate.size)).astype(float) for f in a.check]
        still = np.asarray(Image.open(a.plate).convert('L')).astype(float)
        for name, fr in zip(a.check, frames):
            d_in = np.abs(fr - frames[0])[inside].mean()
            d_out = np.abs(fr - frames[0])[~inside].mean()
            d_plate = (fr[inside].mean() - still[inside].mean())
            print(f'{name}: change vs first frame inside patch {d_in:.1f}, outside {d_out:.1f}; '
                  f'brightness vs plate inside {d_plate:+.1f}')


if __name__ == '__main__':
    main()
