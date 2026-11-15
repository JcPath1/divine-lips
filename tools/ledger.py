"""Glimmer Bliss text ledger: files a plain message into the shop's own records and reads back what was saved.

    python tools/ledger.py say "made 24 Pink Paradise" --by Jc
    python tools/ledger.py say "sold 4 Pink Paradise at the show, $48 cash" --by Charlie
    python tools/ledger.py say "spent $56.50 on bottles" --by Jc
    python tools/ledger.py say "donated: 2 oz squalane oil from Grandma" --by Jc
    python tools/ledger.py say "count: 30 Pink Paradise" --by Jc
    python tools/ledger.py say "undo 12" --by Jc
    python tools/ledger.py summary
    python tools/ledger.py recent
    python tools/ledger.py backup

How Phronesis should word a task, and every rule, is in LEDGER_HOWTO.md.

Exit codes: 0 = recorded (or summary shown), 2 = NOT recorded because the message is unclear or was refused,
1 = something broke. Nothing is ever guessed: an unclear message is sent back with what is missing.

--target local (default) talks to the test copy on this computer (python tools/glimmer.py local).
--target live talks to glimmerbliss.com and only works once LEDGER_TOKEN is in the secrets file and the shop has
been deployed. A backup of the live records is saved before the first live entry of each day.
Never prints a password or token.
"""
import argparse
import datetime
import json
import os
import re
import sys
import urllib.error
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
BACKUPS = os.path.join(ROOT, 'private', 'backups')
TARGET_FILE = os.path.join(ROOT, 'private', 'ledger-target.txt')   # holds "live" once JC has approved go-live
TARGETS = {'local': 'http://localhost:8080/api/ledger.php', 'live': 'https://glimmerbliss.com/api/ledger.php'}
LOCAL_TOKEN = 'test-ledger-token-local-only-0000'

CHANNELS = {'show': 'show', 'web': 'website', 'tiktok': 'TikTok Shop', 'other': 'other'}
PAY = {'cash': 'cash', 'venmo': 'Venmo', 'card': 'card', 'zelle': 'Zelle', 'paypal': 'PayPal', 'tiktok': 'TikTok payout', 'other': 'other'}
KINDS = {'supplies': 'supplies', 'bottles': 'bottles', 'labels': 'labels', 'hosting': 'hosting', 'show_fees': 'show fees',
         'ads': 'ads', 'shipping': 'shipping', 'other': 'other'}
NUMBER_WORDS = {'a': 1, 'an': 1, 'one': 1, 'two': 2, 'three': 3, 'four': 4, 'five': 5, 'six': 6, 'seven': 7, 'eight': 8,
                'nine': 9, 'ten': 10, 'eleven': 11, 'twelve': 12}
GLOSS = {'pink paradise': 'pink-paradise', 'true love': 'true-love', 'gloss angeles': 'gloss-angeles',
         'sugar cookie': 'sugar-cookie', 'glimmer kiss': 'glimmer-kiss'}
NO_GLITTER = r'(?:no|without|w/o)\s+(?:glitter|sparkles?)|plain|non[- ]?glitter'
GLITTER = r'(?:with\s+)?(?:glitter|sparkles?)'
MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec']


class Unclear(Exception):
    def __init__(self, problems):
        self.problems = problems if isinstance(problems, list) else [problems]


def money(cents):
    return '${:,.2f}'.format(cents / 100)


def default_target():
    try:
        t = open(TARGET_FILE, encoding='utf-8').read().strip()
        return t if t in TARGETS else 'local'
    except OSError:
        return 'local'


def token_for(target):
    if target == 'local':
        return LOCAL_TOKEN
    import glimmer
    tok = glimmer.load_secrets().get('LEDGER_TOKEN', '')
    if len(tok) < 24:
        sys.exit('The live text ledger is not switched on yet (no LEDGER_TOKEN in the secrets file).')
    return tok


