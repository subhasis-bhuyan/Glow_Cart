/**
 * GlowCart Cosmetics - Main JavaScript Engine
 * Features: Mobile Nav, Toast Notifications, AJAX Cart, Quick View, Order Management
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initMobileSearch();
  initQuantityControls();
  initOrderCancellation();
  initLiveSearch();
  initBackToTop();
  initQuickViewModal();
  initLiveCartUpdates();
  initCouponEngine();
  initProductDetailFeatures();
  initMobileFilterDrawer();
});

// --------------------------------------------------------------------------
// 1. Mobile Menu & Off-Canvas Drawer Toggle
// --------------------------------------------------------------------------
function initMobileMenu() {
  const toggleBtn = document.getElementById('mobileNavToggleBtn') || document.querySelector('.mobile-nav-toggle');
  const drawer = document.getElementById('mobileDrawer');
  const overlay = document.getElementById('mobileDrawerOverlay');
  const closeBtn = document.getElementById('drawerCloseBtn');

  if (!drawer || !toggleBtn) return;

  const openDrawer = () => {
    drawer.classList.add('active');
    if (overlay) overlay.classList.add('active');
    toggleBtn.classList.add('active');
    toggleBtn.setAttribute('aria-expanded', 'true');
    document.body.classList.add('no-scroll');
  };

  const closeDrawer = () => {
    drawer.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    toggleBtn.classList.remove('active');
    toggleBtn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('no-scroll');
  };

  toggleBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (drawer.classList.contains('active')) {
      closeDrawer();
    } else {
      openDrawer();
    }
  });

  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  if (overlay) overlay.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('active')) {
      closeDrawer();
    }
  });
}

// --------------------------------------------------------------------------
// 2. Toast Notification System with Action Links
// --------------------------------------------------------------------------
function showToast(message, type = 'success', actionUrl = null, actionText = 'View') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  
  let actionHtml = '';
  if (actionUrl) {
    actionHtml = `<a href="${actionUrl}" class="toast-action-btn" style="margin-left: 10px; color: #ffffff; text-decoration: underline; font-weight: 600; font-size: 12.5px;">${actionText}</a>`;
  }
  
  toast.innerHTML = `<span>${message}</span>${actionHtml}`;
  container.appendChild(toast);

  // Trigger animation
  requestAnimationFrame(() => {
    toast.classList.add('show');
  });

  // Remove after 3.8 seconds
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 3800);
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
        showToast(data.message || '✓ Product added to cart', 'success', 'cart.php', 'View Cart ➔');
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
  document.querySelectorAll('.cart-badge, .bottom-nav-badge, .cart-badge-inline').forEach(badge => {
    badge.textContent = count;
    badge.style.transform = 'scale(1.35)';
    setTimeout(() => {
      badge.style.transform = 'scale(1)';
    }, 250);
  });
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
// 5. Order Cancellation Modal & Workflow
// --------------------------------------------------------------------------
function initOrderCancellation() {
  const modalOverlay = document.getElementById('cancelOrderModal');
  const closeBtn = document.getElementById('closeCancelModalBtn');
  const dismissBtn = document.getElementById('dismissCancelModalBtn');
  const orderIdInput = document.getElementById('cancelOrderIdInput');
  const orderCodeSpan = document.getElementById('cancelOrderCodeDisplay');

  if (!modalOverlay) return;

  const openModal = (orderId, orderCode) => {
    if (orderIdInput) orderIdInput.value = orderId;
    if (orderCodeSpan) orderCodeSpan.textContent = orderCode;
    modalOverlay.classList.add('active');
  };

  const closeModal = () => {
    modalOverlay.classList.remove('active');
  };

  document.querySelectorAll('.btn-cancel-order-trigger').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const orderId = btn.getAttribute('data-order-id');
      const orderCode = btn.getAttribute('data-order-code') || `#GC-${String(orderId).padStart(5, '0')}`;
      openModal(orderId, orderCode);
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (dismissBtn) dismissBtn.addEventListener('click', closeModal);

  modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
  });
}

// --------------------------------------------------------------------------
// 8. Delete Confirmation Helper
// --------------------------------------------------------------------------
function confirmDelete(message = 'Are you sure you want to delete this item?') {
  return confirm(message);
}

// --------------------------------------------------------------------------
// 9. Favorites & Wishlist Engine
// --------------------------------------------------------------------------
function toggleFavorite(productId, buttonElement = null) {
  const formData = new FormData();
  formData.append('action', 'toggle');
  formData.append('product_id', productId);

  const ajaxUrl = window.location.pathname.includes('/admin/') ? '../ajax_favorite.php' : 'ajax_favorite.php';

  fetch(ajaxUrl, {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      if (data.require_login) {
        showToast(data.message || 'Please log in to save favorites', 'info');
        setTimeout(() => {
          window.location.href = `login.php?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`;
        }, 1200);
        return;
      }

      if (data.success) {
        showToast(data.message, data.is_favorite ? 'success' : 'info');
        updateFavoriteUI(productId, data.is_favorite);
        if (typeof data.favorite_count !== 'undefined') {
          updateFavoriteCounters(data.favorite_count);
        }
      } else {
        showToast(data.message || 'Error updating favorites', 'error');
      }
    })
    .catch(err => {
      console.error('Favorites Error:', err);
      showToast('Could not connect to server', 'error');
    });
}

function updateFavoriteUI(productId, isFavorite) {
  // Update all matching favorite buttons across the current page
  const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
  buttons.forEach(btn => {
    if (isFavorite) {
      btn.classList.add('active');
      btn.title = 'Remove from Favorites';
      
      const icon = btn.querySelector('.fav-heart-icon') || btn;
      if (btn.classList.contains('detail-fav-btn')) {
        const heartSpan = btn.querySelector('.fav-heart-icon');
        const labelSpan = btn.querySelector('.fav-btn-label');
        if (heartSpan) heartSpan.textContent = '❤️';
        if (labelSpan) labelSpan.textContent = 'In Favorites';
      } else {
        btn.innerHTML = '❤️';
      }
      
      // Pulse animation effect
      btn.style.transform = 'scale(1.35)';
      setTimeout(() => { btn.style.transform = ''; }, 250);

    } else {
      btn.classList.remove('active');
      btn.title = 'Add to Favorites';

      if (btn.classList.contains('detail-fav-btn')) {
        const heartSpan = btn.querySelector('.fav-heart-icon');
        const labelSpan = btn.querySelector('.fav-btn-label');
        if (heartSpan) heartSpan.textContent = '🤍';
        if (labelSpan) labelSpan.textContent = 'Add to Favorites';
      } else {
        btn.innerHTML = '🤍';
      }
    }
  });
}

function removeFromFavorites(productId, cardElement) {
  const formData = new FormData();
  formData.append('action', 'remove');
  formData.append('product_id', productId);

  fetch('ajax_favorite.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast(data.message || 'Product removed from favorites', 'info');

        // Animate card removal smoothly
        if (cardElement) {
          cardElement.style.transition = 'all 0.35s ease';
          cardElement.style.opacity = '0';
          cardElement.style.transform = 'scale(0.85) translateY(10px)';
          setTimeout(() => {
            cardElement.remove();
            
            // Check if any favorite cards remain
            const remaining = document.querySelectorAll('.favorite-item-card');
            if (remaining.length === 0) {
              const container = document.getElementById('favoritesGridContainer');
              if (container) {
                container.innerHTML = `
                  <div class="favorites-empty-state" id="favoritesEmptyState">
                      <div class="empty-heart-icon">❤️</div>
                      <h3>No Favorite Products Yet</h3>
                      <p>You haven't liked or favorited any cosmetic products yet. Browse our catalog and click the ❤️ icon on items you love to save them here!</p>
                      <a href="products.php" class="btn btn-primary btn-lg" style="margin-top: 15px;">
                          🛍️ Explore Beauty Collection
                      </a>
                  </div>
                `;
              }
            }
          }, 350);
        }

        if (typeof data.favorite_count !== 'undefined') {
          updateFavoriteCounters(data.favorite_count);
        }

        // Also update any card buttons on the page if applicable
        updateFavoriteUI(productId, false);

      } else {
        showToast(data.message || 'Could not remove product', 'error');
      }
    })
    .catch(err => {
      console.error('Remove Favorite Error:', err);
      showToast('Server connection error', 'error');
    });
}

function updateFavoriteCounters(count) {
  document.querySelectorAll('.fav-count-display').forEach(el => {
    el.textContent = count;
  });
}

// --------------------------------------------------------------------------
// 10. Real-time Live Interactive Search Engine
// --------------------------------------------------------------------------
function initLiveSearch() {
  const searchWrapper = document.getElementById('navSearchWrapper');
  const searchInput = document.getElementById('navSearchInput');
  const clearBtn = document.getElementById('navSearchClear');
  const spinner = document.getElementById('navSearchSpinner');
  const dropdown = document.getElementById('searchDropdownMenu');

  if (!searchInput || !dropdown) return;

  let debounceTimer = null;
  let activeIndex = -1;
  let currentResults = [];

  // Determine root path for ajax_search.php
  const ajaxUrl = window.location.pathname.includes('/admin/') ? '../ajax_search.php' : 'ajax_search.php';

  const hideDropdown = () => {
    dropdown.style.display = 'none';
    dropdown.innerHTML = '';
    activeIndex = -1;
  };

  const setSpinner = (loading) => {
    if (spinner) {
      spinner.style.display = loading ? 'block' : 'none';
    }
  };

  const updateClearBtn = () => {
    if (clearBtn) {
      clearBtn.style.display = searchInput.value.trim().length > 0 ? 'flex' : 'none';
    }
  };

  if (clearBtn) {
    clearBtn.addEventListener('click', (e) => {
      e.preventDefault();
      searchInput.value = '';
      updateClearBtn();
      hideDropdown();
      searchInput.focus();
    });
  }

  const performSearch = (query) => {
    if (!query || query.length === 0) {
      hideDropdown();
      setSpinner(false);
      return;
    }

    setSpinner(true);

    fetch(`${ajaxUrl}?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        setSpinner(false);
        if (!data.success) {
          hideDropdown();
          return;
        }

        renderSearchResults(query, data.products || [], data.orders || []);
      })
      .catch(err => {
        console.error('Search AJAX error:', err);
        setSpinner(false);
      });
  };

  const renderSearchResults = (query, products, orders) => {
    activeIndex = -1;
    currentResults = [];

    if (products.length === 0 && orders.length === 0) {
      dropdown.innerHTML = `
        <div class="search-dropdown-empty">
          <div class="empty-search-icon">🔍</div>
          <div class="empty-search-title">No matches found for "<strong>${escapeHtml(query)}</strong>"</div>
          <p class="empty-search-desc">Try checking your spelling or explore popular beauty categories below:</p>
          <div class="search-quick-tags">
            <button type="button" class="search-quick-tag" data-tag="Lipstick">💄 Lipstick</button>
            <button type="button" class="search-quick-tag" data-tag="Foundation">✨ Foundation</button>
            <button type="button" class="search-quick-tag" data-tag="Skincare">🌿 Skincare</button>
            <button type="button" class="search-quick-tag" data-tag="Makeup Kits">🎁 Kits</button>
          </div>
        </div>
      `;
      dropdown.style.display = 'block';

      dropdown.querySelectorAll('.search-quick-tag').forEach(tagBtn => {
        tagBtn.addEventListener('click', () => {
          const tag = tagBtn.getAttribute('data-tag');
          searchInput.value = tag;
          updateClearBtn();
          performSearch(tag);
          searchInput.focus();
        });
      });
      return;
    }

    let html = '';

    // Products Section
    if (products.length > 0) {
      html += `
        <div class="search-section-header">
          <span>Cosmetic Products</span>
          <span class="search-count-badge">${products.length} found</span>
        </div>
        <div class="search-items-list">
      `;

      products.forEach((p) => {
        const itemIndex = currentResults.length;
        currentResults.push({ type: 'product', url: p.url });

        const priceHtml = p.has_discount
          ? `<span class="search-price">${p.formatted_price}</span> <span class="search-price-old">${p.formatted_original_price}</span> <span class="search-discount-badge">-${p.discount_percent}%</span>`
          : `<span class="search-price">${p.formatted_price}</span>`;

        const stockBadge = p.in_stock
          ? `<span class="search-stock in-stock">✓ In Stock</span>`
          : `<span class="search-stock out-of-stock">✕ Out of Stock</span>`;

        const quickAddBtn = p.in_stock
          ? `<button type="button" class="search-quick-add-btn" data-product-id="${p.id}" title="Quick Add to Cart">🛍️ Add</button>`
          : '';

        html += `
          <div class="search-item search-product-item" data-index="${itemIndex}" data-url="${p.url}">
            <div class="search-item-thumb">
              <img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" onerror="this.src='https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=120&q=80'">
            </div>
            <div class="search-item-details">
              <div class="search-item-meta">
                <span class="search-category-pill">${escapeHtml(p.category)}</span>
                ${stockBadge}
              </div>
              <a href="${p.url}" class="search-item-title">${highlightSearchMatch(p.name, query)}</a>
              <div class="search-item-bottom">
                <div class="search-item-pricing">${priceHtml}</div>
                <div class="search-item-rating">★ ${p.rating.toFixed(1)}</div>
              </div>
            </div>
            <div class="search-item-actions">
              ${quickAddBtn}
              <a href="${p.url}" class="search-view-btn" title="View details">👁️</a>
            </div>
          </div>
        `;
      });

      html += `</div>`;
    }

    // Orders Section
    if (orders.length > 0) {
      html += `
        <div class="search-section-header search-order-section-header">
          <span>Matching Orders</span>
          <span class="search-count-badge">${orders.length} found</span>
        </div>
        <div class="search-items-list">
      `;

      orders.forEach((ord) => {
        const itemIndex = currentResults.length;
        currentResults.push({ type: 'order', url: ord.url });

        html += `
          <div class="search-item search-order-item" data-index="${itemIndex}" data-url="${ord.url}">
            <div class="search-order-icon">📦</div>
            <div class="search-item-details">
              <div class="search-item-meta">
                <strong class="search-order-code">${highlightSearchMatch(ord.order_code, query)}</strong>
                <span class="status-pill status-${ord.status}" style="font-size: 11px; padding: 2px 8px;">● ${ord.status}</span>
              </div>
              <div class="search-order-subtext" style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                Placed on ${ord.formatted_date} &bull; Total: <strong>${ord.formatted_total}</strong> (${ord.payment_method})
              </div>
            </div>
            <div class="search-item-actions">
              <a href="${ord.url}" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 12px;">View</a>
            </div>
          </div>
        `;
      });

      html += `</div>`;
    }

    // Dropdown Footer
    html += `
      <a href="products.php?search=${encodeURIComponent(query)}" class="search-dropdown-footer">
        <span>View all results for "<strong>${escapeHtml(query)}</strong>" in Shop</span>
        <span class="footer-arrow">➔</span>
      </a>
    `;

    dropdown.innerHTML = html;
    dropdown.style.display = 'block';

    // Click on item row navigates
    dropdown.querySelectorAll('.search-item').forEach(itemEl => {
      itemEl.addEventListener('click', (e) => {
        if (e.target.closest('.search-quick-add-btn') || e.target.closest('a')) {
          return;
        }
        const url = itemEl.getAttribute('data-url');
        if (url) window.location.href = url;
      });
    });

    // Quick add to cart
    dropdown.querySelectorAll('.search-quick-add-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        e.preventDefault();
        const pid = btn.getAttribute('data-product-id');
        btn.disabled = true;
        btn.textContent = '⏳';
        addToCart(pid, 1, (success) => {
          btn.disabled = false;
          btn.textContent = success ? '✓ Added' : '🛍️ Add';
          if (success) {
            setTimeout(() => {
              btn.textContent = '🛍️ Add';
            }, 2000);
          }
        });
      });
    });
  };

  // Input event listener
  searchInput.addEventListener('input', () => {
    updateClearBtn();
    const query = searchInput.value.trim();

    clearTimeout(debounceTimer);
    if (query.length === 0) {
      hideDropdown();
      setSpinner(false);
      return;
    }

    debounceTimer = setTimeout(() => {
      performSearch(query);
    }, 220);
  });

  // Focus event listener
  searchInput.addEventListener('focus', () => {
    const query = searchInput.value.trim();
    if (query.length > 0 && dropdown.innerHTML.trim() !== '') {
      dropdown.style.display = 'block';
    } else if (query.length > 0) {
      performSearch(query);
    }
  });

  // Keyboard navigation
  searchInput.addEventListener('keydown', (e) => {
    const items = dropdown.querySelectorAll('.search-item');
    if (!items.length || dropdown.style.display === 'none') {
      if (e.key === 'Escape') hideDropdown();
      return;
    }

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIndex = (activeIndex + 1) % items.length;
      updateActiveItem(items);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIndex = (activeIndex - 1 + items.length) % items.length;
      updateActiveItem(items);
    } else if (e.key === 'Enter') {
      if (activeIndex >= 0 && activeIndex < items.length) {
        e.preventDefault();
        const activeUrl = items[activeIndex].getAttribute('data-url');
        if (activeUrl) window.location.href = activeUrl;
      }
    } else if (e.key === 'Escape') {
      hideDropdown();
      searchInput.blur();
    }
  });

  const updateActiveItem = (items) => {
    items.forEach((it, i) => {
      if (i === activeIndex) {
        it.classList.add('search-item-active');
        it.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      } else {
        it.classList.remove('search-item-active');
      }
    });
  };

  // Click outside to dismiss
  document.addEventListener('click', (e) => {
    if (searchWrapper && !searchWrapper.contains(e.target)) {
      hideDropdown();
    }
  });
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function highlightSearchMatch(text, query) {
  if (!text || !query) return escapeHtml(text);
  const escapedText = escapeHtml(text);
  const cleanQ = query.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  if (!cleanQ) return escapedText;
  const regex = new RegExp(`(${cleanQ})`, 'gi');
  return escapedText.replace(regex, '<mark class="search-highlight">$1</mark>');
}

/**
 * Quick Price Range Preset Filter
 */
