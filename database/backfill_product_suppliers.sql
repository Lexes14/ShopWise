-- Backfill missing product primary suppliers
-- 1) Prefer supplier_products marked as primary
-- 2) Fallback to first supplier_products entry
-- 3) Fallback to first active supplier in suppliers table

UPDATE products p
LEFT JOIN (
    SELECT sp1.product_id, sp1.supplier_id
    FROM supplier_products sp1
    JOIN (
        SELECT product_id, MAX(is_primary) AS max_primary, MIN(sp_id) AS first_sp_id
        FROM supplier_products
        GROUP BY product_id
    ) pick ON pick.product_id = sp1.product_id
    WHERE (pick.max_primary = 1 AND sp1.is_primary = 1)
       OR (pick.max_primary = 0 AND sp1.sp_id = pick.first_sp_id)
) sp ON sp.product_id = p.product_id
LEFT JOIN (
    SELECT supplier_id
    FROM suppliers
    WHERE status = 'active'
    ORDER BY supplier_id ASC
    LIMIT 1
) fallback ON 1 = 1
SET p.primary_supplier_id = COALESCE(sp.supplier_id, fallback.supplier_id)
WHERE p.primary_supplier_id IS NULL;