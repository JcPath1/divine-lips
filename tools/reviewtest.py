import json,re,urllib.request,urllib.parse,http.cookiejar,sqlite3,io
# Reviews: customers write them, NOTHING shows until the admin approves.
# Run against the local test shop (python tools/glimmer.py local + php -S localhost:8080).
B='http://localhost:8080/'
cj=http.cookiejar.CookieJar(); op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
def get(path): return op.open(B+path).read().decode('utf-8')
def post(path,data): return op.open(B+path,urllib.parse.urlencode(data,doseq=True).encode()).read().decode('utf-8')
def api(ep,d=None):
    r=urllib.request.Request(B+'api/'+ep,data=None if d is None else json.dumps(d).encode(),headers={'Content-Type':'application/json'})
    try: return json.load(urllib.request.urlopen(r))
    except urllib.error.HTTPError as e: return json.load(e)
fails=0
def check(n,got,want):
    global fails; ok=got==want; fails+=not ok; print('PASS' if ok else 'FAIL',n,'| got',got,'want',want)
api('reviews.php')  # makes sure the tables exist
db=sqlite3.connect('private/local-test/data/shop.sqlite')
db.execute("DELETE FROM reviews"); db.execute("DELETE FROM attempts WHERE kind='review'"); db.commit()
def rows(): return db.execute('SELECT name,status,order_code FROM reviews ORDER BY id').fetchall()

check('review page loads','id="reviewForm"' in get('review/'),True)
check('no stars refused',api('review.php',{'product':'sugar-cookie','body':'Love it','name':'Emma'})['error'],'Please tap the stars to pick 1 to 5.')
check('no name refused',api('review.php',{'stars':5,'body':'Love it','name':' '})['error'],'Please type your first name.')
check('bad email refused','email' in api('review.php',{'stars':5,'body':'Love it','name':'Emma','email':'nope'})['error'],True)
check('bot (hidden box) ignored',api('review.php',{'stars':5,'body':'spam','name':'Bot','website':'x.com'})['ok'],True)
check('bot saved nothing',rows(),[])
r=api('review.php',{'product':'sugar-cookie','stars':5,'body':'Smells like cookies!\nSo shiny.','name':'emma smith','email':'emma@example.test'})
check('good review accepted',r['ok'],True)
check('saved as waiting',rows(),[('emma smith','pending','')])
check('waiting review NOT on site',api('reviews.php?product=sugar-cookie')['count'],0)
log=io.open('private/local-test/data/emails.txt',encoding='utf-8').read()
check('owner emailed',log.rstrip().split('==== ')[-1].count('New review waiting'),1)

post('admin/',{'action':'login','password':'test-admin'})
h=get('admin/?view=reviews'); csrf=re.search(r'name="csrf" value="([0-9a-f]+)"',h).group(1)
check('admin tab shows count','⭐ Reviews (1)' in h,True)
check('admin shows email','emma@example.test' in h,True)
rid=db.execute('SELECT id FROM reviews').fetchone()[0]
h=post('admin/',{'csrf':csrf,'action':'review_approve','id':rid}); check('approve flash','Approved' in h,True)
pub=api('reviews.php?product=sugar-cookie')
check('approved shows',pub['count'],1); check('average',pub['average'],5)
check('name shown short',pub['reviews'][0]['name'],'Emma S.')
check('email never public','emma@example.test' in json.dumps(pub),False)
check('other gloss has none',api('reviews.php?product=pink-paradise')['count'],0)
post('admin/',{'csrf':csrf,'action':'review_hide','id':rid}); check('hidden is off site',api('reviews.php?product=sugar-cookie')['count'],0)
post('admin/',{'csrf':csrf,'action':'review_delete','id':rid}); check('deleted',rows(),[])

# Verified buyer: a paid order's email link (o + t) marks it
o=api('order.php',{'items':[{'id':'pink-paradise','qty':1}],'delivery':'pickup','name':'Review Test','email':'rt@example.test','payment_method':'venmo','state':'TX'})
api('review.php',{'product':'pink-paradise','stars':4,'body':'Nice','name':'Ava','order':o['code'],'token':o['token']})
check('unpaid order not verified',rows()[-1][2],'')
post('admin/',{'csrf':csrf,'action':'mark_paid','code':o['code']})
log=io.open('private/local-test/data/emails.txt',encoding='utf-8').read()
last=[e for e in log.split('==== ') if 'Payment received' in e][-1]
check('receipt has review link','/review/?o='+o['code']+'&t='+o['token'] in last,True)
api('review.php',{'product':'pink-paradise','stars':4,'body':'Nice','name':'Ava','order':o['code'],'token':o['token']})
check('paid order verified',rows()[-1][2],o['code'])
api('review.php',{'stars':3,'body':'Hmm','name':'Zed','order':o['code'],'token':'wrong'})
check('wrong token not verified',rows()[-1][2],'')
post('admin/',{'csrf':csrf,'action':'mark_picked_up','code':o['code']})
log=io.open('private/local-test/data/emails.txt',encoding='utf-8').read()
check('pickup email has review link','Leave a review' in [e for e in log.split('==== ') if 'picking up' in e][-1],True)
# 5 reviews an hour per person, then a polite no (3 sent above + 2 more = 5)
api('review.php',{'stars':5,'body':'One more','name':'Ava'}); api('review.php',{'stars':5,'body':'Two more','name':'Ava'})
check('limit after 5',api('review.php',{'stars':5,'body':'Too many','name':'Ava'})['ok'],False)
db.execute("DELETE FROM reviews"); db.execute("DELETE FROM attempts WHERE kind='review'"); db.commit()
print('ALL PASS' if not fails else f'{fails} FAILED')