function applyPricePreset(min, max) {
  const minInput = document.getElementById('minPriceInput');
  const maxInput = document.getElementById('maxPriceInput');
  const form = document.getElementById('filterForm');
  if (minInput && maxInput && form) {
    minInput.value = min > 0 ? min : '';
    maxInput.value = max > 0 ? max : '';
    form.submit();
  }
}

// --------------------------------------------------------------------------
// 11. Mobile Search Bar Toggle
// --------------------------------------------------------------------------
function initMobileSearch() {
  const searchToggleBtn = document.getElementById('mobileSearchToggleBtn');
  const bottomSearchBtn = document.getElementById('bottomNavSearchBtn');
  const searchWrapper = document.getElementById('navSearchWrapper');
  const searchInput = document.getElementById('navSearchInput');

  const toggleSearch = (e) => {
    if (e) e.preventDefault();
    if (!searchWrapper) return;

    const isActive = searchWrapper.classList.toggle('active');
    if (searchToggleBtn) searchToggleBtn.classList.toggle('active', isActive);

    if (isActive && searchInput) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
      setTimeout(() => searchInput.focus(), 150);
    }
  };

  if (searchToggleBtn) searchToggleBtn.addEventListener('click', toggleSearch);
  if (bottomSearchBtn) bottomSearchBtn.addEventListener('click', toggleSearch);
}

