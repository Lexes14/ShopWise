<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║              SHOPWISE AI — PAGINATION HELPER FUNCTIONS              ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Provides pagination functions for list views.
 */

declare(strict_types=1);

/**
 * Generate pagination metadata
 * 
 * @param int $total Total number of records
 * @param int $page Current page
 * @param int $perPage Records per page
 * @param string $baseUrl Base URL for pagination links
 * @return array Pagination metadata
 */
function paginate(int $total, int $page = 1, int $perPage = 25, string $baseUrl = ''): array
{
    $perPage = max(1, $perPage);
    $page = max(1, $page);
    $lastPage = (int) ceil($total / $perPage);
    $lastPage = max(1, $lastPage);
    $page = min($page, $lastPage);
    
    $from = ($page - 1) * $perPage + 1;
    $to = min($page * $perPage, $total);
    
    // Calculate visible page numbers
    $maxLinks = PAGINATION_MAX_LINKS ?? 7;
    $halfMax = (int) floor($maxLinks / 2);
    
    $startPage = max(1, $page - $halfMax);
    $endPage = min($lastPage, $page + $halfMax);
    
    // Adjust if we're near the start or end
    if ($page <= $halfMax) {
        $endPage = min($lastPage, $maxLinks);
    } elseif ($page >= $lastPage - $halfMax) {
        $startPage = max(1, $lastPage - $maxLinks + 1);
    }
    
    $pages = range($startPage, $endPage);
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'last_page' => $lastPage,
        'from' => $from,
        'to' => $to,
        'has_prev' => $page > 1,
        'has_next' => $page < $lastPage,
        'prev_page' => max(1, $page - 1),
        'next_page' => min($lastPage, $page + 1),
        'base_url' => $baseUrl,
        'pages' => $pages,
        'show_first' => $startPage > 1,
        'show_last' => $endPage < $lastPage
    ];
}

/**
 * Generate pagination HTML
 * 
 * @param array $meta Pagination metadata from paginate()
 * @return string HTML pagination component
 */
function paginationHtml(array $meta): string
{
    if ($meta['total'] === 0) {
        return '';
    }
    
    $html = '<div class="sw-pagination-wrapper">';
    
    // Showing X to Y of Z results
    $html .= '<div class="sw-pagination-info">';
    $html .= sprintf(
        'Showing <strong>%d</strong> to <strong>%d</strong> of <strong>%d</strong> results',
        $meta['from'],
        $meta['to'],
        $meta['total']
    );
    $html .= '</div>';
    
    // Only show pagination if more than one page
    if ($meta['last_page'] > 1) {
        $html .= '<nav class="sw-pagination" aria-label="Pagination">';
        $html .= '<ul class="sw-pagination-list">';
        
        // Previous button
        $prevClass = $meta['has_prev'] ? '' : 'disabled';
        $prevUrl = $meta['has_prev'] ? buildPaginationUrl($meta['base_url'], $meta['prev_page']) : '#';
        $html .= '<li class="sw-pagination-item ' . $prevClass . '">';
        $html .= '<a href="' . htmlspecialchars($prevUrl) . '" class="sw-pagination-link">';
        $html .= '<span aria-hidden="true">« Prev</span>';
        $html .= '</a></li>';
        
        // First page
        if ($meta['show_first']) {
            $html .= '<li class="sw-pagination-item">';
            $html .= '<a href="' . htmlspecialchars(buildPaginationUrl($meta['base_url'], 1)) . '" class="sw-pagination-link">1</a>';
            $html .= '</li>';
            $html .= '<li class="sw-pagination-item disabled"><span class="sw-pagination-ellipsis">...</span></li>';
        }
        
        // Page numbers
        foreach ($meta['pages'] as $pageNum) {
            $activeClass = $pageNum === $meta['current_page'] ? 'active' : '';
            $html .= '<li class="sw-pagination-item ' . $activeClass . '">';
            
            if ($pageNum === $meta['current_page']) {
                $html .= '<span class="sw-pagination-link sw-pagination-current">' . $pageNum . '</span>';
            } else {
                $url = buildPaginationUrl($meta['base_url'], $pageNum);
                $html .= '<a href="' . htmlspecialchars($url) . '" class="sw-pagination-link">' . $pageNum . '</a>';
            }
            
            $html .= '</li>';
        }
        
        // Last page
        if ($meta['show_last']) {
            $html .= '<li class="sw-pagination-item disabled"><span class="sw-pagination-ellipsis">...</span></li>';
            $html .= '<li class="sw-pagination-item">';
            $html .= '<a href="' . htmlspecialchars(buildPaginationUrl($meta['base_url'], $meta['last_page'])) . '" class="sw-pagination-link">' . $meta['last_page'] . '</a>';
            $html .= '</li>';
        }
        
        // Next button
        $nextClass = $meta['has_next'] ? '' : 'disabled';
        $nextUrl = $meta['has_next'] ? buildPaginationUrl($meta['base_url'], $meta['next_page']) : '#';
        $html .= '<li class="sw-pagination-item ' . $nextClass . '">';
        $html .= '<a href="' . htmlspecialchars($nextUrl) . '" class="sw-pagination-link">';
        $html .= '<span aria-hidden="true">Next »</span>';
        $html .= '</a></li>';
        
        $html .= '</ul>';
        $html .= '</nav>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Build pagination URL with page parameter
 * 
 * @param string $baseUrl
 * @param int $page
 * @return string
 */
function buildPaginationUrl(string $baseUrl, int $page): string
{
    // Parse existing query parameters
    $parsedUrl = parse_url($baseUrl);
    $queryParams = [];
    
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParams);
    }
    
    // Set page parameter
    $queryParams['page'] = $page;
    
    // Rebuild URL
    $url = $parsedUrl['path'] ?? '';
    $queryString = http_build_query($queryParams);
    
    if (!empty($queryString)) {
        $url .= '?' . $queryString;
    }
    
    return $url;
}

