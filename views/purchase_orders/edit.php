<?php
$po = $po ?? ($extra['po'] ?? []);
$items = $items ?? ($extra['items'] ?? []);
$products = $products ?? ($extra['products'] ?? []);
?>

<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Edit Purchase Order</h5>
        <p class="text-muted small">PO #<?= e($po['po_number'] ?? '') ?> - <?= e($po['supplier_name'] ?? '') ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <!-- PO Header Info -->
        <div class="card card-soft mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <div>
                            <small class="text-muted">Supplier</small>
                            <div class="fw-600"><?= e($po['supplier_name'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div>
                            <small class="text-muted">Status</small>
                            <div>
                                <?php
                                $status = $po['status'] ?? 'draft';
                                $badgeClass = match($status) {
                                    'draft' => 'badge-secondary',
                                    'submitted' => 'badge-warning',
                                    'approved' => 'badge-info',
                                    'ordered' => 'badge-primary',
                                    'received' => 'badge-success',
                                    'rejected' => 'badge-danger',
                                    default => 'badge-light'
                                };
                                ?>
                                <span class="badge <?= e($badgeClass) ?>"><?= ucfirst(e($status)) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 mt-3">
                        <div>
                            <small class="text-muted">PO Date</small>
                            <div class="fw-600"><?= date('F d, Y', strtotime($po['po_date'] ?? 'now')) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 mt-3">
                        <div>
                            <small class="text-muted">Expected Delivery</small>
                            <div class="fw-600"><?= date('F d, Y', strtotime($po['expected_delivery_date'] ?? 'now')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Line Items Table -->
        <div class="card card-soft mb-3">
            <div class="card-header">
                <h6 class="mb-0">Line Items</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40%">Product</th>
                            <th style="width: 15%">Qty</th>
                            <th style="width: 15%">Unit Price</th>
                            <th style="width: 20%">Total</th>
                            <th style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        foreach (($items ?? []) as $item): 
                            $lineTotal = $item['quantity'] * $item['unit_price'];
                            $total += $lineTotal;
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($item['product_name'] ?? '') ?></strong>
                                <br><small class="text-muted"><?= e($item['product_code'] ?? '') ?></small>
                            </td>
                            <td><?= intval($item['quantity']) ?></td>
                            <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                            <td class="fw-600">₱<?= number_format($lineTotal, 2) ?></td>
                            <td>
                                <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/items/<?= intval($item['po_item_id']) ?>/remove" style="display:inline;">
                                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this item?');">Remove</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-top-2">
                            <td colspan="3" class="text-end fw-600">Total:</td>
                            <td class="fw-600" style="font-size: 16px;">₱<?= number_format($total, 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <?php if (empty($items)): ?>
            <div class="card-body text-center text-muted py-4">
                <p>No items added yet. Add your first line item below.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Add Item Form -->
        <div class="card card-soft">
            <div class="card-header">
                <h6 class="mb-0">Add Line Item</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/add-item" class="row g-3">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    <input type="hidden" name="po_id" value="<?= intval($po['po_id']) ?>">
                    
                    <div class="col-md-6">
                        <label class="form-label fw-600">Product *</label>
                        <input type="text" class="form-control" id="productSearch" placeholder="Search product..." required>
                        <input type="hidden" name="product_id" id="product_id" required>
                        <div id="productList" class="list-group mt-2" style="display:none; max-height: 200px; overflow-y: auto;"></div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-600">Quantity *</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-600">Unit Price (₱) *</label>
                        <input type="number" name="unit_price" class="form-control" min="0" step="0.01" placeholder="0.00" required>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm btn-outline-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Summary Card -->
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-3">Order Summary</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <strong>₱<?= number_format($total, 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Tax (0%):</span>
                    <strong>₱0.00</strong>
                </div>
                <div class="border-top pt-2 d-flex justify-content-between">
                    <span class="fw-600">Total:</span>
                    <strong style="font-size: 18px;">₱<?= number_format($total, 2) ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card card-soft mt-3">
            <div class="card-header">
                <h6 class="mb-0">Actions</h6>
            </div>
            <div class="card-body">
                <?php if ($po['status'] === 'draft'): ?>
                <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/<?= intval($po['po_id']) ?>/submit" class="mb-2">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    <button type="submit" class="btn btn-primary w-100" <?= empty($items) ? 'disabled' : '' ?>>
                        Submit for Approval
                    </button>
                </form>
                <?php endif; ?>
                
                <a href="<?= e(BASE_URL) ?>/purchase-orders/<?= intval($po['po_id']) ?>" class="btn btn-outline-secondary w-100">View Details</a>
            </div>
        </div>
        
        <!-- Info -->
        <div class="card card-soft mt-3">
            <div class="card-body">
                <h6 class="mb-3">📋 Info</h6>
                <p class="text-muted small mb-0">
                    Add all line items for this purchase order before submitting for approval. You can edit items until the order is submitted.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('productSearch').addEventListener('input', function(e) {
    let query = e.target.value.trim();
    let list = document.getElementById('productList');
    
    if (query.length < 2) {
        list.style.display = 'none';
        return;
    }
    
    fetch('<?= e(BASE_URL) ?>/products/search?q=' + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            list.innerHTML = '';
            if (data.items && data.items.length > 0) {
                data.items.forEach(product => {
                    let div = document.createElement('div');
                    div.className = 'list-group-item list-group-item-action cursor-pointer';
                    div.innerHTML = `<strong>${product.product_name}</strong> (${product.product_code})<br><small class="text-muted">₱${parseFloat(product.cost_price).toFixed(2)}</small>`;
                    div.onclick = () => {
                        document.getElementById('product_id').value = product.product_id;
                        document.getElementById('productSearch').value = product.product_name;
                        document.querySelector('input[name="unit_price"]').value = product.cost_price;
                        list.style.display = 'none';
                    };
                    list.appendChild(div);
                });
                list.style.display = 'block';
            }
        });
});
</script>
