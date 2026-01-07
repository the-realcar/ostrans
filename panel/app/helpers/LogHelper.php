<?php
namespace App\Helpers;

use App\Core\Database;

/**
 * Activity logging helper
 */
class LogHelper {
    
    private static $db;
    
    public static function init($database) {
        self::$db = $database->pdo;
    }
    
    /**
     * Log an activity with user-agent tracking (F26)
     * @param int $user_id User who performed the action
     * @param string $action What happened (login, edit_employee, add_schedule, etc)
     * @param string $entity Entity type (pracownik, pojazd, grafik, etc)
     * @param int|null $entity_id ID of entity affected
     * @param array $data Optional metadata (old values, new values, etc)
     */
    public static function log($user_id, $action, $entity = null, $entity_id = null, $data = []) {
        if (!self::$db) return;
        
        try {
            // Create activity_log table if not exists - with user_agent column (F26)
            self::$db->exec("CREATE TABLE IF NOT EXISTS activity_log (
                id SERIAL PRIMARY KEY,
                user_id INT REFERENCES pracownicy(id),
                action VARCHAR(255),
                entity_type VARCHAR(100),
                entity_id INT,
                data JSONB,
                ip_address VARCHAR(50),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT NOW()
            )");
            
            // F26: Capture user-agent from request headers
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = self::$db->prepare('INSERT INTO activity_log (user_id, action, entity_type, entity_id, data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $user_id,
                $action,
                $entity,
                $entity_id,
                json_encode($data),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $userAgent
            ]);
        } catch (\Throwable $e) {
            error_log('LogHelper: ' . $e->getMessage());
        }
    }
    
    /**
     * Get activity log for a user or entity
     */
    public static function getLog($filters = []) {
        if (!self::$db) return [];
        
        $query = 'SELECT * FROM activity_log WHERE 1=1';
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $query .= ' AND user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['entity_type'])) {
            $query .= ' AND entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['entity_id'])) {
            $query .= ' AND entity_id = ?';
            $params[] = $filters['entity_id'];
        }
        
        $query .= ' ORDER BY created_at DESC LIMIT 500';
        
        try {
            $stmt = self::$db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
?>
