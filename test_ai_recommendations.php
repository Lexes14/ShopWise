<?php
/**
 * Test AI Recommendation Engine
 * Run this once to populate initial recommendations
 */

// Bootstrap
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('CORE_PATH', ROOT_PATH . 'core' . DIRECTORY_SEPARATOR);
define('MODEL_PATH', ROOT_PATH . 'models' . DIRECTORY_SEPARATOR);

// Load configuration
require_once CONFIG_PATH . 'app.php';
require_once CONFIG_PATH . 'database.php';

// Load core classes
require_once CORE_PATH . 'Model.php';

// Load AI Model
require_once MODEL_PATH . 'AIModel.php';

echo "=== AI Recommendation Engine Test ===\n\n";

$aiModel = new AIModel();

try {
    echo "1. Generating dead stock recommendations...\n";
    $db = Database::getInstance();
    
    // Manually trigger dead stock analysis
    $aiModel->analyzeAction('inventory', 'finalize_stocktake', null, null);
    echo "   ✓ Dead stock analysis completed\n\n";
    
    // Count recommendations
    $count = $db->query(
        "SELECT COUNT(*) FROM ai_recommendations WHERE status = 'pending'"
    )->fetchColumn();
    
    echo "2. Checking recommendations in database...\n";
    echo "   Total pending recommendations: {$count}\n\n";
    
    if ($count > 0) {
        echo "3. Recent recommendations:\n";
        $recent = $db->query(
            "SELECT rec_type, LEFT(recommendation, 80) as rec_preview, urgency, confidence_score, generated_at
             FROM ai_recommendations
             WHERE status = 'pending'
             ORDER BY generated_at DESC
             LIMIT 5"
        )->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($recent as $rec) {
            echo "   [{$rec['rec_type']}] {$rec['rec_preview']}...\n";
            echo "   Urgency: {$rec['urgency']}, Confidence: {$rec['confidence_score']}%\n";
            echo "   Generated: {$rec['generated_at']}\n\n";
        }
    } else {
        echo "3. No recommendations yet. AI will generate them based on:\n";
        echo "   - Sales transactions (stock replenishment)\n";
        echo "   - Purchase order actions (supplier performance)\n";
        echo "   - Inventory adjustments (anomaly detection)\n";
        echo "   - Product sales (pricing optimization)\n";
        echo "   - Product associations (bundling opportunities)\n\n";
        echo "   Recommendations will appear automatically as you use the system.\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
