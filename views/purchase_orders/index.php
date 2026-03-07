<?php
$summary = $summary ?? ($extra['summary'] ?? []);
$overdue = $overdue ?? (int)($extra['overdue'] ?? 0);
$filters = $filters ?? ($extra['filters'] ?? []);
$filters = array_merge(['search' => '', 'status' => '', 'supplier_id' => 0], is_array($filters) ? $filters : []);
$pos = $pos ?? ($records ?? []);

$pagination = $pagination ?? [];
$pagination = [
    'current' => (int)($pagination['current'] ?? $pagination['page'] ?? 1),
    'total' => max(1, (int)($pagination['total'] ?? $pagination['pages'] ?? 1)),
];
?>

<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Purchase Orders</h5>
        <p class="text-muted small">Manage supplier purchase orders and deliveries</p>
    </div>
    <?php if (can('purchase_orders.create')): ?>
        <button type="button" class="btn btn-primary" onclick="location.href='<?= e(BASE_URL) ?>/purchase-orders/create'">+ New PO</button>
    <?php endif; ?>
</div>

<!-- Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1">📄</div>
                <div class="h5">Total POs</div>
                <div class="text-muted">
                    <?= (int)($summary['total_pos'] ?? 0) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-warning">⏳</div>
                <div class="h5">Pending Approval</div>
                <div class="text-muted">
                    <?= (int)($summary['pending_approval'] ?? 0) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-info">📦</div>
                <div class="h5">In Transit</div>
                <div class="text-muted">
                    <?= (int)($summary['ordered'] ?? 0) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-danger">⚠ OVERDUE</div>
                <div class="h5"><?= (int)$overdue ?></div>
                <div class="text-muted">Past expected date</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card card-soft mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(BASE_URL) ?>/purchase-orders" class="row g-3">
            <div class="col-sm-6 col-md-5">
                <label class="form-label small">Search PO</label>
                <input type="text" name="search" class="form-control" placeholder="PO number, supplier..." value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-sm-6 col-md-4">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="draft" <?php if ($filters['status'] === 'draft') echo 'selected'; ?>>Draft</option>
                    <option value="submitted" <?php if ($filters['status'] === 'submitted') echo 'selected'; ?>>Pending Approval</option>
                    <option value="approved" <?php if ($filters['status'] === 'approved') echo 'selected'; ?>>Approved</option>
                    <option value="ordered" <?php if ($filters['status'] === 'ordered') echo 'selected'; ?>>Ordered</option>
                    <option value="received" <?php if ($filters['status'] === 'received') echo 'selected'; ?>>Received</option>
                    <option value="rejected" <?php if ($filters['status'] === 'rejected') echo 'selected'; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Purchase Orders Table -->
<div class="card card-soft">
    <?php if (!empty($pos)): ?>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 12%;">PO Number</th>
                        <th style="width: 18%;">Supplier</th>
                        <th style="width: 10%;">Items</th>
                        <th style="width: 13%;" class="text-end">Amount</th>
                        <th style="width: 12%;">Expected Date</th>
                        <th style="width: 12%;">Status</th>
                        <th style="width: 15%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pos as $po): ?>
                        <tr>
                            <td>
                                <code><strong><?= e($po['po_number']) ?></strong></code>
                            </td>
                            <td>
                                <strong><?= e($po['supplier_name']) ?></strong>
                                <div class="small text-muted">
                                    <?php if (!empty($po['contact_person'])): ?>
                                        <?= e($po['contact_person']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?= (int)($po['item_count'] ?? 0) ?>
                            </td>
                            <td class="text-end">
                                <strong>₦<?= number_format((float)($po['total_amount'] ?? 0), 2) ?></strong>
                            </td>
                            <td>
                                <?php
                                if ($po['expected_delivery_date']) {
                                    $expDate = new DateTime($po['expected_delivery_date']);
                                    $today = new DateTime();
                                    $daysLeft = $today->diff($expDate)->days;
                                    $isOverdue = $today > $expDate;
                                    ?>
                                    <span class="<?= $isOverdue ? 'text-danger fw-600' : 'text-muted' ?>">
                                        <?= $expDate->format('M d, Y') ?>
                                        <?php if ($isOverdue): ?>
                                            <br><small class="text-danger"><?= $daysLeft ?>d overdue</small>
                                        <?php endif; ?>
                                    </span>
                                <?php } else { ?>
                                    <span class="text-muted">-</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php
                                $status = $po['status'] ?? '';
                                $badgeClass = 'text-bg-light';
                                $statusLabel = ucfirst($status);
                                
                                if ($status === 'draft') {
                                    $badgeClass = 'text-bg-secondary';
                                    $statusLabel = 'Draft';
                                } elseif ($status === 'submitted') {
                                    $badgeClass = 'text-bg-warning';
                                    $statusLabel = 'Pending Approval';
                                } elseif ($status === 'approved') {
                                    $badgeClass = 'text-bg-info';
                                    $statusLabel = 'Approved';
                                } elseif ($status === 'ordered') {
                                    $badgeClass = 'text-bg-primary';
                                    $statusLabel = 'Ordered';
                                } elseif ($status === 'received') {
                                    $badgeClass = 'text-bg-success';
                                    $statusLabel = 'Received';
                                } elseif ($status === 'rejected') {
                                    $badgeClass = 'text-bg-danger';
                                    $statusLabel = 'Rejected';
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td class="text-center">
                                <?php if (can('purchase_orders.show')): ?>
                                    <a href="<?= e(BASE_URL) ?>/purchase-orders/<?= e((string)$po['po_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <?php if ($status === 'draft' && can('purchase_orders.edit')): ?>
                                        <a href="<?= e(BASE_URL) ?>/purchase-orders/<?= e((string)$po['po_id']) ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer bg-light">
            <nav aria-label="Page navigation" class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing page <?= (int)$pagination['current'] ?> of <?= (int)$pagination['total'] ?>
                </small>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($pagination['current'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= e(BASE_URL) ?>/purchase-orders?page=1&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= e(BASE_URL) ?>/purchase-orders?page=<?= ($pagination['current'] - 1) ?>&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($pagination['current'] < $pagination['total']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= e(BASE_URL) ?>/purchase-orders?page=<?= ($pagination['current'] + 1) ?>&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= e(BASE_URL) ?>/purchase-orders?page=<?= (int)$pagination['total'] ?>&search=<?= urlencode($filters['search']) ?>&status=<?= urlencode($filters['status']) ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php else: ?>
        <div class="card-body text-center py-5">
            <div class="text-muted mb-3" style="font-size: 48px;">📄</div>
            <p class="text-muted">No purchase orders found.</p>
            <?php if (can('purchase_orders.create')): ?>
                <a href="<?= e(BASE_URL) ?>/purchase-orders/create" class="btn btn-sm btn-primary">Create First PO</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .table-wrap {
        overflow-x: auto;
    }
</style>
