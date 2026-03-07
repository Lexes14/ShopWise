<?php $openShift = $extra['open_shift'] ?? null; ?>
<?php if ($openShift): ?>
    <div class="alert alert-warning">You already have an open shift (#<?= e((string)$openShift['shift_id']) ?>).</div>
<?php else: ?>
    <div class="card card-soft mb-3">
        <div class="card-header">Open Shift</div>
        <div class="card-body">
            <form method="POST" action="<?= e(BASE_URL) ?>/shifts/start" class="row g-3">
                <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                <div class="col-md-4">
                    <label class="form-label">Shift Label</label>
                    <select name="shift_label" class="form-select">
                        <?php foreach (['Morning','Afternoon','Evening','Graveyard','Custom'] as $label): ?>
                            <option value="<?= e($label) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Custom Label</label>
                    <input type="text" name="custom_label" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Opening Cash</label>
                    <input type="number" step="0.01" name="opening_cash" class="form-control" value="<?= e((string)POS_SHIFT_FLOAT) ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Start Shift</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
