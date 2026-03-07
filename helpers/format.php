<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║               SHOPWISE AI — FORMATTING HELPER FUNCTIONS             ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Provides formatting functions for currency, dates, numbers, and UI elements.
 */

declare(strict_types=1);

/**
 * Format number as Philippine Peso currency
 * 
 * @param float $amount
 * @return string
 */
function peso(float $amount): string
{
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

/**
 * Format number as percentage
 * 
 * @param float $value
 * @param int $decimals
 * @return string
 */
function percent(float $value, int $decimals = 2): string
{
    return number_format($value, $decimals) . '%';
}

/**
 * Format date for display
 * 
 * @param string $date
 * @return string
 */
function dateDisplay(string $date): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    return date(DATE_FORMAT_DISPLAY, strtotime($date));
}

/**
 * Format datetime for display
 * 
 * @param string $datetime
 * @return string
 */
function datetimeDisplay(string $datetime): string
{
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '—';
    }
    return date(DATETIME_FORMAT_DISPLAY, strtotime($datetime));
}

/**
 * Format time for display
 * 
 * @param string $time
 * @return string
 */
function timeDisplay(string $time): string
{
    if (empty($time)) {
        return '—';
    }
    return date(TIME_FORMAT_DISPLAY, strtotime($time));
}

/**
 * Calculate days until a given date
 * 
 * @param string $date
 * @return int
 */
function daysUntil(string $date): int
{
    $now = new DateTime();
    $target = new DateTime($date);
    $interval = $now->diff($target);
    return (int) $interval->format('%r%a');
}

/**
 * Calculate days since a given date
 * 
 * @param string $date
 * @return int
 */
function daysSince(string $date): int
{
    return abs(daysUntil($date));
}

/**
 * Display relative time (e.g., "2 hours ago")
 * 
 * @param string $datetime
 * @return string
 */
function timeAgo(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return dateDisplay($datetime);
    }
}

/**
 * Generate stock status badge HTML
 * 
 * @param int $current
 * @param int $minimum
 * @return string
 */
function stockBadge(int $current, int $minimum): string
{
    if ($current <= 0) {
        return '<span class="sw-badge sw-badge-danger">🔴 Out of Stock</span>';
    } elseif ($current <= $minimum) {
        return '<span class="sw-badge sw-badge-warning">🟡 Low Stock (' . $current . ')</span>';
    } else {
        return '<span class="sw-badge sw-badge-success">🟢 In Stock (' . $current . ')</span>';
    }
}

/**
 * Generate expiry badge HTML
 * 
 * @param string $expiryDate
 * @return string
 */
function expiryBadge(string $expiryDate): string
{
    if (empty($expiryDate) || $expiryDate === '0000-00-00') {
        return '<span class="sw-badge sw-badge-muted">No Expiry</span>';
    }
    
    $days = daysUntil($expiryDate);
    
    if ($days < 0) {
        return '<span class="sw-badge sw-badge-danger">⚠️ Expired</span>';
    } elseif ($days <= 7) {
        return '<span class="sw-badge sw-badge-danger">🔴 ' . $days . ' days left</span>';
    } elseif ($days <= 14) {
        return '<span class="sw-badge sw-badge-warning">🟡 ' . $days . ' days left</span>';
    } elseif ($days <= 30) {
        return '<span class="sw-badge sw-badge-info">🔵 ' . $days . ' days left</span>';
    } else {
        return '<span class="sw-badge sw-badge-success">✓ Expires ' . dateDisplay($expiryDate) . '</span>';
    }
}

/**
 * Truncate text to specified length
 * 
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate(string $text, int $length = 50, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Format number with comma separator
 * 
 * @param float $number
 * @param int $decimals
 * @return string
 */
function formatNumber(float $number, int $decimals = 0): string
{
    return number_format($number, $decimals);
}

/**
 * Format file size
 * 
 * @param int $bytes
 * @return string
 */
function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Generate status badge HTML
 * 
 * @param string $status
 * @param array $config Map of status => [label, class]
 * @return string
 */
function statusBadge(string $status, array $config = []): string
{
    $defaultConfigs = [
        'active' => ['Active', 'success'],
        'inactive' => ['Inactive', 'muted'],
        'pending' => ['Pending', 'warning'],
        'approved' => ['Approved', 'success'],
        'rejected' => ['Rejected', 'danger'],
        'completed' => ['Completed', 'success'],
        'cancelled' => ['Cancelled', 'muted'],
        'open' => ['Open', 'info'],
        'closed' => ['Closed', 'muted'],
    ];
    
    $allConfigs = array_merge($defaultConfigs, $config);
    
    if (isset($allConfigs[$status])) {
        [$label, $class] = $allConfigs[$status];
        return '<span class="sw-badge sw-badge-' . $class . '">' . htmlspecialchars($label) . '</span>';
    }
    
    // Default fallback
    return '<span class="sw-badge sw-badge-muted">' . htmlspecialchars(ucfirst($status)) . '</span>';
}

/**
 * Generate urgency badge HTML
 * 
 * @param string $urgency
 * @return string
 */
function urgencyBadge(string $urgency): string
{
    $badges = [
        'critical' => '<span class="sw-badge sw-badge-danger">🔴 CRITICAL</span>',
        'urgent' => '<span class="sw-badge sw-badge-warning">🟡 URGENT</span>',
        'normal' => '<span class="sw-badge sw-badge-info">🔵 NORMAL</span>',
        'monitor' => '<span class="sw-badge sw-badge-muted">⚪ MONITOR</span>',
    ];
    
    return $badges[strtolower($urgency)] ?? '<span class="sw-badge sw-badge-muted">' . htmlspecialchars($urgency) . '</span>';
}

/**
 * Generate confidence score bar HTML
 * 
 * @param float $score 0-100
 * @return string
 */
function confidenceBar(float $score): string
{
    $color = 'danger';
    if ($score >= 80) {
        $color = 'success';
    } elseif ($score >= 60) {
        $color = 'warning';
    }
    
    $html = '<div class="confidence-bar">';
    $html .= '<div class="confidence-bar-fill confidence-bar-' . $color . '" style="width: ' . $score . '%"></div>';
    $html .= '</div>';
    $html .= '<span class="confidence-score">' . number_format($score, 1) . '%</span>';
    
    return $html;
}

/**
 * Pluralize a word based on count
 * 
 * @param int $count
 * @param string $singular
 * @param string|null $plural
 * @return string
 */
function pluralize(int $count, string $singular, ?string $plural = null): string
{
    if ($count === 1) {
        return $count . ' ' . $singular;
    }
    
    $plural = $plural ?? $singular . 's';
    return $count . ' ' . $plural;
}

/**
 * Generate emoji for category
 * 
 * @param string $category
 * @return string
 */
function categoryEmoji(string $category): string
{
    $emojis = [
        'snacks' => '🍪',
        'beverages' => '🥤',
        'personal care' => '🧴',
        'household' => '🧹',
        'canned goods' => '🥫',
        'noodles' => '🍜',
        'dairy' => '🥛',
        'medicine' => '💊',
        'frozen' => '🧊',
        'bread' => '🍞',
        'condiments' => '🧂',
        'candy' => '🍬',
        'chips' => '🥔',
    ];
    
    $key = strtolower($category);
    return $emojis[$key] ?? '📦';
}

/**
 * Generate avatar initials HTML
 * 
 * @param string $name
 * @return string
 */
function avatarInitials(string $name): string
{
    $parts = explode(' ', $name);
    $initials = '';
    
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    
    return '<div class="sw-avatar">' . htmlspecialchars($initials) . '</div>';
}
