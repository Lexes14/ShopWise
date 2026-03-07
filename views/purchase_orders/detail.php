<?php $po = $extra['po'] ?? []; $items = $extra['po_items'] ?? []; $canManagePo = can('purchase_orders.edit') || can('purchase_orders.approve'); ?>
<div class="card card-soft mb-3">
    <div class="card-header">PO Details #<?= e((string)($po['po_number'] ?? '')) ?></div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-4"><strong>Supplier:</strong> <?= e($po['supplier_name'] ?? '') ?></div>
            <div class="col-md-4"><strong>Status:</strong> <?= e($po['status'] ?? '') ?></div>
            <div class="col-md-4"><strong>Total:</strong> ₱<?= e(number_format((float)($po['total_amount'] ?? 0), 2)) ?></div>
            <div class="col-md-4"><strong>Expected:</strong> <?= e((string)($po['expected_delivery'] ?? '')) ?></div>
            <div class="col-md-8"><strong>Notes:</strong> <?= e((string)($po['notes'] ?? '')) ?></div>
        </div>

        <div class="table-wrap mb-3">
            <table class="table table-sm mb-0">
                <thead><tr><th>Product</th><th class="text-end">Ordered</th><th class="text-end">Received</th><th class="text-end">Unit Cost</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td><?= e($row['product_name']) ?></td>
                            <td class="text-end"><?= e((string)$row['qty_ordered']) ?></td>
                            <td class="text-end"><?= e((string)$row['qty_received']) ?></td>
                            <td class="text-end">₱<?= e(number_format((float)$row['unit_cost'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($canManagePo): ?>
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/<?= e((string)($extra['id'] ?? '')) ?>/approve">
                <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                <button class="btn btn-success btn-sm" type="submit">Approve</button>
            </form>
            <a href="<?= e(BASE_URL) ?>/purchase-orders/<?= e((string)($extra['id'] ?? '')) ?>/receive" class="btn btn-primary btn-sm">Record Delivery</a>
            <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/<?= e((string)($extra['id'] ?? '')) ?>/cancel">
                <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                <button class="btn btn-outline-danger btn-sm" type="submit">Cancel PO</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
