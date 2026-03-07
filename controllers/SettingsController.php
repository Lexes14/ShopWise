<?php
declare(strict_types=1);

class SettingsController extends ModuleController
{
    protected string $module = 'settings';
    protected string $title = 'Settings';

    public function index(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value, setting_group FROM system_settings ORDER BY setting_group, setting_key");
        $this->moduleIndex($stmt->fetchAll());
    }

    public function updateStore(): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        $this->saveSettings('store', [
            'store_name' => (string)$this->post('store_name', STORE_NAME),
            'store_address' => (string)$this->post('store_address', STORE_ADDRESS),
            'store_phone' => (string)$this->post('store_phone', STORE_PHONE),
            'store_email' => (string)$this->post('store_email', STORE_EMAIL),
        ]);
        $this->done('Store settings updated.', '/settings');
    }

    public function updateTax(): void
    {
        $this->requireAuth(['owner', 'manager', 'bookkeeper']);
        Auth::csrfVerify();
        $this->saveSettings('tax', [
            'vat_rate' => (string)$this->post('vat_rate', VAT_RATE),
            'senior_pwd_discount' => (string)$this->post('senior_pwd_discount', SENIOR_PWD_DISCOUNT),
            'vat_exempt_senior_pwd' => (string)$this->post('vat_exempt_senior_pwd', SENIOR_PWD_VAT_EXEMPT ? '1' : '0'),
        ]);
        $this->done('Tax settings updated.', '/settings');
    }

    public function updatePOS(): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        $this->saveSettings('pos', [
            'pos_shift_float' => (string)$this->post('pos_shift_float', POS_SHIFT_FLOAT),
            'pos_variance_flag' => (string)$this->post('pos_variance_flag', POS_VARIANCE_FLAG),
            'pos_idle_lock_minutes' => (string)$this->post('pos_idle_lock_minutes', POS_IDLE_LOCK_MINUTES),
            'receipt_copies' => (string)$this->post('receipt_copies', POS_RECEIPT_COPIES),
        ]);
        $this->done('POS settings updated.', '/settings');
    }

    public function updateAI(): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        $this->saveSettings('ai', [
            'ai_safety_multiplier' => (string)$this->post('ai_safety_multiplier', AI_SAFETY_MULTIPLIER),
            'ai_max_promo_discount' => (string)$this->post('ai_max_promo_discount', AI_MAX_PROMO_DISCOUNT),
            'ai_dead_stock_days' => (string)$this->post('ai_dead_stock_days', AI_DEAD_STOCK_DAYS),
            'ai_forecast_horizon' => (string)$this->post('ai_forecast_horizon', AI_FORECAST_HORIZON),
        ]);
        $this->done('AI settings updated.', '/settings');
    }

    private function saveSettings(string $group, array $pairs): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO system_settings (setting_key, setting_value, setting_group, description, updated_by, updated_at)
             VALUES (?, ?, ?, NULL, ?, NOW())
             ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                setting_group = VALUES(setting_group),
                updated_by = VALUES(updated_by),
                updated_at = NOW()"
        );

        $userId = (int)$this->user()['user_id'];
        foreach ($pairs as $key => $value) {
            $stmt->execute([$key, (string)$value, $group, $userId]);
        }

        $logger = new Logger();
        $logger->log('settings', 'update_' . $group, null, null, $pairs, 'Settings updated.');
    }
}