def call(target, action, **data):
    body = json.dumps(dict(data, action=action, token=token_for(target))).encode()
    req = urllib.request.Request(TARGETS[target], data=body, headers={'Content-Type': 'application/json'})
    try:
        with urllib.request.urlopen(req, timeout=40) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        try:
            return json.load(e)
        except ValueError:
            sys.exit(f'The shop answered with an error ({e.code}).')
    except urllib.error.URLError as e:
        hint = ' Start the test shop first: python tools/glimmer.py local, then run the php command it prints.' if target == 'local' else ''
        sys.exit(f'Could not reach the shop ({type(e.reason).__name__}).{hint}')


def backup(target, quiet=False):
    out = call(target, 'export')
    if not out.get('ok'):
        sys.exit('Backup failed: ' + str(out.get('error')))
    os.makedirs(BACKUPS, exist_ok=True)
    path = os.path.join(BACKUPS, f"shop-{target}-{datetime.datetime.now():%Y%m%d-%H%M%S}.json")
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(out, f, indent=1)
    rows = {t: (len(v) if v is not None else 'not made yet') for t, v in out['tables'].items()}
    if not quiet:
        print('BACKUP SAVED:', os.path.relpath(path, ROOT))
        print('Rows:', ', '.join(f'{t} {n}' for t, n in rows.items()))
    return path


def backup_before_live_write(target):
    if target != 'live':
        return
    today = f"shop-live-{datetime.date.today():%Y%m%d}"
    if not os.path.isdir(BACKUPS) or not any(n.startswith(today) for n in os.listdir(BACKUPS)):
        print('(Backup of the live records saved first: ' + os.path.relpath(backup(target, quiet=True), ROOT) + ')')


# ---------- reading a plain message ----------

def find_date(text, today):
    low = text.lower()
    if re.search(r'\byesterday\b', low):
        return (today - datetime.timedelta(days=1)).isoformat(), re.sub(r'(?i)\byesterday\b', ' ', text)
    m = re.search(r'\b(?:on\s+)?(\d{4})-(\d{2})-(\d{2})\b', low)
    if m:
        try:
            d = datetime.date(int(m.group(1)), int(m.group(2)), int(m.group(3)))
        except ValueError:
            raise Unclear(f'The date "{m.group(0).strip()}" is not a real date.')
        return d.isoformat(), text[:m.start()] + ' ' + text[m.end():]
    m = re.search(r'\b(?:on\s+)?(' + '|'.join(MONTHS) + r')[a-z]*\.?\s+(\d{1,2})(?:st|nd|rd|th)?\b', low)
    if m:
        try:
            d = datetime.date(today.year, MONTHS.index(m.group(1)) + 1, int(m.group(2)))
        except ValueError:
            raise Unclear(f'The date "{m.group(0).strip()}" is not a real date.')
        if d > today:
            d = d.replace(year=today.year - 1)
        return d.isoformat(), text[:m.start()] + ' ' + text[m.end():]
    return None, re.sub(r'(?i)\btoday\b', ' ', text)


def find_items(text):
    """Returns ([(key, qty)], problems). A gloss named without a number, or Glimmer Kiss without glitter / no glitter,
    is a problem, never a guess."""
    low = text.lower()
    num = r'(\d{1,4}|' + '|'.join(NUMBER_WORDS) + r')'
    pat = re.compile(num + r'\s+(?:bottles?\s+of\s+|tubes?\s+of\s+|x\s+)?(?:(' + NO_GLITTER + '|' + GLITTER + r')\s+)?('
                     + '|'.join(GLOSS) + r')(?:\s*\(?\s*(' + NO_GLITTER + '|' + GLITTER + r')\s*\)?)?')
    items, problems, used = [], [], []
    for m in pat.finditer(low):
        qty = int(m.group(1)) if m.group(1).isdigit() else NUMBER_WORDS[m.group(1)]
        key = GLOSS[m.group(3)]
        qual = m.group(2) or m.group(4)
        if key == 'glimmer-kiss':
            if not qual:
                problems.append('Glimmer Kiss: with glitter or no glitter?')
                used.append(m.span(3))
                continue
            key += '|No glitter' if re.fullmatch(NO_GLITTER, qual) else '|With glitter'
        elif qual:
            problems.append(f'{m.group(3).title()} does not come with a glitter choice. Did you mean Glimmer Kiss?')
        items.append((key, qty))
        used.append(m.span(3))
    for name in GLOSS:
        for m in re.finditer(re.escape(name), low):
            if not any(a <= m.start() < b for a, b in used):
                problems.append(f'How many {name.title()}? Put the number right before the name, like "4 {name.title()}".')
    keys = [k for k, _ in items]
    for k in set(keys):
        if keys.count(k) > 1:
            problems.append('The same gloss is listed twice. Send one number per gloss.')
    return items, problems


