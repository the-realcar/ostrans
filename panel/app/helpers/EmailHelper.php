<?php
namespace App\Helpers;

class EmailHelper
{
    private static $smtpHost;
    private static $smtpPort;
    private static $smtpUser;
    private static $smtpPass;
    private static $smtpFrom;
    
    public static function init($config = [])
    {
        // Load from config or environment
        self::$smtpHost = $config['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? 'localhost';
        self::$smtpPort = $config['SMTP_PORT'] ?? getenv('SMTP_PORT') ?? 587;
        self::$smtpUser = $config['SMTP_USER'] ?? getenv('SMTP_USER') ?? '';
        self::$smtpPass = $config['SMTP_PASS'] ?? getenv('SMTP_PASS') ?? '';
        self::$smtpFrom = $config['SMTP_FROM'] ?? getenv('SMTP_FROM') ?? 'noreply@ostrans.local';
    }
    
    /**
     * Send email via SMTP
     * @param string $to Email address
     * @param string $subject Email subject
     * @param string $body HTML or plain text body
     * @param array $options Additional options (cc, bcc, replyTo, isHtml)
     * @return array [success, error]
     */
    public static function send($to, $subject, $body, $options = [])
    {
        if (!self::$smtpHost) {
            return [false, 'SMTP not configured'];
        }
        
        $isHtml = $options['isHtml'] ?? true;
        
        // Prepare headers
        $headers = "From: " . self::$smtpFrom . "\r\n";
        $headers .= "Reply-To: " . (self::$smtpFrom) . "\r\n";
        $headers .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        
        if (isset($options['cc'])) {
            $headers .= "Cc: " . $options['cc'] . "\r\n";
        }
        if (isset($options['bcc'])) {
            $headers .= "Bcc: " . $options['bcc'] . "\r\n";
        }
        
        try {
            // Try to use PHP mail() with SMTP settings
            if (self::$smtpUser && self::$smtpPass) {
                // Use simple mail function (requires server SMTP config)
                $success = mail($to, $subject, $body, $headers);
            } else {
                // Fallback to basic mail
                $success = mail($to, $subject, $body, $headers);
            }
            
            if ($success) {
                return [true, null];
            } else {
                return [false, 'mail_send_failed'];
            }
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }
    }
    
    /**
     * Send password reset email
     * @param string $email User email
     * @param string $resetToken Reset token
     * @param string $appUrl Base URL of application
     * @return array [success, error]
     */
    public static function sendPasswordReset($email, $resetToken, $appUrl = 'http://localhost')
    {
        $resetLink = $appUrl . '/reset-password?token=' . urlencode($resetToken);
        
        $subject = 'PPUT Ostrans - Resetowanie hasła';
        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Resetowanie hasła</h2>
    <p>Otrzymaliśmy prośbę o resetowanie Twojego hasła do systemu PPUT Ostrans.</p>
    <p>Kliknij poniższy link, aby ustawić nowe hasło:</p>
    <p><a href="{$resetLink}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Resetuj hasło</a></p>
    <p>Link wygasa za 1 godzinę.</p>
    <p>Jeśli nie prosiłeś o resetowanie hasła, zignoruj tę wiadomość.</p>
    <hr>
    <p><small>PPUT Ostrans - System Zarządzania Transportem</small></p>
</body>
</html>
HTML;
        
        return self::send($email, $subject, $body, ['isHtml' => true]);
    }
    
    /**
     * Send request approval notification
     * @param string $email User email
     * @param string $typWniosku Request type
     * @param string $status Approval status (zaakceptowany/odrzucony)
     * @return array [success, error]
     */
    public static function sendRequestNotification($email, $typWniosku, $status)
    {
        $statusPL = ($status === 'zaakceptowany') ? 'zaakceptowany' : 'odrzucony';
        $colorStatus = ($status === 'zaakceptowany') ? '#28a745' : '#dc3545';
        
        $subject = 'PPUT Ostrans - Wniosek ' . $statusPL;
        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Status wniosku</h2>
    <p>Twój wniosek typu <strong>{$typWniosku}</strong> został <span style="color: {$colorStatus}; font-weight: bold;">{$statusPL}</span>.</p>
    <p>Zaloguj się do systemu PPUT Ostrans, aby zobaczyć szczegóły.</p>
    <hr>
    <p><small>PPUT Ostrans - System Zarządzania Transportem</small></p>
</body>
</html>
HTML;
        
        return self::send($email, $subject, $body, ['isHtml' => true]);
    }
}
