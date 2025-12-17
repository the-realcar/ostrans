<?php
namespace App\Controllers;

use App\Core\Database;

class ApiController
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db->pdo;
    }

    public function login($login, $password, $jwtSecret)
    {
        $stmt = $this->db->prepare('SELECT p.*, u.poziom AS uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.login = :login LIMIT 1');
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
        $stmt = $this->db->prepare('SELECT p.id, p.imie, p.nazwisko, p.login, u.poziom as uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.id=:id');
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch();
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
        $q = $this->db->query('SELECT p.id,p.imie,p.nazwisko,p.login,u.poziom as uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id ORDER BY p.id');
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
        $stmt = $this->db->prepare('INSERT INTO pracownicy (imie,nazwisko,login,haslo,stanowisko_id,uprawnienie_id,discord_id) VALUES (:im,:na,:lo,:ha,:stan,:upr,:discord) RETURNING id');
        $stmt->execute([
            'im'=>$data['imie'] ?? null,
            'na'=>$data['nazwisko'] ?? null,
            'lo'=>$data['login'] ?? null,
            'ha'=>$data['haslo'] ?? null,
            'stan'=>$data['stanowisko_id'] ?? null,
            'upr'=>$data['uprawnienie_id'] ?? null,
            'discord'=>$data['discord_id'] ?? null,
        ]);
        return $stmt->fetch();
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
