"""Make one picture with fal.ai from a real product photo (reference-based editing).

JC's rule (15 Nov 2026): always Fal; NEVER FLUX Kontext. Any model with "kontext" in its name is refused below.
The model for drafts AND finals is fal-ai/nano-banana-pro/edit, and it is the default when no model is named.

EVERY CALL COSTS MONEY on JC's fal account. Price checked 2026-11-11:
  fal-ai/nano-banana-pro/edit   $0.15 per image at 1K   (4K is double)

Usage (the default model, fal-ai/nano-banana-pro/edit):
  python tools/fal_generate.py <image-url-or-local-file> <out-file> <aspect> "<prompt>"
Or name the model first (only models in the PRICE table work):
  python tools/fal_generate.py <model> <image-url-or-local-file> <out-file> <aspect> "<prompt>"

The key is read from private/fal.env and is never printed.
A sidecar <out-file>.json records the model, prompt, seed and price so a good result can be repeated.
If a call fails in an unclear way, DO NOT just run it again: a second call is a second charge.
"""
import json
import os
import sys
import urllib.error
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ENV = os.path.join(ROOT, 'private', 'fal.env')

DEFAULT_MODEL = 'fal-ai/nano-banana-pro/edit'
PRICE = {
    DEFAULT_MODEL: 0.15,
}


def load_key():
    if not os.path.isfile(ENV):
        sys.exit('Missing private/fal.env')
    with open(ENV, encoding='utf-8-sig') as f:
        for line in f:
            line = line.strip()
            if line.startswith('FAL_KEY='):
                return line.split('=', 1)[1].strip().strip('\'"')
    sys.exit('No FAL_KEY line in private/fal.env')


def build_input(model, image_url, aspect, prompt):
    if model == DEFAULT_MODEL:
        return {'prompt': prompt, 'image_urls': [image_url], 'aspect_ratio': aspect,
                'num_images': 1, 'output_format': 'jpeg', 'resolution': '1K'}
    sys.exit('Unknown model. Read https://fal.ai/models/<model>/llms.txt and add its schema here first.')


def check_model(model):
    """Runs BEFORE anything is uploaded or paid for."""
    if 'kontext' in model.lower():
        sys.exit("FLUX Kontext is banned (JC's rule, 15 Nov 2026). Use " + DEFAULT_MODEL + '. Nothing was sent or charged.')
    if model not in PRICE:
        sys.exit('Unknown model: ' + model + '. Only ' + ', '.join(PRICE) + ' is set up in this tool. Nothing was sent or charged.')


def main():
    args = sys.argv[1:]
    if len(args) == 4:
        args = [DEFAULT_MODEL] + args          # no model named: use the default
    if len(args) != 5:
        sys.exit(__doc__)
    model, image_url, out_file, aspect, prompt = args
    check_model(model)
    if os.path.isfile(image_url):
        # A local picture: upload it to fal storage first (needs `pip install fal-client`).
        os.environ['FAL_KEY'] = load_key()
        import fal_client
        image_url = fal_client.upload_file(image_url)
    body = json.dumps(build_input(model, image_url, aspect, prompt)).encode('utf-8')
    req = urllib.request.Request(
        'https://fal.run/' + model, data=body, method='POST',
        headers={'Authorization': 'Key ' + load_key(), 'Content-Type': 'application/json'})
    try:
        with urllib.request.urlopen(req, timeout=300) as resp:
            request_id = resp.headers.get('x-fal-request-id', '')
            data = json.loads(resp.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        detail = e.read().decode('utf-8', 'replace')[:400]
        sys.exit(f'fal said HTTP {e.code}: {detail}')

    images = data.get('images') or []
    if not images:
        sys.exit('fal returned no image. Keys in reply: ' + ', '.join(data.keys()))
    url = images[0]['url']
    if not url.startswith('https://') or '.fal.media/' not in url and 'fal.media/' not in url:
        sys.exit('Unexpected image host, not downloading: ' + url.split('/')[2])
    os.makedirs(os.path.dirname(os.path.abspath(out_file)), exist_ok=True)
    with urllib.request.urlopen(url, timeout=120) as r, open(out_file, 'wb') as f:
        f.write(r.read())
    with open(out_file + '.json', 'w', encoding='utf-8') as f:
        json.dump({'model': model, 'price_usd': PRICE[model], 'request_id': request_id,
                   'source_image': image_url, 'aspect_ratio': aspect, 'prompt': prompt,
                   'seed': data.get('seed'), 'ai_generated': True}, f, indent=2)
    print(f'saved {out_file}  (${PRICE[model]:.2f}, request {request_id or "id not returned"})')


if __name__ == '__main__':
    main()