// --------------------------------------------------------------------------
// 12. Floating Back-to-Top Button
// --------------------------------------------------------------------------
function initBackToTop() {
  const btn = document.getElementById('backToTopBtn');
  if (!btn) return;

  window.addEventListener('scroll', () => {
    if (window.scrollY > 280) {
      btn.classList.add('visible');
    } else {
      btn.classList.remove('visible');
    }
  }, { passive: true });

  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

// --------------------------------------------------------------------------
// 13. Interactive Quick View Modal
// --------------------------------------------------------------------------
function initQuickViewModal() {
  const modal = document.getElementById('quickViewModal');
  const content = document.getElementById('quickViewContent');
  const closeBtn = document.getElementById('closeQuickViewBtn');

  if (!modal || !content) return;

  const closeModal = () => {
    modal.classList.remove('active');
    document.body.classList.remove('no-scroll');
  };

  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) {
      closeModal();
    }
  });

  // Delegate clicks on any Quick View trigger
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-quickview-id]');
    if (!trigger) return;

    e.preventDefault();
    e.stopPropagation();

    const productId = trigger.getAttribute('data-quickview-id');
    if (!productId) return;

    openQuickView(productId);
  });

  const openQuickView = (productId) => {
    modal.classList.add('active');
    document.body.classList.add('no-scroll');

    content.innerHTML = `
      <div class="quickview-loading-state">
        <div class="search-spinner" style="width: 36px; height: 36px; margin: 0 auto 12px;"></div>
        <p style="color: var(--text-muted); font-size: 14px;">Loading beauty product details...</p>
      </div>
    `;

    const ajaxUrl = window.location.pathname.includes('/admin/') ? '../ajax_cart.php' : 'ajax_cart.php';

    fetch(`${ajaxUrl}?action=quick_view&product_id=${productId}`)
      .then(res => res.json())
      .then(data => {
        if (!data.success || !data.product) {
          content.innerHTML = `
            <div style="padding: 40px; text-align: center; grid-column: 1 / -1;">
              <h3>Could not load product details</h3>
              <p style="color: var(--text-muted); margin-top: 6px;">Please try visiting the product page directly.</p>
            </div>
          `;
          return;
        }

        renderQuickViewProduct(data.product);
      })
      .catch(err => {
        console.error('Quick View Error:', err);
        content.innerHTML = `
          <div style="padding: 40px; text-align: center; grid-column: 1 / -1;">
            <h3>Connection Error</h3>
            <p style="color: var(--text-muted); margin-top: 6px;">Could not connect to the server.</p>
          </div>
        `;
      });
  };

  const renderQuickViewProduct = (p) => {
    const priceHtml = p.has_discount
      ? `<span class="quickview-price">${p.formatted_price}</span>
         <span style="font-size: 15px; color: var(--text-muted); text-decoration: line-through;">${p.formatted_original_price}</span>
         <span class="badge badge-sale" style="font-size: 11px;">-${p.discount_percent}% OFF</span>`
      : `<span class="quickview-price">${p.formatted_price}</span>`;

    const stockBadge = p.in_stock
      ? `<span class="badge badge-in-stock">✓ In Stock (${p.stock} available)</span>`
      : `<span class="badge badge-out-of-stock">✕ Out of Stock</span>`;

    content.innerHTML = `
      <div class="quickview-image-wrap">
        <img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" onerror="this.src='https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80'">
      </div>
      <div class="quickview-details">
        <div class="quickview-category">${escapeHtml(p.category)}</div>
        <h2 class="quickview-title" id="qvProductTitle">${escapeHtml(p.name)}</h2>

        <div class="quickview-rating">
          <span style="color: #fbc02d; font-size: 15px;">★ ★ ★ ★ ★</span>
          <strong>${p.rating.toFixed(1)}</strong>
          <span style="color: var(--text-muted); font-size: 12px;">(Verified Beauty Rating)</span>
        </div>

        <div class="quickview-price-box">
          ${priceHtml}
        </div>

        <div style="margin-bottom: 16px;">
          ${stockBadge}
        </div>

        <p class="quickview-desc">${escapeHtml(p.description)}</p>

        ${p.in_stock ? `
          <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Quantity:</label>
            <div class="quantity-control">
              <button type="button" class="qty-btn" id="qvMinusBtn">-</button>
              <input type="number" id="qvQtyInput" class="qty-input" value="1" min="1" max="${p.stock}" readonly>
              <button type="button" class="qty-btn" id="qvPlusBtn">+</button>
            </div>
          </div>

          <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary btn-lg" id="qvAddToCartBtn" style="flex: 1; min-width: 160px;">
              🛍️ Add to Cart
            </button>
            <a href="${p.url}" class="btn btn-outline btn-lg" style="flex: 1; min-width: 140px;">
              View Full Details &rarr;
            </a>
          </div>
        ` : `
          <a href="${p.url}" class="btn btn-outline btn-lg btn-block">
            View Similar Products &rarr;
          </a>
        `}
      </div>
    `;

    if (p.in_stock) {
      const qvMinus = document.getElementById('qvMinusBtn');
      const qvPlus = document.getElementById('qvPlusBtn');
      const qvInput = document.getElementById('qvQtyInput');
      const qvAddBtn = document.getElementById('qvAddToCartBtn');

      if (qvMinus && qvPlus && qvInput) {
        qvMinus.addEventListener('click', () => {
          let val = parseInt(qvInput.value) || 1;
          if (val > 1) qvInput.value = val - 1;
        });

        qvPlus.addEventListener('click', () => {
          let val = parseInt(qvInput.value) || 1;
          if (val < p.stock) {
            qvInput.value = val + 1;
          } else {
            showToast(`Maximum available stock is ${p.stock}`, 'error');
          }
        });
      }

      if (qvAddBtn && qvInput) {
        qvAddBtn.addEventListener('click', () => {
          const qty = parseInt(qvInput.value) || 1;
          qvAddBtn.disabled = true;
          qvAddBtn.textContent = 'Adding...';

          addToCart(p.id, qty, (success) => {
            qvAddBtn.disabled = false;
            qvAddBtn.textContent = success ? '✓ Added!' : '🛍️ Add to Cart';
            if (success) {
              setTimeout(() => {
                closeModal();
              }, 1000);
            }
          });
        });
      }
    }
  };
}

