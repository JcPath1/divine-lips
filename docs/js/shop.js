// Glimmer Bliss - the real shop: cart, checkout, paying, and "My Orders".
// Prices always come from the server (api/catalog.php), never from this file.

const GB_ROOT = (document.currentScript && document.currentScript.src)
  ? document.currentScript.src.replace(/js\/shop\.js.*$/, '')
  : '/';
const GB_CART_KEY = 'gbCart';

// ---------- small helpers ----------

function gbMoney(cents) {
  return '$' + (cents / 100).toFixed(2);
}

function gbEsc(s) {
  const d = document.createElement('div');
  d.textContent = s == null ? '' : String(s);
  return d.innerHTML;
}

async function gbApi(path, body) {
  const opts = body === undefined
    ? { credentials: 'same-origin' }
    : { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) };
  let res;
  try {
    res = await fetch(GB_ROOT + 'api/' + path, opts);
  } catch (e) {
    throw new Error("We couldn't reach the shop. Check your internet and try again.");
  }
  let data = null;
  try { data = await res.json(); } catch (e) { /* not JSON */ }
  if (!data || !data.ok) {
    throw new Error((data && data.error) || 'Something went wrong. Please try again.');
  }
  return data;
}

let gbCatalogPromise = null;
function gbCatalog() {
  if (!gbCatalogPromise) {
    gbCatalogPromise = gbApi('catalog.php');
  }
  return gbCatalogPromise;
}

// ---------- the cart (kept in this browser) ----------

function gbCart() {
  try {
    const items = JSON.parse(localStorage.getItem(GB_CART_KEY) || '[]');
    return Array.isArray(items) ? items : [];
  } catch (e) {
    return [];
  }
}

function gbSaveCart(items) {
  try { localStorage.setItem(GB_CART_KEY, JSON.stringify(items)); } catch (e) { /* private mode */ }
  gbUpdateCartCount();
}

function gbSameOptions(a, b) {
  return JSON.stringify(a || {}) === JSON.stringify(b || {});
}

function gbAddToCart(id, options, qty) {
  const items = gbCart();
  const found = items.find(i => i.id === id && gbSameOptions(i.options, options));
  if (found) {
    found.qty = Math.min(20, found.qty + qty);
  } else {
    items.push({ id: id, options: options, qty: qty });
  }
  gbSaveCart(items);
}

function gbUpdateCartCount() {
  const count = gbCart().reduce((n, i) => n + (i.qty || 0), 0);
  document.querySelectorAll('#cartCount').forEach(el => { el.textContent = count; });
}

// ---------- every page ----------

function gbSetupHeader() {
  gbUpdateCartCount();
  // Old demo cart only stored a number - clear it out
  try { localStorage.removeItem('glimmerBlissCart'); } catch (e) { /* ignore */ }

  // The pink bar at the top shows whatever deals are switched on in Admin
  const bar = document.querySelector('.announcement-bar');
  gbCatalog().then(cat => {
    if (!bar) return;
    if (cat.promotions.length) {
      bar.textContent = cat.promotions.map(p => p.label).join('  ✨  ');
    } else {
      bar.style.display = 'none';
    }
  }).catch(() => { /* keep the bar's built-in text */ });
}

// Product pages: <button id="addToCart" data-product="pink-paradise">
function gbSetupAddToCart() {
  const btn = document.getElementById('addToCart');
  if (!btn || !btn.dataset.product) return;
  btn.addEventListener('click', function() {
    const options = {};
    document.querySelectorAll('.product-options[data-option]').forEach(group => {
      const picked = group.querySelector('.option-btn.selected');
      if (picked && picked.dataset.value) {
        options[group.dataset.option] = picked.dataset.value;
      }
    });
    gbAddToCart(btn.dataset.product, options, 1);

    const note = document.getElementById('cartNotification');
    if (note) {
      note.classList.add('show');
      setTimeout(() => note.classList.remove('show'), 3000);
    }
    const original = btn.textContent;
    btn.textContent = 'Added!';
    btn.classList.add('added');
    setTimeout(() => {
      btn.textContent = original;
      btn.classList.remove('added');
    }, 2000);
  });
}