/**
 * Get current page number from request
 * 
 * @return int
 */
function getCurrentPage(): int
{
    $page = $_GET['page'] ?? 1;
    return max(1, (int) $page);
}

/**
 * Get per page value from request (with validation)
 * 
 * @param int $default
 * @return int
 */
function getPerPage(int $default = 25): int
{
    $perPage = $_GET['per_page'] ?? $default;
    $perPage = (int) $perPage;
    
    // Validate against allowed options
    $allowed = PER_PAGE_OPTIONS ?? [10, 25, 50, 100];
    
    if (!in_array($perPage, $allowed)) {
        $perPage = $default;
    }
    
    return $perPage;
}

/**
 * Generate per-page selector HTML
 * 
 * @param int $current Current per-page value
 * @param string $baseUrl Base URL for links
 * @return string
 */
function perPageSelector(int $current = 25, string $baseUrl = ''): string
{
    $options = PER_PAGE_OPTIONS ?? [10, 25, 50, 100];
    
    $html = '<div class="sw-per-page-selector">';
    $html .= '<label for="per-page-select">Show:</label>';
    $html .= '<select id="per-page-select" class="sw-select sw-select-sm" onchange="window.location=this.value">';
    
    foreach ($options as $option) {
        $selected = $option === $current ? 'selected' : '';
        $url = buildPerPageUrl($baseUrl, $option);
        $html .= sprintf(
            '<option value="%s" %s>%d per page</option>',
            htmlspecialchars($url),
            $selected,
            $option
        );
    }
    
    $html .= '</select>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Build URL with per_page parameter
 * 
 * @param string $baseUrl
 * @param int $perPage
 * @return string
 */
function buildPerPageUrl(string $baseUrl, int $perPage): string
{
    $parsedUrl = parse_url($baseUrl);
    $queryParams = [];
    
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParams);
    }
    
    $queryParams['per_page'] = $perPage;
    $queryParams['page'] = 1; // Reset to first page when changing per-page
    
    $url = $parsedUrl['path'] ?? '';
    $queryString = http_build_query($queryParams);
    
    if (!empty($queryString)) {
        $url .= '?' . $queryString;
    }
    
    return $url;
}
