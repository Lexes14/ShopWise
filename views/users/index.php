<?php
// Page actions
$actions = [];
if (can('users.create')) {
    $actions[] = [
        'label' => 'Create User',
        'href' => BASE_URL . '/users/create',
        'class' => 'btn btn-primary',
        'icon' => '➕'
    ];
}

pageHeader(
    'User Management',
    'Manage user accounts and permissions',
    $actions
);
?>

<!-- USERS TABLE -->
<div class="card card-soft">
    <div class="card-header" style="background: var(--sw-surface); border-bottom: 1px solid var(--sw-border); padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h6 style="margin: 0; font-weight: 600;">All Users (<?= count($records) ?>)</h6>
            <div style="display: flex; gap: 12px; align-items: center;">
                <input type="text" id="userSearch" class="form-control form-control-sm" placeholder="Search users..." style="width: 200px;">
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0" id="usersTable">
                <thead style="background: var(--sw-surface2); border-bottom: 2px solid var(--sw-border);">
                    <tr>
                        <th style="padding: 12px 20px; font-weight: 600; font-size: 13px; color: var(--sw-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">User</th>
                        <th style="padding: 12px 20px; font-weight: 600; font-size: 13px; color: var(--sw-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Username</th>
                        <th style="padding: 12px 20px; font-weight: 600; font-size: 13px; color: var(--sw-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Role</th>
                        <th style="padding: 12px 20px; font-weight: 600; font-size: 13px; color: var(--sw-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                        <th style="padding: 12px 20px; font-weight: 600; font-size: 13px; color: var(--sw-text-muted); text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records) && is_array($records)): ?>
                        <?php foreach ($records as $record): ?>
                            <tr style="border-bottom: 1px solid var(--sw-border);">
                                <td style="padding: 16px 20px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--sw-accent); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; font-size: 14px;">
                                            <?= e(strtoupper(substr($record['full_name'] ?? 'U', 0, 1))) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--sw-text);"><?= e($record['full_name'] ?? '') ?></div>
                                            <div style="font-size: 12px; color: var(--sw-text-muted);">ID: <?= e((string)($record['user_id'] ?? '')) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <span style="font-family: var(--sw-font-mono); font-size: 13px; background: var(--sw-surface2); padding: 4px 8px; border-radius: 4px;">
                                        <?= e($record['username'] ?? '') ?>
                                    </span>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <?php
                                    $roleColors = [
                                        'Owner' => 'background: var(--sw-danger-light); color: var(--sw-danger);',
                                        'Manager' => 'background: var(--sw-warning-light); color: var(--sw-warning);',
                                        'Inventory Staff' => 'background: var(--sw-info-light); color: var(--sw-info);',
                                        'Cashier' => 'background: var(--sw-success-light); color: var(--sw-success);',
                                        'Purchasing Officer' => 'background: var(--sw-primary-light); color: var(--sw-primary);',
                                        'Security' => 'background: var(--sw-surface2); color: var(--sw-text-muted);',
                                        'Bookkeeper' => 'background: var(--sw-accent-light); color: var(--sw-accent-dark);',
                                    ];
                                    $roleName = $record['role_name'] ?? 'Unknown';
                                    $roleStyle = $roleColors[$roleName] ?? 'background: var(--sw-surface2); color: var(--sw-text-muted);';
                                    ?>
                                    <span style="<?= $roleStyle ?> padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">
                                        <?= e($roleName) ?>
                                    </span>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <?php
                                    $status = $record['status'] ?? 'active';
                                    $statusStyle = match($status) {
                                        'active' => 'background: var(--sw-success-light); color: var(--sw-success);',
                                        'inactive' => 'background: var(--sw-surface2); color: var(--sw-text-muted);',
                                        'locked' => 'background: var(--sw-danger-light); color: var(--sw-danger);',
                                        default => 'background: var(--sw-surface2); color: var(--sw-text-muted);',
                                    };
                                    ?>
                                    <span style="<?= $statusStyle ?> padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">
                                        <?= e(ucfirst($status)) ?>
                                    </span>
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <?php if (can('users.edit')): ?>
                                        <a href="<?= e(BASE_URL) ?>/users/<?= e((string)($record['user_id'] ?? '')) ?>/edit" 
                                           class="btn btn-sm btn-outline-primary" 
                                           style="padding: 6px 12px; font-size: 12px;">
                                            Edit
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (can('users.delete') && $status === 'active'): ?>
                                        <button onclick="deactivateUser(<?= e((string)($record['user_id'] ?? '')) ?>)" 
                                                class="btn btn-sm btn-outline-danger"
                                                style="padding: 6px 12px; font-size: 12px;">
                                            Deactivate
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 40px;">
                                <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
                                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No Users Found</div>
                                <div style="font-size: 14px; color: var(--sw-text-muted);">Create your first user to get started</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DEACTIVATE MODAL -->
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deactivate User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate this user? They will no longer be able to log in.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deactivateForm" method="POST" style="display: inline;">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    <button type="submit" class="btn btn-danger">Deactivate User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// User search functionality
document.getElementById('userSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Deactivate user function
function deactivateUser(userId) {
    const form = document.getElementById('deactivateForm');
    form.action = '<?= e(BASE_URL) ?>/users/' + userId + '/deactivate';
    new bootstrap.Modal(document.getElementById('deactivateModal')).show();
}
</script>