// ---------- cart page ----------

async function gbRenderCartPage() {
  const box = document.getElementById('cartPage');
  if (!box) return;
  const items = gbCart();
  if (!items.length) {
    box.innerHTML = '<div class="shop-empty"><p>Your cart is empty.</p><a class="shop-btn" href="index.html#products">Shop the glosses 💄</a></div>';
    return;
  }
  let cat;
  try {
    cat = await gbCatalog();
  } catch (e) {
    box.innerHTML = '<p class="shop-error">' + gbEsc(e.message) + '</p>';
    return;
  }

  // Drop anything no longer sold
  const known = items.filter(i => cat.products[i.id]);
  if (known.length !== items.length) gbSaveCart(known);

  let subtotal = 0;
  const rows = known.map((item, index) => {
    const p = cat.products[item.id];
    const line = p.price_cents * item.qty;
    subtotal += line;
    const opts = Object.values(item.options || {}).join(', ');
    return '<div class="cart-row">'
      + '<img src="' + gbEsc(GB_ROOT + p.image) + '" alt="">'
      + '<div class="cart-row-info"><strong>' + gbEsc(p.name) + '</strong>'
      + (opts ? '<span class="cart-row-opts">' + gbEsc(opts) + '</span>' : '')
      + (p.available ? '' : '<span class="shop-error">Sold out - please remove</span>')
      + '<span class="cart-row-price">' + gbMoney(p.price_cents) + ' each</span></div>'
      + '<div class="qty"><button type="button" data-i="' + index + '" data-d="-1" aria-label="One less">−</button>'
      + '<span>' + item.qty + '</span>'
      + '<button type="button" data-i="' + index + '" data-d="1" aria-label="One more">+</button></div>'
      + '<div class="cart-row-total">' + gbMoney(line) + '<button type="button" class="link-btn" data-remove="' + index + '">Remove</button></div>'
      + '</div>';
  }).join('');

  const nudge = cat.promotions
    .filter(p => p.min_subtotal_cents > subtotal)
    .map(p => 'Add ' + gbMoney(p.min_subtotal_cents - subtotal) + ' more to get: ' + p.label)[0];

  box.innerHTML = rows
    + '<div class="cart-summary">'
    + '<div class="cart-subtotal">Glosses: <strong>' + gbMoney(subtotal) + '</strong></div>'
    + (nudge ? '<p class="shop-nudge">✨ ' + gbEsc(nudge) + '</p>' : '')
    + '<p class="shop-hint">Shipping, deals and Texas sales tax are added on the next page. Local pickup is free.</p>'
    + '<a class="shop-btn" href="checkout.html">Checkout →</a>'
    + '</div>';

  box.querySelectorAll('.qty button').forEach(b => b.addEventListener('click', () => {
    const list = gbCart();
    const i = +b.dataset.i;
    list[i].qty = Math.max(1, Math.min(20, list[i].qty + (+b.dataset.d)));
    gbSaveCart(list);
    gbRenderCartPage();
  }));
  box.querySelectorAll('[data-remove]').forEach(b => b.addEventListener('click', () => {
    const list = gbCart();
    list.splice(+b.dataset.remove, 1);
    gbSaveCart(list);
    gbRenderCartPage();
  }));
}

// ---------- checkout page ----------

const GB_METHOD_TEXT = {
  venmo: { title: 'Venmo', note: 'Pay in the Venmo app. Put your order code in the note.' },
  zelle: { title: 'Zelle', note: "Pay from your bank's app. Put your order code in the memo." },
  paypal: { title: 'PayPal or debit/credit card', note: 'Pay safely through PayPal. No PayPal account needed for cards.' }
};

