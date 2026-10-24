// Glimmer Bliss - Mockup Site JavaScript

// Cart state (stored in localStorage for persistence)
let cartCount = parseInt(localStorage.getItem('glimmerBlissCart') || '0');

// Update cart count display
function updateCartDisplay() {
  const cartCountElements = document.querySelectorAll('#cartCount');
  cartCountElements.forEach(el => {
    el.textContent = cartCount;
  });
}

// Show cart notification
function showNotification() {
  const notification = document.getElementById('cartNotification');
  if (notification) {
    notification.classList.add('show');
    setTimeout(() => {
      notification.classList.remove('show');
    }, 3000);
  }
}

// Add to cart functionality
function addToCart() {
  cartCount++;
  localStorage.setItem('glimmerBlissCart', cartCount);
  updateCartDisplay();
  showNotification();

  // Change button text temporarily
  const btn = document.getElementById('addToCart');
  if (btn) {
    const originalText = btn.textContent;
    btn.textContent = 'Added!';
    btn.classList.add('added');
    setTimeout(() => {
      btn.textContent = originalText;
      btn.classList.remove('added');
    }, 2000);
  }
}

// Option button selection
function setupOptionButtons() {
  const optionButtons = document.querySelectorAll('.option-btn');
  optionButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      // Remove selected from siblings
      const siblings = this.parentElement.querySelectorAll('.option-btn');
      siblings.forEach(sib => sib.classList.remove('selected'));
      // Add selected to clicked
      this.classList.add('selected');
    });
  });
}

// Thumbnail image selection - clicking a small photo shows it in the big frame
function setupThumbnails() {
  const thumbnails = document.querySelectorAll('.thumbnail');
  const mainImg = document.getElementById('mainImageImg');

  thumbnails.forEach(thumb => {
    thumb.addEventListener('click', function() {
      // Remove active from siblings
      const siblings = this.parentElement.querySelectorAll('.thumbnail');
      siblings.forEach(sib => sib.classList.remove('active'));
      // Add active to clicked
      this.classList.add('active');

      // Swap the big picture
      const full = this.getAttribute('data-full');
      if (full && mainImg) {
        mainImg.src = full;
      }
    });
  });
}

// Welcome screen photo ad: fade from one photo to the next, over and over
function setupPhotoAd() {
  const ad = document.getElementById('photoAd');
  if (!ad) return;

  const slides = ad.querySelectorAll('.photo-ad-slide');
  const caption = document.getElementById('photoAdCaption');
  const dotRow = document.getElementById('photoAdDots');
  if (slides.length < 2) return;

  let current = 0;
  let timer = null;

  // One dot per photo, so you can jump straight to one
  const dots = [];
  slides.forEach((slide, i) => {
    const dot = document.createElement('button');
    dot.className = 'photo-ad-dot' + (i === 0 ? ' is-on' : '');
    dot.type = 'button';
    dot.setAttribute('aria-label', 'Show photo ' + (i + 1));
    dot.addEventListener('click', () => {
      show(i);
      restart();
    });
    dotRow.appendChild(dot);
    dots.push(dot);
  });

  function show(index) {
    slides[current].classList.remove('is-on');
    dots[current].classList.remove('is-on');
    current = index;
    slides[current].classList.add('is-on');
    dots[current].classList.add('is-on');
    if (caption) {
      caption.textContent = slides[current].getAttribute('data-caption') || '';
    }
  }

  function next() {
    show((current + 1) % slides.length);
  }

  function restart() {
    clearInterval(timer);
    timer = setInterval(next, 2600);
  }

  // Pause while someone is looking closely at one photo
  ad.addEventListener('mouseenter', () => clearInterval(timer));
  ad.addEventListener('mouseleave', restart);

  restart();
}

