<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Edit Supplier</h5>
        <p class="text-muted small">Update supplier details and settings</p>
    </div>
</div>

<?php if (!isset($supplier) || !$supplier): ?>
    <div class="alert alert-danger">Supplier not found</div>
<?php else: ?>
    <form method="POST" action="<?= e(BASE_URL) ?>/suppliers/<?= e((string)$supplier['supplier_id']) ?>/update">
        <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
        
        <!-- Basic Information -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card card-soft">
                    <div class="card-header">
                        <h6 class="mb-0">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-600">Supplier Name *</label>
                            <input type="text" name="supplier_name" class="form-control" value="<?= e($supplier['supplier_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-600">Supplier Code</label>
                                <input type="text" class="form-control" value="<?= e($supplier['supplier_code'] ?? '') ?>" readonly>
                                <small class="text-muted">Cannot be changed</small>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-600">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php if (($supplier['status'] ?? '') === 'active') echo 'selected'; ?>>Active</option>
                                    <option value="inactive" <?php if (($supplier['status'] ?? '') === 'inactive') echo 'selected'; ?>>Inactive</option>
                                    <option value="archived" <?php if (($supplier['status'] ?? '') === 'archived') echo 'selected'; ?>>Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="card card-soft mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Contact Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-600">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="<?= e($supplier['contact_person'] ?? '') ?>">
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-600">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= e($supplier['email'] ?? '') ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-600">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($supplier['phone'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label fw-600">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= e($supplier['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-600">City</label>
                                <input type="text" name="city" class="form-control" value="<?= e($supplier['city'] ?? '') ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-600">State</label>
                                <input type="text" name="state" class="form-control" value="<?= e($supplier['state'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Terms -->
                <div class="card card-soft mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Payment Terms</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-600">Payment Terms *</label>
                                <select name="payment_terms" class="form-select" required>
                                    <option value="">Select payment terms</option>
                                    <option value="net30" <?php if (($supplier['payment_terms'] ?? '') === 'net30') echo 'selected'; ?>>Net 30 days</option>
                                    <option value="net60" <?php if (($supplier['payment_terms'] ?? '') === 'net60') echo 'selected'; ?>>Net 60 days</option>
                                    <option value="net90" <?php if (($supplier['payment_terms'] ?? '') === 'net90') echo 'selected'; ?>>Net 90 days</option>
                                    <option value="cod" <?php if (($supplier['payment_terms'] ?? '') === 'cod') echo 'selected'; ?>>Cash on Delivery</option>
                                    <option value="prepaid" <?php if (($supplier['payment_terms'] ?? '') === 'prepaid') echo 'selected'; ?>>Prepaid</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-600">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="bank_transfer" <?php if (($supplier['payment_method'] ?? '') === 'bank_transfer') echo 'selected'; ?>>Bank Transfer</option>
                                    <option value="check" <?php if (($supplier['payment_method'] ?? '') === 'check') echo 'selected'; ?>>Check</option>
                                    <option value="cash" <?php if (($supplier['payment_method'] ?? '') === 'cash') echo 'selected'; ?>>Cash</option>
                                    <option value="card" <?php if (($supplier['payment_method'] ?? '') === 'card') echo 'selected'; ?>>Credit Card</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-600">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" value="<?= e($supplier['bank_name'] ?? '') ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-600">Account Number</label>
                                <input type="text" name="account_number" class="form-control" value="<?= e($supplier['account_number'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label fw-600">Tax ID</label>
                            <input type="text" name="tax_id" class="form-control" value="<?= e($supplier['tax_id'] ?? '') ?>" placeholder="VAT/Tax identification">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Supplier Settings -->
            <div class="col-lg-4">
                <div class="card card-soft">
                    <div class="card-header">
                        <h6 class="mb-0">Supplier Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-600">Lead Time (Days)</label>
                            <input type="number" name="lead_time_days" class="form-control" value="<?= (int)($supplier['lead_time_days'] ?? 5) ?>" min="1">
                            <small class="text-muted">Days needed for delivery</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-600">Minimum Order Value</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="minimum_order_value" class="form-control" step="0.01" value="<?= number_format((float)($supplier['minimum_order_value'] ?? 0), 2, '.', '') ?>" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($supplier['created_at'])): ?>
                    <div class="card card-soft mt-3">
                        <div class="card-body small">
                            <div class="mb-2">
                                <span class="text-muted">Created:</span>
                                <div><?= (new DateTime($supplier['created_at']))->format('M d, Y H:i') ?></div>
                            </div>
                            <?php if (isset($supplier['updated_at'])): ?>
                                <div>
                                    <span class="text-muted">Last Updated:</span>
                                    <div><?= (new DateTime($supplier['updated_at']))->format('M d, Y H:i') ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-lg btn-primary">Update Supplier</button>
            <a href="<?= e(BASE_URL) ?>/suppliers/<?= e((string)$supplier['supplier_id']) ?>" class="btn btn-lg btn-outline-secondary">View</a>
            <a href="<?= e(BASE_URL) ?>/suppliers" class="btn btn-lg btn-outline-secondary">Back to List</a>
        </div>
    </form>
<?php endif; ?>

<style>
    .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #ddd;
    }
</style>
