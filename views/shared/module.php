<?php
$flashTypeClass = 'success';
if (!empty($flash['type'])) {
    $flashTypeClass = match ($flash['type']) {
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        default => 'success',
    };
}
?>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flashTypeClass) ?> py-2 mb-3"><?= e($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <div>
        <h5 class="mb-0"><?= e($title ?? 'Module') ?></h5>
        <small class="text-muted">Section: <?= e($section ?? 'index') ?></small>
    </div>
    <span class="badge text-bg-primary"><?= e(strtoupper((string)($module ?? 'module'))) ?></span>
</div>

<?php if (!empty($extra['id'])): ?>
    <div class="alert alert-info py-2">Record ID: #<?= e((string)$extra['id']) ?></div>
<?php endif; ?>

<?php if (!empty($extra['history']) && is_array($extra['history'])): ?>
    <div class="card card-soft mb-3">
        <div class="card-header">History</div>
        <div class="card-body p-0">
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>Field</th><th>Old</th><th>New</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($extra['history'] as $row): ?>
                        <tr>
                            <td><?= e((string)($row['field_changed'] ?? '')) ?></td>
                            <td><?= e((string)($row['old_value'] ?? '')) ?></td>
                            <td><?= e((string)($row['new_value'] ?? '')) ?></td>
                            <td><?= e((string)($row['changed_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($extra['batches']) && is_array($extra['batches'])): ?>
    <div class="card card-soft mb-3">
        <div class="card-header">Batch Details</div>
        <div class="card-body p-0">
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Batch</th><th>Qty</th><th>Expiry</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($extra['batches'] as $row): ?>
                        <tr>
                            <td><?= e((string)($row['batch_number'] ?? '')) ?></td>
                            <td><?= e((string)($row['qty_remaining'] ?? '')) ?></td>
                            <td><?= e((string)($row['expiration_date'] ?? '')) ?></td>
                            <td><?= e((string)($row['status'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$partialPath = VIEWS_PATH . ($module ?? 'module') . '/' . ($section ?? 'index') . '.php';
$hasPartial = file_exists($partialPath);
if ($hasPartial) {
    include $partialPath;
}
?>

<?php if (!$hasPartial || ($section ?? 'index') === 'index'): ?>
<div class="card card-soft">
    <div class="card-body p-0">
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <?php if (!empty($records) && is_array($records) && isset($records[0]) && is_array($records[0])): ?>
                            <?php foreach (array_keys($records[0]) as $column): ?>
                                <th><?= e(ucwords(str_replace('_', ' ', (string)$column))) ?></th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <th>Info</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records) && is_array($records)): ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <?php foreach ($record as $value): ?>
                                    <td><?= e((string)$value) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td class="text-muted">No records available for this section yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
