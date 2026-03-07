/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║              SHOPWISE AI — LIVE SEARCH v5.0                         ║
 * ║         Debounced AJAX search with dropdown results                  ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Usage:
 * <input type="text" 
 *        data-live-search 
 *        data-search-url="/products/search" 
 *        data-action="navigate">
 * <div data-search-results></div>
 */

(() => {
    'use strict';

    // ═══════════════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════

    const DEBOUNCE_MS = 300;
    const MIN_CHARS = 2;

    // ═══════════════════════════════════════════════════════════════════════
    // LIVE SEARCH INSTANCES
    // ═══════════════════════════════════════════════════════════════════════

    const searchInputs = document.querySelectorAll('[data-live-search]');

    searchInputs.forEach((input) => {
        const searchUrl = input.dataset.searchUrl;
        const action = input.dataset.action || 'navigate'; // navigate | add-to-cart
        
        if (!searchUrl) {
            console.error('Live search requires data-search-url attribute');
            return;
        }

        // Create dropdown container
        const dropdown = document.createElement('div');
        dropdown.className = 'search-dropdown';
        dropdown.style.cssText = `
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: var(--sw-surface);
            border: 1.5px solid var(--sw-border);
            border-radius: var(--sw-radius-md);
            box-shadow: var(--sw-shadow-lg);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        `;

        // Position input wrapper
        const wrapper = input.parentElement;
        if (wrapper && getComputedStyle(wrapper).position === 'static') {
            wrapper.style.position = 'relative';
        }

        wrapper.appendChild(dropdown);

        let debounceTimer;
        let selectedIndex = -1;

        // ═══════════════════════════════════════════════════════════════════════
        // INPUT HANDLER
        // ═══════════════════════════════════════════════════════════════════════

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            
            const query = input.value.trim();
            
            if (query.length < MIN_CHARS) {
                hideDropdown();
                return;
            }

            debounceTimer = setTimeout(async () => {
                await performSearch(query);
            }, DEBOUNCE_MS);
        });

        // ═══════════════════════════════════════════════════════════════════════
        // SEARCH FUNCTION
        // ═══════════════════════════════════════════════════════════════════════

        async function performSearch(query) {
            try {
                const url = new URL(searchUrl, window.location.origin);
                url.searchParams.set('q', query);

                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) throw new Error('Search failed');

                const data = await response.json();
                
                if (data.success && data.results) {
                    renderResults(data.results);
                } else {
                    showNoResults();
                }
            } catch (error) {
                console.error('Live search error:', error);
                showError();
            }
        }

        // ═══════════════════════════════════════════════════════════════════════
        // RENDER RESULTS
        // ═══════════════════════════════════════════════════════════════════════

        function renderResults(results) {
            if (results.length === 0) {
                showNoResults();
                return;
            }

            dropdown.innerHTML = results.map((result, index) => `
                <div class="search-result-item" 
                     data-index="${index}"
                     data-id="${result.id || ''}"
                     data-url="${result.url || ''}"
                     data-product="${result.product_id || ''}">
                    <div class="search-result-icon">
                        ${result.emoji || result.icon || '📦'}
                    </div>
                    <div class="search-result-content">
                        <div class="search-result-title">${highlightMatch(result.name || result.title, input.value)}</div>
                        <div class="search-result-meta">
                            ${result.price ? `<span class="search-result-price">${window.ShopWise.formatPeso(result.price)}</span>` : ''}
                            ${result.stock_qty !== undefined ? `
                                <span class="sw-badge sw-badge-${result.stock_qty > 10 ? 'success' : result.stock_qty > 0 ? 'warning' : 'danger'}">
                                    ${result.stock_qty > 0 ? `${result.stock_qty} in stock` : 'Out of stock'}
                                </span>
                            ` : ''}
                            ${result.category ? `<span class="text-muted">${result.category}</span>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');

            // Add styles for results
            if (!document.getElementById('searchResultStyles')) {
                const style = document.createElement('style');
                style.id = 'searchResultStyles';
                style.textContent = `
                    .search-result-item {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 12px 16px;
                        cursor: pointer;
                        transition: background 0.15s ease;
                        border-bottom: 1px solid var(--sw-border);
                    }
                    .search-result-item:last-child {
                        border-bottom: none;
                    }
                    .search-result-item:hover,
                    .search-result-item.selected {
                        background: var(--sw-primary-light);
                    }
                    .search-result-icon {
                        font-size: 28px;
                        width: 40px;
                        height: 40px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: var(--sw-surface2);
                        border-radius: var(--sw-radius-md);
                    }
                    .search-result-content {
                        flex: 1;
                    }
                    .search-result-title {
                        font-size: 14px;
                        font-weight: 600;
                        color: var(--sw-text);
                        margin-bottom: 4px;
                    }
                    .search-result-meta {
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        font-size: 12px;
                    }
                    .search-result-price {
                        color: var(--sw-accent);
                        font-weight: 700;
                        font-family: var(--sw-font-display);
                    }
                    .search-highlight {
                        background: var(--sw-accent-light);
                        color: var(--sw-accent-dark);
                        font-weight: 700;
                    }
                `;
                document.head.appendChild(style);
            }

            showDropdown();
            selectedIndex = -1;

            // Click handlers
            dropdown.querySelectorAll('.search-result-item').forEach((item) => {
                item.addEventListener('click', () => {
                    handleResultClick(item);
                });
            });
        }

        function highlightMatch(text, query) {
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<span class="search-highlight">$1</span>');
        }

        function showNoResults() {
            dropdown.innerHTML = `
                <div style="padding: 24px; text-align: center; color: var(--sw-text-muted);">
                    <div style="font-size: 32px; margin-bottom: 8px;">🔍</div>
                    <div style="font-size: 13px;">No results found</div>
                </div>
            `;
            showDropdown();
        }

        function showError() {
            dropdown.innerHTML = `
                <div style="padding: 16px; color: var(--sw-danger);">
                    ⚠️ Search failed. Please try again.
                </div>
            `;
            showDropdown();
        }

        function showDropdown() {
            dropdown.style.display = 'block';
        }

        function hideDropdown() {
            dropdown.style.display = 'none';
            selectedIndex = -1;
        }

        // ═══════════════════════════════════════════════════════════════════════
        // RESULT ACTIONS
        // ═══════════════════════════════════════════════════════════════════════

        function handleResultClick(item) {
            const url = item.dataset.url;
            const productId = item.dataset.product;

            hideDropdown();
            input.value = '';

            if (action === 'add-to-cart' && productId && typeof window.POSTerminal !== 'undefined') {
                // Trigger add to cart in POS
                const productData = {
                    product_id: parseInt(productId),
                    name: item.querySelector('.search-result-title').textContent.replace(/<[^>]*>/g, ''),
                    selling_price: parseFloat(item.querySelector('.search-result-price')?.textContent.replace(/[₱,]/g, '') || 0),
                    emoji: item.querySelector('.search-result-icon').textContent.trim(),
                    stock_qty: parseInt(item.querySelector('.sw-badge')?.textContent.match(/\d+/)?.[0] || 0)
                };
                window.POSTerminal.addToCart(productData);
            } else if (url && action === 'navigate') {
                window.location.href = url;
            }
        }

        // ═══════════════════════════════════════════════════════════════════════
        // KEYBOARD NAVIGATION
        // ═══════════════════════════════════════════════════════════════════════

        input.addEventListener('keydown', (e) => {
            const items = dropdown.querySelectorAll('.search-result-item');
            
            if (items.length === 0) return;

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelectedItem(items);
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, 0);
                    updateSelectedItem(items);
                    break;

                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        handleResultClick(items[selectedIndex]);
                    }
                    break;

                case 'Escape':
                    hideDropdown();
                    break;
            }
        });

        function updateSelectedItem(items) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('selected');
                    item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                } else {
                    item.classList.remove('selected');
                }
            });
        }

        // ═══════════════════════════════════════════════════════════════════════
        // CLICK OUTSIDE TO CLOSE
        // ═══════════════════════════════════════════════════════════════════════

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                hideDropdown();
            }
        });
    });

    console.log('Live search initialized');
})();
