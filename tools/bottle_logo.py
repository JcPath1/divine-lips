"""Put the Glimmer Bliss logo on a bottle photo in the ONE locked position. Free: runs on this computer.

The rule lives in brand/design-tokens.json under "bottle-logo". Do not place the logo by eye and do not
type positions by hand. Give this tool three points and it does the maths.

The three points are all on the CENTER LINE OF THE BOTTLE'S FRONT FACE (the wide, bright side), in pixels
of the finished picture:
  --cap-top   where the white cap starts (the far end of the cap)
  --join      where the white cap meets the clear bottle
  --bottom    the bottom outside edge of the clear bottle

Usage:
  python tools/bottle_logo.py --photo scene.jpg --size 1080x1920 --cap-top 565,594 --join 565,990 \
      --bottom 565,1407 --out plate.png [--squeeze 0.84]

--squeeze is only for a front face that is turned away from the camera (1 = facing us).
It writes plate.html next to plate.png, renders it with Chrome, and prints the CSS numbers so a layout with
words on it can reuse them.
"""
import argparse
import json
import math
import os
import subprocess
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
TOKENS = os.path.join(ROOT, 'brand', 'design-tokens.json')
LOGO_DIR = os.path.join(ROOT, 'brand', 'logo')
CHROME = r'C:\Program Files\Google\Chrome\Application\chrome.exe'


def spec():
    with open(TOKENS, encoding='utf-8') as f:
        s = json.load(f)['bottle-logo']
    return s['wordmark-length-of-body'], s['monogram-width-of-cap-length'], s['opacity']


def pt(text):
    x, y = text.split(',')
    return float(x), float(y)


def place(cap_top, join, bottom):
    """Return rotation and the two logo boxes (center x, center y, length along the bottle)."""
    wm_part, gb_part, _ = spec()
    cap_len = math.dist(cap_top, join)
    body_len = math.dist(join, bottom)
    # LOCKED RULE (JC, 11 Nov 2026): GLIMMER BLISS and GB always read from the BOTTOM of the bottle toward the
    # cap, in every picture and video, whatever way the bottle is lying. So the logo's reading direction is
    # the direction from the bottom point to the cap-top point.
    angle = math.degrees(math.atan2(cap_top[1] - bottom[1], cap_top[0] - bottom[0]))
    # LOCKED RULE (JC and Charlie, 11 Nov 2026): GB stands UPRIGHT on the cap. It sits across the cap, a quarter
    # turn from GLIMMER BLISS, with the top of the letters toward the end of the cap. GLIMMER BLISS is unchanged.
    # gb[2] is the WIDTH of GB (across the cap), as a part of the cap's length.
    gb = ((cap_top[0] + join[0]) / 2, (cap_top[1] + join[1]) / 2, cap_len * gb_part)
    wm = ((join[0] + bottom[0]) / 2, (join[1] + bottom[1]) / 2, body_len * wm_part)
    return angle, gb, wm, cap_len, body_len


def css(angle, gb, wm, squeeze):
    _, _, opacity = spec()
    sq = '' if squeeze == 1 else f';scale:1 {squeeze:g}'
    onb = f'translate:-50% -50%;rotate:{angle:.1f}deg;mix-blend-mode:multiply;opacity:{opacity:g}{sq}'
    gb_sq = '' if squeeze == 1 else f';scale:{squeeze:g} 1'
    return (f'.onb{{{onb}}}\n'
            f'.gb{{left:{gb[0]:.0f}px;top:{gb[1]:.0f}px;width:{gb[2]:.0f}px;rotate:{angle + 90:.1f}deg{gb_sq}}}'
            f'.wm{{left:{wm[0]:.0f}px;top:{wm[1]:.0f}px;width:{wm[2]:.0f}px}}')


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument('--photo', required=True)
    ap.add_argument('--size', required=True, help='WIDTHxHEIGHT of the finished picture')
    ap.add_argument('--cap-top', required=True, type=pt)
    ap.add_argument('--join', required=True, type=pt)
    ap.add_argument('--bottom', required=True, type=pt)
    ap.add_argument('--squeeze', type=float, default=1.0)
    ap.add_argument('--out', help='PNG to write. Leave out to only print the numbers.')
    a = ap.parse_args()

    w, h = (int(v) for v in a.size.lower().split('x'))
    angle, gb, wm, cap_len, body_len = place(a.cap_top, a.join, a.bottom)
    rules = css(angle, gb, wm, a.squeeze)
    gap = (body_len - wm[2]) / 2
    print(f'cap {cap_len:.0f}px, bottle {body_len:.0f}px, angle {angle:.1f} deg')
    print(f'GLIMMER BLISS: {wm[2]:.0f}px long, {gap:.0f}px clear at each end ({gap / body_len:.1%} of the bottle)')
    print(f'GB: {gb[2]:.0f}px wide, upright, centered on the cap')
    print(rules)
    if not a.out:
        return

    out = os.path.abspath(a.out)
    folder = os.path.dirname(out)
    os.makedirs(folder, exist_ok=True)

    def rel(p):
        return os.path.relpath(os.path.abspath(p), folder).replace('\\', '/')

    html = (f'<!doctype html><html><head><meta charset="utf-8"><style>\n'
            f'*{{margin:0;padding:0}}body{{overflow:hidden}}\n'
            f'.ad{{position:relative;width:{w}px;height:{h}px;overflow:hidden}}\n'
            f'.ad>*{{position:absolute}}\n'
            f'.photo{{inset:0;width:100%;height:100%;object-fit:cover}}\n'
            f'{rules}\n'
            f'</style></head><body><div class="ad"><img class="photo" src="{rel(a.photo)}">\n'
            f'<img class="onb gb" src="{rel(os.path.join(LOGO_DIR, "logo-gb.png"))}">'
            f'<img class="onb wm" src="{rel(os.path.join(LOGO_DIR, "logo-wordmark.png"))}"></div></body></html>')
    page = os.path.splitext(out)[0] + '.html'
    with open(page, 'w', encoding='utf-8', newline='') as f:
        f.write(html)
    if not os.path.isfile(CHROME):
        sys.exit('Chrome not found. The page was written: ' + page)
    subprocess.run([CHROME, '--headless=new', '--disable-gpu', '--hide-scrollbars',
                    f'--window-size={w},{h}', '--virtual-time-budget=5000',
                    '--screenshot=' + out, 'file:///' + page.replace('\\', '/')],
                   check=True, capture_output=True)
    print('saved', out)


if __name__ == '__main__':
    main()