def find_amounts(text):
    out = []
    for m in re.finditer(r'\$\s?(\d{1,6}(?:,\d{3})*(?:\.\d{1,2})?)|\b(\d{1,6}(?:\.\d{1,2})?)\s*(?:dollars|bucks)\b', text.lower()):
        raw = (m.group(1) or m.group(2)).replace(',', '')
        out.append((round(float(raw) * 100), m.span()))
    return out


def one_of(text, table, question):
    hits = [k for k, pat in table.items() if re.search(pat, text.lower())]
    if len(hits) == 1:
        return hits[0], None
    return None, question if not hits else question + ' (the message mentions more than one: ' + ', '.join(hits) + ')'


CHANNEL_WORDS = {'show': r'\b(shows?|booth|canton|market|fair|trade days|first monday|in person)\b', 'tiktok': r'tik\s?tok',
                 'web': r'\b(web|website|online)\b', 'other': r'\bother\b'}
PAY_WORDS = {'cash': r'\bcash\b', 'venmo': r'\bvenmo\b', 'card': r'\b(card|credit|debit|tap to pay)\b', 'zelle': r'\bzelle\b',
             'paypal': r'\bpaypal\b'}
KIND_WORDS = {'bottles': r'\b(bottles?|tubes?|wands?)\b', 'labels': r'\b(labels?|stickers?)\b',
              'hosting': r'\b(hosting|domain)\b', 'show_fees': r'\b(booth|show fees?|vendor fees?|table fees?|entry fees?)\b',
              'ads': r'\b(ads?|advert\w*|boost\w*)\b', 'shipping': r'\b(shipping|postage|stamps?|mailers?|envelopes?)\b',
              'supplies': r'\b(supplies|supply|base|versagel|oils?|flavor\w*|glitter|pigments?|lip liquid|squalane|polybutene|syringes?|gloves|ingredients?)\b',
              'other': r'\bother\b'}