async function gbSetupCheckout() {
  const form = document.getElementById('checkoutForm');
  if (!form) return;
  const summary = document.getElementById('checkoutSummary');
  const errorBox = document.getElementById('checkoutError');
  const shipFields = document.getElementById('shipFields');
  const promoMsg = document.getElementById('promoMessage');

  if (!gbCart().length) {
    form.innerHTML = '<div class="shop-empty"><p>Your cart is empty.</p><a class="shop-btn" href="index.html#products">Shop the glosses 💄</a></div>';
    return;
  }

  // Payment choices that are switched on
  try {
    const cat = await gbCatalog();
    const wrap = document.getElementById('payMethods');
    wrap.innerHTML = cat.payment_methods.map((m, i) =>
      '<label class="choice"><input type="radio" name="payment_method" value="' + m + '"' + (i === 0 ? ' checked' : '') + '>'
      + '<span><strong>' + GB_METHOD_TEXT[m].title + '</strong><small>' + GB_METHOD_TEXT[m].note + '</small></span></label>'
    ).join('') || '<p class="shop-error">Payments are not switched on yet.</p>';
  } catch (e) {
    errorBox.textContent = e.message;
  }

  // Returning customers: fill in what we know
  gbApi('me.php').then(me => {
    if (!me.logged_in) return;
    form.email.value = form.email.value || me.email;
    if (me.saved) {
      ['name', 'phone', 'address1', 'address2', 'city', 'state', 'zip'].forEach(k => {
        if (form[k] && !form[k].value) form[k].value = me.saved[k] || '';
      });
    }
  }).catch(() => {});

  function delivery() {
    return form.querySelector('input[name="delivery"]:checked').value;
  }

  let quoteTimer = null;
  async function refreshQuote() {
    shipFields.hidden = delivery() !== 'ship';
    shipFields.querySelectorAll('[data-required]').forEach(el => { el.required = delivery() === 'ship'; });
    try {
      const q = await gbApi('quote.php', { items: gbCart(), delivery: delivery(), state: form.state.value, promo_code: form.promo_code.value });
      summary.innerHTML = q.lines.map(l => '<div class="sum-row"><span>' + l.qty + ' × ' + gbEsc(l.label) + '</span><span>' + gbMoney(l.total_cents) + '</span></div>').join('')
        + '<div class="sum-row sum-line"><span>Glosses</span><span>' + gbMoney(q.subtotal_cents) + '</span></div>'
        + (q.discount_cents ? '<div class="sum-row"><span>Discount</span><span>−' + gbMoney(q.discount_cents) + '</span></div>' : '')
        + '<div class="sum-row"><span>' + (delivery() === 'ship' ? 'Shipping' : 'Local pickup') + '</span><span>'
        + (q.shipping_cents ? gbMoney(q.shipping_cents) : 'FREE')
        + (q.shipping_before_promo_cents && !q.shipping_cents ? ' <s>' + gbMoney(q.shipping_before_promo_cents) + '</s>' : '') + '</span></div>'
        + (q.tax_cents ? '<div class="sum-row"><span>' + gbEsc(q.tax_label) + '</span><span>' + gbMoney(q.tax_cents) + '</span></div>' : '')
        + (q.promo_label ? '<div class="sum-promo">🎉 ' + gbEsc(q.promo_label) + ' - you save ' + gbMoney(q.promo_savings_cents) + '</div>' : '')
        + '<div class="sum-row sum-total"><span>Total</span><span>' + gbMoney(q.total_cents) + '</span></div>'
        + (q.nudge ? '<p class="shop-nudge">✨ ' + gbEsc(q.nudge) + '</p>' : '');
      promoMsg.textContent = q.code_message || (form.promo_code.value && q.promo_label ? 'Code applied!' : '');
      errorBox.textContent = '';
    } catch (e) {
      errorBox.textContent = e.message;
    }
  }

  form.querySelectorAll('input[name="delivery"]').forEach(r => r.addEventListener('change', refreshQuote));
  document.getElementById('applyPromo').addEventListener('click', refreshQuote);
  [form.promo_code, form.state].forEach(input => input.addEventListener('input', () => {
    clearTimeout(quoteTimer);
    quoteTimer = setTimeout(refreshQuote, 600);
  }));
  refreshQuote();

  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Placing your order...';
    errorBox.textContent = '';
    const data = Object.fromEntries(new FormData(form).entries());
    data.items = gbCart();
    try {
      const r = await gbApi('order.php', data);
      gbSaveCart([]);
      window.location.href = GB_ROOT + 'order.html?code=' + encodeURIComponent(r.code) + '&t=' + encodeURIComponent(r.token) + '&new=1';
    } catch (err) {
      errorBox.textContent = err.message;
      button.disabled = false;
      button.textContent = 'Place my order';
    }
  });
}

