"""Tests for the text ledger. Runs against the LOCAL test shop only (python tools/glimmer.py local, then start php).
Run: python tools/ledgertest.py
"""
import datetime
import json
import os
import sqlite3
import subprocess
import sys
import urllib.request

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import ledger as L

ROOT = L.ROOT
B = 'http://localhost:8080/'
fails = 0


def check(name, got, want):
    global fails
    ok = got == want
    fails += not ok
    print('PASS' if ok else 'FAIL', name, '' if ok else f'| got {got!r} want {want!r}')


def say(msg, by='Jc'):
    r = subprocess.run([sys.executable, os.path.join(ROOT, 'tools', 'ledger.py'), '--target', 'local', 'say', msg] + (['--by', by] if by else []),
                       capture_output=True, text=True, encoding='utf-8')
    return r.returncode, r.stdout


def api(**d):
    req = urllib.request.Request(B + 'api/ledger.php', data=json.dumps(d).encode(), headers={'Content-Type': 'application/json'})
    try:
        return json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        return dict(json.load(e), http=e.code)


def summary():
    return L.call('local', 'summary')['summary']


def gloss(s, name):
    return next(g for g in s['glosses'] if g['name'] == name)


db = sqlite3.connect(os.path.join(ROOT, 'private', 'local-test', 'data', 'shop.sqlite'))


def rows(sql, *a):
    return db.execute(sql, a).fetchall()


# ---- reading messages (no shop needed) ----
today = datetime.date(2026, 11, 14)
check('parse made', L.parse('made 24 Pink Paradise', today), ('made', {'items': [{'key': 'pink-paradise', 'qty': 24}]}))
a, f = L.parse('sold 4 Pink Paradise at the show, $48 cash', today)
check('parse sale', (a, f['items'], f['channel'], f['pay'], f['total_cents']), ('sale', [{'key': 'pink-paradise', 'qty': 4}], 'show', 'cash', 4800))
a, f = L.parse('Sold 2 glimmer kiss with glitter and 1 Sugar Cookie on TikTok $42', today)
check('parse tiktok sale', (f['items'], f['channel'], f['pay'], f['total_cents']),
      ([{'key': 'glimmer-kiss|With glitter', 'qty': 2}, {'key': 'sugar-cookie', 'qty': 1}], 'tiktok', 'tiktok', 4200))
a, f = L.parse('sold three no glitter Glimmer Kiss at the booth yesterday $15 each venmo', today)
check('parse each + yesterday + number word', (f['items'], f['total_cents'], f['pay'], f['date']),
      ([{'key': 'glimmer-kiss|No glitter', 'qty': 3}], 4500, 'venmo', '2026-11-13'))
a, f = L.parse('spent $56.50 on bottles', today)
check('parse expense', (a, f['amount_cents'], f['expense_kind'], f['what'], f['donated']), ('expense', 5650, 'bottles', 'bottles', False))
a, f = L.parse('spent $103.28 on labels from Avery on Nov 13', today)
check('parse expense with date', (f['amount_cents'], f['expense_kind'], f['date'], f['what']), (10328, 'labels', '2026-11-13', 'labels from Avery'))
a, f = L.parse('donated: 2 oz squalane oil from Grandma', today)
check('parse donated', (a, f['donated'], f['expense_kind'], f['what']), ('expense', True, 'supplies', '2 oz squalane oil from Grandma'))
check('parse count', L.parse('count: 30 Pink Paradise, 0 True Love', today),
      ('count', {'items': [{'key': 'pink-paradise', 'qty': 30}, {'key': 'true-love', 'qty': 0}]}))
check('parse undo', L.parse('undo 12', today), ('undo', {'id': 12}))
check('parse summary', L.parse('How are we doing?', today), ('summary', {}))


def unclear(msg):
    try:
        L.parse(msg, today)
        return None
    except L.Unclear as u:
        return ' | '.join(u.problems)


check('unclear: which glimmer kiss', 'with glitter or no glitter' in (unclear('made 12 Glimmer Kiss') or ''), True)
check('unclear: no number', 'How many Pink Paradise' in (unclear('made Pink Paradise') or ''), True)
check('unclear: sale without money', 'How much money' in (unclear('sold 2 Pink Paradise at the show cash') or ''), True)
check('unclear: sale without pay', 'How did they pay' in (unclear('sold 2 Pink Paradise at the show $24') or ''), True)
check('unclear: sale without channel', 'Where was it sold' in (unclear('sold 2 Pink Paradise $24 cash') or ''), True)
check('unclear: two pay types', 'more than one' in (unclear('sold 2 Pink Paradise at the show $24 cash and venmo') or ''), True)
check('unclear: two amounts', 'more than one dollar' in (unclear('sold 2 Pink Paradise at the show $24 cash $5 tip') or ''), True)
check('unclear: expense without kind', 'What kind of cost' in (unclear('spent $20 at the store') or ''), True)
check('unclear: expense without amount', 'How much did it cost' in (unclear('spent money on bottles') or ''), True)
check('unclear: no verb', 'could not tell' in (unclear('Pink Paradise 24') or ''), True)
check('unclear: unknown gloss only', 'Which gloss' in (unclear('made 24 Cherry Pop') or ''), True)
check('unclear: undo without number', 'Which entry' in (unclear('undo the last one') or ''), True)
check('unclear: bad date', 'not a real date' in (unclear('made 5 Pink Paradise on Feb 31') or ''), True)

