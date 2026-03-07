<?php
// Variables are now passed at top-level from controller
// Ensure $product exists for backward compatibility
if (!isset($product)) {
    $product = [];
}

$productId = (int)($id ?? ($product['product_id'] ?? ($extra['id'] ?? 0)));
?>

<div class="page-header mb-4">
    <div>
        <h5 class="mb-1">Edit Product #<?= e((string)$productId) ?></h5>
        <p class="text-muted small">Update product details and pricing</p>
    </div>
</div>

<form method="POST" action="<?= e(BASE_URL) ?>/products/<?= e((string)$productId) ?>/update" id="editForm" class="row g-4">
    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
    
    <!-- Left Column: Basic Info -->
    <div class="col-lg-6">
        <div class="card card-soft">
            <div class="card-header">Basic Information</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-600">Product Code</label>
                    <input type="text" class="form-control sw-input" value="<?= e($product['product_code'] ?? '') ?>" disabled>
                    <small class="text-muted">Read-only • Cannot be changed</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-600">Product Name *</label>
                    <input type="text" name="product_name" class="form-control sw-input" value="<?= e($product['product_name'] ?? '') ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Alias (Alternative Name)</label>
                    <input type="text" name="product_alias" class="form-control sw-input" value="<?= e($product['product_alias'] ?? '') ?>">
                </div>
                
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-600">Category *</label>
                        <select name="category_id" class="form-select sw-input" required>
                            <?php foreach (($categories ?? []) as $cat): ?>
                                <option value="<?= e((string)$cat['category_id']) ?>" <?= ((int)($product['category_id'] ?? 0) === (int)$cat['category_id']) ? 'selected' : '' ?>>
                                    <?= e($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" class="form-select sw-input">
                            <option value="">Select or leave blank</option>
                            <?php foreach (($brands ?? []) as $brand): ?>
                                <option value="<?= e((string)$brand['brand_id']) ?>" <?= ((int)($product['brand_id'] ?? 0) === (int)$brand['brand_id']) ? 'selected' : '' ?>>
                                    <?= e($brand['brand_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3 mt-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control sw-input" rows="3"><?= e($product['description'] ?? '') ?></textarea>
                </div>
                
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label">Unit of Measure</label>
                        <select name="unit_of_measure" class="form-select sw-input">
                            <option value="pc" <?= (($product['unit_of_measure'] ?? '') === 'pc') ? 'selected' : '' ?>>Piece(s)</option>
                            <option value="box" <?= (($product['unit_of_measure'] ?? '') === 'box') ? 'selected' : '' ?>>Box(es)</option>
                            <option value="case" <?= (($product['unit_of_measure'] ?? '') === 'case') ? 'selected' : '' ?>>Case(s)</option>
                            <option value="kg" <?= (($product['unit_of_measure'] ?? '') === 'kg') ? 'selected' : '' ?>>Kilogram(s)</option>
                            <option value="litr" <?= (($product['unit_of_measure'] ?? '') === 'litr') ? 'selected' : '' ?>>Liter(s)</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Storage Condition</label>
                        <select name="storage_condition" class="form-select sw-input">
                            <option value="dry" <?= (($product['storage_condition'] ?? '') === 'dry') ? 'selected' : '' ?>>Dry Storage</option>
                            <option value="cold" <?= (($product['storage_condition'] ?? '') === 'cold') ? 'selected' : '' ?>>Refrigerated</option>
                            <option value="frozen" <?= (($product['storage_condition'] ?? '') === 'frozen') ? 'selected' : '' ?>>Frozen</option>
                            <option value="ambient" <?= (($product['storage_condition'] ?? '') === 'ambient') ? 'selected' : '' ?>>Room Temperature</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Pricing & Inventory -->
    <div class="col-lg-6">
        <!-- Pricing Card -->
        <div class="card card-soft mb-4">
            <div class="card-header">Pricing</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-600">Cost Price ₱ *</label>
                    <input type="number" step="0.01" name="cost_price" id="costPrice" class="form-control sw-input" value="<?= e((string)($product['cost_price'] ?? '0.00')) ?>" required>
                    <small class="text-muted">The amount you pay to acquire this product</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-600">Selling Price ₱ *</label>
                    <input type="number" step="0.01" name="selling_price" id="sellingPrice" class="form-control sw-input" value="<?= e((string)($product['selling_price'] ?? '0.00')) ?>" required>
                    <small class="text-muted">The amount customers will pay</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Minimum Selling Price ₱</label>
                    <input type="number" step="0.01" name="min_selling_price" id="minSellingPrice" class="form-control sw-input" value="<?= e((string)($product['min_selling_price'] ?? '0.00')) ?>">
                    <small class="text-muted">Do not sell below this price</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Wholesale Price ₱</label>
                    <input type="number" step="0.01" name="wholesale_price" class="form-control sw-input" value="<?= e((string)($product['wholesale_price'] ?? '0.00')) ?>">
                </div>
                
                <!-- Markup Calculator -->
                <div class="alert alert-light border d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">Markup</div>
                        <div class="h6 mb-0" id="markupPercent">0%</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Profit per Unit</div>
                        <div class="h6 mb-0 text-success" id="profitAmount">₱0.00</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small">Price Change Reason</label>
                    <input type="text" name="price_change_reason" class="form-control sw-input" placeholder="Why are you changing the price?">
                </div>
                
                <div class="form-check">
                    <input type="checkbox" name="is_vatable" class="form-check-input" id="isVatable" value="1" <?= ((int)($product['is_vatable'] ?? 0)) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isVatable">
                        This product is subject to VAT (12%)
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Inventory Card -->
        <div class="card card-soft">
            <div class="card-header">Inventory Settings</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-600">Current Stock</label>
                    <input type="number" name="current_stock" class="form-control sw-input" value="<?= e((string)($product['current_stock'] ?? '0')) ?>">
                    <small class="text-muted">Current stock level (read-only in POS)</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-600">Reorder Point *</label>
                    <input type="number" name="reorder_point" class="form-control sw-input" value="<?= e((string)($product['reorder_point'] ?? '20')) ?>" required>
                    <small class="text-muted">Alert when stock drops to this level</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-600">Reorder Quantity *</label>
                    <input type="number" name="reorder_qty" class="form-control sw-input" value="<?= e((string)($product['reorder_qty'] ?? '50')) ?>" required>
                    <small class="text-muted">Typical order quantity</small>
                </div>
                
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label">Minimum Stock</label>
                        <input type="number" name="minimum_stock" class="form-control sw-input" value="<?= e((string)($product['minimum_stock'] ?? '10')) ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Maximum Stock</label>
                        <input type="number" name="maximum_stock" class="form-control sw-input" value="<?= e((string)($product['maximum_stock'] ?? '500')) ?>">
                    </div>
                </div>
                
                <div class="mt-3">
                    <label class="form-label fw-600">Status</label>
                    <select name="status" class="form-select sw-input">
                        <option value="active" <?= (($product['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($product['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        <option value="discontinued" <?= (($product['status'] ?? '') === 'discontinued') ? 'selected' : '' ?>>Discontinued</option>
                        <option value="seasonal" <?= (($product['status'] ?? '') === 'seasonal') ? 'selected' : '' ?>>Seasonal</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Supplier Info Card -->
        <div class="card card-soft mt-4">
            <div class="card-header">Suppliers</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Primary Supplier</label>
                    <select name="primary_supplier_id" class="form-select sw-input">
                        <option value="">Select or leave blank</option>
                        <?php foreach (($suppliers ?? []) as $supplier): ?>
                            <option value="<?= e((string)$supplier['supplier_id']) ?>" <?= ((int)($product['primary_supplier_id'] ?? 0) === (int)$supplier['supplier_id']) ? 'selected' : '' ?>>
                                <?= e($supplier['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-0">
                    <label class="form-label">Secondary Supplier</label>
                    <select name="secondary_supplier_id" class="form-select sw-input">
                        <option value="">Select or leave blank</option>
                        <?php foreach (($suppliers ?? []) as $supplier): ?>
                            <option value="<?= e((string)$supplier['supplier_id']) ?>" <?= ((int)($product['secondary_supplier_id'] ?? 0) === (int)$supplier['supplier_id']) ? 'selected' : '' ?>>
                                <?= e($supplier['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Form Actions -->
<div class="row mt-4 mb-4">
    <div class="col-lg-6"></div>
    <div class="col-lg-6">
        <div class="d-flex gap-2">
            <button type="submit" form="editForm" class="btn btn-primary flex-grow-1">
                💾 Update Product
            </button>
            <a href="<?= e(BASE_URL) ?>/products" class="btn btn-outline-secondary flex-grow-1">Back</a>
        </div>
    </div>
</div>

<style>
    .sw-input {
        border: 1.5px solid #E8E8E8;
        border-radius: 0.5rem;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    
    .sw-input:focus {
        border-color: #1A3C5E;
        box-shadow: 0 0 0 3px rgba(26, 60, 94, 0.1);
    }
    
    .sw-input:disabled {
        background-color: #F8F9FA;
        color: #6C757D;
    }
    
    .form-label.fw-600 {
        color: #1A3C5E;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .card-header {
        background-color: #F8F9FA;
        border-bottom: 2px solid #E8E8E8;
        padding: 1rem;
        font-weight: 600;
        color: #1A3C5E;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 2rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const costPriceInput = document.getElementById('costPrice');
    const sellingPriceInput = document.getElementById('sellingPrice');
    const markupPercentSpan = document.getElementById('markupPercent');
    const profitAmountSpan = document.getElementById('profitAmount');
    
    function updateMarkup() {
        const cost = parseFloat(costPriceInput.value) || 0;
        const selling = parseFloat(sellingPriceInput.value) || 0;
        
        if (cost > 0) {
            const profit = selling - cost;
            const markup = ((profit / cost) * 100).toFixed(1);
            markupPercentSpan.textContent = markup + '%';
            profitAmountSpan.textContent = '₱' + profit.toFixed(2);
        } else {
            markupPercentSpan.textContent = '0%';
            profitAmountSpan.textContent = '₱0.00';
        }
    }
    
    costPriceInput.addEventListener('input', updateMarkup);
    sellingPriceInput.addEventListener('input', updateMarkup);
    
    // Initial calculation
    updateMarkup();
});
</script>
