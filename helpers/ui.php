<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║              SHOPWISE AI — UI COMPONENT HELPERS                     ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Provides reusable UI layout components for consistent design.
 * Note: Basic formatting functions (peso, statusBadge, etc.) are in format.php
 */

declare(strict_types=1);

/**
 * Render a standardized page header with title, description, and action buttons
 * 
 * @param string $title Page title
 * @param string $description Optional description text
 * @param array $actions Optional array of action buttons
 * @return void
 */
function pageHeader(string $title, string $description = '', array $actions = []): void
{
    echo '<div class="sw-page-header">';
    echo '  <div class="sw-page-header-content">';
    echo '    <h1 class="sw-page-header-title">' . e($title) . '</h1>';
    if ($description) {
        echo '    <p class="sw-page-header-description">' . e($description) . '</p>';
    }
    echo '  </div>';
    
    if (!empty($actions)) {
        echo '  <div class="sw-page-header-actions">';
        foreach ($actions as $action) {
            $label = $action['label'] ?? 'Action';
            $href = $action['href'] ?? '#';
            $class = $action['class'] ?? 'btn btn-primary';
            $icon = $action['icon'] ?? '';
            
            echo '<a href="' . e($href) . '" class="' . e($class) . '">';
            if ($icon) {
                echo '<span style="margin-right: 8px;">' . $icon . '</span>';
            }
            echo e($label);
            echo '</a>';
        }
        echo '  </div>';
    }
    
    echo '</div>';
}

/**
 * Render a section header for content organization
 * 
 * @param string $title Section title
 * @param string $description Optional description
 * @return void
 */
function sectionHeader(string $title, string $description = ''): void
{
    echo '<div class="sw-section-header">';
    echo '  <h2 class="sw-section-title">' . e($title) . '</h2>';
    if ($description) {
        echo '  <p class="sw-text-muted sw-mb-0" style="margin-top: 4px; font-size: 13px;">' . e($description) . '</p>';
    }
    echo '</div>';
}

/**
 * Render an empty state placeholder
 * 
 * @param string $icon Emoji or icon
 * @param string $title Title text
 * @param string $description Description text
 * @param array $action Optional CTA button
 * @return void
 */
function emptyState(string $icon, string $title, string $description, array $action = []): void
{
    echo '<div class="sw-empty-state">';
    echo '  <div class="sw-empty-icon">' . $icon . '</div>';
    echo '  <h3 class="sw-empty-title">' . e($title) . '</h3>';
    echo '  <p class="sw-empty-description">' . e($description) . '</p>';
    
    if (!empty($action)) {
        $label = $action['label'] ?? 'Get Started';
        $href = $action['href'] ?? '#';
        $class = $action['class'] ?? 'btn btn-primary';
        
        echo '  <a href="' . e($href) . '" class="' . e($class) . '">' . e($label) . '</a>';
    }
    
    echo '</div>';
}

/**
 * Render a role badge with color coding
 * 
 * @param string $roleName Role name
 * @return string HTML badge
 */
function roleBadge(string $roleName): string
{
    $roleColors = [
        'Owner' => 'background: var(--sw-danger-light); color: var(--sw-danger);',
        'Manager' => 'background: var(--sw-warning-light); color: var(--sw-warning);',
        'Store Manager' => 'background: var(--sw-warning-light); color: var(--sw-warning);',
        'Inventory Staff' => 'background: var(--sw-info-light); color: var(--sw-info);',
        'Cashier' => 'background: var(--sw-success-light); color: var(--sw-success);',
        'Purchasing Officer' => 'background: var(--sw-primary-light); color: var(--sw-primary);',
        'Security' => 'background: var(--sw-surface2); color: var(--sw-text-muted);',
        'Security Personnel' => 'background: var(--sw-surface2); color: var(--sw-text-muted);',
        'Bookkeeper' => 'background: var(--sw-accent-light); color: var(--sw-accent-dark);',
        'Bookkeeper/Accountant' => 'background: var(--sw-accent-light); color: var(--sw-accent-dark);',
    ];
    
    $style = $roleColors[$roleName] ?? 'background: var(--sw-surface2); color: var(--sw-text-muted);';
    
    return '<span style="' . $style . ' padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">' 
           . e($roleName) 
           . '</span>';
}

/**
 * Render a card with header, body, and optional footer
 * 
 * @param string $title Card title
 * @param string $body Card body HTML content
 * @param string $footer Optional footer HTML content
 * @param array $headerActions Optional header action buttons
 * @return void
 */
function card(string $title, string $body, string $footer = '', array $headerActions = []): void
{
    echo '<div class="card card-soft">';
    
    // Header
    echo '  <div class="card-header" style="background: var(--sw-surface); border-bottom: 1px solid var(--sw-border); padding: 16px 20px;">';
    echo '    <div style="display: flex; justify-content: space-between; align-items: center;">';
    echo '      <h6 style="margin: 0; font-weight: 600;">' . e($title) . '</h6>';
    
    if (!empty($headerActions)) {
        echo '      <div style="display: flex; gap: 8px;">';
        foreach ($headerActions as $action) {
            echo $action;
        }
        echo '      </div>';
    }
    
    echo '    </div>';
    echo '  </div>';
    
    // Body
    echo '  <div class="card-body" style="padding: 24px;">';
    echo $body;
    echo '  </div>';
    
    // Footer (optional)
    if ($footer) {
        echo '  <div class="card-footer">';
        echo $footer;
        echo '  </div>';
    }
    
    echo '</div>';
}