def parse(message, today=None):
    """Plain message -> (action, fields). Raises Unclear with what is missing."""
    today = today or datetime.date.today()
    text = ' '.join(message.strip().split())
    low = text.lower()
    if not text:
        raise Unclear('The message is empty.')
    if re.match(r'^(summary|totals?|report|how are we doing|how is it going|what(\'s| is) in stock|stock\??$)', low):
        return 'summary', {}
    if re.match(r'^(recent|last entries|history)', low):
        return 'recent', {}
    m = re.match(r'^(?:undo|reverse|cancel|take back)\s+(?:entry\s+|number\s+)?#?l?(\d+)\b', low)
    if m:
        return 'undo', {'id': int(m.group(1))}
    if re.match(r'^(undo|reverse|cancel|take back)\b', low):
        raise Unclear('Which entry? Send "undo" and the entry number, like "undo 12".')

    verbs = {'made': r'^(made|make|filled|finished|new batch|batch)\b', 'sale': r'^(sold|sale|sell)\b',
             'expense': r'^(spent|paid|bought|purchased)\b', 'donated': r'^(donated|donation|gift|given|free)\b',
             'count': r'^(count|counted|stock count|inventory|we have)\b'}
    action = next((k for k, pat in verbs.items() if re.match(pat, low)), None)
    if not action:
        raise Unclear('I could not tell what happened. Start the message with one of: made, sold, spent, donated, count, undo, summary.')

    date, rest = find_date(text, today)
    problems = []
    fields = {'date': date} if date else {}

    if action in ('made', 'sale', 'count'):
        items, item_problems = find_items(rest)
        problems += item_problems
        if not items and not item_problems:
            problems.append('Which gloss, and how many? Like "4 Pink Paradise".')
        fields['items'] = [{'key': k, 'qty': q} for k, q in items]

    if action == 'sale':
        channel, q = one_of(rest, CHANNEL_WORDS, 'Where was it sold: show, web, TikTok or other?')
        if q:
            problems.append(q)
        pay, q = one_of(rest, PAY_WORDS, 'How did they pay: cash, Venmo, card, Zelle or PayPal?')
        if q and channel == 'tiktok' and 'more than one' not in q:
            pay, q = 'tiktok', None            # TikTok Shop sales are paid out by TikTok
        if q:
            problems.append(q)
        amounts = find_amounts(rest)
        if len(amounts) != 1:
            problems.append('How much money came in, in total? Write it once with a $ sign, like "$48".' if not amounts
                            else 'I see more than one dollar amount. Send the one total for this sale.')
        else:
            cents, span = amounts[0]
            if re.match(r'\s*(each|apiece|a piece|per)\b', rest.lower()[span[1]:]):
                cents *= sum(i['qty'] for i in fields['items']) or 1
                fields['each'] = True
            fields['total_cents'] = cents
        fields.update(channel=channel, pay=pay)

    if action in ('expense', 'donated'):
        kind, q = one_of(rest, KIND_WORDS, 'What kind of cost is it: supplies, bottles, labels, hosting, show fees, ads, shipping or other?')
        if q:
            problems.append(q)
        amounts = find_amounts(rest)
        what = rest
        if action == 'expense':
            if len(amounts) != 1:
                problems.append('How much did it cost? Write it once with a $ sign, like "$56.50".' if not amounts
                                else 'I see more than one dollar amount. Send one purchase per message.')
            else:
                fields['amount_cents'] = amounts[0][0]
                a, b = amounts[0][1]
                what = rest[:a] + ' ' + rest[b:]
        what = re.sub(r'(?i)^(spent|paid|bought|purchased|donated|donation|gift|given|free)\b[:\s]*', '', what.strip())
        what = re.sub(r'(?i)^(on|for)\b\s*', '', ' '.join(what.split())).strip(' ,.:;')
        if len(what) < 3:
            problems.append('What was it? Like "spent $56.50 on bottles".')
        fields.update(what=what, expense_kind=kind, donated=action == 'donated')
        action = 'expense'

    if problems:
        raise Unclear(problems)
    return action, fields


# ---------- reading back ----------

def lines_text(lines):
    return ', '.join(f"{l['qty']} {l['name']}" for l in lines)


def stock_text(stock):
    return 'In stock now: ' + ', '.join(f"{s['name']} {s['in_stock']}" for s in stock) + '.'