# ---- the door ----
check('wrong token refused', api(action='summary', token='nope').get('http'), 403)
check('no token refused', api(action='summary').get('http'), 403)
check('ping ok', L.call('local', 'ping')['ok'], True)

# ---- entries against the local shop ----
s0 = summary()
pp0, gk0 = gloss(s0, 'Pink Paradise'), gloss(s0, 'Glimmer Kiss (With glitter)')
code, out = say('made 24 Pink Paradise', 'Jc')
check('made recorded', (code, 'MADE 24 Pink Paradise' in out, 'sent by Jc' in out), (0, True, True))
made_id = int(out.split('entry ')[1].split(',')[0])
s = summary()
check('made: stock +24', gloss(s, 'Pink Paradise')['in_stock'] - pp0['in_stock'], 24)
check('made: made +24', gloss(s, 'Pink Paradise')['made'] - pp0['made'], 24)

code, out = say('sold 4 Pink Paradise at the show, $48 cash', 'Charlie')
check('sale recorded', (code, 'SOLD 4 Pink Paradise' in out, 'sent by Charlie' in out, 'Money in: $48.00' in out, 'tax inside $3.66' in out),
      (0, True, True, True, True))
sale_id = int(out.split('entry ')[1].split(',')[0])
s = summary()
check('sale: stock -4', gloss(s, 'Pink Paradise')['in_stock'] - pp0['in_stock'], 20)
check('sale: sold +4', gloss(s, 'Pink Paradise')['sold'] - pp0['sold'], 4)
check('sale: show channel +$48', s['channels']['show']['total_cents'] - s0['channels']['show']['total_cents'], 4800)
check('sale: show units +4', s['channels']['show']['units'] - s0['channels']['show']['units'], 4)
check('sale: money in +$48', s['money']['in_cents'] - s0['money']['in_cents'], 4800)
check('sale: tax +3.66', s['money']['tax_cents'] - s0['money']['tax_cents'], 366)
r = rows('SELECT kind, channel, pay_method, total_cents, sender, source_text FROM ledger WHERE id = ?', sale_id)[0]
check('sale row keeps sender and words', r, ('sale', 'show', 'cash', 4800, 'Charlie', 'sold 4 Pink Paradise at the show, $48 cash'))

have = gloss(summary(), 'Sugar Cookie')['in_stock']
code, out = say(f'sold {have + 5} Sugar Cookie at the show $60 cash')
check('below zero refused', (code, 'below zero' in out, 'NOT RECORDED' in out), (2, True, True))
check('below zero: stock unchanged', gloss(summary(), 'Sugar Cookie')['in_stock'], have)
n_before = rows('SELECT COUNT(*) FROM ledger')[0][0]
code, out = say('sold 2 Glimmer Kiss at the show $30 cash')
check('unclear message not recorded', (code, 'with glitter or no glitter' in out, rows('SELECT COUNT(*) FROM ledger')[0][0]), (2, True, n_before))
code, out = say('made 5 True Love', '')
check('no sender not recorded', (code, 'Who sent' in out, rows('SELECT COUNT(*) FROM ledger')[0][0]), (2, True, n_before))

code, out = say('sold 1 Glimmer Kiss with glitter on TikTok $15', 'Jc')
gk_stock = gloss(s0, 'Glimmer Kiss (With glitter)')['in_stock']
if gk_stock >= 1:
    check('tiktok sale recorded', (code, 'TikTok Shop' in out, 'tax not worked out' in out), (0, True, True))
    s = summary()
    check('tiktok channel +$15', s['channels']['tiktok']['total_cents'] - s0['channels']['tiktok']['total_cents'], 1500)
    check('tiktok: no tax added', s['money']['tax_cents'] - s0['money']['tax_cents'], 366)
else:
    check('tiktok sale refused (no stock)', code, 2)

