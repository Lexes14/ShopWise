<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : 'success') ?> py-2 mb-3">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<link rel="stylesheet" href="<?= e(ASSET_URL) ?>/css/pos.css">
<style>
    .page-content {
        padding: 0 !important;
        overflow: auto !important;
    }

    .pos-terminal.pos-body {
        height: auto !important;
        min-height: calc(100vh - 110px);
        overflow: visible !important;
    }

    .pos-grid {
        min-height: calc(100vh - 130px);
    }

    .cart-layout {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .cart-items {
        overflow-y: auto !important;
        min-height: 220px;
        max-height: calc(100vh - 320px);
    }

    .cart-summary {
        position: sticky;
        bottom: 0;
        z-index: 2;
    }
</style>

<div class="pos-terminal pos-body">
    <div class="pos-grid">
        <section class="pos-panel">
            <div class="pos-panel-header">
                <h6 class="pos-panel-title mb-0">Products</h6>
                <span class="chip open-shift">OPEN SHIFT</span>
            </div>
            <div class="pos-panel-body">
                <!-- Search Input -->
                <div class="mb-2 pos-product-search">
                    <input type="text" id="posSearch" class="form-control" placeholder="Search product or barcode...">
                </div>
                
                <!-- Category Filters -->
                <div class="mb-3 pos-category-filters">
                    <button class="btn btn-sm category-filter-btn active" data-category="all">
                        All
                    </button>
                    <?php foreach (($categories ?? []) as $category): ?>
                        <button 
                            class="btn btn-sm category-filter-btn" 
                            data-category="<?= e((string)$category['category_id']) ?>"
                            data-category-name="<?= e((string)$category['category_name']) ?>"
                        >
                            <?php if (!empty($category['icon'])): ?>
                                <?= e($category['icon']) ?>
                            <?php endif; ?>
                            <?= e((string)$category['category_name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <!-- Product Grid -->
                <div class="product-grid">
                    <?php foreach (($products ?? []) as $product): ?>
                        <button
                            type="button"
                            class="product-item text-start"
                            data-product-id="<?= e((string)$product['product_id']) ?>"
                            data-product-name="<?= e((string)$product['product_name']) ?>"
                            data-product-price="<?= e((string)$product['selling_price']) ?>"
                            data-product-stock="<?= e((string)$product['current_stock']) ?>"
                            data-product-category="<?= e((string)($product['category_id'] ?? '0')) ?>"
                            data-promo-id="<?= e((string)($product['active_promo_id'] ?? 0)) ?>"
                            data-promo-name="<?= e((string)($product['active_promo_name'] ?? '')) ?>"
                            data-promo-pct="<?= e((string)($product['active_promo_discount_pct'] ?? 0)) ?>"
                            data-promo-amount="<?= e((string)($product['active_promo_discount_amount'] ?? 0)) ?>"
                            data-product-emoji="📦"
                        >
                            <?php
                            $promoPct = (float)($product['active_promo_discount_pct'] ?? 0);
                            $promoAmt = (float)($product['active_promo_discount_amount'] ?? 0);
                            $hasPromo = ((int)($product['active_promo_id'] ?? 0) > 0) && ($promoPct > 0 || $promoAmt > 0);
                            $promoLabel = $promoPct > 0
                                ? rtrim(rtrim(number_format($promoPct, 2), '0'), '.') . '% OFF'
                                : ('₱' . number_format($promoAmt, 2) . ' OFF');
                            ?>
                            <?php if ($hasPromo): ?>
                                <div class="product-promo-badge" title="<?= e((string)($product['active_promo_name'] ?? 'Active Promotion')) ?>">
                                    PROMO <?= e($promoLabel) ?>
                                </div>
                            <?php endif; ?>
                            <div class="product-name"><?= e((string)$product['product_name']) ?></div>
                            <div class="product-price">₱<?= e(number_format((float)$product['selling_price'], 2)) ?></div>
                            <div class="product-stock">Stock: <?= e((string)$product['current_stock']) ?></div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="pos-panel cart-layout">
            <div class="pos-panel-header">
                <h6 class="pos-panel-title mb-0">Cart</h6>
                <span class="chip offline">OFFLINE SAFE</span>
            </div>
            <div class="cart-items" id="cartItems"></div>
            <div class="cart-summary">
                <div class="summary-row" style="display:none;"><span>Items</span><strong id="cartCount">0</strong></div>
                <div class="summary-row" style="display:none;"><span>Subtotal</span><strong id="subtotal">₱0.00</strong></div>
                <div class="summary-row" style="display:none;"><span>Discount</span><strong id="discount">₱0.00</strong></div>
                <div class="summary-row" style="display:none;"><span>VAT</span><strong id="vat">₱0.00</strong></div>
                <div class="summary-row total">
                    <span>Total</span>
                    <strong id="grandTotal">₱0.00</strong>
                </div>
                <div class="checkout-actions">
                    <button type="button" id="checkoutBtn" class="btn btn-checkout">Checkout</button>
                    <button type="button" id="holdBtn" class="btn btn-hold">Hold</button>
                    <button type="button" class="btn btn-clear-cart" id="clearCartBtn">Clear</button>
                    <button type="button" id="recallBtn" class="btn btn-outline-secondary">Recall</button>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="<?= e(ASSET_URL) ?>/js/pos.js"></script>
