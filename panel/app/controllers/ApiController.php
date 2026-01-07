<?php
namespace App\Controllers;

use App\Core\Database;
use App\Helpers\AuthHelper;
use App\Helpers\LogHelper;

class ApiController
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db->pdo;
        LogHelper::init($db);
    }

    public function login($login, $password, $jwtSecret)
    {
        $stmt = $this->db->prepare('SELECT p.*, u.poziom AS uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.login = :login AND p.is_active = true LIMIT 1');
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();
        if (!$user) {
            return [null, 'invalid'];
        }
        $stored = $user['haslo'] ?? '';
        $ok = false;
        if (strpos($stored, '$2') === 0) {
            $ok = password_verify($password, $stored);
        } else {
            $ok = ($password === $stored);
        }
        if (!$ok) return [null, 'invalid'];
        
        // Log login activity
        LogHelper::log($user['id'], 'login', 'pracownik', $user['id'], ['ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
        
        unset($user['haslo']);
        $token = $this->signJwt([
            'id' => $user['id'],
            'login' => $user['login'],
            'uprawnienie' => $user['uprawnienie'],
            'iat' => time(),
            'exp' => time() + 8 * 3600
        ], $jwtSecret);
        return [
            ['token' => $token, 'user' => [
                'id' => $user['id'],
                'imie' => $user['imie'],
                'nazwisko' => $user['nazwisko'],
                'login' => $user['login'],
                'uprawnienie' => $user['uprawnienie'],
            ]],
            null
        ];
    }

    public function me($userId)
    {
        $stmt = $this->db->prepare('SELECT p.id, p.imie, p.nazwisko, p.login, u.poziom as uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.id=:id AND p.is_active = true');
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Request password reset - sends reset token (simplified version)
     */
    public function requestPasswordReset($login)
    {
        $stmt = $this->db->prepare('SELECT id, email FROM pracownicy WHERE login = :login AND is_active = true LIMIT 1');
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();
        if (!$user) return [null, 'not_found'];
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Save reset token
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS password_resets (id SERIAL PRIMARY KEY, user_id INT REFERENCES pracownicy(id), token VARCHAR(64), expires_at TIMESTAMP)');
            $stmt = $this->db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $token, $expiresAt]);
        } catch (\Throwable $e) { /* ignore */ }
        
        LogHelper::log($user['id'], 'request_password_reset', 'pracownik', $user['id']);
        
        return [['token' => $token, 'email' => $user['email'] ?? null], null];
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword)
    {
        if (!$token || !$newPassword) return [null, 'missing fields'];
        
        $stmt = $this->db->prepare('SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) return [null, 'invalid_or_expired_token'];
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('UPDATE pracownicy SET haslo = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $reset['user_id']]);
        
        // Delete used token
        $stmt = $this->db->prepare('DELETE FROM password_resets WHERE token = ?');
        $stmt->execute([$token]);
        
        LogHelper::log($reset['user_id'], 'reset_password', 'pracownik', $reset['user_id']);
        
        return [[true], null];
    }
    
    /**
     * Change password (authenticated user)
     */
    public function changePassword($userId, $oldPassword, $newPassword)
    {
        if (!$oldPassword || !$newPassword) return [null, 'missing fields'];
        if (strlen($newPassword) < 6) return [null, 'password_too_short'];
        
        $stmt = $this->db->prepare('SELECT haslo FROM pracownicy WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) return [null, 'user_not_found'];
        
        // Verify old password
        $ok = false;
        if (strpos($user['haslo'], '$2') === 0) {
            $ok = password_verify($oldPassword, $user['haslo']);
        } else {
            $ok = ($oldPassword === $user['haslo']);
        }
        if (!$ok) return [null, 'invalid_old_password'];
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('UPDATE pracownicy SET haslo = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $userId]);
        
        LogHelper::log($userId, 'change_password', 'pracownik', $userId);
        
        return [[true], null];
    }

    public function getWnioski($reqUser, $userId = null)
    {
        if ($reqUser['uprawnienie'] === 'kierowca') {
            $stmt = $this->db->prepare('SELECT * FROM wnioski WHERE pracownik_id=:id ORDER BY data_zlozenia DESC');
            $stmt->execute(['id' => $reqUser['id']]);
            return $stmt->fetchAll();
        }
        if ($userId) {
            $stmt = $this->db->prepare('SELECT * FROM wnioski WHERE pracownik_id=:id ORDER BY data_zlozenia DESC');
            $stmt->execute(['id' => $userId]);
            return $stmt->fetchAll();
        }
        $stmt = $this->db->query('SELECT * FROM wnioski ORDER BY data_zlozenia DESC LIMIT 500');
        return $stmt->fetchAll();
    }

    public function addWniosek($reqUser, $payload)
    {
        $typ = $payload['typ'] ?? null;
        if (!$typ) return [null, 'missing typ'];
        $stmt = $this->db->prepare('INSERT INTO wnioski (pracownik_id, typ, opis) VALUES (:pid,:typ,:opis) RETURNING *');
        $stmt->execute([
            'pid' => $reqUser['id'],
            'typ' => $typ,
            'opis' => $payload['opis'] ?? null,
        ]);
        $wniosek = $stmt->fetch();
        // meta
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS wnioski_meta (id SERIAL PRIMARY KEY, wniosek_id INT REFERENCES wnioski(id), meta JSONB)');
            $ins = $this->db->prepare('INSERT INTO wnioski_meta (wniosek_id, meta) VALUES (:wid, :meta)');
            $ins->execute(['wid' => $wniosek['id'], 'meta' => json_encode($payload)]);
        } catch (\Throwable $e) {
            // ignore
        }
        return [$wniosek, null];
    }

    public function addZgloszenie($reqUser, $body, $files)
    {
        $pojazd_id = $body['pojazd_id'] ?? null;
        $data_zdarzenia = $body['data_zdarzenia'] ?? null;
        $opis = $body['opis'] ?? null;
        if (!$pojazd_id || !$data_zdarzenia || !$opis) return [null, 'missing fields'];

        $savedFiles = [];
        $uploadDir = __DIR__ . '/../../uploads';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        foreach ($files as $f) {
            if ($f['error'] === UPLOAD_ERR_OK) {
                $name = time() . '-' . bin2hex(random_bytes(4)) . '.' . pathinfo($f['name'], PATHINFO_EXTENSION);
                $target = $uploadDir . '/' . $name;
                if (move_uploaded_file($f['tmp_name'], $target)) {
                    $savedFiles[] = ['path' => 'panel/uploads/' . $name, 'originalname' => $f['name']];
                }
            }
        }
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS zgloszenia (id SERIAL PRIMARY KEY, pracownik_id INT, pojazd_id INT, data_zdarzenia TIMESTAMP, opis TEXT, wyjasnienie TEXT, uwagi TEXT, files JSONB, created_at TIMESTAMP DEFAULT NOW())');
        } catch (\Throwable $e) { /* ignore */ }
        $stmt = $this->db->prepare('INSERT INTO zgloszenia (pracownik_id, pojazd_id, data_zdarzenia, opis, wyjasnienie, uwagi, files) VALUES (:pid,:pojazd,:data,:opis,:wyjas,:uwagi,:files) RETURNING *');
        $stmt->execute([
            'pid' => $reqUser['id'],
            'pojazd' => (int)$pojazd_id,
            'data' => $data_zdarzenia,
            'opis' => $opis,
            'wyjas' => $body['wyjasnienie'] ?? null,
            'uwagi' => $body['uwagi'] ?? null,
            'files' => json_encode($savedFiles)
        ]);
        return [$stmt->fetch(), null];
    }

    public function raportyPending()
    {
        $q = $this->db->query("SELECT g.* FROM grafiki g LEFT JOIN raporty r ON r.grafik_id=g.id WHERE r.id IS NULL AND g.data<=CURRENT_DATE+1 ORDER BY g.data");
        return $q->fetchAll();
    }
    public function raportySent()
    {
        $q = $this->db->query('SELECT * FROM raporty ORDER BY created_at DESC LIMIT 200');
        return $q->fetchAll();
    }
    public function raportyCancelled()
    {
        $q = $this->db->query("SELECT * FROM grafiki WHERE status='anulowana' ORDER BY data DESC");
        return $q->fetchAll();
    }

    public function pojazdy()
    {
        $q = $this->db->query('SELECT * FROM pojazdy ORDER BY id');
        return $q->fetchAll();
    }
    public function linie()
    {
        $q = $this->db->query('SELECT * FROM linie ORDER BY id');
        return $q->fetchAll();
    }
    public function grafik($userId = null)
    {
        if ($userId) {
            $stmt = $this->db->prepare('SELECT * FROM grafiki WHERE pracownik_id=:id ORDER BY data');
            $stmt->execute(['id' => (int)$userId]);
            return $stmt->fetchAll();
        }
        $q = $this->db->query('SELECT * FROM grafiki ORDER BY data LIMIT 500');
        return $q->fetchAll();
    }
    public function pracownicy()
    {
        $q = $this->db->query('SELECT p.id,p.imie,p.nazwisko,p.login,u.poziom as uprawnienie,p.is_active FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.is_active = true ORDER BY p.id');
        return $q->fetchAll();
    }
    
    /**
     * Get all employees (for zarzad admin panel) - includes inactive
     */
    public function pracownicyAll()
    {
        $q = $this->db->query('SELECT p.id,p.imie,p.nazwisko,p.login,u.poziom as uprawnienie,p.is_active FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id ORDER BY p.id');
        return $q->fetchAll();
    }

    public function adminPojazd($data)
    {
        $id = $data['id'] ?? null;
        $fields = ['nr_rejestracyjny','marka','model','rok_produkcji','sprawny'];
        foreach ($fields as $f) {
            if (!isset($data[$f]) && $f !== 'rok_produkcji' && $f !== 'model') {
                // minimal check
            }
        }
        if ($id) {
            $stmt = $this->db->prepare('UPDATE pojazdy SET nr_rejestracyjny=:nr, marka=:marka, model=:model, rok_produkcji=:rok, sprawny=:spr WHERE id=:id RETURNING *');
            $stmt->execute([
                'nr'=>$data['nr_rejestracyjny'] ?? null,
                'marka'=>$data['marka'] ?? null,
                'model'=>$data['model'] ?? null,
                'rok'=>$data['rok_produkcji'] ?? null,
                'spr'=>isset($data['sprawny']) ? (bool)$data['sprawny'] : true,
                'id'=>$id
            ]);
            return $stmt->fetch();
        }
        $stmt = $this->db->prepare('INSERT INTO pojazdy (nr_rejestracyjny, marka, model, rok_produkcji, sprawny) VALUES (:nr,:marka,:model,:rok,:spr) RETURNING *');
        $stmt->execute([
            'nr'=>$data['nr_rejestracyjny'] ?? null,
            'marka'=>$data['marka'] ?? null,
            'model'=>$data['model'] ?? null,
            'rok'=>$data['rok_produkcji'] ?? null,
            'spr'=>isset($data['sprawny']) ? (bool)$data['sprawny'] : true
        ]);
        return $stmt->fetch();
    }

    public function adminPracownik($data)
    {
        $id = $data['id'] ?? null;
        
        if ($id) {
            // Update existing
            $updates = [];
            $params = ['id' => $id];
            
            if (!empty($data['imie'])) { $updates[] = 'imie = :imie'; $params['imie'] = $data['imie']; }
            if (!empty($data['nazwisko'])) { $updates[] = 'nazwisko = :nazwisko'; $params['nazwisko'] = $data['nazwisko']; }
            if (!empty($data['haslo'])) { 
                $updates[] = 'haslo = :haslo';
                $params['haslo'] = password_hash($data['haslo'], PASSWORD_BCRYPT);
            }
            if (isset($data['uprawnienie_id'])) { $updates[] = 'uprawnienie_id = :upr'; $params['upr'] = $data['uprawnienie_id']; }
            if (isset($data['is_active'])) { $updates[] = 'is_active = :active'; $params['active'] = $data['is_active']; }
            
            if (empty($updates)) return [null, 'no_updates'];
            
            $stmt = $this->db->prepare('UPDATE pracownicy SET ' . implode(', ', $updates) . ' WHERE id = :id RETURNING *');
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            LogHelper::log($id, 'edit_employee', 'pracownik', $id, $data);
            return [$result, null];
        } else {
            // Create new
            if (empty($data['imie']) || empty($data['nazwisko']) || empty($data['login']) || empty($data['haslo']) || empty($data['uprawnienie_id'])) {
                return [null, 'missing required fields'];
            }
            
            $hashedPassword = password_hash($data['haslo'], PASSWORD_BCRYPT);
            $stmt = $this->db->prepare('INSERT INTO pracownicy (imie,nazwisko,login,haslo,stanowisko_id,uprawnienie_id,discord_id,is_active) VALUES (:im,:na,:lo,:ha,:stan,:upr,:discord,:active) RETURNING *');
            $stmt->execute([
                'im'=>$data['imie'],
                'na'=>$data['nazwisko'],
                'lo'=>$data['login'],
                'ha'=>$hashedPassword,
                'stan'=>$data['stanowisko_id'] ?? null,
                'upr'=>$data['uprawnienie_id'],
                'discord'=>$data['discord_id'] ?? null,
                'active'=>true
            ]);
            $result = $stmt->fetch();
            
            LogHelper::log($result['id'], 'create_employee', 'pracownik', $result['id'], $data);
            return [$result, null];
        }
    }
    
    /**
     * Deactivate employee (soft delete)
     */
    public function deactivateEmployee($employeeId)
    {
        $stmt = $this->db->prepare('UPDATE pracownicy SET is_active = false WHERE id = ?');
        $stmt->execute([$employeeId]);
        
        LogHelper::log($employeeId, 'deactivate_employee', 'pracownik', $employeeId);
        
        return [[true], null];
    }
    
    /**
     * Get activity log
     */
    public function getActivityLog($filters = [])
    {
        return LogHelper::getLog($filters);
    }

    public function adminRejestracja($data)
    {
        $stmt = $this->db->prepare('INSERT INTO rejestracje (login, token) VALUES (:login,:token) RETURNING *');
        $stmt->execute(['login'=>$data['login'] ?? null, 'token'=>$data['token'] ?? null]);
        return $stmt->fetch();
    }

    public function adminGrafik($data)
    {
        $stmt = $this->db->prepare('INSERT INTO grafiki (pracownik_id, data, brygada_id, pojazd_id) VALUES (:pid,:data,:bry,:poj) RETURNING *');
        $stmt->execute([
            'pid'=>$data['pracownik_id'] ?? null,
            'data'=>$data['data'] ?? null,
            'bry'=>$data['brygada_id'] ?? null,
            'poj'=>$data['pojazd_id'] ?? null,
        ]);
        return $stmt->fetch();
    }

    // --- JWT helpers (HS256 manual) ---
    private function b64url($data) { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
    private function signJwt(array $payload, $secret)
    {
        $header = ['alg'=>'HS256','typ'=>'JWT'];
        $h = $this->b64url(json_encode($header));
        $p = $this->b64url(json_encode($payload));
        $sig = $this->b64url(hash_hmac('sha256', "$h.$p", $secret, true));
        return "$h.$p.$sig";
    }
}
