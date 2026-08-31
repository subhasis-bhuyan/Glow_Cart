/**
 * GlowCart Cosmetics - Main JavaScript Engine
 * Features: Mobile Nav, Toast Notifications, AJAX Cart, Voice Recognition & Synthesis (EN/HI/OR)
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initQuantityControls();
  initVoiceAssistant();
});

// --------------------------------------------------------------------------
// 1. Mobile Menu Toggle
// --------------------------------------------------------------------------
function initMobileMenu() {
  const toggleBtn = document.querySelector('.mobile-nav-toggle');
  const navLinks = document.querySelector('.nav-links');

  if (toggleBtn && navLinks) {
    toggleBtn.addEventListener('click', () => {
      navLinks.classList.toggle('active');
      toggleBtn.innerHTML = navLinks.classList.contains('active') ? '✕' : '☰';
    });
  }
}

// --------------------------------------------------------------------------
// 2. Toast Notification System
// --------------------------------------------------------------------------
function showToast(message, type = 'success') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span>${message}</span>`;
  container.appendChild(toast);

  // Trigger animation
  requestAnimationFrame(() => {
    toast.classList.add('show');
  });

  // Remove after 3.5 seconds
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 3500);
}

// --------------------------------------------------------------------------
// 3. AJAX Shopping Cart Operations
// --------------------------------------------------------------------------
function addToCart(productId, quantity = 1, callback = null) {
  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('product_id', productId);
  formData.append('quantity', quantity);

  // Determine root path for ajax_cart.php
  const ajaxUrl = window.location.pathname.includes('/admin/') ? '../ajax_cart.php' : 'ajax_cart.php';

  fetch(ajaxUrl, {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast(data.message || '✓ Product added to cart', 'success');
        updateCartBadge(data.cart_count);
        if (typeof callback === 'function') callback(true, data);
      } else {
        showToast(data.message || 'Could not add product to cart', 'error');
        if (typeof callback === 'function') callback(false, data);
      }
    })
    .catch(err => {
      console.error('Cart Error:', err);
      showToast('Error connecting to server', 'error');
      if (typeof callback === 'function') callback(false, null);
    });
}

function updateCartBadge(count) {
  const badge = document.querySelector('.cart-badge');
  if (badge) {
    badge.textContent = count;
    badge.style.transform = 'scale(1.3)';
    setTimeout(() => {
      badge.style.transform = 'scale(1)';
    }, 200);
  }
}

// --------------------------------------------------------------------------
// 4. Quantity Stepper Controls
// --------------------------------------------------------------------------
function initQuantityControls() {
  document.querySelectorAll('.quantity-control').forEach(ctrl => {
    const input = ctrl.querySelector('.qty-input');
    const btnMinus = ctrl.querySelector('.qty-minus');
    const btnPlus = ctrl.querySelector('.qty-plus');

    if (!input) return;

    const maxStock = parseInt(input.getAttribute('max')) || 999;
    const minQty = parseInt(input.getAttribute('min')) || 1;

    if (btnMinus) {
      btnMinus.addEventListener('click', () => {
        let val = parseInt(input.value) || minQty;
        if (val > minQty) {
          input.value = val - 1;
          input.dispatchEvent(new Event('change'));
        }
      });
    }

    if (btnPlus) {
      btnPlus.addEventListener('click', () => {
        let val = parseInt(input.value) || minQty;
        if (val < maxStock) {
          input.value = val + 1;
          input.dispatchEvent(new Event('change'));
        } else {
          showToast(`Maximum available stock is ${maxStock}`, 'error');
        }
      });
    }
  });
}

// --------------------------------------------------------------------------
// 5. Web Speech API Voice Assistant (English, Hindi, Odia)
// --------------------------------------------------------------------------
let voiceRecognition = null;
let isListening = false;

function initVoiceAssistant() {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const triggerBtn = document.getElementById('voiceTriggerBtn');
  const navMicBtn = document.getElementById('navMicBtn');
  const overlay = document.getElementById('voiceOverlay');
  const closeBtn = document.getElementById('voiceCloseBtn');
  const langSelect = document.getElementById('voiceLangSelect');
  const startBtn = document.getElementById('voiceStartBtn');
  const transcriptBox = document.getElementById('voiceTranscript');
  const responseBox = document.getElementById('voiceResponse');
  const statusText = document.getElementById('voiceStatus');
  const visualizer = document.getElementById('voiceVisualizer');

  if (!triggerBtn && !navMicBtn) return;

  const openVoicePanel = () => {
    if (overlay) overlay.classList.add('active');
    startVoiceRecognition();
  };

  const closeVoicePanel = () => {
    if (overlay) overlay.classList.remove('active');
    stopVoiceRecognition();
  };

  if (triggerBtn) triggerBtn.addEventListener('click', openVoicePanel);
  if (navMicBtn) navMicBtn.addEventListener('click', openVoicePanel);
  if (closeBtn) closeBtn.addEventListener('click', closeVoicePanel);
  if (overlay) {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) closeVoicePanel();
    });
  }

  if (startBtn) {
    startBtn.addEventListener('click', () => {
      if (isListening) {
        stopVoiceRecognition();
      } else {
        startVoiceRecognition();
      }
    });
  }

  // Voice Recognition setup
  if (SpeechRecognition) {
    voiceRecognition = new SpeechRecognition();
    voiceRecognition.continuous = false;
    voiceRecognition.interimResults = false;

    voiceRecognition.onstart = () => {
      isListening = true;
      if (statusText) statusText.textContent = 'Listening... Speak your command now';
      if (visualizer) visualizer.classList.add('listening');
      if (startBtn) startBtn.innerHTML = '🛑 Stop Listening';
    };

    voiceRecognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript.trim();
      if (transcriptBox) transcriptBox.textContent = `"${transcript}"`;
      processVoiceCommand(transcript, langSelect ? langSelect.value : 'en-US');
    };

    voiceRecognition.onerror = (event) => {
      console.warn('Speech Recognition Error:', event.error);
      if (statusText) statusText.textContent = `Error: ${event.error}. Please try again.`;
      stopVisualizer();
    };

    voiceRecognition.onend = () => {
      isListening = false;
      stopVisualizer();
      if (startBtn) startBtn.innerHTML = '🎤 Tap to Speak';
    };
  } else {
    if (statusText) {
      statusText.textContent = 'Web Speech API is not supported in this browser. Please use Google Chrome or Edge.';
    }
  }

  function startVoiceRecognition() {
    if (!voiceRecognition) return;
    try {
      const lang = langSelect ? langSelect.value : 'en-US';
      voiceRecognition.lang = lang;
      voiceRecognition.start();
    } catch (e) {
      console.warn('Recognition already started:', e);
    }
  }

  function stopVoiceRecognition() {
    if (!voiceRecognition) return;
    try {
      voiceRecognition.stop();
    } catch (e) {}
    stopVisualizer();
  }

  function stopVisualizer() {
    isListening = false;
    if (visualizer) visualizer.classList.remove('listening');
    if (statusText) statusText.textContent = 'Ready. Tap microphone to speak.';
  }
}

/**
 * Process spoken command across English, Hindi, and Odia
 */
