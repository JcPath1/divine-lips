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

// Thumbnail image selection
function setupThumbnails() {
  const thumbnails = document.querySelectorAll('.thumbnail');
  thumbnails.forEach(thumb => {
    thumb.addEventListener('click', function() {
      // Remove active from siblings
      const siblings = this.parentElement.querySelectorAll('.thumbnail');
      siblings.forEach(sib => sib.classList.remove('active'));
      // Add active to clicked
      this.classList.add('active');
    });
  });
}

// Smooth scroll for anchor links
function setupSmoothScroll() {
  const links = document.querySelectorAll('a[href^="#"]');
  links.forEach(link => {
    link.addEventListener('click', function(e) {
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
console.log('%cðŸ’– Welcome to Glimmer Bliss! ðŸ’–', 'font-size: 20px; color: #ff69b4;');
console.log('%cMade with love by Charlie & Martina', 'font-size: 14px; color: #db7093;');
