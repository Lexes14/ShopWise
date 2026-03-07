<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Backup & Recovery</h5>
        <p class="text-muted small">Manage database backups and system recovery</p>
    </div>
    <div class="ms-auto">
        <?php if (can('backup.create')): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                Create New Backup
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Info Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Total Backups</small>
                <h4 class="mb-0 mt-1"><?= intval($extra['summary']['total'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Last Backup</small>
                <h6 class="mb-0 mt-1"><?= isset($extra['summary']['last_backup']) ? date('M d, Y', strtotime($extra['summary']['last_backup'])) : 'Never' ?></h6>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Total Size</small>
                <h4 class="mb-0 mt-1"><?= number_format($extra['summary']['total_size'] ?? 0, 2) ?> MB</h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Auto Backup</small>
                <h6 class="mb-0 mt-1">
                    <span class="badge badge-success">Enabled</span>
                </h6>
            </div>
        </div>
    </div>
</div>

<!-- Backups List -->
<div class="card card-soft">
    <div class="card-header">
        <h6 class="mb-0">Available Backups</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 25%">Backup Name</th>
                    <th style="width: 15%">Date Created</th>
                    <th style="width: 12%">Size</th>
                    <th style="width: 15%">Type</th>
                    <th style="width: 13%">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $backup): ?>
                    <tr>
                        <td>
                            <strong><?= e($backup['backup_name'] ?? 'Unnamed Backup') ?></strong><br>
                            <small class="text-muted">ID: <?= e($backup['backup_id'] ?? '') ?></small>
                        </td>
                        <td>
                            <?= date('M d, Y', strtotime($backup['created_at'] ?? 'now')) ?><br>
                            <small class="text-muted"><?= date('h:i A', strtotime($backup['created_at'] ?? 'now')) ?></small>
                        </td>
                        <td><?= number_format($backup['file_size'] ?? 0, 2) ?> MB</td>
                        <td>
                            <?php
                            $type = $backup['backup_type'] ?? 'manual';
                            $badgeClass = $type === 'auto' ? 'badge-info' : 'badge-secondary';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst(e($type)) ?></span>
                        </td>
                        <td>
                            <?php
                            $status = $backup['status'] ?? 'completed';
                            if ($status === 'completed') {
                                echo '<span class="badge badge-success">Completed</span>';
                            } elseif ($status === 'failed') {
                                echo '<span class="badge badge-danger">Failed</span>';
                            } else {
                                echo '<span class="badge badge-warning">Processing</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (can('backup.download') || can('backup.restore') || can('backup.delete')): ?>
                                <div class="d-flex gap-1">
                                    <?php if (can('backup.download')): ?>
                                        <a href="<?= e(BASE_URL) ?>/backup/download/<?= intval($backup['backup_id']) ?>" class="btn btn-sm btn-outline-primary" title="Download">
                                            ⬇️
                                        </a>
                                    <?php endif; ?>
                                    <?php if (can('backup.restore')): ?>
                                        <form method="POST" action="<?= e(BASE_URL) ?>/backup/restore/<?= intval($backup['backup_id']) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore this backup? This will overwrite current data.');">
                                            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Restore">
                                                🔄
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (can('backup.delete')): ?>
                                        <form method="POST" action="<?= e(BASE_URL) ?>/backup/delete/<?= intval($backup['backup_id']) ?>" style="display:inline;" onsubmit="return confirm('Delete this backup?');">
                                            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                🗑️
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No backups available. Create your first backup to get started.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Backup Modal -->
<?php if (can('backup.create')): ?>
<div class="modal fade" id="createBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= e(BASE_URL) ?>/backup/create">
                <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600">Backup Name *</label>
                        <input type="text" name="backup_name" class="form-control" placeholder="e.g., Pre-Migration Backup" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600">Backup Type</label>
                        <select name="backup_type" class="form-select">
                            <option value="full">Full Database</option>
                            <option value="data_only">Data Only</option>
                            <option value="structure_only">Structure Only</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about this backup..."></textarea>
                    </div>
                    <div class="alert alert-info mb-0">
                        <small><strong>Note:</strong> Large databases may take several minutes to backup. You'll be notified when complete.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
