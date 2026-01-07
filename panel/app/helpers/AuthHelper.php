<?php
namespace App\Helpers;

/**
 * Authorization/permission helper
 */
class AuthHelper {
    
    /**
     * Check if user has at least one of the required roles
     */
    public static function hasRole($user, $roles) {
        if (!$user || !isset($user['uprawnienie'])) return false;
        $roleArray = is_array($roles) ? $roles : [$roles];
        return in_array($user['uprawnienie'], $roleArray);
    }
    
    /**
     * Check if user is driver (kierowca)
     */
    public static function isDriver($user) {
        return self::hasRole($user, 'kierowca');
    }
    
    /**
     * Check if user is dispatcher or higher
     */
    public static function isDispatcher($user) {
        return self::hasRole($user, ['dyspozytor', 'zarzad']);
    }
    
    /**
     * Check if user is management
     */
    public static function isManagement($user) {
        return self::hasRole($user, 'zarzad');
    }
    
    /**
     * Require a specific role; exit with 403 if not authorized
     */
    public static function requireRole($user, $roles) {
        if (!self::hasRole($user, $roles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień']);
            exit;
        }
    }
}
?>
