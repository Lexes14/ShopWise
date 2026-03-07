<?php
pageHeader(
    'Create User',
    'Add a new user to the system',
    [
        [
            'label' => '← Back to Users',
            'href' => BASE_URL . '/users',
            'class' => 'btn btn-outline-secondary'
        ]
    ]
);
?>

<!-- CREATE USER FORM -->
<div class="card card-soft mb-3">
    <div class="card-header" style="background: var(--sw-surface); border-bottom: 1px solid var(--sw-border); padding: 16px 20px;">
        <h6 style="margin: 0; font-weight: 600;">User Information</h6>
    </div>
    <div class="card-body" style="padding: 24px;">
        <form method="POST" action="<?= e(BASE_URL) ?>/users" class="row g-4">
            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
            
            <!-- Full Name -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Full Name <span style="color: var(--sw-danger);">*</span>
                </label>
                <input type="text" 
                       name="full_name" 
                       class="form-control" 
                       placeholder="e.g., Juan Dela Cruz"
                       required
                       style="padding: 10px 14px;">
                <small class="text-muted">User's complete name for display</small>
            </div>

            <!-- Username -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Username <span style="color: var(--sw-danger);">*</span>
                </label>
                <input type="text" 
                       name="username" 
                       class="form-control" 
                       placeholder="e.g., jdelacruz"
                       required
                       pattern="[a-zA-Z0-9_]+"
                       style="padding: 10px 14px; font-family: var(--sw-font-mono);">
                <small class="text-muted">Login username (letters, numbers, underscore only)</small>
            </div>

            <!-- Password -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Password <span style="color: var(--sw-danger);">*</span>
                </label>
                <input type="password" 
                       name="password" 
                       class="form-control" 
                       placeholder="Minimum 8 characters"
                       required
                       minlength="8"
                       style="padding: 10px 14px;">
                <small class="text-muted">At least 8 characters with uppercase, lowercase, and numbers</small>
            </div>

            <!-- Role -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Role <span style="color: var(--sw-danger);">*</span>
                </label>
                <select name="role_id" class="form-select" required style="padding: 10px 14px;">
                    <option value="">-- Select Role --</option>
                    <?php if (!empty($extra['roles'])): ?>
                        <?php foreach ($extra['roles'] as $role): ?>
                            <option value="<?= e((string)$role['role_id']) ?>">
                                <?= e($role['role_name']) ?>
                                <?php if (!empty($role['description'])): ?>
                                    - <?= e($role['description']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="text-muted">Determines user permissions and access level</small>
            </div>

            <!-- Email -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Email Address
                </label>
                <input type="email" 
                       name="email" 
                       class="form-control" 
                       placeholder="user@shopwise.ph"
                       style="padding: 10px 14px;">
                <small class="text-muted">Optional - for notifications and password recovery</small>
            </div>

            <!-- Phone -->
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600; color: var(--sw-text); margin-bottom: 8px;">
                    Phone Number
                </label>
                <input type="text" 
                       name="phone" 
                       class="form-control" 
                       placeholder="0917-123-4567"
                       style="padding: 10px 14px;">
                <small class="text-muted">Optional - contact number</small>
            </div>

            <!-- Cashier PIN -->
            <div class="col-12">
                <div style="background: var(--sw-info-light); border: 1px solid var(--sw-info); border-radius: var(--sw-radius-md); padding: 16px;">
                    <div style="display: flex; align-items: start; gap: 12px;">
                        <div style="font-size: 24px;">🔐</div>
                        <div style="flex: 1;">
                            <h6 style="margin: 0 0 8px 0; color: var(--sw-info);">Cashier PIN (Optional)</h6>
                            <p style="margin: 0 0 12px 0; font-size: 13px; color: var(--sw-text-muted);">
                                If this user will use the POS terminal, set a 6-digit PIN for quick login
                            </p>
                            <input type="text" 
                                   name="pin" 
                                   class="form-control" 
                                   placeholder="Enter 6-digit PIN"
                                   maxlength="6"
                                   pattern="[0-9]{6}"
                                   style="max-width: 200px; padding: 10px 14px; font-family: var(--sw-font-mono); font-size: 16px; letter-spacing: 4px;">
                            <small class="text-muted">Example: 123456</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="col-12">
                <div style="display: flex; gap: 12px; padding-top: 8px; border-top: 1px solid var(--sw-border);">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                        ✓ Create User
                    </button>
                    <a href="<?= e(BASE_URL) ?>/users" class="btn btn-outline-secondary" style="padding: 10px 24px;">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- INFO CARD -->
<div class="card" style="background: var(--sw-accent-light); border: 1px solid var(--sw-accent);">
    <div class="card-body">
        <div style="display: flex; gap: 12px;">
            <div style="font-size: 20px;">💡</div>
            <div>
                <h6 style="margin: 0 0 8px 0; color: var(--sw-accent-dark);">Password Security Tips</h6>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--sw-text-muted);">
                    <li>Use at least 8 characters</li>
                    <li>Include uppercase and lowercase letters</li>
                    <li>Include numbers and special characters</li>
                    <li>Avoid common words or patterns</li>
                    <li>Users should change their password after first login</li>
                </ul>
            </div>
        </div>
    </div>
</div>
