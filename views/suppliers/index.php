<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Supplier Management</h5>
        <p class="text-muted small">Manage supplier relationships and contacts</p>
    </div>
    <?php if (can('suppliers.create')): ?>
        <button type="button" class="btn btn-primary" onclick="location.href='<?= e(BASE_URL) ?>/suppliers/create'">+ New Supplier</button>
    <?php endif; ?>
</div>

<!-- Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1">🏢</div>
                <div class="h5">Total Suppliers</div>
                <div class="text-muted">
                    <?= (int)($summary['total_suppliers'] ?? 0) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-success">✓</div>
                <div class="h5">Active</div>
                <div class="text-muted">
                    <?= (int)($summary['active_suppliers'] ?? 0) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-warning">⏸</div>
                <div class="h5">Inactive</div>
                <div class="text-muted">
                    <?= (int)($summary['inactive_suppliers'] ?? 0) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card card-soft mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(BASE_URL) ?>/suppliers" class="row g-3">
            <div class="col-sm-6 col-md-8">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Supplier name, code, email..." value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-sm-6 col-md-4">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php if (($filters['status'] ?? '') === 'active') echo 'selected'; ?>>Active</option>
                    <option value="inactive" <?php if (($filters['status'] ?? '') === 'inactive') echo 'selected'; ?>>Inactive</option>
                    <option value="archived" <?php if (($filters['status'] ?? '') === 'archived') echo 'selected'; ?>>Archived</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Suppliers Table -->
<div class="card card-soft">
    <?php if (!empty($suppliers)): ?>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%;">Supplier</th>
                        <th style="width: 15%;">Contact Person</th>
                        <th style="width: 15%;">Email</th>
                        <th style="width: 12%;">Phone</th>
                        <th style="width: 12%;">Payment Terms</th>
                        <th style="width: 12%;">Status</th>
                        <th style="width: 15%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td>
                                <strong><?= e($supplier['supplier_name']) ?></strong>
                                <div class="small text-muted">
                                    <?php if (!empty($supplier['total_orders'])): ?>
                                        📦 <?= (int)$supplier['total_orders'] ?> orders
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?= e($supplier['contact_person'] ?? '-') ?>
                            </td>
                            <td>
                                <?php if (!empty($supplier['email'])): ?>
                                    <a href="mailto:<?= e($supplier['email']) ?>" class="text-truncate"><?= e($supplier['email']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= !empty($supplier['phone']) ? e($supplier['phone']) : '-' ?>
                            </td>
                            <td>
                                <span class="badge text-bg-light text-dark"><?= e(ucfirst($supplier['payment_terms'] ?? 'N/A')) ?></span>
                            </td>
                            <td>
                                <?php
                                $status = $supplier['status'] ?? '';
                                $badgeClass = match($status) {
                                    'active' => 'text-bg-success',
                                    'inactive' => 'text-bg-warning',
                                    'archived' => 'text-bg-secondary',
                                    default => 'text-bg-light'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= e(ucfirst($status)) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if (can('suppliers.show')): ?>
                                    <a href="<?= e(BASE_URL) ?>/suppliers/<?= e((string)$supplier['supplier_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                <?php endif; ?>
                                <?php if (can('suppliers.edit')): ?>
                                    <a href="<?= e(BASE_URL) ?>/suppliers/<?= e((string)$supplier['supplier_id']) ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
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
                            <a class="page-link" href="<?= e(BASE_URL) ?>/suppliers?page=1&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= e(BASE_URL) ?>/suppliers?page=<?= ($pagination['current'] - 1) ?>&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($pagination['current'] < $pagination['total']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= e(BASE_URL) ?>/suppliers?page=<?= ($pagination['current'] + 1) ?>&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= e(BASE_URL) ?>/suppliers?page=<?= (int)$pagination['total'] ?>&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php else: ?>
        <div class="card-body text-center py-5">
            <div class="text-muted mb-3" style="font-size: 48px;">🏢</div>
            <p class="text-muted">No suppliers found.</p>
            <?php if (can('suppliers.create')): ?>
                <a href="<?= e(BASE_URL) ?>/suppliers/create" class="btn btn-sm btn-primary">Create First Supplier</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .table-wrap {
        overflow-x: auto;
    }
</style>
