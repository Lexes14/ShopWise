<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Shelf Locations</h5>
        <p class="text-muted small">Manage product shelf assignments and locations</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newShelfModal">+ New Shelf</button>
</div>

<!-- Shelf Grid -->
<div class="row g-3">
    <?php if (!empty($shelves)): ?>
        <?php foreach ($shelves as $shelf): ?>
            <div class="col-lg-4">
                <div class="card card-soft h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0"><?= e($shelf['shelf_code']) ?></h6>
                            <small class="text-muted"><?= e($shelf['aisle']) ?> | Row <?= e($shelf['row']) ?> | Col <?= e($shelf['column']) ?></small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= e((string)$shelf['shelf_id']) ?>" title="Delete Shelf">×</button>
                        
                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal<?= e((string)$shelf['shelf_id']) ?>" tabindex="-1">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h6 class="modal-title">Delete Shelf?</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Delete shelf <strong><?= e($shelf['shelf_code']) ?></strong>?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <form method="POST" action="<?= e(BASE_URL) ?>/inventory/shelves/<?= e((string)$shelf['shelf_id']) ?>/delete" style="display:inline;">
                                            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted">Assigned Products</small>
                            <?php
                            $productCount = isset($shelf['product_count']) ? (int)$shelf['product_count'] : 0;
                            ?>
                            <div class="h5 mb-0"><?= $productCount ?> products</div>
                        </div>
                        
                        <?php if ($productCount > 0 && isset($shelf['products'])): ?>
                            <div class="mb-3">
                                <div class="list-group list-group-sm">
                                    <?php foreach (array_slice($shelf['products'], 0, 3) as $product): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <small class="fw-600"><?= e($product['product_name']) ?></small>
                                                <div class="badge text-bg-secondary"><?= e($product['product_code']) ?></div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger p-0" style="width: 24px; height: 24px;" data-bs-toggle="modal" data-bs-target="#removeProductModal<?= e((string)$shelf['shelf_id']) ?>_<?= e((string)$product['product_id']) ?>" title="Remove product">×</button>
                                            
                                            <!-- Remove Product Modal -->
                                            <div class="modal fade" id="removeProductModal<?= e((string)$shelf['shelf_id']) ?>_<?= e((string)$product['product_id']) ?>" tabindex="-1">
                                                <div class="modal-dialog modal-sm">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h6 class="modal-title">Remove Product?</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Remove <strong><?= e($product['product_name']) ?></strong> from this shelf?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <form method="POST" action="<?= e(BASE_URL) ?>/inventory/shelves/<?= e((string)$shelf['shelf_id']) ?>/remove-product" style="display:inline;">
                                                                <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                                                <input type="hidden" name="product_id" value="<?= e((string)$product['product_id']) ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($productCount > 3): ?>
                                    <div class="text-center pt-2">
                                        <small class="text-muted">+ <?= ($productCount - 3) ?> more</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light text-center py-3 mb-0">
                                <small class="text-muted">Empty shelf</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer bg-light">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#assignProductModal<?= e((string)$shelf['shelf_id']) ?>">+ Assign Product</button>
                        
                        <!-- Assign Product Modal -->
                        <div class="modal fade" id="assignProductModal<?= e((string)$shelf['shelf_id']) ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Assign Product to <?= e($shelf['shelf_code']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="<?= e(BASE_URL) ?>/inventory/shelves/<?= e((string)$shelf['shelf_id']) ?>/assign">
                                        <div class="modal-body">
                                            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-600">Product *</label>
                                                <input type="text" class="form-control" id="productSearch<?= e((string)$shelf['shelf_id']) ?>" placeholder="Search product..." required>
                                                <input type="hidden" name="product_id" id="productId<?= e((string)$shelf['shelf_id']) ?>">
                                                <small class="text-muted">Start typing to search...</small>
                                            </div>
                                            
                                            <div id="searchResults<?= e((string)$shelf['shelf_id']) ?>" class="list-group" style="display:none; max-height: 200px; overflow-y: auto;"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Assign</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card card-soft">
                <div class="card-body text-center py-5">
                    <div class="text-muted mb-3" style="font-size: 48px;">📭</div>
                    <p class="text-muted">No shelves defined yet.</p>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newShelfModal">Create First Shelf</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- New Shelf Modal -->
<div class="modal fade" id="newShelfModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Shelf Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= e(BASE_URL) ?>/inventory/shelves/create">
                <div class="modal-body">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-600">Aisle *</label>
                                <input type="text" name="aisle" class="form-control" placeholder="e.g., A, B, Main" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-600">Shelf Code *</label>
                                <input type="text" name="shelf_code" class="form-control" placeholder="e.g., A1, B2" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-600">Row *</label>
                                <input type="number" name="row" class="form-control" min="1" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-600">Column *</label>
                                <input type="number" name="column" class="form-control" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-600">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional shelf notes or capacity info"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Shelf</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Product search for each shelf
const shelfIds = [<?php echo implode(',', array_map(fn($s) => e((string)$s['shelf_id']), $shelves ?? [])); ?>];

shelfIds.forEach(shelfId => {
    const input = document.getElementById(`productSearch${shelfId}`);
    const results = document.getElementById(`searchResults${shelfId}`);
    const hiddenInput = document.getElementById(`productId${shelfId}`);
    
    if (input) {
        input.addEventListener('input', function(e) {
            let query = e.target.value.trim();
            if (query.length < 2) {
                results.style.display = 'none';
                return;
            }
            
            fetch('<?= e(BASE_URL) ?>/products/search?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    results.innerHTML = '';
                    if (data.items && data.items.length > 0) {
                        data.items.slice(0, 5).forEach(item => {
                            let div = document.createElement('div');
                            div.className = 'list-group-item list-group-item-action cursor-pointer';
                            div.textContent = `${item.name} (${item.code})`;
                            div.onclick = () => {
                                input.value = item.name;
                                hiddenInput.value = item.id;
                                results.style.display = 'none';
                            };
                            results.appendChild(div);
                        });
                        results.style.display = 'block';
                    }
                });
        });
    }
});
</script>

<style>
    .cursor-pointer {
        cursor: pointer;
    }
</style>
