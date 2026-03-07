<div class="row g-3">
    <?php $canManageAllSettings = can('settings.updateStore') || can('settings.updatePOS') || can('settings.updateAI'); ?>
    <?php $canManageTaxSettings = can('settings.updateTax'); ?>

    <?php if ($canManageAllSettings): ?>
    <div class="col-lg-6">
        <div class="card card-soft">
            <div class="card-header">Store Settings</div>
            <div class="card-body">
                <form method="POST" action="<?= e(BASE_URL) ?>/settings/store" class="row g-2">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    <div class="col-12"><input type="text" name="store_name" class="form-control" placeholder="Store Name"></div>
                    <div class="col-12"><input type="text" name="store_address" class="form-control" placeholder="Address"></div>
                    <div class="col-md-6"><input type="text" name="store_phone" class="form-control" placeholder="Phone"></div>
                    <div class="col-md-6"><input type="email" name="store_email" class="form-control" placeholder="Email"></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Save Store</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-soft">
            <div class="card-header">Tax Settings</div>
            <div class="card-body">
                <form method="POST" action="<?= e(BASE_URL) ?>/settings/tax" class="row g-2">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    <div class="col-md-6"><input type="number" step="0.01" name="vat_rate" class="form-control" placeholder="VAT Rate"></div>
                    <div class="col-md-6"><input type="number" step="0.01" name="senior_pwd_discount" class="form-control" placeholder="Senior/PWD Discount"></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Save Tax</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-soft">
            <div class="card-header">POS Settings</div>
            <div class="card-body">
                <form method="POST" action="<?= e(BASE_URL) ?>/settings/pos" class="row g-2">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    <div class="col-md-6"><input type="number" step="0.01" name="pos_shift_float" class="form-control" placeholder="Opening Float"></div>
                    <div class="col-md-6"><input type="number" step="0.01" name="pos_variance_flag" class="form-control" placeholder="Variance Flag"></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Save POS</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-soft">
            <div class="card-header">AI Settings</div>
            <div class="card-body">
                <form method="POST" action="<?= e(BASE_URL) ?>/settings/ai" class="row g-2">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    <div class="col-md-6"><input type="number" step="0.01" name="ai_safety_multiplier" class="form-control" placeholder="Safety Multiplier"></div>
                    <div class="col-md-6"><input type="number" step="0.01" name="ai_max_promo_discount" class="form-control" placeholder="Max Promo %"></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Save AI</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canManageTaxSettings && !$canManageAllSettings): ?>
    <div class="col-lg-6">
        <div class="card card-soft">
            <div class="card-header">Tax Settings</div>
            <div class="card-body">
                <form method="POST" action="<?= e(BASE_URL) ?>/settings/tax" class="row g-2">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    <div class="col-md-6"><input type="number" step="0.01" name="vat_rate" class="form-control" placeholder="VAT Rate"></div>
                    <div class="col-md-6"><input type="number" step="0.01" name="senior_pwd_discount" class="form-control" placeholder="Senior/PWD Discount"></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Save Tax</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$canManageAllSettings && !$canManageTaxSettings): ?>
    <div class="col-12">
        <div class="alert alert-warning">
            <strong>Access Denied</strong><br>
            You do not have permission to modify system settings.
        </div>
    </div>
    <?php endif; ?>
</div>
