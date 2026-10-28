const fs=require('fs');
const src=fs.readFileSync(process.argv[2],'utf8');
const m=src.match(/const HELP_ANSWERS = (\[[\s\S]*?\n\]);/);
const HELP_ANSWERS=eval(m[1]);
function answer(question){const q=question.toLowerCase();let best=null,bestHits=0,bestLongest=0;
HELP_ANSWERS.forEach(e=>{const mt=e.k.filter(w=>q.includes(w));if(!mt.length)return;const l=Math.max(...mt.map(w=>w.length));if(mt.length>bestHits||(mt.length===bestHits&&l>bestLongest)){bestHits=mt.length;bestLongest=l;best=e;}});return best?best.a:'NONE';}
const tests=[['how much is tax?','8.25%'],['do you charge sales tax','8.25%'],['how much tax','8.25%'],['is there tax','8.25%'],
['can i return my gloss','All sales are final'],['can I get a refund','All sales are final'],['can i exchange it','All sales are final'],
['my gloss arrived broken','replacement'],['it was leaking','replacement'],['i got the wrong color','right one free'],
['how much is shipping','$35 or more'],['when is shipping free','$35 or more'],['how much is pink paradise','$12.00'],['how much does it cost','$12.00'],
['can I pay with venmo','Venmo'],['where is my order','My Orders']];
let fail=0;for(const [q,want] of tests){const a=answer(q);const ok=a.includes(want);if(!ok)fail++;console.log(ok?'PASS':'FAIL',q,'=>',a.replace(/<[^>]+>/g,'').slice(0,70));}
console.log(fail?fail+' FAILED':'ALL PASS');
