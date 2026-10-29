import json,re,urllib.request,urllib.parse,http.cookiejar,sqlite3
B='http://localhost:8080/'
cj=http.cookiejar.CookieJar(); op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
def get(path): return op.open(B+path).read().decode('utf-8')
def post(path,data): return op.open(B+path,urllib.parse.urlencode(data,doseq=True).encode()).read().decode('utf-8')
def api(ep,d):
    r=urllib.request.Request(B+'api/'+ep,data=json.dumps(d).encode(),headers={'Content-Type':'application/json'}); return json.load(urllib.request.urlopen(r))
fails=0
def check(n,got,want):
    global fails; ok=got==want; fails+=not ok; print('PASS' if ok else 'FAIL',n,'| got',got,'want',want)
db=sqlite3.connect('private/local-test/data/shop.sqlite')
def count(key): return db.execute('SELECT COALESCE(SUM(change_qty),0) FROM stock_moves WHERE item_key=?',(key,)).fetchone()[0]
post('admin/',{'action':'login','password':'test-admin'})
html=get('admin/?view=stock'); csrf=re.search(r'name="csrf" value="([0-9a-f]+)"',html).group(1)
check('stock page has 6 glosses (kiss x2)', html.count('class="tile" id='),6)
base_pp=count('pink-paradise'); base_gk=count('glimmer-kiss|With glitter')
h=post('admin/',{'csrf':csrf,'action':'stock_made','key':'pink-paradise','n':'10'}); check('made 10 flash','Added 10 Pink Paradise' in h,True)
post('admin/',{'csrf':csrf,'action':'stock_made','key':'glimmer-kiss|With glitter','n':'5'})
post('admin/',{'csrf':csrf,'action':'stock_add','key':'pink-paradise'}); post('admin/',{'csrf':csrf,'action':'stock_remove','key':'pink-paradise'}); post('admin/',{'csrf':csrf,'action':'stock_remove','key':'pink-paradise'})
check('pink count after +10 +1 -1 -1',count('pink-paradise')-base_pp,9)
# booth sale: 2 pink + 1 kiss glitter, cash => $39
h=post('admin/',{'csrf':csrf,'action':'sell','pay':'cash','qty[pink-paradise]':'2','qty[glimmer-kiss|With glitter]':'1','qty[sugar-cookie]':'0'})
check('sold flash $39','Sold! $39.00' in h,True)
sale=db.execute('SELECT id,total_cents,tax_cents,pay_method FROM show_sales ORDER BY id DESC LIMIT 1').fetchone()
check('sale total',sale[1],3900); check('tax inside 39 (=2.97)',sale[2],297); check('pay',sale[3],'cash')
check('pink after sale',count('pink-paradise')-base_pp,7); check('kiss after sale',count('glimmer-kiss|With glitter')-base_gk,4)
h=post('admin/',{'csrf':csrf,'action':'sell','pay':'card','qty[sugar-cookie]':'1'}); check('card sale accepted','Sold! $12.00' in h,True)
card=db.execute('SELECT id,pay_method FROM show_sales ORDER BY id DESC LIMIT 1').fetchone(); check('card pay saved',card[1],'card')
post('admin/',{'csrf':csrf,'action':'sale_undo','id':card[0]})
h=post('admin/',{'csrf':csrf,'action':'sell','pay':'bitcoin','qty[sugar-cookie]':'1'}); check('unknown pay rejected','Tap a gloss first' in h,True)
h=post('admin/',{'csrf':csrf,'action':'sell','pay':'cash'}); check('empty sale rejected','Tap a gloss first' in h,True)
h=post('admin/',{'csrf':csrf,'action':'sale_undo','id':sale[0]}); check('undo flash','taken back' in h,True)
check('pink back after undo',count('pink-paradise')-base_pp,9)
h=post('admin/',{'csrf':csrf,'action':'sale_undo','id':sale[0]}); check('double undo refused','already taken back' in h,True)
check('pink unchanged after double undo',count('pink-paradise')-base_pp,9)
# online order: 3 pink pickup, mark paid -> -3; cancel -> +3; reopen+paid -> -3
o=api('order.php',{'items':[{'id':'pink-paradise','qty':3}],'delivery':'pickup','name':'Stock Test','email':'stock@example.test','payment_method':'venmo','state':'TX'})
check('order before paid no change',count('pink-paradise')-base_pp,9)
post('admin/',{'csrf':csrf,'action':'mark_paid','code':o['code']}); check('after mark paid',count('pink-paradise')-base_pp,6)
post('admin/',{'csrf':csrf,'action':'mark_picked_up','code':o['code']}); check('picked up no double',count('pink-paradise')-base_pp,6)
post('admin/',{'csrf':csrf,'action':'cancel','code':o['code']}); check('cancel puts back',count('pink-paradise')-base_pp,9)
post('admin/',{'csrf':csrf,'action':'reopen','code':o['code']}); post('admin/',{'csrf':csrf,'action':'mark_paid','code':o['code']}); check('repaid takes again',count('pink-paradise')-base_pp,6)
# expenses
h=post('admin/',{'csrf':csrf,'action':'expense_add','what':'Gloss bottles','amount':'$24.99','spent_on':'2026-10-20'}); check('expense saved','Saved: Gloss bottles $24.99' in h,True)
h=post('admin/',{'csrf':csrf,'action':'expense_add','what':'','amount':'5'}); check('blank expense refused','Type what you bought' in h,True)
# sale again for money page
post('admin/',{'csrf':csrf,'action':'sell','pay':'venmo','qty[sugar-cookie]':'1'})
m=get('admin/?view=money'); check('money page loads','You kept' in m or 'Still to earn back' in m,True); check('money page shows tax row','Tax to save' in m,True)
s=get('admin/?view=sell'); check('sell page: 3 pay buttons',s.count('name="pay"'),3); check('sell page today list','Oops' in s,True)
sp=get('admin/?view=spent'); check('spent list','Gloss bottles' in sp,True)
check('orders page still works','Check payment' in get('admin/?view=orders'),True)
print('ALL PASS' if not fails else f'{fails} FAILED')
# floor: true-love is 0 -> minus does nothing
tl=count('true-love')
if tl<=0:
    post('admin/',{'csrf':csrf,'action':'stock_remove','key':'true-love'}); check('minus at 0 stays',count('true-love'),tl)
h=get('admin/?view=stock'); check('placeholder How many', 'placeholder="How many?"' in h, True)