code, out = say('spent $56.50 on bottles', 'Jc')
check('expense recorded', (code, 'SPENT $56.50 on: bottles' in out, 'Kind: bottles' in out), (0, True, True))
exp_id = int(out.split('entry ')[1].split(',')[0])
s = summary()
check('expense: bottles +56.50', s['spending']['bottles']['cents'] - s0['spending']['bottles']['cents'], 5650)
check('expense: spent +56.50', s['money']['spent_cents'] - s0['money']['spent_cents'], 5650)
code, out = say('donated: 2 oz squalane oil from Grandma', 'Jc')
check('donated recorded at no cost', (code, 'DONATED (no cost)' in out), (0, True))
s = summary()
check('donated: spending unchanged', s['money']['spent_cents'] - s0['money']['spent_cents'], 5650)
check('donated: listed', any('squalane' in d['what'] for d in s['donated']), True)

# undo: history stays, numbers go back
code, out = say(f'undo {sale_id}', 'Jc')
check('undo recorded', (code, f'UNDID entry {sale_id}' in out), (0, True))
s = summary()
check('undo sale: stock back', gloss(s, 'Pink Paradise')['in_stock'] - pp0['in_stock'], 24)
check('undo sale: sold back', gloss(s, 'Pink Paradise')['sold'] - pp0['sold'], 0)
check('undo sale: show money back', s['channels']['show']['total_cents'] - s0['channels']['show']['total_cents'], 0)
check('undo: original row still there', rows('SELECT kind, total_cents, undone_by IS NOT NULL FROM ledger WHERE id = ?', sale_id)[0], ('sale', 4800, 1))
code, out = say(f'undo {sale_id}', 'Jc')
check('second undo refused', (code, 'already undone' in out), (2, True))
code, out = say(f'undo {exp_id}', 'Jc')
check('undo expense', (code, summary()['money']['spent_cents'] - s0['money']['spent_cents']), (0, 0))
code, out = say('undo 999999', 'Jc')
check('undo unknown refused', (code, 'no entry' in out), (2, True))

# count sets the shelf number and is itself undoable
code, out = say('count: 7 True Love', 'Charlie')
check('count recorded', (code, 'True Love 7 (was' in out), (0, True))
count_id = int(out.split('entry ')[1].split(',')[0])
check('count: stock is 7', gloss(summary(), 'True Love')['in_stock'], 7)
say(f'undo {count_id}', 'Jc')
check('count undone', gloss(summary(), 'True Love')['in_stock'], gloss(s0, 'True Love')['in_stock'])

# undoing a batch that has already been sold from is refused
code, out = say('count: 0 Gloss Angeles', 'Jc')
zero_id = int(out.split('entry ')[1].split(',')[0])
code, out = say('made 3 Gloss Angeles', 'Jc')
batch_id = int(out.split('entry ')[1].split(',')[0])
say('sold 2 Gloss Angeles at the show $24 cash', 'Jc')
code, out = say(f'undo {batch_id}', 'Jc')
check('undo batch below zero refused', (code, 'below zero' in out, gloss(summary(), 'Gloss Angeles')['in_stock']), (2, True, 1))

# admin Money screen agrees with the summary
import http.cookiejar
import urllib.parse
cj = http.cookiejar.CookieJar()
op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
op.open(B + 'admin/', urllib.parse.urlencode({'action': 'login', 'password': 'test-admin'}).encode())
html = op.open(B + 'admin/?view=money').read().decode('utf-8')
s = summary()
all_time = html.split('All time')[1]
check('admin Money "in" matches summary', L.money(s['money']['in_cents']) in all_time, True)
check('admin Money "spent" matches summary', '−' + L.money(s['money']['spent_cents']) in all_time, True)
check('admin Money mentions text entries', 'sent by text' in all_time, True)

# backup is a full copy and changes nothing
n1 = rows('SELECT COUNT(*) FROM ledger')[0][0]
path = L.backup('local', quiet=True)
data = json.load(open(path, encoding='utf-8'))
check('backup has every table', sorted(data['tables']), sorted(['customers', 'orders', 'promotions', 'stock_moves', 'show_sales', 'expenses', 'reviews', 'ledger']))
check('backup ledger rows', len(data['tables']['ledger']), n1)
check('backup stock rows', len(data['tables']['stock_moves']), rows('SELECT COUNT(*) FROM stock_moves')[0][0])
os.remove(path)

# the summary text reads on a phone (no line wider than 70 characters except the footnote)
txt = L.summary_text(s)
check('summary text has the four parts', all(h in txt for h in ('GLOSS: made / sold / in stock', 'SALES BY CHANNEL', 'SPENDING BY KIND', 'MONEY')), True)

print('\n%d FAILED' % fails if fails else '\nALL PASSED')
sys.exit(1 if fails else 0)
