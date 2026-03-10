<?php
$records = $extra['records'] ?? [];
$customers = $extra['customers'] ?? [];
$filters = $extra['filters'] ?? [];
$summary = $extra['summary'] ?? [];
?>

<div class="card card-soft mb-3">
    <div class="card-header">Customer Transaction History</div>
    <div class="card-body">
        <form method="GET" action="<?= e(BASE_URL) ?>/reports/customer-transactions" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-select">
                    <option value="0">All Customers</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= e((string)$customer['customer_id']) ?>" <?= (int)($filters['customer_id'] ?? 0) === (int)$customer['customer_id'] ? 'selected' : '' ?>>
                            <?= e((string)$customer['full_name']) ?> (<?= e((string)$customer['phone']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= e((string)($filters['start_date'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e((string)($filters['end_date'] ?? '')) ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted d-block">Transactions</small>
                <h5 class="mb-0"><?= e((string)($summary['transactions'] ?? 0)) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted d-block">Total Sales</small>
                <h5 class="mb-0">PHP <?= e(number_format((float)($summary['total_sales'] ?? 0), 2)) ?></h5>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft">
    <div class="card-body p-0">
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction #</th>
                        <th>OR #</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Payment</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= e((string)$row['created_at']) ?></td>
                        <td><?= e((string)$row['transaction_number']) ?></td>
                        <td><?= e((string)$row['or_number']) ?></td>
                        <td><?= e((string)$row['customer_name']) ?></td>
                        <td><?= e((string)$row['customer_phone']) ?></td>
                        <td><?= e(ucfirst((string)$row['customer_type'])) ?></td>
                        <td><?= e(strtoupper((string)$row['payment_method'])) ?></td>
                        <td class="text-end"><?= e(number_format((float)$row['total_amount'], 2)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-muted text-center py-4">No customer transactions found for the selected filters.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
