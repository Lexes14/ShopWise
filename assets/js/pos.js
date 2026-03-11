/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║              SHOPWISE AI — POS TERMINAL v5.0                        ║
 * ║         Complete cart management and checkout logic                  ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Keyboard shortcuts:
 * F1  = Focus search
 * F2  = Open checkout
 * F3  = Hold transaction
 * F4  = Recall held transactions
 * Esc = Close modal
 */

(() => {
    'use strict';

    const api = (path) => {
        const base = window.ShopWise?.baseUrl || '';
        return `${base}${path}`;
    };

    // ═══════════════════════════════════════════════════════════════════════
    // STATE MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════

    const state = {
        cart: [],
        customerType: 'regular', // regular, senior, pwd
        paymentMethod: 'cash',
        currentTransaction: null,
        heldTransactions: []
    };

    // ═══════════════════════════════════════════════════════════════════════
    // DOM ELEMENTS
    // ═══════════════════════════════════════════════════════════════════════

    const elements = {
        cartItems: document.getElementById('cartItems'),
        cartCount: document.getElementById('cartCount'),
        subtotal: document.getElementById('subtotal'),
        discount: document.getElementById('discount'),
        vat: document.getElementById('vat'),
        grandTotal: document.getElementById('grandTotal'),
        checkoutBtn: document.getElementById('checkoutBtn'),
        clearCartBtn: document.getElementById('clearCartBtn'),
        holdBtn: document.getElementById('holdBtn'),
        recallBtn: document.getElementById('recallBtn'),
        searchInput: document.getElementById('posSearch')
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CART OPERATIONS
    // ═══════════════════════════════════════════════════════════════════════

    function addToCart(product) {
        // Validate stock
        if (product.stock_qty <= 0) {
            window.ShopWise.toast('Product is out of stock', 'error');
            return;
        }

        // Check if product already in cart
        const existingItem = state.cart.find(item => item.product_id === product.product_id);
        
        if (existingItem) {
            // Check if adding more would exceed stock
            if (existingItem.qty + 1 > product.stock_qty) {
                window.ShopWise.toast(`Only ${product.stock_qty} available in stock`, 'warning');
                return;
            }
            existingItem.qty++;
        } else {
            state.cart.push({
                product_id: product.product_id,
                name: product.name,
                price: product.selling_price,
                emoji: product.emoji || '📦',
                stock_qty: product.stock_qty,
                qty: 1,
                promotion: product.promotion || null,
                promo_applied: !!(product.promotion && Number(product.promotion.promo_id) > 0)
            });
        }

        renderCart();
        animateProductCard(product.product_id);
    }

    function removeFromCart(productId) {
        state.cart = state.cart.filter(item => item.product_id !== productId);
        renderCart();
    }

    function updateQty(productId, qty) {
        const item = state.cart.find(item => item.product_id === productId);
        if (!item) return;

        const newQty = parseInt(qty);
        
        if (newQty <= 0) {
            removeFromCart(productId);
            return;
        }

        if (newQty > item.stock_qty) {
            window.ShopWise.toast(`Only ${item.stock_qty} available in stock`, 'warning');
            return;
        }

        item.qty = newQty;
        renderCart();
    }

    function clearCart() {
        if (state.cart.length === 0) return;
        
        window.ShopWise.confirm('Clear all items from cart?', () => {
            state.cart = [];
            renderCart();
            window.ShopWise.toast('Cart cleared', 'info');
        });
    }

    function togglePromo(productId) {
        const item = state.cart.find((entry) => entry.product_id === productId);
        if (!item || !item.promotion || !item.promotion.promo_id) {
            return;
        }
        item.promo_applied = !item.promo_applied;
        renderCart();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RENDERING
    // ═══════════════════════════════════════════════════════════════════════

    function renderCart() {
        if (!elements.cartItems) return;

        if (state.cart.length === 0) {
            elements.cartItems.innerHTML = `
                <div class="pos-cart-empty">
                    <div class="pos-cart-empty-icon">🛒</div>
                    <div class="pos-cart-empty-text">Cart is empty</div>
                </div>
            `;
            renderTotals();
            return;
        }

        elements.cartItems.innerHTML = state.cart.map(item => {
            const hasPromo = !!(item.promotion && Number(item.promotion.promo_id) > 0);
            const promoText = hasPromo
                ? (Number(item.promotion.discount_pct) > 0
                    ? `${Number(item.promotion.discount_pct)}% OFF`
                    : `${window.ShopWise.formatPeso(Number(item.promotion.discount_amount || 0))} OFF`)
                : '';
            return `
            <div class="pos-cart-item" data-product-id="${item.product_id}">
                <div class="pos-cart-item-header">
                    <div class="pos-cart-item-emoji">${item.emoji}</div>
                    <div class="pos-cart-item-name">${item.name}</div>
                    <button class="pos-cart-item-remove" onclick="window.POSTerminal.removeFromCart(${item.product_id})">
                        ×
                    </button>
                </div>
                <div class="pos-cart-item-controls">
                    <button class="pos-qty-btn" onclick="window.POSTerminal.updateQty(${item.product_id}, ${item.qty - 1})">−</button>
                    <input type="number" 
                           class="pos-qty-display" 
                           value="${item.qty}" 
                           min="1" 
                           max="${item.stock_qty}"
                           onchange="window.POSTerminal.updateQty(${item.product_id}, this.value)">
                    <button class="pos-qty-btn" onclick="window.POSTerminal.updateQty(${item.product_id}, ${item.qty + 1})">+</button>
                </div>
                <div class="pos-cart-item-price">
                    ${window.ShopWise.formatPeso(item.price * item.qty)}
                </div>
                ${hasPromo ? `
                    <div class="mt-1 d-flex justify-content-between align-items-center">
                        <small class="text-muted">${item.promotion.promo_name || 'Promo'} (${promoText})</small>
                        <button class="btn btn-sm ${item.promo_applied ? 'btn-success' : 'btn-outline-success'}"
                                onclick="window.POSTerminal.togglePromo(${item.product_id})">
                            ${item.promo_applied ? 'Promo Applied' : 'Apply Promo'}
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
        }).join('');

        elements.cartItems.scrollTop = elements.cartItems.scrollHeight;

        renderTotals();
    }

    function renderTotals() {
        const totals = calculateTotals();
        
        if (elements.cartCount) {
            elements.cartCount.textContent = state.cart.reduce((sum, item) => sum + item.qty, 0);
        }
        
        if (elements.subtotal) {
            elements.subtotal.textContent = window.ShopWise.formatPeso(totals.subtotal);
        }
        
        if (elements.discount) {
            elements.discount.textContent = window.ShopWise.formatPeso(totals.discount);
        }
        
        if (elements.vat) {
            elements.vat.textContent = window.ShopWise.formatPeso(totals.vat);
        }
        
        if (elements.grandTotal) {
            elements.grandTotal.textContent = window.ShopWise.formatPeso(totals.grandTotal);
        }
        
        if (elements.checkoutBtn) {
            elements.checkoutBtn.disabled = state.cart.length === 0;
        }
    }

    function calculateTotals() {
        const subtotal = state.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        
        const promoDiscount = state.cart.reduce((sum, item) => {
            if (!item.promo_applied || !item.promotion) return sum;
            const lineSubtotal = item.price * item.qty;
            let lineDiscount = 0;
            const pct = Number(item.promotion.discount_pct || 0);
            const amt = Number(item.promotion.discount_amount || 0);
            if (pct > 0) {
                lineDiscount = lineSubtotal * (pct / 100);
            } else if (amt > 0) {
                lineDiscount = amt * item.qty;
            }
            return sum + Math.min(lineSubtotal, lineDiscount);
        }, 0);

        // Apply discount for senior/PWD (20%) after item promotions
        const baseAfterPromo = Math.max(0, subtotal - promoDiscount);
        const customerDiscountRate = (state.customerType === 'senior' || state.customerType === 'pwd') ? 0.20 : 0;
        const customerDiscount = baseAfterPromo * customerDiscountRate;
        const discount = promoDiscount + customerDiscount;

        const vatableSales = subtotal - discount;
        
        // VAT-inclusive calculation: vatAmount = vatableSales - (vatableSales / 1.12)
        const vat = vatableSales - (vatableSales / 1.12);
        
        const grandTotal = vatableSales;
        
        return { subtotal, discount, promoDiscount, customerDiscount, vat, grandTotal };
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CHECKOUT MODAL
    // ═══════════════════════════════════════════════════════════════════════

    function openCheckoutModal() {
        if (state.cart.length === 0) return;

        const totals = calculateTotals();
        const modal = document.createElement('div');
        modal.id = 'checkoutModal';
        modal.className = 'sw-modal-backdrop';
        
        modal.innerHTML = `
            <div class="sw-modal pos-checkout-modal">
                <div class="sw-modal-header">
                    <h3 class="sw-modal-title">Checkout</h3>
                    <button class="sw-modal-close" onclick="this.closest('.sw-modal-backdrop').remove()">×</button>
                </div>
                <div class="sw-modal-body">
                    <!-- Order Summary -->
                    <div class="pos-order-summary">
                        <div class="pos-total-row">
                            <span class="pos-total-label">Subtotal:</span>
                            <span class="pos-total-value">${window.ShopWise.formatPeso(totals.subtotal)}</span>
                        </div>
                        <div class="pos-total-row">
                            <span class="pos-total-label">Promo Discount:</span>
                            <span class="pos-total-value">${window.ShopWise.formatPeso(totals.promoDiscount || 0)}</span>
                        </div>
                        <div class="pos-total-row">
                            <span class="pos-total-label">Customer Discount:</span>
                            <span class="pos-total-value">${window.ShopWise.formatPeso(totals.customerDiscount || 0)}</span>
                        </div>
                        <div class="pos-total-row">
                            <span class="pos-total-label">Total Discount:</span>
                            <span class="pos-total-value">${window.ShopWise.formatPeso(totals.discount)}</span>
                        </div>
                        <div class="pos-total-row">
                            <span class="pos-total-label">VAT (12%):</span>
                            <span class="pos-total-value">${window.ShopWise.formatPeso(totals.vat)}</span>
                        </div>
                        <div class="pos-total-row pos-grand-total">
                            <span class="pos-total-label">Total:</span>
                            <span class="pos-total-value">${window.ShopWise.formatPeso(totals.grandTotal)}</span>
                        </div>
                    </div>

                    <!-- Customer Type -->
                    <div class="pos-customer-type-tabs">
                        <button class="pos-customer-tab ${state.customerType === 'regular' ? 'active' : ''}" 
                                onclick="window.POSTerminal.setCustomerType('regular')">
                            Regular
                        </button>
                        <button class="pos-customer-tab ${state.customerType === 'senior' ? 'active' : ''}" 
                                onclick="window.POSTerminal.setCustomerType('senior')">
                            Senior (20% OFF)
                        </button>
                        <button class="pos-customer-tab ${state.customerType === 'pwd' ? 'active' : ''}" 
                                onclick="window.POSTerminal.setCustomerType('pwd')">
                            PWD (20% OFF)
                        </button>
                    </div>

                    <!-- Payment Method -->
                    <div class="pos-payment-tabs">
                        <button class="pos-payment-tab ${state.paymentMethod === 'cash' ? 'active' : ''}" 
                                onclick="window.POSTerminal.setPaymentMethod('cash')">
                            💵 Cash
                        </button>
                        <button class="pos-payment-tab ${state.paymentMethod === 'gcash' ? 'active' : ''}" 
                                onclick="window.POSTerminal.setPaymentMethod('gcash')">
                            📱 GCash
                        </button>
                        <button class="pos-payment-tab ${state.paymentMethod === 'maya' ? 'active' : ''}" 
                                onclick="window.POSTerminal.setPaymentMethod('maya')">
                            🟠 Maya
                        </button>
                        <button class="pos-payment-tab ${state.paymentMethod === 'card' ? 'active' : ''}" 
                                onclick="window.POSTerminal.setPaymentMethod('card')">
                            💳 Card
                        </button>
                    </div>

                    <!-- Cash Payment Section -->
                    <div id="cashPaymentSection" style="display: ${state.paymentMethod === 'cash' ? 'block' : 'none'}">
                        <div class="sw-form-group">
                            <label for="amountTendered">Amount Tendered</label>
                            <input type="number" 
                                   id="amountTendered" 
                                   class="sw-input" 
                                   placeholder="0.00"
                                   step="0.01"
                                   min="${totals.grandTotal}"
                                   oninput="window.POSTerminal.calculateChange()">
                        </div>

                        <!-- Quick Cash Buttons -->
                        <div class="pos-quick-cash">
                            <button class="pos-quick-cash-btn" onclick="window.POSTerminal.setTendered(${Math.ceil(totals.grandTotal / 100) * 100})">
                                ${window.ShopWise.formatPeso(Math.ceil(totals.grandTotal / 100) * 100)}
                            </button>
                            <button class="pos-quick-cash-btn" onclick="window.POSTerminal.setTendered(${Math.ceil(totals.grandTotal / 500) * 500})">
                                ${window.ShopWise.formatPeso(Math.ceil(totals.grandTotal / 500) * 500)}
                            </button>
                            <button class="pos-quick-cash-btn" onclick="window.POSTerminal.setTendered(${Math.ceil(totals.grandTotal / 1000) * 1000})">
                                ${window.ShopWise.formatPeso(Math.ceil(totals.grandTotal / 1000) * 1000)}
                            </button>
                        </div>

                        <!-- Change Display -->
                        <div id="changeDisplay" style="display:none"></div>
                    </div>

                    <!-- Digital Payment Section -->
                    <div id="digitalPaymentSection" style="display: ${state.paymentMethod !== 'cash' ? 'block' : 'none'}">
                        <div class="sw-form-group">
                            <label for="referenceNumber">Reference Number</label>
                            <input type="text" 
                                   id="referenceNumber" 
                                   class="sw-input" 
                                   placeholder="Enter transaction reference">
                        </div>
                    </div>
                </div>
                <div class="sw-modal-footer">
                    <button class="sw-btn sw-btn-outline" onclick="this.closest('.sw-modal-backdrop').remove()">Cancel</button>
                    <button class="sw-btn sw-btn-primary" onclick="window.POSTerminal.confirmPayment()">Process Payment</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        // Focus on amount tendered or reference number
        setTimeout(() => {
            if (state.paymentMethod === 'cash') {
                document.getElementById('amountTendered')?.focus();
            } else {
                document.getElementById('referenceNumber')?.focus();
            }
        }, 100);
    }

    function setCustomerType(type) {
        state.customerType = type;
        // Close and reopen modal to refresh
        document.getElementById('checkoutModal')?.remove();
        openCheckoutModal();
    }

    function setPaymentMethod(method) {
        state.paymentMethod = method;
        
        // Toggle payment sections
        const cashSection = document.getElementById('cashPaymentSection');
        const digitalSection = document.getElementById('digitalPaymentSection');
        
        if (cashSection && digitalSection) {
            cashSection.style.display = method === 'cash' ? 'block' : 'none';
            digitalSection.style.display = method !== 'cash' ? 'block' : 'none';
        }
        
        // Update tab active states
        document.querySelectorAll('.pos-payment-tab').forEach(tab => {
            tab.classList.remove('active');
            if ((tab.textContent || '').toLowerCase().includes(method)) {
                tab.classList.add('active');
            }
        });
    }

    function setTendered(amount) {
        const input = document.getElementById('amountTendered');
        if (input) {
            input.value = amount.toFixed(2);
            calculateChange();
        }
    }

    function calculateChange() {
        const amountTendered = parseFloat(document.getElementById('amountTendered')?.value || 0);
        const totals = calculateTotals();
        const change = amountTendered - totals.grandTotal;
        
        const changeDisplay = document.getElementById('changeDisplay');
        if (!changeDisplay) return;
        
        if (amountTendered > 0) {
            changeDisplay.style.display = 'block';
            changeDisplay.className = change >= 0 ? 'pos-change-display' : 'pos-change-display insufficient';
            changeDisplay.innerHTML = `
                <div class="pos-change-label">${change >= 0 ? 'Change' : 'Insufficient'}</div>
                <div class="pos-change-value">${window.ShopWise.formatPeso(Math.abs(change))}</div>
            `;
        } else {
            changeDisplay.style.display = 'none';
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PAYMENT PROCESSING
    // ═══════════════════════════════════════════════════════════════════════

    async function confirmPayment() {
        const totals = calculateTotals();
        const payload = {
            items: state.cart.map(item => ({
                product_id: item.product_id,
                qty: item.qty,
                price: item.price
            })),
            customer_type: state.customerType,
            payment_method: state.paymentMethod,
            subtotal: totals.subtotal,
            discount_amount: totals.discount,
            grand_total: totals.grandTotal,
            promo_item_ids: state.cart.filter(item => item.promo_applied).map(item => item.product_id),
            _token: window.ShopWise.csrfToken || ''
        };

        // Validate payment
        if (state.paymentMethod === 'cash') {
            const amountTendered = parseFloat(document.getElementById('amountTendered')?.value || 0);
            if (amountTendered < totals.grandTotal) {
                window.ShopWise.toast('Insufficient amount tendered', 'error');
                return;
            }
            payload.amount_tendered = amountTendered;
            payload.change = amountTendered - totals.grandTotal;
        } else {
            const referenceNumber = document.getElementById('referenceNumber')?.value.trim();
            if (!referenceNumber) {
                window.ShopWise.toast('Please enter reference number', 'error');
                return;
            }
            payload.reference_number = referenceNumber;
        }

        try {
            const response = await window.ShopWise.fetchJson(api('/pos/checkout'), {
                method: 'POST',
                body: payload
            });

            if (response.success) {
                const receiptData = response.data ?? {
                    or_number: response.or_number || response.transaction_number || 'N/A',
                    transaction_date: new Date().toISOString(),
                    cashier_name: 'Cashier',
                    customer_type: state.customerType,
                    items: state.cart.map(item => ({
                        product_name: item.name,
                        qty: item.qty,
                        price: item.price
                    })),
                    subtotal: totals.subtotal,
                    discount: totals.discount,
                    vat_amount: totals.vat,
                    grand_total: response.total ?? totals.grandTotal,
                    payment_method: state.paymentMethod,
                    amount_tendered: payload.amount_tendered ?? (response.total ?? totals.grandTotal),
                    change_amount: response.change ?? payload.change ?? 0,
                    reference_number: payload.reference_number || null,
                    branch_name: 'Main Branch',
                    branch_address: ''
                };
                state.currentTransaction = receiptData;
                
                // Close checkout modal
                document.getElementById('checkoutModal')?.remove();
                
                // Show receipt modal
                showReceiptModal(receiptData);
                
                // Clear cart
                state.cart = [];
                state.customerType = 'regular';
                state.paymentMethod = 'cash';
                renderCart();
                
                window.ShopWise.toast('Transaction completed successfully', 'success');
            }
        } catch (error) {
            window.ShopWise.toast(error.message || 'Transaction failed', 'error');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RECEIPT MODAL
    // ═══════════════════════════════════════════════════════════════════════

    function showReceiptModal(transaction) {
        const modal = document.createElement('div');
        modal.id = 'receiptModal';
        modal.className = 'sw-modal-backdrop';
        
        modal.innerHTML = `
            <div class="sw-modal">
                <div class="sw-modal-header">
                    <h3 class="sw-modal-title">Receipt</h3>
                    <button class="sw-modal-close" onclick="this.closest('.sw-modal-backdrop').remove()">×</button>
                </div>
                <div class="sw-modal-body">
                    <div class="pos-receipt" id="receiptContent">
                        <div class="pos-receipt-header">
                            <div class="pos-receipt-store">SHOPWISE AI</div>
                            <div>${transaction.branch_name || 'Main Branch'}</div>
                            <div>${transaction.branch_address || ''}</div>
                            <div>VAT REG TIN: ${transaction.tin || 'XXX-XXX-XXX-XXX'}</div>
                        </div>

                        <div class="pos-receipt-info">
                            <div>OR No: ${transaction.or_number}</div>
                            <div>Date: ${new Date(transaction.transaction_date).toLocaleString('en-PH')}</div>
                            <div>Cashier: ${transaction.cashier_name}</div>
                            <div>Customer: ${transaction.customer_type.toUpperCase()}</div>
                        </div>

                        <div class="pos-receipt-items">
                            ${transaction.items.map(item => `
                                <div class="pos-receipt-item">
                                    <div>${item.product_name}</div>
                                    <div>${item.qty} × ${window.ShopWise.formatPeso(item.price)}</div>
                                    <div>${window.ShopWise.formatPeso(item.qty * item.price)}</div>
                                </div>
                            `).join('')}
                        </div>

                        <div class="pos-receipt-totals">
                            <div class="pos-receipt-row">
                                <span>Subtotal:</span>
                                <span>${window.ShopWise.formatPeso(transaction.subtotal)}</span>
                            </div>
                            ${transaction.discount > 0 ? `
                                <div class="pos-receipt-row">
                                    <span>Total Discount:</span>
                                    <span>-${window.ShopWise.formatPeso(transaction.discount)}</span>
                                </div>
                            ` : ''}
                            <div class="pos-receipt-row">
                                <span>VAT (12%):</span>
                                <span>${window.ShopWise.formatPeso(transaction.vat_amount)}</span>
                            </div>
                            <div class="pos-receipt-row pos-receipt-grand">
                                <span>TOTAL:</span>
                                <span>${window.ShopWise.formatPeso(transaction.grand_total)}</span>
                            </div>
                            ${transaction.payment_method === 'cash' ? `
                                <div class="pos-receipt-row">
                                    <span>Cash:</span>
                                    <span>${window.ShopWise.formatPeso(transaction.amount_tendered)}</span>
                                </div>
                                <div class="pos-receipt-row">
                                    <span>Change:</span>
                                    <span>${window.ShopWise.formatPeso(transaction.change_amount)}</span>
                                </div>
                            ` : `
                                <div class="pos-receipt-row">
                                    <span>${transaction.payment_method.toUpperCase()}:</span>
                                    <span>${window.ShopWise.formatPeso(transaction.grand_total)}</span>
                                </div>
                                <div class="pos-receipt-row">
                                    <span>Ref:</span>
                                    <span>${transaction.reference_number || 'N/A'}</span>
                                </div>
                            `}
                        </div>

                        <div class="pos-receipt-footer">
                            <div>Thank you for shopping!</div>
                            <div>This serves as your official receipt</div>
                            <div>Powered by ShopWise AI</div>
                        </div>
                    </div>
                </div>
                <div class="sw-modal-footer">
                    <button class="sw-btn sw-btn-outline" onclick="this.closest('.sw-modal-backdrop').remove()">Close</button>
                    <button class="sw-btn sw-btn-primary" onclick="window.POSTerminal.printReceipt()">Print Receipt</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    function printReceipt() {
        window.print();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HOLD / RECALL TRANSACTIONS
    // ═══════════════════════════════════════════════════════════════════════

    async function holdTransaction() {
        if (state.cart.length === 0) {
            window.ShopWise.toast('Cart is empty', 'warning');
            return;
        }

        try {
            const response = await window.ShopWise.fetchJson(api('/pos/hold'), {
                method: 'POST',
                body: {
                    items: state.cart,
                    customer_type: state.customerType,
                    _token: window.ShopWise.csrfToken || ''
                }
            });

            if (response.success) {
                state.cart = [];
                state.customerType = 'regular';
                renderCart();
                window.ShopWise.toast('Transaction held successfully', 'success');
            }
        } catch (error) {
            window.ShopWise.toast(error.message || 'Failed to hold transaction', 'error');
        }
    }

    async function recallTransactions() {
        try {
            const response = await window.ShopWise.fetchJson(api('/pos/held'));
            const heldItems = response.items || response.data || [];
            
            if (response.success && heldItems.length > 0) {
                state.heldTransactions = heldItems;
                showRecallModal(heldItems);
            } else {
                window.ShopWise.toast('No held transactions found', 'info');
            }
        } catch (error) {
            window.ShopWise.toast('Failed to load held transactions', 'error');
        }
    }

    function showRecallModal(transactions) {
        const modal = document.createElement('div');
        modal.className = 'sw-modal-backdrop';
        
        modal.innerHTML = `
            <div class="sw-modal">
                <div class="sw-modal-header">
                    <h3 class="sw-modal-title">Held Transactions</h3>
                    <button class="sw-modal-close" onclick="this.closest('.sw-modal-backdrop').remove()">×</button>
                </div>
                <div class="sw-modal-body">
                    ${transactions.map(txn => `
                        <div class="pos-held-item" onclick="window.POSTerminal.loadHeldTransaction(${txn.hold_id})">
                            <div>
                                <strong>${(txn.cart?.items?.length || txn.cart?.length || 0)} items</strong>
                                <div class="text-muted small">${txn.created_at || ''}</div>
                            </div>
                            <div>${window.ShopWise.formatPeso((txn.cart?.grand_total || txn.cart?.subtotal || 0))}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    async function loadHeldTransaction(heldId) {
        const txn = state.heldTransactions.find(item => Number(item.hold_id) === Number(heldId));
        if (!txn || !txn.cart) {
            window.ShopWise.toast('Failed to recall transaction', 'error');
            return;
        }

        const heldItems = txn.cart.items || txn.cart || [];
        state.cart = heldItems.map(item => ({
            product_id: Number(item.product_id || item.id || 0),
            name: item.name || item.product_name || 'Item',
            price: Number(item.price || item.selling_price || 0),
            emoji: item.emoji || '📦',
            stock_qty: Number(item.stock_qty || item.stock || 9999),
            qty: Number(item.qty || item.quantity || 1),
            promotion: item.promotion || null,
            promo_applied: item.promo_applied === undefined
                ? !!(item.promotion && Number(item.promotion.promo_id) > 0)
                : !!item.promo_applied
        })).filter(item => item.product_id > 0 && item.qty > 0);
        state.customerType = txn.cart.customer_type || 'regular';
        renderCart();

        document.querySelector('.sw-modal-backdrop')?.remove();
        window.ShopWise.toast('Transaction recalled', 'success');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRODUCT CARDS
    // ═══════════════════════════════════════════════════════════════════════

    function initProductCards() {
        const productCards = document.querySelectorAll('[data-product-id]');
        
        productCards.forEach(card => {
            card.addEventListener('click', () => {
                const product = {
                    product_id: parseInt(card.dataset.productId),
                    name: card.dataset.productName,
                    selling_price: parseFloat(card.dataset.productPrice),
                    emoji: card.dataset.productEmoji,
                    stock_qty: parseInt(card.dataset.productStock),
                    promotion: Number(card.dataset.promoId || 0) > 0
                        ? {
                            promo_id: Number(card.dataset.promoId || 0),
                            promo_name: card.dataset.promoName || '',
                            discount_pct: Number(card.dataset.promoPct || 0),
                            discount_amount: Number(card.dataset.promoAmount || 0)
                        }
                        : null
                };
                
                addToCart(product);
            });
        });
    }

    function animateProductCard(productId) {
        const card = document.querySelector(`[data-product-id="${productId}"]`);
        if (card) {
            card.classList.add('just-added');
            setTimeout(() => card.classList.remove('just-added'), 300);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SEARCH & FILTER
    // ═══════════════════════════════════════════════════════════════════════

    let currentCategoryFilter = 'all';
    let currentSearchQuery = '';

    function filterProducts() {
        const productCards = document.querySelectorAll('.product-item');
        const searchLower = currentSearchQuery.toLowerCase();
        
        productCards.forEach(card => {
            const productName = card.dataset.productName.toLowerCase();
            const productCategory = card.dataset.productCategory;
            
            // Check category filter
            const categoryMatch = currentCategoryFilter === 'all' || productCategory === currentCategoryFilter;
            
            // Check search query
            const searchMatch = !currentSearchQuery || productName.includes(searchLower);
            
            // Show/hide product
            if (categoryMatch && searchMatch) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function initCategoryFilters() {
        const categoryButtons = document.querySelectorAll('.category-filter-btn');
        
        categoryButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all buttons
                categoryButtons.forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                btn.classList.add('active');
                
                // Update current filter
                currentCategoryFilter = btn.dataset.category;
                
                // Apply filter
                filterProducts();
            });
        });
    }

    function initSearch() {
        if (!elements.searchInput) return;
        
        elements.searchInput.addEventListener('input', (e) => {
            currentSearchQuery = e.target.value.trim();
            filterProducts();
        });
        
        // Clear search on Escape key
        elements.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                elements.searchInput.value = '';
                currentSearchQuery = '';
                filterProducts();
                elements.searchInput.blur();
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // KEYBOARD SHORTCUTS
    // ═══════════════════════════════════════════════════════════════════════

    document.addEventListener('keydown', (e) => {
        // Ignore if typing in input
        if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;

        switch (e.key) {
            case 'F1':
                e.preventDefault();
                elements.searchInput?.focus();
                break;
            
            case 'F2':
                e.preventDefault();
                openCheckoutModal();
                break;
            
            case 'F3':
                e.preventDefault();
                holdTransaction();
                break;
            
            case 'F4':
                e.preventDefault();
                recallTransactions();
                break;
            
            case 'Escape':
                document.querySelector('.sw-modal-backdrop')?.remove();
                break;
        }
    });

    // ═══════════════════════════════════════════════════════════════════════
    // INITIALIZATION
    // ═══════════════════════════════════════════════════════════════════════

    // Expose public API
    window.POSTerminal = {
        addToCart,
        removeFromCart,
        updateQty,
        togglePromo,
        clearCart,
        openCheckoutModal,
        setCustomerType,
        setPaymentMethod,
        setTendered,
        calculateChange,
        confirmPayment,
        printReceipt,
        holdTransaction,
        recallTransactions,
        loadHeldTransaction
    };

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        initProductCards();
        initCategoryFilters();
        initSearch();
        renderCart();
        
        // Event listeners
        if (elements.checkoutBtn) {
            elements.checkoutBtn.addEventListener('click', openCheckoutModal);
        }
        
        if (elements.clearCartBtn) {
            elements.clearCartBtn.addEventListener('click', clearCart);
        }
        
        if (elements.holdBtn) {
            elements.holdBtn.addEventListener('click', holdTransaction);
        }
        
        if (elements.recallBtn) {
            elements.recallBtn.addEventListener('click', recallTransactions);
        }
        
        console.log('POS Terminal v5.0 initialized');
        console.log('Keyboard shortcuts: F1=Search, F2=Checkout, F3=Hold, F4=Recall, Esc=Close');
    });
})();
