<?php
$rows = $records ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h6 class="mb-0">Shift History</h6>
        <small class="text-muted">Open or close your shift using the buttons on the right.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(BASE_URL) ?>/shifts/open" class="btn btn-sm btn-primary">Open Shift</a>
        <a href="<?= e(BASE_URL) ?>/shifts/close" class="btn btn-sm btn-outline-danger">Close Shift</a>
    </div>
</div>

<div class="card card-soft">
    <div class="card-body p-0">
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Shift #</th>
                        <th>Cashier</th>
                        <th>Label</th>
                        <th>Start</th>
                        <th>End</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end">Expected</th>
                        <th class="text-end">Closing</th>
                        <th class="text-end">Variance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e((string)$row['shift_id']) ?></td>
                            <td><?= e((string)$row['cashier_name']) ?></td>
                            <td><?= e((string)$row['shift_label']) ?></td>
                            <td><?= e((string)$row['start_time']) ?></td>
                            <td><?= e((string)($row['end_time'] ?? '-')) ?></td>
                            <td class="text-end">PHP <?= e(number_format((float)($row['opening_cash'] ?? 0), 2)) ?></td>
                            <td class="text-end">PHP <?= e(number_format((float)($row['expected_cash'] ?? 0), 2)) ?></td>
                            <td class="text-end">PHP <?= e(number_format((float)($row['closing_cash'] ?? 0), 2)) ?></td>
                            <td class="text-end">PHP <?= e(number_format((float)($row['cash_variance'] ?? 0), 2)) ?></td>
                            <td>
                                <?php $status = (string)($row['status'] ?? ''); ?>
                                <span class="badge <?= $status === 'open' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= e(ucfirst($status)) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No shift records found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
