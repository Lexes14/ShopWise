-- Update ai_recommendations table to support additional recommendation types
-- Run this SQL script in phpMyAdmin or MySQL client

-- Add new recommendation types: pricing, anomaly, bulk_order
ALTER TABLE ai_recommendations 
MODIFY COLUMN rec_type ENUM(
    'restock',
    'promotion',
    'dead_stock',
    'supplier',
    'staffing',
    'substitution',
    'pricing',
    'anomaly',
    'bulk_order'
) NOT NULL;

-- Optional: Clean up old pending recommendations older than 30 days
UPDATE ai_recommendations 
SET status = 'expired' 
WHERE status = 'pending' 
  AND generated_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Verify changes
SELECT rec_type, COUNT(*) as count, status
FROM ai_recommendations
GROUP BY rec_type, status
ORDER BY rec_type, status;