// --------------------------------------------------------------------------
// 14. Real-time AJAX Cart Quantity & Price Recalculation Engine
// --------------------------------------------------------------------------
function initLiveCartUpdates() {
  const cartTable = document.querySelector('.cart-table');
  if (!cartTable) return;

  // Listen for quantity change events on the cart page
  document.querySelectorAll('.cart-table .quantity-control').forEach(ctrl => {
    const input = ctrl.querySelector('.qty-input');
    const btnMinus = ctrl.querySelector('.qty-minus');
    const btnPlus = ctrl.querySelector('.qty-plus');

    if (!input) return;

    // Extract product ID from name="quantities[PID]"
    const match = input.name.match(/\[(\d+)\]/);
    if (!match) return;
    const productId = parseInt(match[1]);

    const triggerUpdate = (newQty) => {
      ctrl.style.opacity = '0.6';

      const formData = new FormData();
      formData.append('action', 'update');
      formData.append('product_id', productId);
      formData.append('quantity', newQty);

      fetch('ajax_cart.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          ctrl.style.opacity = '1';
          if (data.success) {
            updateCartBadge(data.cart_count);
            updateCartSummaryDOM(data);

            // Update item line subtotal
            const lineSubEl = document.getElementById(`lineSubtotal-${productId}`);
            if (lineSubEl && data.formatted_item_line_subtotal) {
              lineSubEl.textContent = data.formatted_item_line_subtotal;
              lineSubEl.style.transform = 'scale(1.15)';
              setTimeout(() => lineSubEl.style.transform = '', 200);
            }

            if (data.cart_empty) {
              setTimeout(() => window.location.reload(), 300);
            }
          } else {
            showToast(data.message || 'Could not update quantity', 'error');
          }
        })
        .catch(err => {
          ctrl.style.opacity = '1';
          console.error('Cart live update error:', err);
        });
    };

    // Override or hook into plus/minus
    if (btnMinus) {
      btnMinus.addEventListener('click', () => {
        const val = parseInt(input.value) || 1;
        triggerUpdate(val);
      });
    }

    if (btnPlus) {
      btnPlus.addEventListener('click', () => {
        const val = parseInt(input.value) || 1;
        triggerUpdate(val);
      });
    }
  });
}