function processVoiceCommand(text, lang) {
  const lower = text.toLowerCase();
  const responseBox = document.getElementById('voiceResponse');
  let responseText = '';
  let actionCallback = null;

  // 1. Navigation Commands
  if (
    lower.includes('go home') || lower.includes('open home') || lower.includes('home page') ||
    lower.includes('होम') || lower.includes('घर') ||
    lower.includes('ହୋମ') || lower.includes('ମୁଖ୍ୟ ପୃଷ୍ଠା')
  ) {
    responseText = 'Navigating to Home Page';
    actionCallback = () => { window.location.href = 'index.php'; };
  }
  else if (
    lower.includes('open cart') || lower.includes('open my cart') || lower.includes('show cart') || lower.includes('view cart') ||
    lower.includes('कार्ट') || lower.includes('थैला') ||
    lower.includes('କାର୍ଟ') || lower.includes('କାର୍ଟ ଖୋଲ')
  ) {
    responseText = 'Opening your Shopping Cart';
    actionCallback = () => { window.location.href = 'cart.php'; };
  }
  else if (
    lower.includes('checkout') || lower.includes('place order') || lower.includes('buy now') ||
    lower.includes('चेकआउट') || lower.includes('ऑर्डर करो') ||
    lower.includes('ଚେକଆଉଟ') || lower.includes('ଅର୍ଡର କରନ୍ତୁ')
  ) {
    responseText = 'Proceeding to Checkout';
    actionCallback = () => { window.location.href = 'checkout.php'; };
  }
  else if (
    lower.includes('show my orders') || lower.includes('open orders') || lower.includes('my orders') ||
    lower.includes('मेरे ऑर्डर') || lower.includes('ऑर्डर दिखाओ') ||
    lower.includes('ମୋର ଅର୍ଡର') || lower.includes('ଅର୍ଡର ଦେଖାଅ')
  ) {
    responseText = 'Opening your Orders';
    actionCallback = () => { window.location.href = 'orders.php'; };
  }
  else if (
    lower.includes('open profile') || lower.includes('my profile') || lower.includes('account') ||
    lower.includes('प्रोफाइल') || lower.includes('खाता') ||
    lower.includes('ପ୍ରୋଫାଇଲ୍') || lower.includes('ଆକାଉଣ୍ଟ')
  ) {
    responseText = 'Opening your Profile';
    actionCallback = () => { window.location.href = 'profile.php'; };
  }
  // 2. Direct Add-to-Cart Voice Action (e.g. "Add lipstick to cart", "लिपस्टिक कार्ट में जोड़ें")
  else if (
    lower.includes('add') && lower.includes('cart') ||
    lower.includes('जोड़ें') || lower.includes('ଡାଳନ୍ତୁ') || lower.includes('ଯୋଡନ୍ତୁ')
  ) {
    let keyword = lower
      .replace(/add|to|my|cart|please|in|put|लिपस्टिक|जोड़ें|में|କାର୍ଟ|ଯୋଡନ୍ତୁ|ଡାଳନ୍ତୁ/gi, '')
      .trim();
    if (!keyword) keyword = 'lipstick';

    responseText = `Adding ${keyword} to your cart...`;
    if (responseBox) responseBox.textContent = `🔊 ${responseText}`;
    speakVoiceResponse(responseText, lang);

    // Call AJAX endpoint to match and add item
    fetch(`ajax_cart.php?action=voice_add&keyword=${encodeURIComponent(keyword)}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const successMsg = `${data.product_name} added to cart!`;
          if (responseBox) responseBox.textContent = `🔊 ${successMsg}`;
          speakVoiceResponse(successMsg, lang);
          showToast(`✓ ${data.product_name} added to cart`, 'success');
          updateCartBadge(data.cart_count);
        } else {
          const failMsg = data.message || 'Product not found or out of stock.';
          if (responseBox) responseBox.textContent = `🔊 ${failMsg}`;
          speakVoiceResponse(failMsg, lang);
          showToast(failMsg, 'error');
        }
      });
    return;
  }
  // 3. Search / Show Category Commands (e.g. "Show lipstick", "Find foundation", "लिपस्टिक दिखाओ", "ଲିପଷ୍ଟିକ୍ ଦେଖାଅ")
  else {
    let searchKeyword = lower
      .replace(/show|find|search|display|look for|products|product|दिखाओ|खोजो|ढूंढो|ଦେଖାଅ|ଖୋଜ/gi, '')
      .trim();

    // Map common multilingual category names
    if (lower.includes('lipstick') || lower.includes('लिपस्टिक') || lower.includes('ଲିପଷ୍ଟିକ୍')) searchKeyword = 'Lipstick';
    else if (lower.includes('foundation') || lower.includes('फाउंडेशन') || lower.includes('ଫାଉଣ୍ଡେସନ')) searchKeyword = 'Foundation';
    else if (lower.includes('blush') || lower.includes('ब्लश') || lower.includes('ବ୍ଲସ୍')) searchKeyword = 'Blush';
    else if (lower.includes('eyeshadow') || lower.includes('आइशैडो') || lower.includes('ଆଇସ୍ୟାଡୋ')) searchKeyword = 'Eyeshadow';
    else if (lower.includes('mascara') || lower.includes('मस्कारा') || lower.includes('ମସ୍କାରା')) searchKeyword = 'Mascara';
    else if (lower.includes('skincare') || lower.includes('स्किनकेयर') || lower.includes('ତ୍ୱଚା')) searchKeyword = 'Skincare';
    else if (lower.includes('makeup kit') || lower.includes('किट') || lower.includes('କିଟ୍')) searchKeyword = 'Makeup Kits';
    else if (lower.includes('brush') || lower.includes('accessories') || lower.includes('ब्रश')) searchKeyword = 'Accessories';

    if (!searchKeyword) searchKeyword = text;

    responseText = `Showing ${searchKeyword} products`;
    actionCallback = () => {
      window.location.href = `products.php?search=${encodeURIComponent(searchKeyword)}`;
    };
  }

  // Update UI and Speak Response
  if (responseBox) responseBox.textContent = `🔊 "${responseText}"`;
  speakVoiceResponse(responseText, lang);

  if (actionCallback) {
    setTimeout(actionCallback, 1400);
  }
}

/**
 * SpeechSynthesis Audio Voice Output
 */
function speakVoiceResponse(text, lang = 'en-US') {
  if (!('speechSynthesis' in window)) return;

  window.speechSynthesis.cancel(); // Stop any previous speech
  const utterance = new SpeechSynthesisUtterance(text);
  utterance.rate = 1.0;
  utterance.pitch = 1.0;
  utterance.lang = lang;

  window.speechSynthesis.speak(utterance);
}

// --------------------------------------------------------------------------
// 6. Delete Confirmation Helper
// --------------------------------------------------------------------------
function confirmDelete(message = 'Are you sure you want to delete this item?') {
  return confirm(message);
}