// Welcome screen: reveal the whole site when "Shop Now" is pressed
function setupEnterShop() {
  const enterShop = document.getElementById('enterShop');
  if (enterShop) {
    enterShop.addEventListener('click', function(e) {
      e.preventDefault();
      document.body.classList.remove('intro');
      document.body.classList.add('shop-open');
      const target = document.getElementById('products');
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }
}

// Smooth scroll for anchor links
function setupSmoothScroll() {
  const links = document.querySelectorAll('a[href^="#"]');
  links.forEach(link => {
    link.addEventListener('click', function(e) {
      // The welcome "Shop Now" button has its own handler
      if (this.id === 'enterShop') return;
      const href = this.getAttribute('href');
      if (href !== '#') {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      }
    });
  });
}

// Cart icon click (show message)
function setupCartIcon() {
  const cartIcon = document.getElementById('cartIcon');
  if (cartIcon) {
    cartIcon.addEventListener('click', function() {
      if (cartCount > 0) {
        alert(`You have ${cartCount} item${cartCount > 1 ? 's' : ''} in your cart!\n\n(This is a demo - in the real store, this would take you to checkout)`);
      } else {
        alert('Your cart is empty!\n\nBrowse our glosses and add some to your cart.');
      }
    });
  }
}

// FAQ page: type in the search box to hide questions that don't match
function setupFaqSearch() {
  const box = document.getElementById('faqSearch');
  const list = document.getElementById('faqList');
  const empty = document.getElementById('faqEmpty');
  if (!box || !list) return;

  const items = list.querySelectorAll('.faq-item');
  const groups = list.querySelectorAll('.faq-group');

  box.addEventListener('input', function() {
    const term = this.value.trim().toLowerCase();
    let showing = 0;

    items.forEach(item => {
      const hit = !term || item.textContent.toLowerCase().includes(term);
      item.hidden = !hit;
      if (hit) showing++;
      // Open matches while searching so the answer is visible right away
      item.open = Boolean(term) && hit;
    });

    // A group heading only makes sense if something under it survived
    groups.forEach(group => {
      let next = group.nextElementSibling;
      let any = false;
      while (next && !next.classList.contains('faq-group')) {
        if (next.classList.contains('faq-item') && !next.hidden) any = true;
        next = next.nextElementSibling;
      }
      group.hidden = Boolean(term) && !any;
    });

    if (empty) empty.hidden = showing !== 0;
  });
}

// Help assistant: answers common questions from a built-in list.
// No internet and no AI service - it matches keywords to a written answer.
// Order matters. Two entries that match equally well are settled by whichever
// has the longer matching word, and then by whichever comes first in this list.
// So: name a gloss -> that gloss wins; everything specific sits above the general.
const HELP_ANSWERS = [
  // --- Each gloss by name, so "how much is Pink Paradise" works ---
  { k: ['pink paradise', 'pink-paradise'],
    a: '<strong>Pink Paradise</strong> is <strong>$12.00</strong>. A dreamy tropical pink - sweet and glossy, and it goes with everything. <a href="products/pink-paradise.html">See it here</a>.' },
  { k: ['true love', 'true-love', 'dark red'],
    a: '<strong>True Love</strong> is <strong>$12.00</strong>. A deep, bold dark red with a rich shine. <a href="products/true-love.html">See it here</a>.' },
  { k: ['love', 'is love', 'about love', 'love gloss', 'love lip'],
    a: '<strong>Love</strong> is <strong>$12.00</strong>. A pretty red with a sweet, warm feel. <a href="products/love.html">See it here</a>.' },
  { k: ['gloss angeles', 'gloss angel', 'gloss-angeles'],
    a: '<strong>Gloss Angeles</strong> is <strong>$12.00</strong>. A clear, sunny shine - a play on Los Angeles. <a href="products/gloss-angeles.html">See it here</a>.' },
  { k: ['sugar cookie', 'sugar-cookie'],
    a: '<strong>Sugar Cookie</strong> is <strong>$12.00</strong>. A warm cookie brown that is sweet as a fresh bake. <a href="products/sugar-cookie.html">See it here</a>.' },
  { k: ['glimmer kiss', 'glimmer-kiss', 'color chang', 'colour chang', 'turns pink', 'goes on clear', 'limited edition'],
    a: '<strong>Glimmer Kiss</strong> is <strong>$15.00</strong> and Limited Edition. It goes on clear, then turns a soft pink - the exact shade comes from your own natural pH, so it is different on everybody. You can get it with glitter or without. <a href="products/glimmer-kiss.html">See it here</a>.' },

  // --- Specific problems ---
  { k: ['broken', 'damaged', 'damage', 'leak', 'cracked', 'spilled', 'smashed'],
    a: 'Sorry! Email us a photo and we will replace it or refund you. That one is on us.' },
  { k: ['canada', 'international', 'outside the us', 'overseas', 'abroad', 'another country'],
    a: 'Right now we ship within the US only. If you are further away, message us and we will see what we can do.' },
  { k: ['pickup', 'pick up', 'pick it up', 'pick', 'collect', 'local', 'meet', 'in person'],
    a: '<strong>Local pickup is free.</strong> Choose it at checkout and we will message you to arrange a safe local spot.' },

  // --- The whole range ---
  { k: ['what colors', 'what colours', 'which colors', 'which colours', 'colors', 'colours', 'shades', 'how many gloss', 'what do you sell', 'red', 'brown', 'sparkl'],
    a: 'There are six! <strong>Pink Paradise</strong> (pink), <strong>Love</strong> (red), <strong>True Love</strong> (dark red), <strong>Gloss Angeles</strong> (clear shine) and <strong>Sugar Cookie</strong> (cookie brown) are <strong>$12</strong> each. <strong>Glimmer Kiss</strong> is <strong>$15</strong> and Limited Edition.' },
  { k: ['flavor', 'flavour', 'taste', 'vanilla', 'smell'],
    a: 'Every batch is made with <strong>vanilla flavoring</strong>. All six glosses are vanilla - there is no unflavored option.' },
  { k: ['safe', 'ingredient', 'allergy', 'allergic', 'glitter', 'toxic', 'what is in'],
    a: 'They are made with <strong>lip-safe, cosmetic-grade ingredients</strong>, including cosmetic glitter (never craft glitter). If you have allergies, email us for the full ingredient list before ordering.' },
  { k: ['size', 'how big', 'bigger', 'small', 'ml', 'tall', 'inch', 'measure', 'dimension'],
    a: 'Each bottle holds <strong>5ml</strong>. It is <strong>3.31 inches</strong> tall and <strong>0.87 inches</strong> wide - about thumb sized. There is a size photo on every gloss page.' },

  // Shipping sits ABOVE price so "how much is shipping" lands here, not on price
  { k: ['shipping', 'ship', 'postage', 'deliver', 'mail', 'usps'],
    a: 'Shipping is a flat <strong>$5.00</strong>, and <strong>free over $30</strong> (three glosses). See the <a href="shipping.html">Shipping page</a> for full details.' },
  { k: ['return', 'refund', 'money back', 'send back', 'exchange', 'swap'],
    a: 'Unopened glosses can be returned. Anything opened or used cannot, for hygiene reasons. If it arrived damaged or wrong, that is on us - see the <a href="returns.html">Returns page</a>.' },
  { k: ['how long', 'when will', 'arrive', 'how fast', 'track', 'tracking'],
    a: 'Glosses are made to order - allow a few days to make and pack, then a few more in the post. You get a tracking number by email when the label is printed.' },
  { k: ['price', 'cost', 'how much', 'expensive', 'cheap', 'dollar', '$'],
    a: 'The five main colors are <strong>$12.00</strong> each. Limited Edition Glimmer Kiss is <strong>$15.00</strong>.' },

  // --- General ---
  { k: ['custom', 'own flavor', 'own colour', 'own color', 'special order', 'make me'],
    a: 'Not just yet - but we would love to. Send us your idea and if enough people want it, it could become a real flavor!' },
  { k: ['booth', 'market', 'fair', 'canton', 'trade days', 'stall'],
    a: 'You can find us in person at First Monday Trade Days in Canton, TX. Come say hi and see the colors for real!' },
  { k: ['who makes', 'who made', 'charlie', 'founder', 'made by', 'who are you'],
    a: '<strong>Charlie Mae</strong> makes them. She picks every color and flavor, mixes each batch by hand, and packs every order herself.' },
  { k: ['contact', 'email', 'talk to', 'phone', 'reach you'],
    a: 'Email us and we will get back to you. You can also read the <a href="faq.html">full FAQ</a>.' },
  { k: ['hello', 'hi ', 'hey', 'help'],
    a: 'Hi! Ask me about flavors, sizes, prices, shipping, pickup, or returns.' }
];

function setupHelpAssistant() {
  // Every page gets the button, built here so no page has to include the markup
  const wrap = document.createElement('div');
  wrap.className = 'helper';
  wrap.innerHTML =
    '<button class="helper-toggle" id="helperToggle" type="button" aria-label="Open help">' +
      '<span class="helper-toggle-icon">💬</span><span>Help</span>' +
    '</button>' +
    '<div class="helper-panel" id="helperPanel" hidden>' +
      '<div class="helper-head">' +
        '<span>Ask Glimmer Bliss</span>' +
        '<button class="helper-close" id="helperClose" type="button" aria-label="Close help">&times;</button>' +
      '</div>' +
      '<div class="helper-log" id="helperLog"></div>' +
      '<div class="helper-chips" id="helperChips"></div>' +
      '<form class="helper-form" id="helperForm">' +
        '<input type="text" id="helperInput" placeholder="Type your question..." autocomplete="off" aria-label="Type your question">' +
        '<button type="submit" aria-label="Send">Ask</button>' +
      '</form>' +
    '</div>';
  document.body.appendChild(wrap);

  const panel = document.getElementById('helperPanel');
  const log = document.getElementById('helperLog');
  const input = document.getElementById('helperInput');

  function say(text, who) {
    const line = document.createElement('div');
    line.className = 'helper-msg helper-' + who;
    line.innerHTML = text;
    log.appendChild(line);
    log.scrollTop = log.scrollHeight;
  }

  function answer(question) {
    const q = question.toLowerCase();
    let best = null;
    let bestHits = 0;
    let bestLongest = 0;

    HELP_ANSWERS.forEach(entry => {
      const matched = entry.k.filter(word => q.includes(word));
      if (!matched.length) return;
      // A longer matched word is a more specific match, so it wins a tie on count
      const longest = Math.max.apply(null, matched.map(w => w.length));
      if (matched.length > bestHits ||
         (matched.length === bestHits && longest > bestLongest)) {
        bestHits = matched.length;
        bestLongest = longest;
        best = entry;
      }
    });

    if (best) return best.a;
    return 'I am not sure about that one, sorry! Try asking about flavors, size, price, ' +
           'shipping, pickup or returns - or read the <a href="faq.html">full FAQ</a>.';
  }

  function ask(question) {
    say(question, 'you');
    say(answer(question), 'bot');
  }

  // Tappable starter questions
  const chips = ['What flavor is it?', 'How much is shipping?', 'Can I pick it up?', 'How big is a bottle?'];
  const chipRow = document.getElementById('helperChips');
  chips.forEach(text => {
    const chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'helper-chip';
    chip.textContent = text;
    chip.addEventListener('click', () => ask(text));
    chipRow.appendChild(chip);
  });

  document.getElementById('helperToggle').addEventListener('click', function() {
    panel.hidden = !panel.hidden;
    if (!panel.hidden) {
      if (!log.childElementCount) {
        say('Hi! I am the Glimmer Bliss helper. Ask me anything about the glosses, ' +
            'shipping or returns.', 'bot');
      }
      input.focus();
    }
  });

  document.getElementById('helperClose').addEventListener('click', function() {
    panel.hidden = true;
  });

  document.getElementById('helperForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const question = input.value.trim();
    if (!question) return;
    ask(question);
    input.value = '';
  });
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  // Update cart display
  updateCartDisplay();

  // Setup add to cart button
  const addToCartBtn = document.getElementById('addToCart');
  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', addToCart);
  }

  // Setup other interactions
  setupPhotoAd();
  setupFaqSearch();
  setupHelpAssistant();
  setupEnterShop();
  setupOptionButtons();
  setupThumbnails();
  setupSmoothScroll();
  setupCartIcon();

  // Add hover effect to product cards
  const productCards = document.querySelectorAll('.product-card');
  productCards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-5px)';
    });
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
    });
  });
});

// Console message for the girls!
console.log('%c💖 Welcome to Glimmer Bliss! 💖', 'font-size: 20px; color: #ff69b4;');
console.log('%cMade with love by Charlie Mae', 'font-size: 14px; color: #db7093;');