function updateCartSummaryDOM(data) {
  const subtotalEl = document.getElementById('cartSubtotalDisplay');
  const discountRow = document.getElementById('cartDiscountRow');
  const discountEl = document.getElementById('cartDiscountDisplay');
  const deliveryEl = document.getElementById('cartDeliveryDisplay');
  const grandTotalEl = document.getElementById('cartGrandTotalDisplay');

  if (subtotalEl && data.formatted_subtotal) {
    subtotalEl.textContent = data.formatted_subtotal;
  }

  if (discountRow && discountEl) {
    if (data.discount > 0) {
      discountRow.style.display = 'flex';
      discountEl.textContent = data.formatted_discount;
    } else {
      discountRow.style.display = 'none';
    }
  }

  if (deliveryEl && data.formatted_delivery_charge) {
    deliveryEl.innerHTML = data.delivery_charge === 0 
      ? '<span style="color: var(--success);">FREE</span>' 
      : data.formatted_delivery_charge;
  }

  if (grandTotalEl && data.formatted_grand_total) {
    grandTotalEl.textContent = data.formatted_grand_total;
    grandTotalEl.style.transform = 'scale(1.1)';
    setTimeout(() => grandTotalEl.style.transform = '', 250);
  }
}

// Override removeItemFromCart for smooth row deletion
function removeItemFromCart(productId) {
  if (!confirm('Remove this item from your shopping cart?')) return;

  const formData = new FormData();
  formData.append('action', 'remove');
  formData.append('product_id', productId);

  fetch('ajax_cart.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast('Item removed from cart', 'info');
        updateCartBadge(data.cart_count);

        // Animate row removal
        const row = document.getElementById(`cartRow-${productId}`);
        if (row) {
          row.style.transition = 'all 0.3s ease';
          row.style.opacity = '0';
          row.style.transform = 'scale(0.9) translateY(10px)';
          setTimeout(() => {
            row.remove();
            if (data.cart_empty) {
              window.location.reload();
            } else {
              updateCartSummaryDOM(data);
            }
          }, 300);
        } else {
          window.location.reload();
        }
      }
    })
    .catch(err => {
      console.error('Cart item remove error:', err);
      window.location.href = `cart.php?action=remove&id=${productId}`;
    });
}

