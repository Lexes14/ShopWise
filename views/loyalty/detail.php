<?php $customer = $extra['customer'] ?? []; $history = $extra['points_history'] ?? []; ?>
<div class="card card-soft mb-3">
    <div class="card-header">Loyalty Customer Details</div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-6"><strong>Name:</strong> <?= e($customer['full_name'] ?? '') ?></div>
            <div class="col-md-6"><strong>Phone:</strong> <?= e($customer['phone'] ?? '') ?></div>
            <div class="col-md-4"><strong>Tier:</strong> <?= e($customer['tier'] ?? '') ?></div>
            <div class="col-md-4"><strong>Points:</strong> <?= e((string)($customer['points_balance'] ?? 0)) ?></div>
            <div class="col-md-4"><strong>Total Spend:</strong> ₱<?= e(number_format((float)($customer['total_spend'] ?? 0),2)) ?></div>
        </div>
        <div class="table-wrap">
            <table class="table table-sm mb-0">
                <thead><tr><th>Type</th><th class="text-end">Points</th><th class="text-end">Balance</th><th>Notes</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= e($row['points_type']) ?></td>
                            <td class="text-end"><?= e((string)$row['points_amount']) ?></td>
                            <td class="text-end"><?= e((string)$row['balance_after']) ?></td>
                            <td><?= e((string)$row['notes']) ?></td>
                            <td><?= e((string)$row['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
