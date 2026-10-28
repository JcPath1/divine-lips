import json,urllib.request,sys,sqlite3
B='http://localhost:8080/api/'
def post(ep,d):
    r=urllib.request.Request(B+ep,data=json.dumps(d).encode(),headers={'Content-Type':'application/json'})
    try: return json.load(urllib.request.urlopen(r))
    except urllib.error.HTTPError as e: return json.load(e)
fails=0
def check(name,got,want):
    global fails
    ok=got==want; fails+=not ok
    print('PASS' if ok else 'FAIL',name,'got',got,'want',want)
# The 20%-off code the promo checks use (made here so a fresh test shop works too)
post('quote.php',{'items':[{'id':'love','qty':1}],'delivery':'pickup'})  # makes sure the tables exist
db=sqlite3.connect('private/local-test/data/shop.sqlite')
if not db.execute("SELECT 1 FROM promotions WHERE code='GLOSS20'").fetchone():
    db.execute("INSERT INTO promotions (label,kind,min_subtotal_cents,percent,code,active,created_at) VALUES ('20% off','percent_off',0,20,'GLOSS20',1,'2026-10-27 00:00:00')"); db.commit()
one=[{'id':'pink-paradise','qty':1}]
three=[{'id':'pink-paradise','qty':3}]
kiss2_love1=[{'id':'glimmer-kiss','qty':2,'options':{'glitter':'With glitter'}},{'id':'love','qty':1}]
# pickup $12 -> tax 0.99 (12*0.0825=0.99)
q=post('quote.php',{'items':one,'delivery':'pickup','state':'TX'}); check('pickup 12 tax',q.get('tax_cents'),99); check('pickup 12 total',q['total_cents'],1299)
# ship TX $12 + $7 ship = 19 -> tax 1.5675 -> 157
q=post('quote.php',{'items':one,'delivery':'ship','state':'TX'}); check('ship TX 12 tax',q['tax_cents'],157); check('ship TX total',q['total_cents'],2057)
q=post('quote.php',{'items':one,'delivery':'ship','state':'texas'}); check('ship "texas" tax',q['tax_cents'],157)
q=post('quote.php',{'items':one,'delivery':'ship','state':'Tx.'}); check('ship "Tx." tax',q['tax_cents'],157)
# ship OK state -> no tax
q=post('quote.php',{'items':one,'delivery':'ship','state':'OK'}); check('ship OK tax',q['tax_cents'],0); check('ship OK total',q['total_cents'],1900)
# $36 ship TX -> free shipping, tax on 36 = 2.97
q=post('quote.php',{'items':three,'delivery':'ship','state':'TX'}); check('36 free ship',q['shipping_cents'],0); check('36 tax',q['tax_cents'],297); check('36 total',q['total_cents'],3897); check('promo label',q['promo_label'],'Free shipping on orders of $35 or more')
# $24 ship -> not free; nudge mentions $35
q=post('quote.php',{'items':[{'id':'love','qty':2}],'delivery':'ship','state':'CA'}); check('24 ship charged',q['shipping_cents'],700); check('nudge', 'Add $11.00 more' in q['nudge'], True)
# $42 (15+15+12) ship TX: free ship, tax 3.465 -> 347 (round half up)
q=post('quote.php',{'items':kiss2_love1,'delivery':'ship','state':'TX'}); check('42 tax',q['tax_cents'],347); check('42 total',q['total_cents'],4547)
# GLOSS20 on $36 ship TX: 20% off=7.20 beats free ship 7.00 -> 28.80 + 7 ship = 35.80 -> tax 2.9535 -> 295
q=post('quote.php',{'items':three,'delivery':'ship','state':'TX','promo_code':'GLOSS20'}); check('code discount',q['discount_cents'],720); check('code tax',q['tax_cents'],295); check('code total',q['total_cents'],3580+295)
# real order saves tax
o=post('order.php',{'items':one,'delivery':'pickup','name':'Tax Test','email':'taxtest@example.test','payment_method':'venmo','state':'TX'})
check('order ok',o.get('ok'),True)
s=json.load(urllib.request.urlopen(B+'order-status.php?code='+o['code']+'&t='+o['token']))
od=s.get('order',s)
check('order tax saved',od.get('tax_cents'),99); check('order total',od.get('total_cents'),1299); check('tax label','8.25%' in (od.get('tax_label') or ''),True)
print('ALL PASS' if not fails else f'{fails} FAILED')