def read_back(entry):
    k, n, who = entry['kind'], entry['id'], entry['sender']
    head = f"RECORDED as entry {n}, sent by {who}"
    if k == 'made':
        body = f"MADE {lines_text(entry['lines'])} on {entry['date']}. " + stock_text(entry['stock'])
    elif k == 'sale':
        tax = (f"tax inside {money(entry['tax_cents'])}" if entry['tax_worked_out'] else 'tax not worked out for this channel')
        body = (f"SOLD {lines_text(entry['lines'])} on {entry['date']}. Where: {CHANNELS[entry['channel']]}. Paid by: {PAY[entry['pay']]}. "
                f"Money in: {money(entry['total_cents'])} ({tax}). ")
        if entry['total_cents'] != entry['list_price_cents']:
            body += f"Note: the normal price for these is {money(entry['list_price_cents'])}. "
        body += stock_text(entry['stock'])
    elif k == 'expense' and entry['donated']:
        body = f"DONATED (no cost): {entry['what']}. Kind: {KINDS[entry['expense_kind']]}. Date: {entry['date']}."
    elif k == 'expense':
        body = f"SPENT {money(entry['amount_cents'])} on: {entry['what']}. Kind: {KINDS[entry['expense_kind']]}. Date: {entry['date']}."
    elif k == 'count':
        body = 'COUNT SET: ' + ', '.join(f"{l['name']} {l['qty']} (was {l['was']})" for l in entry['lines']) + '.'
    elif k == 'undo':
        o = entry['undoes']
        what = lines_text(o['lines']) if o['lines'] else o['what']
        body = f"UNDID entry {o['id']} ({o['kind']}: {what}). Both entries stay in the history."
        if entry['stock']:
            body += ' ' + stock_text(entry['stock'])
        return f"{head}.\n{body}"
    return f"{head}.\n{body}\nWrong? Send: undo {n}"


def summary_text(s):
    out = ['GLIMMER BLISS SUMMARY', '']
    out.append('GLOSS: made / sold / in stock')
    for g in s['glosses']:
        adj = f"  (counts and fixes {g['adjusted']:+d})" if g['adjusted'] else ''
        out.append(f"  {g['name']}: {g['made']} / {g['sold']} / {g['in_stock']}{adj}")
    out.append(f"  ALL: {sum(g['made'] for g in s['glosses'])} / {sum(g['sold'] for g in s['glosses'])} / {sum(g['in_stock'] for g in s['glosses'])}")
    out += ['', 'SALES BY CHANNEL']
    for c in s['channels'].values():
        out.append(f"  {c['label']}: {c['sales']} sales, {c['units']} glosses, {money(c['total_cents'])}")
    out += ['', 'SPENDING BY KIND']
    for k in s['spending'].values():
        if k['n']:
            out.append(f"  {k['label']}: {money(k['cents'])} ({k['n']})")
    if not any(k['n'] for k in s['spending'].values()):
        out.append('  Nothing recorded yet.')
    if s['donated']:
        out += ['', 'DONATED (no cost)'] + [f"  {d['what']} ({KINDS[d['kind']]})" for d in s['donated']]
    m = s['money']
    out += ['', 'MONEY', f"  In: {money(m['in_cents'])}", f"  Tax set aside: {money(m['tax_cents'])}", f"  Spent: {money(m['spent_cents'])}",
            f"  Left: {money(m['left_cents'])}"]
    if s['web_orders_waiting_for_payment']:
        out += ['', f"{s['web_orders_waiting_for_payment']} website order(s) still waiting for payment are not counted."]
    out += ['', 'This only knows what has been entered. Made = batches entered; stock typed in as a count shows under "counts and fixes".']
    return '\n'.join(out)


def recent_text(entries):
    out = ['LAST ENTRIES (newest first)']
    for e in entries:
        what = lines_text(e['lines']) if e['lines'] else e['what']
        extra = f" {money(e['total_cents'])}" if e['kind'] in ('sale', 'expense') and not e['donated'] else ''
        gone = f"  [undone by {e['undone_by']}]" if e['undone_by'] else ''
        out.append(f"  {e['id']}. {e['date']} {e['kind']}: {what}{extra} - {e['sender']}{gone}")
    return '\n'.join(out) if entries else 'No entries yet.'