// --------------------------------------------------------------------------
// 15. Interactive Promo Coupon Engine
// --------------------------------------------------------------------------
function initCouponEngine() {
  const applyBtn = document.getElementById('applyCouponBtn');
  const input = document.getElementById('couponCodeInput');
  const removeBtn = document.getElementById('removeCouponBtn');

  if (!applyBtn || !input) return;

  applyBtn.addEventListener('click', (e) => {
    e.preventDefault();
    const code = input.value.trim();
    if (!code) {
      showToast('Please enter a coupon code', 'info');
      input.focus();
      return;
    }

    applyBtn.disabled = true;
    applyBtn.textContent = 'Verifying...';

    const formData = new FormData();
    formData.append('action', 'apply_coupon');
    formData.append('coupon_code', code);

    fetch('ajax_cart.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        applyBtn.disabled = false;
        applyBtn.textContent = 'Apply';

        if (data.success) {
          showToast(data.message, 'success');
          if (data.totals) {
            updateCartSummaryDOM(data.totals);
          }
          // Show applied badge
          const badgeWrap = document.getElementById('appliedCouponBadgeWrap');
          if (badgeWrap) {
            badgeWrap.innerHTML = `
              <div class="cart-coupon-badge-applied">
                <span>🎉 Coupon <strong>${escapeHtml(data.code)}</strong> Applied!</span>
                <button type="button" class="cart-coupon-remove-btn" onclick="removeCouponCode()">✕</button>
              </div>
            `;
          }
        } else {
          showToast(data.message || 'Invalid coupon code', 'error');
          input.classList.add('shake-anim');
          setTimeout(() => input.classList.remove('shake-anim'), 400);
        }
      })
      .catch(err => {
        applyBtn.disabled = false;
        applyBtn.textContent = 'Apply';
        console.error('Coupon error:', err);
      });
  });
}

