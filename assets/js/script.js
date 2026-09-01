/**
 * GlowCart Cosmetics - Main JavaScript Engine
 * Features: Mobile Nav, Toast Notifications, AJAX Cart, Order Management
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initQuantityControls();
  initOrderCancellation();
  initLiveSearch();
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