def run(action, fields, sender, target, text=''):
    banner = '' if target == 'live' else '[TEST COPY on this computer - not the live shop]\n'
    if action == 'summary':
        out = call(target, 'summary')
        print(banner + summary_text(out['summary']) if out.get('ok') else 'ERROR: ' + str(out.get('error')))
        return 0 if out.get('ok') else 1
    if action == 'recent':
        out = call(target, 'recent', n=20)
        print(banner + recent_text(out['entries']) if out.get('ok') else 'ERROR: ' + str(out.get('error')))
        return 0 if out.get('ok') else 1
    if not sender:
        print('NOT RECORDED. Who sent this message? Run again with --by and their name.')
        return 2
    backup_before_live_write(target)
    out = call(target, action, sender=sender, text=text, **fields)
    if out.get('ok'):
        print(banner + read_back(out['entry']))
        return 0
    print(banner + 'NOT RECORDED. ' + str(out.get('error')))
    return 2 if out.get('refused') else 1


def parse_items(spec):
    items = []
    for part in spec.split(','):
        key, _, qty = part.strip().rpartition('=')
        items.append({'key': key.strip(), 'qty': int(qty)})
    return items


def main():
    ap = argparse.ArgumentParser(description='Glimmer Bliss text ledger')
    ap.add_argument('--target', choices=list(TARGETS), default=None)
    sub = ap.add_subparsers(dest='cmd', required=True)
    p = sub.add_parser('say')
    p.add_argument('message')
    p.add_argument('--by', default='')
    p.add_argument('--check', action='store_true', help='only show how the message was understood; record nothing')
    for name in ('summary', 'recent', 'backup', 'ping'):
        sub.add_parser(name)
    for name in ('made', 'count'):
        p = sub.add_parser(name)
        p.add_argument('--items', required=True, help='"pink-paradise=24,glimmer-kiss|With glitter=6"')
    p = sub.add_parser('sold')
    p.add_argument('--items', required=True)
    p.add_argument('--channel', required=True, choices=list(CHANNELS))
    p.add_argument('--pay', required=True, choices=list(PAY))
    p.add_argument('--total', required=True, type=float)
    p.add_argument('--tax', type=float)
    p = sub.add_parser('spent')
    p.add_argument('--amount', type=float, default=0)
    p.add_argument('--what', required=True)
    p.add_argument('--kind', required=True, choices=list(KINDS))
    p.add_argument('--donated', action='store_true')
    p = sub.add_parser('undo')
    p.add_argument('--id', required=True, type=int)
    for name in ('made', 'count', 'sold', 'spent', 'undo'):
        sub.choices[name].add_argument('--by', required=True)
        sub.choices[name].add_argument('--date')
        sub.choices[name].add_argument('--text', default='')
    a = ap.parse_args()
    target = a.target or default_target()

    if a.cmd == 'backup':
        backup(target)
        return 0
    if a.cmd == 'ping':
        out = call(target, 'ping')
        print(json.dumps(out, indent=1))
        return 0 if out.get('ok') else 1
    if a.cmd in ('summary', 'recent'):
        return run(a.cmd, {}, '', target)
    if a.cmd == 'say':
        try:
            action, fields = parse(a.message)
        except Unclear as u:
            print('NOT RECORDED. I need to ask:')
            for q in u.problems:
                print('  - ' + q)
            return 2
        if a.check:
            print('UNDERSTOOD AS:', action, json.dumps(fields))
            return 0
        fields.pop('each', None)
        return run(action, fields, a.by.strip(), target, a.message)

    fields = {'date': a.date} if a.date else {}
    if a.cmd in ('made', 'count'):
        fields['items'] = parse_items(a.items)
        action = a.cmd
    elif a.cmd == 'sold':
        fields.update(items=parse_items(a.items), channel=a.channel, pay=a.pay, total_cents=round(a.total * 100))
        if a.tax is not None:
            fields['tax_cents'] = round(a.tax * 100)
        action = 'sale'
    elif a.cmd == 'spent':
        fields.update(what=a.what, expense_kind=a.kind, donated=a.donated, amount_cents=round(a.amount * 100))
        action = 'expense'
    else:
        fields['id'] = a.id
        action = 'undo'
    return run(action, fields, a.by.strip(), target, a.text)


if __name__ == '__main__':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except AttributeError:
        pass
    sys.exit(main())