function removeCouponCode() {
  const formData = new FormData();
  formData.append('action', 'apply_coupon');
  formData.append('coupon_code', '');

  fetch('ajax_cart.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      showToast('Coupon removed', 'info');
      const badgeWrap = document.getElementById('appliedCouponBadgeWrap');
      if (badgeWrap) badgeWrap.innerHTML = '';
      const input = document.getElementById('couponCodeInput');
      if (input) input.value = '';
      if (data.totals) updateCartSummaryDOM(data.totals);
    });
}

// --------------------------------------------------------------------------
// 16. Product Details Interactive Tabs & Sticky Mobile Purchase Bar
// --------------------------------------------------------------------------
function initProductDetailFeatures() {
  // Tabs Navigation
  const tabBtns = document.querySelectorAll('.detail-tab-btn');
  const tabPanes = document.querySelectorAll('.detail-tab-pane');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-tab-target');
      if (!targetId) return;

      tabBtns.forEach(b => b.classList.remove('active'));
      tabPanes.forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      const pane = document.getElementById(targetId);
      if (pane) pane.classList.add('active');
    });
  });

  // Sticky Mobile Purchase Bar
  const stickyBar = document.getElementById('detailStickyBar');
  const mainActions = document.querySelector('.detail-actions');

  if (stickyBar && mainActions) {
    window.addEventListener('scroll', () => {
      if (window.innerWidth <= 768) {
        const rect = mainActions.getBoundingClientRect();
        if (rect.bottom < 0) {
          stickyBar.classList.add('visible');
        } else {
          stickyBar.classList.remove('visible');
        }
      } else {
        stickyBar.classList.remove('visible');
      }
    }, { passive: true });
  }
}

