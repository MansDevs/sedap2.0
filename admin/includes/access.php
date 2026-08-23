<?php
/**
 * Role-based access helpers. Admin always has full access — every
 * other role is only let in where explicitly listed.
 */

/**
 * Should this nav item be shown to this role?
 */
function navItemAllowed(array $item, string $userRole): bool
{
    if ($userRole === 'admin') {
        return true;
    }
    return in_array($userRole, $item['roles'] ?? [], true);
}

/**
 * Gate an entire page to a set of roles. Admin always passes.
 * Call this BEFORE requiring header.php (it may redirect).
 */
function requireRole(array $currentUser, array $allowedRoles, string $adminBase): void
{
    if ($currentUser['role'] === 'admin') {
        return;
    }
    if (!in_array($currentUser['role'], $allowedRoles, true)) {
        header("Location: " . $adminBase . "dashboard.php?error=forbidden");
        exit();
    }
}
