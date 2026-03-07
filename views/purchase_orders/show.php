<?php
$po = $po ?? ($extra['po'] ?? []);
$items = $items ?? ($extra['items'] ?? []);
$canManagePo = can('purchase_orders.edit')
    || can('purchase_orders.approve')
    || can('purchase_orders.markOrdered')
    || can('purchase_orders.markReceived')
    || can('purchase_orders.reject');
?>

<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Purchase Order Details</h5>
        <p class="text-muted small">PO #<?= e($po['po_number'] ?? '') ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <!-- PO Header -->
        <div class="card card-soft mb-3">
            <div class="card-header">
                <h6 class="mb-0">Purchase Order Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted">PO Number</small>
                        <div class="fw-600 font-mono"><?= e($po['po_number'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Status</small>
                        <div>
                            <?php
                            $status = $po['status'] ?? 'draft';
                            if ($status === 'draft') {
                                $badgeClass = 'badge-secondary';
                            } elseif ($status === 'submitted') {
                                $badgeClass = 'badge-warning';
                            } elseif ($status === 'approved') {
                                $badgeClass = 'badge-info';
                            } elseif ($status === 'ordered') {
                                $badgeClass = 'badge-primary';
                            } elseif ($status === 'received') {
                                $badgeClass = 'badge-success';
                            } elseif ($status === 'rejected') {
                                $badgeClass = 'badge-danger';
                            } else {
                                $badgeClass = 'badge-light';
                            }
                            ?>
                            <span class="badge <?= e($badgeClass) ?>"><?= ucfirst(e($status)) ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Supplier</small>
                        <div class="fw-600"><?= e($po['supplier_name'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Contact Person</small>
                        <div><?= e($po['contact_person'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">PO Date</small>
                        <div><?= date('F d, Y', strtotime($po['po_date'] ?? 'now')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Expected Delivery</small>
                        <div><?= $po['expected_delivery_date'] ? date('F d, Y', strtotime($po['expected_delivery_date'])) : '-' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Delivery Location</small>
                        <div><?= e($po['delivery_location'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Requested By</small>
                        <div><?= e($po['requested_by'] ?? '-') ?></div>
                    </div>
                    <?php if (!empty($po['notes'])): ?>
                    <div class="col-12">
                        <small class="text-muted">Notes</small>
                        <div class="alert alert-light mt-1 mb-0"><?= nl2br(e($po['notes'])) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($po['status'] === 'rejected' && !empty($po['rejection_reason'])): ?>
                    <div class="col-12">
                        <small class="text-muted">Rejection Reason</small>
                        <div class="alert alert-danger mt-1 mb-0"><?= nl2br(e($po['rejection_reason'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Line Items -->
        <div class="card card-soft">
            <div class="card-header">
                <h6 class="mb-0">Line Items</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40%">Product</th>
                            <th style="width: 15%">Qty</th>
                            <th style="width: 20%">Unit Price</th>
                            <th style="width: 25%">Total</th>
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
                            <td>₦<?= number_format($item['unit_price'], 2) ?></td>
                            <td class="fw-600">₦<?= number_format($lineTotal, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-top-2">
                            <td colspan="3" class="text-end fw-600">Total:</td>
                            <td class="fw-600" style="font-size: 16px;">₦<?= number_format($total, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <?php if (empty($items)): ?>
            <div class="card-body text-center text-muted py-4">
                <p>No items in this purchase order.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Actions Sidebar -->
    <div class="col-lg-4">
        <!-- Actions Card -->
        <div class="card card-soft">
            <div class="card-header">
                <h6 class="mb-0">Actions</h6>
            </div>
            <div class="card-body">
                <?php if ($canManagePo && $po['status'] === 'draft'): ?>
                    <a href="<?= e(BASE_URL) ?>/purchase-orders/<?= intval($po['po_id']) ?>/edit" class="btn btn-primary w-100 mb-2">
                        Edit PO
                    </a>
                <?php endif; ?>
                
                <?php if ($canManagePo && $po['status'] === 'submitted'): ?>
                    <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/<?= intval($po['po_id']) ?>/approve" class="mb-2">
                        <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                        <button type="submit" class="btn btn-success w-100">Approve PO</button>
                    </form>
                    
                    <button type="button" class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        Reject PO
                    </button>
                <?php endif; ?>
                
                <?php if ($canManagePo && $po['status'] === 'approved'): ?>
                    <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/<?= intval($po['po_id']) ?>/mark-ordered" class="mb-2">
                        <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                        <button type="submit" class="btn btn-primary w-100">Mark as Ordered</button>
                    </form>
                <?php endif; ?>
                
                <?php if ($canManagePo && $po['status'] === 'ordered'): ?>
                    <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/<?= intval($po['po_id']) ?>/mark-received" class="mb-2">
                        <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                        <button type="submit" class="btn btn-success w-100">Mark as Received</button>
                    </form>
                <?php endif; ?>
                
                <a href="<?= e(BASE_URL) ?>/purchase-orders" class="btn btn-outline-secondary w-100">Back to List</a>
            </div>
        </div>
        
        <!-- Summary Card -->
        <div class="card card-soft mt-3">
            <div class="card-body">
                <h6 class="mb-3">Order Summary</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span>Items:</span>
                    <strong><?= count($items ?? []) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <strong>₦<?= number_format($total, 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Tax (0%):</span>
                    <strong>₦0.00</strong>
                </div>
                <div class="border-top pt-2 d-flex justify-content-between">
                    <span class="fw-600">Total:</span>
                    <strong style="font-size: 18px;">₦<?= number_format($total, 2) ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Timeline -->
        <div class="card card-soft mt-3">
            <div class="card-body">
                <h6 class="mb-3">Status Timeline</h6>
                <div class="timeline-item">
                    <div class="timeline-marker <?= in_array($po['status'], ['draft', 'submitted', 'approved', 'ordered', 'received']) ? 'active' : '' ?>"></div>
                    <div class="timeline-content">
                        <div class="fw-600">Draft</div>
                        <small class="text-muted"><?= $po['po_date'] ? date('M d, Y', strtotime($po['po_date'])) : '-' ?></small>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker <?= in_array($po['status'], ['submitted', 'approved', 'ordered', 'received']) ? 'active' : '' ?>"></div>
                    <div class="timeline-content">
                        <div class="fw-600">Submitted</div>
                        <small class="text-muted"><?= $po['submitted_at'] ? date('M d, Y', strtotime($po['submitted_at'])) : '-' ?></small>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker <?= in_array($po['status'], ['approved', 'ordered', 'received']) ? 'active' : '' ?>"></div>
                    <div class="timeline-content">
                        <div class="fw-600">Approved</div>
                        <small class="text-muted"><?= $po['approved_at'] ? date('M d, Y', strtotime($po['approved_at'])) : '-' ?></small>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker <?= in_array($po['status'], ['ordered', 'received']) ? 'active' : '' ?>"></div>
                    <div class="timeline-content">
                        <div class="fw-600">Ordered</div>
                        <small class="text-muted">Sent to supplier</small>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker <?= $po['status'] === 'received' ? 'active' : '' ?>"></div>
                    <div class="timeline-content">
                        <div class="fw-600">Received</div>
                        <small class="text-muted"><?= $po['received_at'] ? date('M d, Y', strtotime($po['received_at'])) : '-' ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<?php if ($canManagePo): ?>
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/<?= intval($po['po_id']) ?>/reject">
                <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600">Reason for Rejection *</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why this PO is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject PO</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .timeline-item {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #ddd;
    }
    
    .timeline-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .timeline-marker {
        min-width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #e9ecef;
        margin-top: 4px;
    }
    
    .timeline-marker.active {
        background: #1A3C5E;
    }
    
    .timeline-content {
        flex: 1;
    }
</style>
