<?php $openShift = $extra['open_shift'] ?? null; ?>
<?php if (!$openShift): ?>
    <div class="alert alert-info">No open shift found.</div>
<?php else: ?>
    <div class="card card-soft mb-3">
        <div class="card-header">Close Shift #<?= e((string)$openShift['shift_id']) ?></div>
        <div class="card-body">
            <p class="mb-2">Expected Cash (opening + cash sales): <strong>₱<?= e(number_format((float)$openShift['opening_cash'] + (float)$openShift['total_cash_sales'], 2)) ?></strong></p>
            <form method="POST" action="<?= e(BASE_URL) ?>/shifts/end" class="row g-3">
                <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                <div class="col-md-4">
                    <label class="form-label">Closing Cash Count</label>
                    <input type="number" step="0.01" name="closing_cash" class="form-control" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-danger">Close Shift</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