// ---------- order + pay page ----------

function gbLoadScript(src, attrs) {
  return new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = src;
    Object.entries(attrs || {}).forEach(([k, v]) => s.setAttribute(k, v));
    s.onload = resolve;
    s.onerror = () => reject(new Error('Could not load ' + src));
    document.head.appendChild(s);
  });
}

function gbOrderTable(o) {
  return '<div class="sum-box">'
    + o.items.map(l => '<div class="sum-row"><span>' + l.qty + ' × ' + gbEsc(l.label) + '</span><span>' + gbMoney(l.total_cents) + '</span></div>').join('')
    + '<div class="sum-row sum-line"><span>Glosses</span><span>' + gbMoney(o.subtotal_cents) + '</span></div>'
    + (o.discount_cents ? '<div class="sum-row"><span>Discount</span><span>−' + gbMoney(o.discount_cents) + '</span></div>' : '')
    + '<div class="sum-row"><span>' + (o.delivery === 'ship' ? 'Shipping' : 'Local pickup') + '</span><span>' + (o.shipping_cents ? gbMoney(o.shipping_cents) : 'FREE') + '</span></div>'
    + (o.tax_cents ? '<div class="sum-row"><span>' + gbEsc(o.tax_label || 'Sales tax') + '</span><span>' + gbMoney(o.tax_cents) + '</span></div>' : '')
    + (o.promo_label ? '<div class="sum-promo">🎉 ' + gbEsc(o.promo_label) + ' - you saved ' + gbMoney(o.promo_savings_cents) + '</div>' : '')
    + '<div class="sum-row sum-total"><span>Total</span><span>' + gbMoney(o.total_cents) + '</span></div>'
    + (o.address.length ? '<p class="shop-hint">Shipping to: ' + o.address.map(gbEsc).join(', ') + '</p>' : '')
    + '</div>';
}