// Share Product Feature
function shareCurrentProduct(title, text) {
  if (navigator.share) {
    navigator.share({
      title: title || document.title,
      text: text || 'Check out this luxury beauty product on GlowCart Cosmetics!',
      url: window.location.href
    }).catch(err => console.log('Share dismissed', err));
  } else {
    // Clipboard copy fallback
    navigator.clipboard.writeText(window.location.href).then(() => {
      showToast('✓ Link copied to clipboard!', 'success');
    }).catch(() => {
      showToast('Could not copy link', 'info');
    });
  }
}

// --------------------------------------------------------------------------
// 17. Mobile Filter Drawer (products.php)
// --------------------------------------------------------------------------
function initMobileFilterDrawer() {
  const openBtn = document.getElementById('mobileFilterOpenBtn');
  const closeBtn = document.getElementById('mobileFilterCloseBtn');
  const sidebar = document.querySelector('.filter-sidebar');
  const overlay = document.getElementById('mobileFilterOverlay');

  if (!sidebar) return;

  const openDrawer = () => {
    sidebar.classList.add('active');
    if (overlay) overlay.classList.add('active');
    document.body.classList.add('no-scroll');
  };

  const closeDrawer = () => {
    sidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.classList.remove('no-scroll');
  };

  if (openBtn) openBtn.addEventListener('click', openDrawer);
  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  if (overlay) overlay.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('active')) {
      closeDrawer();
    }
  });
}


