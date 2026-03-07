<div class="card card-soft mb-3">
    <div class="card-header">Record Delivery for PO #<?= e((string)($extra['id'] ?? '')) ?></div>
    <div class="card-body">
        <form method="POST" action="<?= e(BASE_URL) ?>/purchase-orders/<?= e((string)($extra['id'] ?? '')) ?>/record-delivery" class="row g-3">
            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
            <div class="col-md-4">
                <label class="form-label">Delivery Date</label>
                <input type="date" name="delivery_date" class="form-control" value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Delivery Items (JSON)</label>
                <textarea name="items" class="form-control" rows="6" placeholder='[{"po_item_id":1,"product_id":1,"qty_received":10,"unit_cost":20,"batch_number":"B-001","expiration_date":"2026-12-31"}]'></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save Delivery</button>
                <a href="<?= e(BASE_URL) ?>/purchase-orders/<?= e((string)($extra['id'] ?? '')) ?>" class="btn btn-outline-secondary">Back</a>
            </div>
        </form>
    </div>
</div>