async function gbSetupOrderPage() {
  const box = document.getElementById('orderPage');
  if (!box) return;
  const params = new URLSearchParams(location.search);
  const code = params.get('code') || '';
  const token = params.get('t') || '';

  let data;
  try {
    data = await gbApi('order-status.php?code=' + encodeURIComponent(code) + '&t=' + encodeURIComponent(token));
  } catch (e) {
    box.innerHTML = '<p class="shop-error">' + gbEsc(e.message) + '</p><p><a href="account.html">Go to My Orders</a></p>';
    return;
  }
  const o = data.order;
  const pay = data.pay;
  const amount = (o.total_cents / 100).toFixed(2);
  const waiting = o.status === 'awaiting_payment';

  let html = (params.get('new') ? '<div class="shop-thanks">💖 Thank you, ' + gbEsc(o.name) + '! Your order is saved. We emailed a copy to ' + gbEsc(o.email) + '.</div>' : '')
    + '<h1>Order <span class="order-code">' + gbEsc(o.code) + '</span></h1>'
    + '<p class="order-status status-' + gbEsc(o.status) + '">' + gbEsc(o.status_text) + '</p>'
    + (o.tracking ? '<p>Tracking number: <strong>' + gbEsc(o.tracking) + '</strong></p>' : '');

  if (waiting && o.payment_method === 'venmo') {
    // Venmo "pay" link with the amount and note filled in.
    // NOTE: this link format must be tested with the real Venmo account before launch.
    const venmoLink = 'https://venmo.com/?txn=pay&recipients=' + encodeURIComponent(pay.venmo_username)
      + '&amount=' + amount + '&note=' + encodeURIComponent(o.code);
    html += '<div class="pay-box"><h2>Pay ' + gbMoney(o.total_cents) + ' with Venmo</h2>'
      + '<ol class="pay-steps"><li>Send <strong>' + gbMoney(o.total_cents) + '</strong> to <strong>@' + gbEsc(pay.venmo_username) + '</strong></li>'
      + '<li>In the note, type <strong class="order-code">' + gbEsc(o.code) + '</strong></li>'
      + '<li>Come back and tap <em>I sent the money</em></li></ol>'
      + '<a class="shop-btn venmo" href="' + gbEsc(venmoLink) + '">Open Venmo</a>'
      + '<div class="qr-pair"><figure><div id="venmoQr"></div><figcaption>On a computer? Scan with your phone.</figcaption></figure>'
      + '<figure class="hide-if-missing"><img src="' + GB_ROOT + 'images/pay/venmo-qr.png" alt="Glimmer Bliss Venmo QR code" onerror="this.parentNode.remove()"><figcaption>Our Venmo QR code</figcaption></figure></div>'
      + '</div>';
  }
  if (waiting && o.payment_method === 'zelle') {
    html += '<div class="pay-box"><h2>Pay ' + gbMoney(o.total_cents) + ' with Zelle</h2>'
      + '<ol class="pay-steps"><li>Open your <strong>bank\'s app</strong> and pick Zelle</li>'
      + '<li>Send <strong>' + gbMoney(o.total_cents) + '</strong> to <strong>' + gbEsc(pay.zelle_contact) + '</strong>'
      + (pay.zelle_name ? ' (it will show the name <strong>' + gbEsc(pay.zelle_name) + '</strong>)' : '') + '</li>'
      + '<li>In the memo, type <strong class="order-code">' + gbEsc(o.code) + '</strong></li>'
      + '<li>Come back and tap <em>I sent the money</em></li></ol>'
      + '<div class="qr-pair"><figure class="hide-if-missing"><img src="' + GB_ROOT + 'images/pay/zelle-qr.png" alt="Glimmer Bliss Zelle QR code" onerror="this.parentNode.remove()"><figcaption>Scan in your bank\'s Zelle screen</figcaption></figure></div>'
      + '</div>';
  }
  if (waiting && (o.payment_method === 'venmo' || o.payment_method === 'zelle')) {
    html += '<form id="iPaidForm" class="pay-box"><label for="payRef">Name on your ' + (o.payment_method === 'venmo' ? 'Venmo' : 'bank') + ' account (helps us find your payment)</label>'
      + '<input id="payRef" maxlength="120" placeholder="Optional">'
      + '<button class="shop-btn" type="submit">I sent the money 💸</button>'
      + '<p class="shop-hint">We check every payment by hand, so your order will say "Checking your payment" until we see it.</p></form>';
  }
  if (waiting && o.payment_method === 'paypal') {
    html += '<div class="pay-box"><h2>Pay ' + gbMoney(o.total_cents) + '</h2><div id="paypalButtons"></div><p id="paypalMsg" class="shop-error"></p></div>';
  }
  if (o.status === 'customer_says_paid') {
    html += '<div class="pay-box"><p>Thanks! We\'ll check our ' + gbEsc(o.payment_method_text) + ' and email you as soon as we see your payment. 💖</p></div>';
  }

  html += '<h2>What you ordered</h2>' + gbOrderTable(o)
    + '<p class="shop-hint">Keep this page\'s link (it\'s in your email too). You can also see all your orders any time on <a href="account.html">My Orders</a>.</p>';
  box.innerHTML = html;

  if (document.getElementById('venmoQr')) {
    gbLoadScript('https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js').then(() => {
      const link = box.querySelector('a.venmo').href;
      new QRCode(document.getElementById('venmoQr'), { text: link, width: 180, height: 180 });
    }).catch(() => {});
  }

  const iPaid = document.getElementById('iPaidForm');
  if (iPaid) {
    iPaid.addEventListener('submit', async e => {
      e.preventDefault();
      iPaid.querySelector('button').disabled = true;
      try {
        await gbApi('i-paid.php', { code: o.code, token: o.token, payment_ref: document.getElementById('payRef').value });
        location.href = location.pathname + '?code=' + encodeURIComponent(o.code) + '&t=' + encodeURIComponent(o.token);
      } catch (err) {
        alert(err.message);
        iPaid.querySelector('button').disabled = false;
      }
    });
  }

  if (document.getElementById('paypalButtons')) {
    const msg = document.getElementById('paypalMsg');
    if (!pay.paypal_client_id) {
      msg.textContent = 'PayPal is not switched on yet. Please contact us.';
      return;
    }
    try {
      await gbLoadScript('https://www.paypal.com/sdk/js?client-id=' + encodeURIComponent(pay.paypal_client_id) + '&currency=USD&intent=capture&enable-funding=venmo');
    } catch (e) {
      msg.textContent = 'PayPal could not load. Please refresh the page.';
      return;
    }
    paypal.Buttons({
      createOrder: () => gbApi('paypal-create.php', { code: o.code, token: o.token }).then(r => r.id),
      onApprove: () => gbApi('paypal-capture.php', { code: o.code, token: o.token })
        .then(() => { location.href = location.pathname + '?code=' + encodeURIComponent(o.code) + '&t=' + encodeURIComponent(o.token); })
        .catch(err => { msg.textContent = err.message; }),
      onError: () => { msg.textContent = 'PayPal had a problem. You were not charged. Please try again.'; }
    }).render('#paypalButtons');
  }
}

