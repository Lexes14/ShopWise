-- Update batch expiration dates to be relative to March 10, 2026
-- Run this in phpMyAdmin to see expiring items properly

-- Batch 1: Expired 5 days ago (March 5, 2026)
UPDATE batches SET expiration_date = '2026-03-05', mfg_date = '2025-09-05', delivery_date = '2025-09-10' WHERE batch_id = 1;

-- Batch 2: Critical - expires in 3 days (March 13, 2026)
UPDATE batches SET expiration_date = '2026-03-13', mfg_date = '2025-09-13', delivery_date = '2025-09-18' WHERE batch_id = 2;

-- Batch 3: Critical - expires in 6 days (March 16, 2026)
UPDATE batches SET expiration_date = '2026-03-16', mfg_date = '2025-09-16', delivery_date = '2025-09-20' WHERE batch_id = 3;

-- Batch 4: Far future - expires in 300 days (OK)
UPDATE batches SET expiration_date = '2027-01-05', mfg_date = '2026-01-05', delivery_date = '2026-02-01' WHERE batch_id = 4;

-- Batch 5: Urgent - expires in 10 days (March 20, 2026)
UPDATE batches SET expiration_date = '2026-03-20', mfg_date = '2025-09-20', delivery_date = '2025-10-01' WHERE batch_id = 5;

-- Batch 6: Urgent - expires in 12 days (March 22, 2026)
UPDATE batches SET expiration_date = '2026-03-22', mfg_date = '2025-09-22', delivery_date = '2025-10-05' WHERE batch_id = 6;

-- Batch 7: Far future - OK
UPDATE batches SET expiration_date = '2027-02-10', mfg_date = '2026-02-10', delivery_date = '2026-02-15' WHERE batch_id = 7;

-- Batch 8: Expired 33 days ago (February 5, 2026)
UPDATE batches SET expiration_date = '2026-02-05', mfg_date = '2025-08-05', delivery_date = '2025-08-10' WHERE batch_id = 8;

-- Batch 9: Warning - expires in 20 days (March 30, 2026)
UPDATE batches SET expiration_date = '2026-03-30', mfg_date = '2025-09-30', delivery_date = '2025-10-10' WHERE batch_id = 9;

-- Batch 10: Warning - expires in 25 days (April 4, 2026)
UPDATE batches SET expiration_date = '2026-04-04', mfg_date = '2025-10-04', delivery_date = '2025-10-15' WHERE batch_id = 10;
