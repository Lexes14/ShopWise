/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║              SHOPWISE AI — DENOMINATION CALCULATOR v5.0             ║
 * ║         Cash counting for shift close                                ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Usage on shift close form:
 * Calculate cash denominations and show variance vs expected cash
 */

(() => {
    'use strict';

    // ═══════════════════════════════════════════════════════════════════════
    // PHILIPPINE DENOMINATIONS
    // ═══════════════════════════════════════════════════════════════════════

    const DENOMINATIONS = [
        { value: 1000, label: '₱1,000' },
        { value: 500, label: '₱500' },
        { value: 200, label: '₱200' },
        { value: 100, label: '₱100' },
        { value: 50, label: '₱50' },
        { value: 20, label: '₱20' },
        { value: 10, label: '₱10' },
        { value: 5, label: '₱5' },
        { value: 1, label: '₱1' }
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // DOM ELEMENTS
    // ═══════════════════════════════════════════════════════════════════════

    const denominationTable = document.getElementById('denominationTable');
    const actualCashTotal = document.getElementById('actualCashTotal');
    const expectedCashInput = document.getElementById('expectedCash');
    const varianceDisplay = document.getElementById('varianceDisplay');

    if (!denominationTable) return;

    // ═══════════════════════════════════════════════════════════════════════
    // INITIALIZE DENOMINATION TABLE
    // ═══════════════════════════════════════════════════════════════════════

    function initDenominationTable() {
        denominationTable.innerHTML = `
            <table class="sw-table">
                <thead>
                    <tr>
                        <th>Denomination</th>
                        <th style="width: 150px;">Quantity</th>
                        <th style="width: 150px; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${DENOMINATIONS.map(denom => `
                        <tr>
                            <td><strong>${denom.label}</strong></td>
                            <td>
                                <input type="number" 
                                       class="sw-input denom-qty-input" 
                                       data-value="${denom.value}"
                                       value="0" 
                                       min="0" 
                                       step="1"
                                       placeholder="0">
                            </td>
                            <td class="denom-amount" style="text-align: right; font-weight: 700;">₱0.00</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr style="font-size: 16px; font-weight: 700; background: var(--sw-primary-light);">
                        <td colspan="2">Total Cash Counted</td>
                        <td id="totalCashCounted" style="text-align: right; color: var(--sw-primary);">₱0.00</td>
                    </tr>
                </tfoot>
            </table>
        `;

        // Attach event listeners to inputs
        const inputs = denominationTable.querySelectorAll('.denom-qty-input');
        inputs.forEach(input => {
            input.addEventListener('input', calculateDenominations);
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CALCULATE DENOMINATIONS
    // ═══════════════════════════════════════════════════════════════════════

    function calculateDenominations() {
        const rows = denominationTable.querySelectorAll('tbody tr');
        let totalCash = 0;

        rows.forEach(row => {
            const input = row.querySelector('.denom-qty-input');
            const amountCell = row.querySelector('.denom-amount');
            
            const qty = parseInt(input.value) || 0;
            const value = parseFloat(input.dataset.value);
            const amount = qty * value;

            amountCell.textContent = window.ShopWise.formatPeso(amount);
            totalCash += amount;
        });

        // Update total
        const totalElement = document.getElementById('totalCashCounted');
        if (totalElement) {
            totalElement.textContent = window.ShopWise.formatPeso(totalCash);
        }

        // Update actual cash hidden input (for form submission)
        if (actualCashTotal) {
            actualCashTotal.value = totalCash.toFixed(2);
        }

        // Calculate variance
        calculateVariance(totalCash);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CALCULATE VARIANCE
    // ═══════════════════════════════════════════════════════════════════════

    function calculateVariance(actualCash) {
        if (!expectedCashInput || !varianceDisplay) return;

        const expectedCash = parseFloat(expectedCashInput.value) || 0;
        const variance = actualCash - expectedCash;

        let variantClass = 'balanced';
        let variantIcon = '✓';
        let variantColor = 'var(--sw-success)';

        if (Math.abs(variance) > 100) {
            // Variance > ₱100 is serious
            variantClass = 'critical';
            variantIcon = '✕';
            variantColor = 'var(--sw-danger)';
        } else if (Math.abs(variance) > 0) {
            // Small variance (₱1-100)
            variantClass = 'minor';
            variantIcon = '⚠';
            variantColor = 'var(--sw-warning)';
        }

        varianceDisplay.innerHTML = `
            <div class="variance-display variance-${variantClass}" style="
                background: ${variantColor}20;
                border: 2px solid ${variantColor};
                border-radius: var(--sw-radius-md);
                padding: 20px;
                text-align: center;
                margin-top: 20px;
            ">
                <div style="font-size: 32px; margin-bottom: 8px;">${variantIcon}</div>
                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: ${variantColor}; margin-bottom: 4px;">
                    ${variance === 0 ? 'Balanced' : variance > 0 ? 'Overage' : 'Shortage'}
                </div>
                <div style="font-family: var(--sw-font-display); font-size: 32px; font-weight: 700; color: ${variantColor};">
                    ${variance >= 0 ? '+' : ''}${window.ShopWise.formatPeso(Math.abs(variance))}
                </div>
                <div style="font-size: 12px; color: var(--sw-text-muted); margin-top: 8px;">
                    Expected: ${window.ShopWise.formatPeso(expectedCash)} | 
                    Actual: ${window.ShopWise.formatPeso(actualCash)}
                </div>
            </div>
        `;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // QUICK FILL BUTTON (Optional enhancement)
    // ═══════════════════════════════════════════════════════════════════════

    window.quickFillDenominations = (amount) => {
        // Auto-fill denominations to reach a target amount
        const inputs = denominationTable.querySelectorAll('.denom-qty-input');
        let remaining = amount;

        inputs.forEach(input => {
            const value = parseFloat(input.dataset.value);
            const qty = Math.floor(remaining / value);
            input.value = qty;
            remaining = remaining % value;
        });

        calculateDenominations();
    };

    // ═══════════════════════════════════════════════════════════════════════
    // INITIALIZATION
    // ═══════════════════════════════════════════════════════════════════════

    initDenominationTable();

    // Listen for expected cash changes
    if (expectedCashInput) {
        expectedCashInput.addEventListener('input', () => {
            calculateDenominations();
        });
    }

    console.log('Denomination calculator initialized');
})();
