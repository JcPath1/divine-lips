"""Make one song clip with fal.ai from our own lyrics.

EVERY CALL COSTS MONEY on JC's fal account. Prices read from the model pages 2026-11-11:
  fal-ai/ace-step-1.5        $0.0003 per output second, doubled when "thinking" is on (the default)
  fal-ai/minimax-music/v2    $0.03 per song (no length setting)
  fal-ai/minimax-music/v2.6  $0.15 per song (no length setting)

Usage:
  python tools/fal_music.py <model> <input.json> <out-file-without-extension>

<input.json> holds the model's input fields (prompt, lyrics, duration ...).
The key is read from private/fal.env and is never printed.
A sidecar <out>.json is written as soon as fal accepts the job, so the request id is never lost.
If a call fails in an unclear way, DO NOT just run it again: a second call is a second charge.
Fetch the finished job instead:  python tools/fal_music.py --fetch <out-file-without-extension>
"""
import json
import os
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from fal_generate import load_key  # noqa: E402


def price(model, inp):
    if model == 'fal-ai/ace-step-1.5':
        secs = float(inp.get('duration', 30)) * int(inp.get('num_outputs', 1))
        return round(secs * 0.0003 * (2 if inp.get('thinking', True) else 1), 4)
    if model == 'fal-ai/minimax-music/v2':
        return 0.03
    if model == 'fal-ai/minimax-music/v2.6':
        return 0.15
    sys.exit('Model has no checked price in this tool: ' + model)


def call(url, key, body=None):
    data = json.dumps(body).encode('utf-8') if body is not None else None
    req = urllib.request.Request(
        url, data=data, method='POST' if body is not None else 'GET',
        headers={'Authorization': 'Key ' + key, 'Content-Type': 'application/json'})
    try:
        with urllib.request.urlopen(req, timeout=120) as resp:
            return json.loads(resp.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        detail = e.read().decode('utf-8', 'replace')[:600]
        sys.exit(f'fal said HTTP {e.code} for {url.split("?")[0]}: {detail}')


def write_side(out, side):
    with open(out + '.json', 'w', encoding='utf-8') as f:
        json.dump(side, f, indent=2)


def finish(out, side, key):
    """Wait for the job, then download the audio."""
    for _ in range(180):  # up to 15 minutes
        status = call(side['status_url'], key).get('status')
        if status == 'COMPLETED':
            break
        if status not in ('IN_QUEUE', 'IN_PROGRESS'):
            sys.exit(f'Unexpected job status: {status}. Request id is saved in {out}.json')
        time.sleep(5)
    else:
        sys.exit(f'Still not finished after 15 minutes. Use --fetch later; id is in {out}.json')

    data = call(side['response_url'], key)
    audio = data.get('audio') or (data.get('audios') or [None])[0]
    if not audio or not audio.get('url'):
        side['reply_keys'] = list(data.keys())
        write_side(out, side)
        sys.exit('fal returned no audio. Keys in reply: ' + ', '.join(data.keys()))
    url = audio['url']
    side['audio_url'] = url
    for k in ('bpm', 'duration', 'key_scale', 'seed', 'enhanced_prompt'):
        if k in data:
            side['result_' + k] = data[k]
    write_side(out, side)

    host = urllib.parse.urlparse(url).hostname or ''
    if not url.startswith('https://') or not (host == 'fal.media' or host.endswith('.fal.media')):
        sys.exit(f'Unexpected audio host, not downloading: {host} (url saved in {out}.json)')
    with urllib.request.urlopen(url, timeout=300) as r:
        blob = r.read()
    # fal sometimes names the file .bin, so pick the extension from the file's own header.
    # (ACE-Step 1.5 returned FLAC behind an ID3 tag on 2026-11-11.)
    head = blob[:65536]
    if b'fLaC' in head:
        ext = '.flac'
    elif head[:4] == b'RIFF':
        ext = '.wav'
    elif head[:3] == b'ID3' or head[:1] == b'\xff':
        ext = '.mp3'
    else:
        ext = os.path.splitext(urllib.parse.urlparse(url).path)[1] or '.bin'
    with open(out + ext, 'wb') as f:
        f.write(blob)
    side['file'] = os.path.basename(out + ext)
    write_side(out, side)
    print(f'saved {out + ext}  (${side["price_usd"]:.3f}, request {side["request_id"]})')


def main():
    key = load_key()
    if len(sys.argv) == 3 and sys.argv[1] == '--fetch':
        out = sys.argv[2]
        with open(out + '.json', encoding='utf-8') as f:
            side = json.load(f)
        return finish(out, side, key)
    if len(sys.argv) != 4:
        sys.exit(__doc__)
    model, input_file, out = sys.argv[1:4]
    with open(input_file, encoding='utf-8') as f:
        inp = json.load(f)
    cost = price(model, inp)
    if os.path.exists(out + '.json'):
        sys.exit(f'{out}.json already exists. Use --fetch, or pick a new name for a new paid call.')
    os.makedirs(os.path.dirname(os.path.abspath(out)), exist_ok=True)

    job = call('https://queue.fal.run/' + model, key, inp)
    side = {'model': model, 'price_usd': cost, 'request_id': job.get('request_id'),
            'status_url': job.get('status_url'), 'response_url': job.get('response_url'),
            'input': inp, 'ai_generated': True}
    write_side(out, side)
    if not side['status_url'] or not side['response_url']:
        sys.exit('fal accepted the job but gave no status address. Reply saved in ' + out + '.json')
    finish(out, side, key)


if __name__ == '__main__':
    main()
