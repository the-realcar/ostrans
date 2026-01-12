<?php
namespace App\Helpers;

/**
 * Security Helper - XSS Protection and Output Escaping
 * 
 * Helper functions for secure output in HTML views
 */
class SecurityHelper
{
    /**
     * Escape HTML output (XSS protection)
     * Use this for all user-generated content displayed in HTML
     */
    public static function escape($string, $encoding = 'UTF-8')
    {
        if ($string === null || $string === '') {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, $encoding);
    }
    
    /**
     * Alias for escape() - shorter syntax
     */
    public static function e($string, $encoding = 'UTF-8')
    {
        return self::escape($string, $encoding);
    }
    
    /**
     * Escape for use in HTML attributes
     */
    public static function escapeAttr($string, $encoding = 'UTF-8')
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, $encoding);
    }
    
    /**
     * Escape for use in JavaScript strings
     */
    public static function escapeJs($string)
    {
        return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    /**
     * Escape for use in URLs
     */
    public static function escapeUrl($url)
    {
        return htmlspecialchars(urlencode($url), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize filename (remove dangerous characters)
     */
    public static function sanitizeFilename($filename)
    {
        // Remove any path components
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        return $filename;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token)
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize input (remove dangerous HTML)
     */
    public static function sanitizeInput($input)
    {
        return strip_tags($input);
    }
    
    /**
     * Validate email
     */
    public static function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Rate limiting check (simple implementation)
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300)
    {
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $key = md5($identifier);
        $now = time();
        
        // Clean old entries
        $_SESSION['rate_limit'] = array_filter($_SESSION['rate_limit'], function($timestamp) use ($now, $timeWindow) {
            return $timestamp > ($now - $timeWindow);
        });
        
        // Count attempts
        $attempts = isset($_SESSION['rate_limit'][$key]) ? count($_SESSION['rate_limit'][$key]) : 0;
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        // Record attempt
        if (!isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = [];
        }
        $_SESSION['rate_limit'][$key][] = $now;
        
        return true;
    }
}