// ---------- My Orders (account) page ----------

async function gbSetupAccount() {
  const box = document.getElementById('accountPage');
  if (!box) return;
  let me;
  try {
    me = await gbApi('me.php');
  } catch (e) {
    box.innerHTML = '<p class="shop-error">' + gbEsc(e.message) + '</p>';
    return;
  }

  if (!me.logged_in) {
    const expired = new URLSearchParams(location.search).get('link') === 'expired';
    box.innerHTML = '<h1>My Orders</h1>'
      + (expired ? '<p class="shop-error">That login link was already used or is too old. Get a new one below.</p>' : '')
      + '<p>No password needed! Type the email you used when you ordered, and we\'ll send you a link that logs you in.</p>'
      + '<form id="loginForm" class="login-form"><label for="loginEmail">Your email</label>'
      + '<input id="loginEmail" type="email" required autocomplete="email">'
      + '<button class="shop-btn" type="submit">Email me a login link</button></form>'
      + '<p id="loginMsg" class="shop-nudge"></p>'
      + '<p class="shop-hint">New here? Your account is made by itself the first time you order.</p>';
    document.getElementById('loginForm').addEventListener('submit', async e => {
      e.preventDefault();
      const btn = e.target.querySelector('button');
      btn.disabled = true;
      try {
        const r = await gbApi('login.php', { email: document.getElementById('loginEmail').value });
        document.getElementById('loginMsg').textContent = r.message;
      } catch (err) {
        document.getElementById('loginMsg').textContent = err.message;
        btn.disabled = false;
      }
    });
    return;
  }

  box.innerHTML = '<h1>My Orders</h1>'
    + '<p>Logged in as <strong>' + gbEsc(me.email) + '</strong> · <button type="button" class="link-btn" id="logoutBtn">Log out</button></p>'
    + (me.orders.length ? me.orders.map(o =>
        '<a class="order-card" href="order.html?code=' + encodeURIComponent(o.code) + '&t=' + encodeURIComponent(o.token) + '">'
        + '<span><strong>' + gbEsc(o.code) + '</strong><small>' + gbEsc(o.created_at.slice(0, 10)) + ' · ' + o.items.reduce((n, l) => n + l.qty, 0) + ' gloss(es)</small></span>'
        + '<span class="order-status status-' + gbEsc(o.status) + '">' + gbEsc(o.status_text) + '</span>'
        + '<strong>' + gbMoney(o.total_cents) + '</strong></a>'
      ).join('') : '<p>No orders yet.</p>');
  document.getElementById('logoutBtn').addEventListener('click', async () => {
    await gbApi('me.php', { logout: true });
    location.reload();
  });
}

document.addEventListener('DOMContentLoaded', function() {
  gbSetupHeader();
  gbSetupAddToCart();
  gbRenderCartPage();
  gbSetupCheckout();
  gbSetupOrderPage();
  gbSetupAccount();
});
