<?php
namespace App\Controllers;

use App\Core\Database;
use App\Helpers\AuthHelper;
use App\Helpers\LogHelper;
use App\Helpers\EmailHelper;

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
     * Request password reset - sends reset token via email (F4)
     */
    public function requestPasswordReset($login, $appUrl = 'http://localhost')
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
        
        // F4: Send reset token via email
        if ($user['email']) {
            EmailHelper::init();
            $sendResult = EmailHelper::sendPasswordReset($user['email'], $token, $appUrl);
            if (!$sendResult[0]) {
                LogHelper::log($user['id'], 'password_reset_email_failed', 'pracownik', $user['id'], ['error' => $sendResult[1]]);
            }
        }
        
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

    /**
     * F22 - Approve employee request (only zarzad/dyspozytor)
     */
    public function approveWniosek($wniosek_id, $reqUser)
    {
        // Check authorization: only zarzad or dyspozytor can approve
        if (!in_array($reqUser['uprawnienie'] ?? null, ['zarzad', 'dyspozytor'])) {
            return [null, 'unauthorized'];
        }
        
        $stmt = $this->db->prepare('SELECT id, pracownik_id FROM wnioski WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $wniosek_id]);
        $wniosek = $stmt->fetch();
        if (!$wniosek) {
            return [null, 'not_found'];
        }
        
        $stmt = $this->db->prepare('UPDATE wnioski SET status = :status, data_rozpatrzenia = NOW() WHERE id = :id RETURNING *');
        $stmt->execute([
            'status' => 'zaakceptowany',
            'id' => $wniosek_id
        ]);
        
        $result = $stmt->fetch();
        if ($result) {
            LogHelper::log($reqUser['id'], 'wniosek_approved', 'wnioski', $wniosek_id, ['pracownik_id' => $wniosek['pracownik_id']]);
        }
        return [$result, null];
    }

    /**
     * F22 - Reject employee request (only zarzad/dyspozytor)
     */
    public function rejectWniosek($wniosek_id, $reqUser, $reason = null)
    {
        // Check authorization: only zarzad or dyspozytor can reject
        if (!in_array($reqUser['uprawnienie'] ?? null, ['zarzad', 'dyspozytor'])) {
            return [null, 'unauthorized'];
        }
        
        $stmt = $this->db->prepare('SELECT id, pracownik_id FROM wnioski WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $wniosek_id]);
        $wniosek = $stmt->fetch();
        if (!$wniosek) {
            return [null, 'not_found'];
        }
        
        $stmt = $this->db->prepare('UPDATE wnioski SET status = :status, data_rozpatrzenia = NOW() WHERE id = :id RETURNING *');
        $stmt->execute([
            'status' => 'odrzucony',
            'id' => $wniosek_id
        ]);
        
        $result = $stmt->fetch();
        if ($result) {
            LogHelper::log($reqUser['id'], 'wniosek_rejected', 'wnioski', $wniosek_id, ['pracownik_id' => $wniosek['pracownik_id'], 'reason' => $reason]);
        }
        return [$result, null];
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
            $stmt = $this->db->prepare('SELECT g.*, b.nazwa AS brygada_nazwa, b.typ_brygady FROM grafiki g LEFT JOIN brygady b ON g.brygada_id=b.id WHERE g.pracownik_id=:id ORDER BY g.data');
            $stmt->execute(['id' => (int)$userId]);
            return $stmt->fetchAll();
        }
        $q = $this->db->query('SELECT g.*, b.nazwa AS brygada_nazwa, b.typ_brygady FROM grafiki g LEFT JOIN brygady b ON g.brygada_id=b.id ORDER BY g.data LIMIT 500');
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

    public function adminPojazd($data, $method = 'POST', $vehicleId = null, $reqUser = null)
    {
        $id = $vehicleId ?? $data['id'] ?? null;
        $fields = ['nr_rejestracyjny','marka','model','rok_produkcji','sprawny'];
        
        // F11: DELETE vehicle (soft delete via is_active flag)
        if ($method === 'DELETE' && $id) {
            $stmt = $this->db->prepare('UPDATE pojazdy SET is_active = false WHERE id = :id RETURNING *');
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();
            if ($result && $reqUser) {
                LogHelper::log($reqUser['id'] ?? null, 'pojazd_deleted', 'pojazdy', $id);
            }
            return $result;
        }
        
        // F11: UPDATE vehicle status
        if ($method === 'PUT' && $id) {
            $updates = [];
            $params = ['id' => $id];
            if (isset($data['sprawny'])) {
                $updates[] = 'sprawny = :spr';
                $params['spr'] = (bool)$data['sprawny'];
            }
            if (isset($data['nr_rejestracyjny'])) {
                $updates[] = 'nr_rejestracyjny = :nr';
                $params['nr'] = $data['nr_rejestracyjny'];
            }
            if (isset($data['marka'])) {
                $updates[] = 'marka = :marka';
                $params['marka'] = $data['marka'];
            }
            if (isset($data['model'])) {
                $updates[] = 'model = :model';
                $params['model'] = $data['model'];
            }
            if (isset($data['rok_produkcji'])) {
                $updates[] = 'rok_produkcji = :rok';
                $params['rok'] = $data['rok_produkcji'];
            }
            
            if (empty($updates)) {
                return [null, 'no_updates'];
            }
            
            $sql = 'UPDATE pojazdy SET ' . implode(', ', $updates) . ' WHERE id = :id RETURNING *';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            if ($result && $reqUser) {
                LogHelper::log($reqUser['id'] ?? null, 'pojazd_updated', 'pojazdy', $id, ['changes' => $data]);
            }
            return $result;
        }
        
        // POST: INSERT new vehicle
        foreach ($fields as $f) {
            if (!isset($data[$f]) && $f !== 'rok_produkcji' && $f !== 'model') {
                // minimal check
            }
        }
        
        $stmt = $this->db->prepare('INSERT INTO pojazdy (nr_rejestracyjny, marka, model, rok_produkcji, sprawny, is_active) VALUES (:nr,:marka,:model,:rok,:spr,:active) RETURNING *');
        $result = $stmt->execute([
            'nr'=>$data['nr_rejestracyjny'] ?? null,
            'marka'=>$data['marka'] ?? null,
            'model'=>$data['model'] ?? null,
            'rok'=>$data['rok_produkcji'] ?? null,
            'spr'=>isset($data['sprawny']) ? (bool)$data['sprawny'] : true,
            'active' => true
        ]);
        
        $vehicle = $stmt->fetch();
        if ($vehicle && $reqUser) {
            LogHelper::log($reqUser['id'] ?? null, 'pojazd_created', 'pojazdy', $vehicle['id'], ['vehicle' => $vehicle]);
        }
        return $vehicle;
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

    /**
     * F20 - Validate conflict: driver cannot be assigned to 2 brigades on same day
     */
    private function validateScheduleConflict($pracownik_id, $data, $brygada_id, $exclude_id = null)
    {
        // Check if driver already has schedule for this date with different brigade
        $sql = 'SELECT id FROM grafiki WHERE pracownik_id = :pid AND data = :data AND brygada_id != :bry AND is_active = true';
        if ($exclude_id) {
            $sql .= ' AND id != :eid';
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [
            'pid' => $pracownik_id,
            'data' => $data,
            'bry' => $brygada_id
        ];
        if ($exclude_id) {
            $params['eid'] = $exclude_id;
        }
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            return [null, 'conflict_schedule']; // Driver already assigned to different brigade this day
        }
        return [true, null];
    }

    public function adminGrafik($data)
    {
        $pracownik_id = $data['pracownik_id'] ?? null;
        $data_grafik = $data['data'] ?? null;
        $brygada_id = $data['brygada_id'] ?? null;
        $pojazd_id = $data['pojazd_id'] ?? null;
        
        // If no vehicle provided, try permanent assignment (F13)
        if (!$pojazd_id && $pracownik_id) {
            $pojazd_id = $this->getPermanentVehicle($pracownik_id);
        }
        
        // F20: Validate conflict
        [$valid, $err] = $this->validateScheduleConflict($pracownik_id, $data_grafik, $brygada_id);
        if (!$valid) {
            return [null, $err];
        }
        
        $stmt = $this->db->prepare('INSERT INTO grafiki (pracownik_id, data, brygada_id, pojazd_id) VALUES (:pid,:data,:bry,:poj) RETURNING *');
        $stmt->execute([
            'pid'=>$pracownik_id,
            'data'=>$data_grafik,
            'bry'=>$brygada_id,
            'poj'=>$pojazd_id,
        ]);
        
        $result = $stmt->fetch();
        if ($result) {
            LogHelper::log($pracownik_id, 'grafik_created', 'grafiki', $result['id']);
            // F12: log vehicle usage if vehicle assigned
            if ($pojazd_id) {
                $this->logVehicleUsage($pojazd_id, $pracownik_id, $result['id'], $data_grafik, null);
            }
        }
        return $result;
    }

    /**
     * F12 - log vehicle usage
     */
    private function logVehicleUsage($pojazd_id, $pracownik_id, $grafik_id, $data_start, $data_end = null)
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS vehicle_usage (
                id SERIAL PRIMARY KEY,
                pojazd_id INT NOT NULL REFERENCES pojazdy(id),
                pracownik_id INT REFERENCES pracownicy(id),
                grafik_id INT REFERENCES grafiki(id),
                data_start TIMESTAMP NOT NULL,
                data_end TIMESTAMP,
                km_start INT,
                km_end INT,
                uwagi TEXT,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            )');
            $stmt = $this->db->prepare('INSERT INTO vehicle_usage (pojazd_id, pracownik_id, grafik_id, data_start, data_end) VALUES (:p,:pr,:g,:ds,:de)');
            $stmt->execute([
                'p' => $pojazd_id,
                'pr' => $pracownik_id,
                'g' => $grafik_id,
                'ds' => $data_start,
                'de' => $data_end,
            ]);
        } catch (\Throwable $e) {
            // swallow to avoid breaking grafik creation
        }
    }

    /**
     * F13 - get permanent vehicle for driver
     */
    private function getPermanentVehicle($pracownik_id)
    {
        try {
            $stmt = $this->db->prepare('SELECT pojazd_id FROM pracownik_pojazd_staly WHERE pracownik_id = :pid AND is_active = true ORDER BY data_przypisania DESC LIMIT 1');
            $stmt->execute(['pid' => $pracownik_id]);
            $row = $stmt->fetch();
            return $row['pojazd_id'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * F13 - assign or update permanent vehicle for driver
     */
    public function assignPermanentVehicle($reqUser, $pracownik_id, $pojazd_id)
    {
        if (!in_array($reqUser['uprawnienie'] ?? null, ['zarzad', 'dyspozytor'])) {
            return [null, 'unauthorized'];
        }
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS pracownik_pojazd_staly (
                id SERIAL PRIMARY KEY,
                pracownik_id INT NOT NULL REFERENCES pracownicy(id) UNIQUE,
                pojazd_id INT NOT NULL REFERENCES pojazdy(id),
                data_przypisania DATE DEFAULT CURRENT_DATE,
                data_zakonczenia DATE,
                uwagi TEXT,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            )');
            // Upsert style: if exists, update; else insert
            $stmt = $this->db->prepare('SELECT id FROM pracownik_pojazd_staly WHERE pracownik_id = :pid LIMIT 1');
            $stmt->execute(['pid' => $pracownik_id]);
            $exists = $stmt->fetch();
            if ($exists) {
                $upd = $this->db->prepare('UPDATE pracownik_pojazd_staly SET pojazd_id = :poj, is_active = true, data_zakonczenia = NULL, updated_at = NOW() WHERE pracownik_id = :pid RETURNING *');
                $upd->execute(['poj'=>$pojazd_id, 'pid'=>$pracownik_id]);
                $row = $upd->fetch();
            } else {
                $ins = $this->db->prepare('INSERT INTO pracownik_pojazd_staly (pracownik_id, pojazd_id, is_active) VALUES (:pid,:poj,true) RETURNING *');
                $ins->execute(['pid'=>$pracownik_id, 'poj'=>$pojazd_id]);
                $row = $ins->fetch();
            }
            LogHelper::log($reqUser['id'] ?? null, 'pojazd_staly_set', 'pracownik_pojazd_staly', $pracownik_id, ['pojazd_id' => $pojazd_id]);
            return [$row, null];
        } catch (\Throwable $e) {
            return [null, 'assign_failed'];
        }
    }

    // --- Export methods (F27-F29) ---
    public function exportGrafiki(array $params)
    {
        $format = $params['format'] ?? 'csv';
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        try {
            $query = "SELECT g.id, g.pracownik_id, g.brygada_id, g.pojazd_id, g.data, g.status,
                             CONCAT(p.imie, ' ', p.nazwisko) as pracownik,
                             b.nazwa as brygada_nazwa, b.typ_brygady,
                             poj.nr_rejestracyjny
                      FROM grafiki g
                      LEFT JOIN pracownicy p ON g.pracownik_id = p.id
                      LEFT JOIN brygady b ON g.brygada_id = b.id
                      LEFT JOIN pojazdy poj ON g.pojazd_id = poj.id
                      WHERE 1=1";
            
            $queryParams = [];
            $paramIndex = 1;
            
            if ($startDate) {
                $query .= " AND g.data >= $" . $paramIndex++;
                $queryParams[] = $startDate;
            }
            if ($endDate) {
                $query .= " AND g.data <= $" . $paramIndex++;
                $queryParams[] = $endDate;
            }
            
            $query .= " ORDER BY g.data DESC, g.id DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($queryParams);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($format === 'pdf') {
                require_once __DIR__ . '/../helpers/ExportHelper.php';
                $html = \App\Helpers\ExportHelper::formatGrafikiHTML($data);
                $pdf = \App\Helpers\ExportHelper::generatePDF($html, 'Grafiki PPUT Ostrans');
                return ['content' => $pdf, 'filename' => 'grafiki_' . date('Y-m-d') . '.pdf', 'mime' => 'application/pdf'];
            } else {
                require_once __DIR__ . '/../helpers/ExportHelper.php';
                $csv = \App\Helpers\ExportHelper::generateCSV($data);
                return ['content' => $csv, 'filename' => 'grafiki_' . date('Y-m-d') . '.csv', 'mime' => 'text/csv'];
            }
        } catch (\Throwable $e) {
            return ['error' => 'export_failed', 'message' => $e->getMessage()];
        }
    }

    public function exportPojazdy(array $params)
    {
        $format = $params['format'] ?? 'csv';

        try {
            $query = "SELECT id, nr_rejestracyjny, marka, model, rok_produkcji, sprawny, is_active
                      FROM pojazdy
                      WHERE is_active = true
                      ORDER BY nr_rejestracyjny";
            
            $stmt = $this->db->query($query);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($format === 'pdf') {
                require_once __DIR__ . '/../helpers/ExportHelper.php';
                $html = \App\Helpers\ExportHelper::formatPojazdyHTML($data);
                $pdf = \App\Helpers\ExportHelper::generatePDF($html, 'Pojazdy PPUT Ostrans');
                return ['content' => $pdf, 'filename' => 'pojazdy_' . date('Y-m-d') . '.pdf', 'mime' => 'application/pdf'];
            } else {
                require_once __DIR__ . '/../helpers/ExportHelper.php';
                $csv = \App\Helpers\ExportHelper::generateCSV($data);
                return ['content' => $csv, 'filename' => 'pojazdy_' . date('Y-m-d') . '.csv', 'mime' => 'text/csv'];
            }
        } catch (\Throwable $e) {
            return ['error' => 'export_failed', 'message' => $e->getMessage()];
        }
    }

    public function exportBrygady(array $params)
    {
        $format = $params['format'] ?? 'csv';

        try {
            $query = "SELECT b.id, b.nazwa, b.linia_id, b.typ_brygady, b.is_active,
                             l.nr_linii, l.typ as linia_typ
                      FROM brygady b
                      LEFT JOIN linie l ON b.linia_id = l.id
                      WHERE b.is_active = true
                      ORDER BY b.nazwa";
            
            $stmt = $this->db->query($query);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($format === 'pdf') {
                require_once __DIR__ . '/../helpers/ExportHelper.php';
                $html = \App\Helpers\ExportHelper::formatBrygadyHTML($data);
                $pdf = \App\Helpers\ExportHelper::generatePDF($html, 'Brygady PPUT Ostrans');
                return ['content' => $pdf, 'filename' => 'brygady_' . date('Y-m-d') . '.pdf', 'mime' => 'application/pdf'];
            } else {
                require_once __DIR__ . '/../helpers/ExportHelper.php';
                $csv = \App\Helpers\ExportHelper::generateCSV($data);
                return ['content' => $csv, 'filename' => 'brygady_' . date('Y-m-d') . '.csv', 'mime' => 'text/csv'];
            }
        } catch (\Throwable $e) {
            return ['error' => 'export_failed', 'message' => $e->getMessage()];
        }
    }

    // ============================================================================
    // F14-F16: Lines and Brigades Management
    // ============================================================================
    
    /**
     * Get all active brigades
     */
    public function getBrygady()
    {
        $stmt = $this->db->query('SELECT b.*, l.nr_linii, l.typ as linia_typ FROM brygady b LEFT JOIN linie l ON b.linia_id = l.id WHERE b.is_active = true ORDER BY b.nazwa');
        return $stmt->fetchAll();
    }
    
    /**
     * F14-F16: Create or update line
     */
    public function adminLinia($data, $method = 'POST', $liniaId = null, $reqUser = null)
    {
        if (!in_array($reqUser['uprawnienie'] ?? null, ['zarzad', 'dyspozytor'])) {
            return [null, 'unauthorized'];
        }
        
        // DELETE line (soft delete)
        if ($method === 'DELETE' && $liniaId) {
            $stmt = $this->db->prepare('UPDATE linie SET is_active = false WHERE id = :id RETURNING *');
            $stmt->execute(['id' => $liniaId]);
            $result = $stmt->fetch();
            if ($result) {
                LogHelper::log($reqUser['id'], 'linia_deleted', 'linie', $liniaId);
            }
            return [$result, null];
        }
        
        // UPDATE line
        if ($method === 'PUT' && $liniaId) {
            $updates = [];
            $params = ['id' => $liniaId];
            
            if (isset($data['nr_linii'])) {
                $updates[] = 'nr_linii = :nr';
                $params['nr'] = $data['nr_linii'];
            }
            if (isset($data['typ'])) {
                $updates[] = 'typ = :typ';
                $params['typ'] = $data['typ'];
            }
            if (isset($data['start_point'])) {
                $updates[] = 'start_point = :start';
                $params['start'] = $data['start_point'];
            }
            if (isset($data['end_point'])) {
                $updates[] = 'end_point = :end';
                $params['end'] = $data['end_point'];
            }
            if (isset($data['opis'])) {
                $updates[] = 'opis = :opis';
                $params['opis'] = $data['opis'];
            }
            
            if (empty($updates)) {
                return [null, 'no_updates'];
            }
            
            $sql = 'UPDATE linie SET ' . implode(', ', $updates) . ' WHERE id = :id RETURNING *';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            if ($result) {
                LogHelper::log($reqUser['id'], 'linia_updated', 'linie', $liniaId, ['changes' => $data]);
            }
            return [$result, null];
        }
        
        // POST: INSERT new line
        if (!isset($data['nr_linii'])) {
            return [null, 'missing_nr_linii'];
        }
        
        // Ensure is_active column exists
        try {
            $this->db->exec('ALTER TABLE linie ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true');
        } catch (\Throwable $e) { /* ignore */ }
        
        $stmt = $this->db->prepare('INSERT INTO linie (nr_linii, typ, start_point, end_point, opis, is_active) VALUES (:nr, :typ, :start, :end, :opis, true) RETURNING *');
        $stmt->execute([
            'nr' => $data['nr_linii'],
            'typ' => $data['typ'] ?? 'bus',
            'start' => $data['start_point'] ?? null,
            'end' => $data['end_point'] ?? null,
            'opis' => $data['opis'] ?? null,
        ]);
        
        $result = $stmt->fetch();
        if ($result) {
            LogHelper::log($reqUser['id'], 'linia_created', 'linie', $result['id'], ['linia' => $result]);
        }
        return [$result, null];
    }
    
    /**
     * F14-F16: Create or update brigade
     */
    public function adminBrygada($data, $method = 'POST', $brygadaId = null, $reqUser = null)
    {
        if (!in_array($reqUser['uprawnienie'] ?? null, ['zarzad', 'dyspozytor'])) {
            return [null, 'unauthorized'];
        }
        
        // DELETE brigade (soft delete)
        if ($method === 'DELETE' && $brygadaId) {
            $stmt = $this->db->prepare('UPDATE brygady SET is_active = false WHERE id = :id RETURNING *');
            $stmt->execute(['id' => $brygadaId]);
            $result = $stmt->fetch();
            if ($result) {
                LogHelper::log($reqUser['id'], 'brygada_deleted', 'brygady', $brygadaId);
            }
            return [$result, null];
        }
        
        // UPDATE brigade
        if ($method === 'PUT' && $brygadaId) {
            $updates = [];
            $params = ['id' => $brygadaId];
            
            if (isset($data['nazwa'])) {
                $updates[] = 'nazwa = :nazwa';
                $params['nazwa'] = $data['nazwa'];
            }
            if (isset($data['linia_id'])) {
                $updates[] = 'linia_id = :linia';
                $params['linia'] = $data['linia_id'];
            }
            if (isset($data['typ_brygady'])) {
                $updates[] = 'typ_brygady = :typ';
                $params['typ'] = $data['typ_brygady'];
            }
            
            if (empty($updates)) {
                return [null, 'no_updates'];
            }
            
            $sql = 'UPDATE brygady SET ' . implode(', ', $updates) . ' WHERE id = :id RETURNING *';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            if ($result) {
                LogHelper::log($reqUser['id'], 'brygada_updated', 'brygady', $brygadaId, ['changes' => $data]);
            }
            return [$result, null];
        }
        
        // POST: INSERT new brigade
        if (!isset($data['nazwa'])) {
            return [null, 'missing_nazwa'];
        }
        
        // Ensure typ_brygady column exists (F16: day/night shift marking)
        try {
            $this->db->exec('ALTER TABLE brygady ADD COLUMN IF NOT EXISTS typ_brygady VARCHAR(20) DEFAULT \'dzienna\'');
        } catch (\Throwable $e) { /* ignore */ }
        
        $stmt = $this->db->prepare('INSERT INTO brygady (nazwa, linia_id, typ_brygady, is_active) VALUES (:nazwa, :linia, :typ, true) RETURNING *');
        $stmt->execute([
            'nazwa' => $data['nazwa'],
            'linia' => $data['linia_id'] ?? null,
            'typ' => $data['typ_brygady'] ?? 'dzienna', // 'dzienna' or 'nocna'
        ]);
        
        $result = $stmt->fetch();
        if ($result) {
            LogHelper::log($reqUser['id'], 'brygada_created', 'brygady', $result['id'], ['brygada' => $result]);
        }
        return [$result, null];
    }
    
    /**
     * F17-F20: Update existing schedule entry
     */
    public function updateGrafik($grafikId, $data, $reqUser)
    {
        if (!in_array($reqUser['uprawnienie'] ?? null, ['zarzad', 'dyspozytor'])) {
            return [null, 'unauthorized'];
        }
        
        $updates = [];
        $params = ['id' => $grafikId];
        
        if (isset($data['pracownik_id'])) {
            $updates[] = 'pracownik_id = :pid';
            $params['pid'] = $data['pracownik_id'];
        }
        if (isset($data['data'])) {
            $updates[] = 'data = :data';
            $params['data'] = $data['data'];
        }
        if (isset($data['brygada_id'])) {
            $updates[] = 'brygada_id = :bry';
            $params['bry'] = $data['brygada_id'];
        }
        if (isset($data['pojazd_id'])) {
            $updates[] = 'pojazd_id = :poj';
            $params['poj'] = $data['pojazd_id'];
        }
        if (isset($data['status'])) {
            $updates[] = 'status = :status';
            $params['status'] = $data['status'];
        }
        
        if (empty($updates)) {
            return [null, 'no_updates'];
        }
        
        // F20: Validate conflict if changing pracownik or brygada or data
        if (isset($data['pracownik_id']) || isset($data['brygada_id']) || isset($data['data'])) {
            // Get current values to merge with update
            $stmt = $this->db->prepare('SELECT pracownik_id, data, brygada_id FROM grafiki WHERE id = :id');
            $stmt->execute(['id' => $grafikId]);
            $current = $stmt->fetch();
            if ($current) {
                $checkPracownikId = $data['pracownik_id'] ?? $current['pracownik_id'];
                $checkData = $data['data'] ?? $current['data'];
                $checkBrygadaId = $data['brygada_id'] ?? $current['brygada_id'];
                
                [$valid, $err] = $this->validateScheduleConflict($checkPracownikId, $checkData, $checkBrygadaId, $grafikId);
                if (!$valid) {
                    return [null, $err];
                }
            }
        }
        
        $updates[] = 'updated_at = NOW()';
        $sql = 'UPDATE grafiki SET ' . implode(', ', $updates) . ' WHERE id = :id RETURNING *';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        if ($result) {
            LogHelper::log($reqUser['id'], 'grafik_updated', 'grafiki', $grafikId, ['changes' => $data]);
        }
        return [$result, null];
    }
    
    /**
     * F17-F20: Delete schedule entry
     */
    public function deleteGrafik($grafikId, $reqUser)
    {
        if (!in_array($reqUser['uprawnienie'] ?? null, ['zarzad', 'dyspozytor'])) {
            return [null, 'unauthorized'];
        }
        
        // Soft delete by setting status to 'anulowana'
        $stmt = $this->db->prepare('UPDATE grafiki SET status = \'anulowana\', updated_at = NOW() WHERE id = :id RETURNING *');
        $stmt->execute(['id' => $grafikId]);
        $result = $stmt->fetch();
        
        if ($result) {
            LogHelper::log($reqUser['id'], 'grafik_deleted', 'grafiki', $grafikId);
        }
        return [$result, null];
    }
    
    /**
     * F12: Get vehicle usage history
     */
    public function getVehicleUsageHistory($pojazdId)
    {
        try {
            $stmt = $this->db->prepare('
                SELECT vu.*, 
                       CONCAT(p.imie, \' \', p.nazwisko) as kierowca,
                       g.data as data_grafiku
                FROM vehicle_usage vu
                LEFT JOIN pracownicy p ON vu.pracownik_id = p.id
                LEFT JOIN grafiki g ON vu.grafik_id = g.id
                WHERE vu.pojazd_id = :pid
                ORDER BY vu.data_start DESC
                LIMIT 100
            ');
            $stmt->execute(['pid' => $pojazdId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Approve/reject request with status update (F22-F24)
     */
    public function updateWniosekStatus($wniosek_id, $status, $reqUser, $reason = null)
    {
        if (!in_array($reqUser['uprawnienie'] ?? null, ['zarzad', 'dyspozytor'])) {
            return [null, 'unauthorized'];
        }
        
        if (!in_array($status, ['zatwierdzony', 'odrzucony', 'anulowany', 'zaakceptowany'])) {
            return [null, 'invalid_status'];
        }
        
        $stmt = $this->db->prepare('SELECT id, pracownik_id FROM wnioski WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $wniosek_id]);
        $wniosek = $stmt->fetch();
        if (!$wniosek) {
            return [null, 'not_found'];
        }
        
        $stmt = $this->db->prepare('UPDATE wnioski SET status = :status, data_rozpatrzenia = NOW() WHERE id = :id RETURNING *');
        $stmt->execute([
            'status' => $status,
            'id' => $wniosek_id
        ]);
        
        $result = $stmt->fetch();
        if ($result) {
            $action = $status === 'zatwierdzony' || $status === 'zaakceptowany' ? 'wniosek_approved' : 'wniosek_' . $status;
            LogHelper::log($reqUser['id'], $action, 'wnioski', $wniosek_id, [
                'pracownik_id' => $wniosek['pracownik_id'],
                'reason' => $reason,
                'status' => $status
            ]);
        }
        return [$result, null];
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
