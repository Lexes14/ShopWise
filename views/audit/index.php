<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Audit Log</h5>
        <p class="text-muted small">Track all system activities and changes</p>
    </div>
</div>

<!-- Filter Section -->
<div class="card card-soft mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($_GET['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    <option value="users">Users</option>
                    <option value="products">Products</option>
                    <option value="inventory">Inventory</option>
                    <option value="pos">POS</option>
                    <option value="shifts">Shifts</option>
                    <option value="suppliers">Suppliers</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-sm w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Audit Log Table -->
<div class="card card-soft">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 15%">Timestamp</th>
                    <th style="width: 12%">User</th>
                    <th style="width: 10%">Module</th>
                    <th style="width: 10%">Action</th>
                    <th style="width: 15%">Entity</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $log): ?>
                    <tr>
                        <td>
                            <small><?= date('M d, Y', strtotime($log['created_at'] ?? 'now')) ?></small><br>
                            <small class="text-muted"><?= date('h:i A', strtotime($log['created_at'] ?? 'now')) ?></small>
                        </td>
                        <td><?= e($log['username'] ?? '-') ?></td>
                        <td>
                            <span class="badge badge-soft-primary"><?= ucfirst(e($log['module'] ?? '')) ?></span>
                        </td>
                        <td>
                            <?php
                            $action = $log['action'] ?? 'unknown';
                            $badgeClass = 'badge-secondary';
                            if (in_array($action, ['create', 'insert'])) $badgeClass = 'badge-success';
                            elseif (in_array($action, ['update', 'edit'])) $badgeClass = 'badge-info';
                            elseif (in_array($action, ['delete', 'remove'])) $badgeClass = 'badge-danger';
                            elseif ($action === 'login') $badgeClass = 'badge-primary';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst(e($action)) ?></span>
                        </td>
                        <td><small><?= e($log['entity_type'] ?? '-') ?> #<?= e($log['entity_id'] ?? '') ?></small></td>
                        <td><small><?= e($log['description'] ?? 'No description') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No audit logs found for the selected criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if (!empty($page) && !empty($pages) && $pages > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($_GET['date_from']) ? '&date_from=' . urlencode($_GET['date_from']) : '' ?><?= !empty($_GET['date_to']) ? '&date_to=' . urlencode($_GET['date_to']) : '' ?><?= !empty($_GET['module']) ? '&module=' . urlencode($_GET['module']) : '' ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <li class="page-item active">
                    <span class="page-link">Page <?= $page ?> of <?= $pages ?></span>
                </li>
                
                <?php if ($page < $pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($_GET['date_from']) ? '&date_from=' . urlencode($_GET['date_from']) : '' ?><?= !empty($_GET['date_to']) ? '&date_to=' . urlencode($_GET['date_to']) : '' ?><?= !empty($_GET['module']) ? '&module=' . urlencode($_GET['module']) : '' ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
