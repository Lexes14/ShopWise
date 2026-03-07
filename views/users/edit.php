<?php 
$target = $extra['target_user'] ?? []; 
$roles = $extra['roles'] ?? [];
$userId = $extra['id'] ?? '';
?>

<?php
pageHeader(
    'Edit User #' . e((string)$userId),
    'Update user information and permissions',
    [
        [
            'label' => '← Back to Users',
            'href' => BASE_URL . '/users',
            'class' => 'btn btn-outline-secondary'
        ]
    ]
);
?>

<!-- USER INFO CARD -->
<div class="card mb-3" style="background: var(--sw-primary-light); border: 1px solid var(--sw-primary);">
    <div class="card-body">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--sw-primary); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; font-size: 18px;">
                <?= e(strtoupper(substr($target['full_name'] ?? 'U', 0, 1))) ?>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 700; font-size: 18px; color: var(--sw-text);">
                    <?= e($target['full_name'] ?? 'Unknown User') ?>
                </div>
                <div style="font-size: 13px; color: var(--sw-text-muted); font-family: var(--sw-font-mono);">
                    @<?= e($target['username'] ?? '') ?> • Joined <?= e(date('M j, Y', strtotime($target['created_at'] ?? 'now'))) ?>
                </div>
            </div>
            <?php
            $status = $target['status'] ?? 'active';
            $statusStyle = match($status) {
                'active' => 'background: var(--sw-success); color: white;',
                'inactive' => 'background: var(--sw-text-muted); color: white;',
                'locked' => 'background: var(--sw-danger); color: white;',
                default => 'background: var(--sw-text-muted); color: white;',
            };
            ?>
            <span style="<?= $statusStyle ?> padding: 6px 16px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                <?= e(ucfirst($status)) ?>
            </span>
        </div>
    </div>
</div>

<!-- EDIT USER FORM -->
<div class="card card-soft mb-3">
    <div class="card-header" style="background: var(--sw-surface); border-bottom: 1px solid var(--sw-border); padding: 16px 20px;">
        <h6 style="margin: 0; font-weight: 600;">User Information</h6>
    </div>
    <div class="card-body" style="padding: 24px;">
        <form method="POST" action="<?= e(BASE_URL) ?>/users/<?= e((string)$userId) ?>/update" class="row g-4">
            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
            
            <!-- Full Name -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Full Name <span style="color: var(--sw-danger);">*</span>
                </label>
                <input type="text" 
                       name="full_name" 
                       class="form-control" 
                       value="<?= e($target['full_name'] ?? '') ?>"
                       required
                       style="padding: 10px 14px;">
            </div>

            <!-- Role -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Role <span style="color: var(--sw-danger);">*</span>
                </label>
                <select name="role_id" class="form-select" required style="padding: 10px 14px;">
                    <?php if (!empty($roles)): ?>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= e((string)$role['role_id']) ?>" 
                                    <?= ((int)($target['role_id'] ?? 0) === (int)$role['role_id']) ? 'selected' : '' ?>>
                                <?= e($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="text-muted">Changing role will update user permissions</small>
            </div>

            <!-- Email -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Email Address
                </label>
                <input type="email" 
                       name="email" 
                       class="form-control" 
                       value="<?= e($target['email'] ?? '') ?>"
                       style="padding: 10px 14px;">
            </div>

            <!-- Phone -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Phone Number
                </label>
                <input type="text" 
                       name="phone" 
                       class="form-control" 
                       value="<?= e($target['phone'] ?? '') ?>"
                       style="padding: 10px 14px;">
            </div>

            <!-- Status -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Account Status <span style="color: var(--sw-danger);">*</span>
                </label>
                <select name="status" class="form-select" style="padding: 10px 14px;">
                    <?php foreach (['active', 'inactive', 'locked'] as $statusOption): ?>
                        <option value="<?= e($statusOption) ?>" 
                                <?= (($target['status'] ?? '') === $statusOption) ? 'selected' : '' ?>>
                            <?= e(ucfirst($statusOption)) ?>
                            <?php if ($statusOption === 'active'): ?> - User can log in
                            <?php elseif ($statusOption === 'inactive'): ?> - User cannot log in
                            <?php elseif ($statusOption === 'locked'): ?> - Account locked (too many failed attempts)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Update PIN -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Update PIN (Optional)
                </label>
                <input type="text" 
                       name="pin" 
                       class="form-control" 
                       placeholder="Enter new 6-digit PIN"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       style="padding: 10px 14px; font-family: var(--sw-font-mono); font-size: 16px; letter-spacing: 4px;">
                <small class="text-muted">Leave blank to keep current PIN • For cashier POS login</small>
            </div>

            <!-- Action Buttons -->
            <div class="col-12">
                <div style="display: flex; gap: 12px; padding-top: 8px; border-top: 1px solid var(--sw-border);">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                        ✓ Update User
                    </button>
                    <a href="<?= e(BASE_URL) ?>/users" class="btn btn-outline-secondary" style="padding: 10px 24px;">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- PASSWORD RESET CARD -->
<?php if (can('users.resetPassword')): ?>
<div class="card card-soft">
    <div class="card-header" style="background: var(--sw-warning-light); border-bottom: 1px solid var(--sw-warning); padding: 16px 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 20px;">⚠️</span>
            <h6 style="margin: 0; font-weight: 600; color: var(--sw-warning);">Password Reset</h6>
        </div>
    </div>
    <div class="card-body" style="padding: 24px;">
        <p style="margin-bottom: 16px; color: var(--sw-text-muted);">
            Reset this user's password. A new random password will be generated and displayed.
        </p>
        <form method="POST" action="<?= e(BASE_URL) ?>/users/<?= e((string)$userId) ?>/reset-password" style="margin-bottom: 0;">
            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
            <button class="btn btn-warning" type="submit" style="padding: 10px 24px;">
                🔄 Reset Password
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
