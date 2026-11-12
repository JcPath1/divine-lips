"""Build brand/preview-site/: a copy of docs/ with the Blush & Black restyle.

PREVIEW ONLY. Never touches docs/ and is never deployed.
Run:  python brand/build_preview.py
Open: brand/preview-site/index.html
"""
import glob
import io
import os
import shutil

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = os.path.join(ROOT, 'docs')
OUT = os.path.join(ROOT, 'brand', 'preview-site')

FONTS = ('<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600'
         '&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">')
SPARK = ('<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 0c.9 7.2 4.8 11.1 12 12'
         '-7.2.9-11.1 4.8-12 12-.9-7.2-4.8-11.1-12-12C7.2 11.1 11.1 7.2 12 0z"/></svg>')

GLOSSES = [  # id, name, colour words from the product pages, price
    ('pink-paradise', 'Pink Paradise', 'Sheer pink', '$12'),
    ('true-love', 'True Love', 'Sheer dark red', '$12'),
    ('sugar-cookie', 'Sugar Cookie', 'Sheer warm brown', '$12'),
    ('gloss-angeles', 'Gloss Angeles', 'Clear', '$12'),
    ('glimmer-kiss', 'Glimmer Kiss', 'Turns pink on you', '$15'),
]

HOME = f'''
  <!-- Hero -->
  <section class="gb-hero">
    <div class="gb-hero-inner">
      <div class="gb-hero-words">
        <h1>Handmade lip gloss.<br><i>Made by a<br>Lip Gloss Boss.</i></h1>
        <p>Sheer color, high shine, vanilla flavored. Every gloss is mixed, bottled and packed in small batches by Charlie Mae.</p>
        <div class="gb-actions">
          <a class="gb-btn" href="#products">Shop the glosses</a>
          <a class="gb-link" href="#about">Meet Charlie Mae</a>
        </div>
      </div>
      <div class="gb-lineup">
        <span class="gb-spark one">{SPARK}</span><span class="gb-spark two">{SPARK}</span>
        {''.join(f'<img src="images/cutouts/{g[0]}.png" alt="{g[1]} lip gloss">' for g in GLOSSES)}
      </div>
    </div>
  </section>

  <!-- Glimmer Kiss -->
  <section class="gb-kiss">
    <div class="gb-kiss-inner">
      <div class="gb-kiss-bottle">
        <img src="images/cutouts/glimmer-kiss.png" alt="Glimmer Kiss color-changing lip gloss">
        <span class="gb-spark one">{SPARK}</span><span class="gb-spark two">{SPARK}</span>
      </div>
      <div>
        <h2>Clear in the bottle.<br><i>Pink on you.</i></h2>
        <ol class="gb-steps">
          <li><span><b>Swipe it on.</b> Glimmer Kiss goes on clear, with fine sparkle.</span></li>
          <li><span><b>Your lips do the rest.</b> The gloss reacts with your lips' natural chemistry, called pH.</span></li>
          <li><span><b>Meet your pink.</b> In a few seconds it turns a pink that's all yours.</span></li>
        </ol>
        <div class="gb-kiss-buy">
          <a class="gb-btn" href="products/glimmer-kiss.html">Shop Glimmer Kiss</a>
          <span class="gb-kiss-price"><strong>$15</strong> &nbsp;Limited edition</span>
        </div>
      </div>
    </div>
  </section>

  <!-- The shelf -->
  <section id="products">
    <div class="gb-shelf-wrap">
      <h2>The glosses</h2>
      <p class="gb-shelf-lead">Five glosses. All sheer, all vanilla, all 5 ml.</p>
      <div class="gb-shelf">
        {''.join(f"""<a class="gb-item" href="products/{g[0]}.html">
          <div class="gb-item-bottle"><img src="images/cutouts/{g[0]}.png" alt="{g[1]} lip gloss" loading="lazy"></div>
          <div class="gb-item-info"><h3>{g[1]}</h3><span>{g[2]}</span><strong>{g[3]}</strong></div>
        </a>""" for g in GLOSSES)}
      </div>
'''


def main():
    if os.path.isdir(OUT):
        shutil.rmtree(OUT)
    shutil.copytree(SRC, OUT, ignore=shutil.ignore_patterns('videos'))
    shutil.copytree(os.path.join(ROOT, 'brand', 'cutouts'), os.path.join(OUT, 'images', 'cutouts'))
    shutil.copy(os.path.join(ROOT, 'brand', 'preview-brand.css'), os.path.join(OUT, 'css', 'brand.css'))

    for path in glob.glob(os.path.join(OUT, '**', '*.html'), recursive=True):
        html = io.open(path, encoding='utf-8').read()
        depth = os.path.relpath(path, OUT).count(os.sep)
        css = ('../' * depth) + 'css/brand.css'
        html = html.replace('</head>', f'  {FONTS}\n  <link rel="stylesheet" href="{css}">\n'
                            '  <meta name="robots" content="noindex">\n</head>', 1)
        if os.path.relpath(path, OUT) == 'index.html':
            a = html.index('<!-- Limited Edition Advertisement -->')
            b = html.index('<p class="wear-note">')
            html = html[:a] + HOME.strip('\n') + '\n      ' + html[b:]
        io.open(path, 'w', encoding='utf-8', newline='').write(html)
    print('Preview built:', os.path.join(OUT, 'index.html'))


if __name__ == '__main__':
    main()
