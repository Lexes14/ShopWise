<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║              SHOPWISE AI — AUTHENTICATION HELPER FUNCTIONS          ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Provides permission checking functions for views.
 */

declare(strict_types=1);

/**
 * Check if the current user has one of the allowed roles
 * 
 * @param array $allowedRoles  Array of role slugs to check against
 * @return bool  True if user has one of the allowed roles, false otherwise
 */
function canAccess(array $allowedRoles): bool
{
    $user = Auth::user();
    if (!$user) {
        return false;
    }
    
    return in_array($user['role_slug'], $allowedRoles, true);
}

/**
 * Check if current user can perform a specific permission key (module.action)
 * based on the central RBAC map in config/permissions.php.
 *
 * @param string $permission
 * @return bool
 */
function can(string $permission): bool
{
    $user = Auth::user();
    if (!$user) {
        return false;
    }

    $role = (string)($user['role_slug'] ?? '');
    if ($role === '') {
        return false;
    }

    if (!function_exists('hasPermission')) {
        return false;
    }

    return hasPermission($role, $permission);
}

/**
 * Check if the current user has a specific role
 * 
 * @param string $role  Role slug to check
 * @return bool  True if user has the role, false otherwise
 */
function hasRole(string $role): bool
{
    $user = Auth::user();
    if (!$user) {
        return false;
    }
    
    return $user['role_slug'] === $role;
}

/**
 * Get the current logged-in user
 * 
 * @return array|null  User array or null if not logged in
 */
function currentUser(): ?array
{
    return Auth::user();
}

/**
 * Check if the current user is logged in
 * 
 * @return bool
 */
function isLoggedIn(): bool
{
    return Auth::isLoggedIn();
}
