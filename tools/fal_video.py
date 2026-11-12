"""Turn one approved hero picture into a short video clip with fal.ai.

EVERY CALL COSTS MONEY on JC's fal account. Prices checked 2026-11-11:
  fal-ai/kling-video/v2.1/pro/image-to-video   $0.49 for 5 s, $0.098 per extra second
  fal-ai/wan-i2v                               $0.20 at 480p, $0.40 at 720p (schema not added here yet)

Usage:
  python tools/fal_video.py <picture-file> <out.mp4> "<prompt>"

Only animate a picture JC has approved. The clip has no words: add type in Remotion (brand/video).
The key is read from private/fal.env and is never printed.
If a call fails in an unclear way, DO NOT just run it again: a second call is a second charge.
The request ID is printed as soon as fal accepts the job, so a lost result can be looked up, not re-bought.
"""
import json
import os
import sys
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ENV = os.path.join(ROOT, 'private', 'fal.env')
MODEL = 'fal-ai/kling-video/v2.1/pro/image-to-video'
PRICE = 0.49
NEGATIVE = ('blur, distort, and low quality, camera movement, zoom, morphing, warped text, '
            'changing logo, extra objects, hands, people')


def load_key():
    if not os.path.isfile(ENV):
        sys.exit('Missing private/fal.env')
    with open(ENV, encoding='utf-8-sig') as f:
        for line in f:
            line = line.strip()
            if line.startswith('FAL_KEY='):
                return line.split('=', 1)[1].strip().strip('\'"')
    sys.exit('No FAL_KEY line in private/fal.env')


def main():
    if len(sys.argv) != 4:
        sys.exit(__doc__)
    picture, out_file, prompt = sys.argv[1:4]
    if not os.path.isfile(picture):
        sys.exit('No such picture: ' + picture)
    os.environ['FAL_KEY'] = load_key()
    import fal_client

    image_url = fal_client.upload_file(picture)
    print('picture uploaded to fal storage', flush=True)
    handle = fal_client.submit(MODEL, arguments={
        'prompt': prompt, 'image_url': image_url, 'duration': '5', 'negative_prompt': NEGATIVE})
    print('job accepted, request', handle.request_id, flush=True)
    result = handle.get()
    url = result['video']['url']
    if not url.startswith('https://') or 'fal.media/' not in url:
        sys.exit('Unexpected video host, not downloading: ' + url.split('/')[2])
    os.makedirs(os.path.dirname(os.path.abspath(out_file)), exist_ok=True)
    with urllib.request.urlopen(url, timeout=300) as r, open(out_file, 'wb') as f:
        f.write(r.read())
    with open(out_file + '.json', 'w', encoding='utf-8') as f:
        json.dump({'model': MODEL, 'price_usd': PRICE, 'request_id': handle.request_id,
                   'source_picture': os.path.relpath(picture, ROOT).replace('\\', '/'), 'duration_s': 5,
                   'prompt': prompt, 'negative_prompt': NEGATIVE, 'ai_generated': True}, f, indent=2)
    print(f'saved {out_file}  (${PRICE:.2f})')


if __name__ == '__main__':
    main()
