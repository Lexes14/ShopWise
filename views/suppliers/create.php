<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">New Supplier</h5>
        <p class="text-muted small">Add a new supplier to your network</p>
    </div>
</div>

<form method="POST" action="<?= e(BASE_URL) ?>/suppliers/store">
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
                        <input type="text" name="supplier_name" class="form-control" required>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Supplier Code</label>
                            <input type="text" name="supplier_code" class="form-control" placeholder="Auto-generated if empty">
                            <small class="text-muted">Leave empty for auto-generation</small>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
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
                        <input type="text" name="contact_person" class="form-control" placeholder="Primary contact name">
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-600">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street address"></textarea>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-600">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-600">State</label>
                            <input type="text" name="state" class="form-control">
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
                                <option value="net30">Net 30 days</option>
                                <option value="net60">Net 60 days</option>
                                <option value="net90">Net 90 days</option>
                                <option value="cod">Cash on Delivery</option>
                                <option value="prepaid">Prepaid</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="cash">Cash</option>
                                <option value="card">Credit Card</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Account Number</label>
                            <input type="text" name="account_number" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-600">Tax ID</label>
                        <input type="text" name="tax_id" class="form-control" placeholder="VAT/Tax identification">
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
                        <input type="number" name="lead_time_days" class="form-control" value="5" min="1">
                        <small class="text-muted">Days needed for delivery</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-600">Minimum Order Value</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" name="minimum_order_value" class="form-control" step="0.01" value="0" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-lg btn-primary">Create Supplier</button>
        <a href="<?= e(BASE_URL) ?>/suppliers" class="btn btn-lg btn-outline-secondary">Cancel</a>
    </div>
</form>

<style>
    .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #ddd;
    }
</style>
