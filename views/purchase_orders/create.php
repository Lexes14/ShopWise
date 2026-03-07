<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Create Purchase Order</h5>
        <p class="text-muted small">Start a new purchase order for a supplier</p>
    </div>
</div>

<form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders">
    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
    
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <!-- Supplier Selection -->
            <div class="card card-soft">
                <div class="card-header">
                    <h6 class="mb-0">Select Supplier</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-600">Supplier *</label>
                        <?php $supplierOptions = $suppliers ?? ($extra['suppliers'] ?? []); ?>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select supplier</option>
                            <?php foreach ($supplierOptions as $supplier): ?>
                                <option value="<?= e((string)($supplier['supplier_id'] ?? '')) ?>">
                                    <?= e((string)($supplier['supplier_name'] ?? '')) ?>
                                    <?php if (!empty($supplier['supplier_code'])): ?>
                                        (<?= e((string)$supplier['supplier_code']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- PO Details -->
            <div class="card card-soft mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Purchase Order Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-600">PO Date</label>
                            <input type="date" name="po_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Expected Delivery Date</label>
                            <input type="date" name="expected_delivery_date" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-600">Delivery Location</label>
                        <input type="text" name="delivery_location" class="form-control" value="Main Store" placeholder="e.g., Main Store, Warehouse A">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-600">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Special instructions or notes for this PO..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Info Card -->
        <div class="col-lg-4">
            <div class="card card-soft">
                <div class="card-header">
                    <h6 class="mb-0">Quick Info</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <strong>📌 Next Step:</strong>
                        <p class="mb-0 mt-2">After creating the PO, you'll be able to add line items (products, quantities, prices) and submit it for approval.</p>
                    </div>
                </div>
            </div>
            
            <div class="card card-soft mt-3">
                <div class="card-body">
                    <h6 class="mb-3">PO Workflow</h6>
                    <div class="step mb-3">
                        <div class="step-number active">1</div>
                        <div class="step-label"><strong>Create PO</strong><br><small class="text-muted">You are here</small></div>
                    </div>
                    <div class="step mb-3">
                        <div class="step-number">2</div>
                        <div class="step-label"><strong>Add Items</strong><br><small class="text-muted">Add products & quantities</small></div>
                    </div>
                    <div class="step mb-3">
                        <div class="step-number">3</div>
                        <div class="step-label"><strong>Submit</strong><br><small class="text-muted">Send for approval</small></div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-label"><strong>Approve</strong><br><small class="text-muted">Manager approves</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-lg btn-primary">Create & Add Items</button>
        <a href="<?= e(BASE_URL) ?>/purchase-orders" class="btn btn-lg btn-outline-secondary">Cancel</a>
    </div>
</form>

<style>
    .step {
        display: flex;
        gap: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #ddd;
    }
    
    .step:last-child {
        border-bottom: none;
    }
    
    .step-number {
        min-width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e9ecef;
        color: #666;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }
    
    .step-number.active {
        background: #1A3C5E;
        color: white;
    }
    
    .step-label {
        padding-top: 4px;
        font-size: 13px;
    }
</style>

