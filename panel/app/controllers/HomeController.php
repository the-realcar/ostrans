<?php
namespace App\Controllers;
use App\Core\Database;

class HomeController {
    protected $db;
    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function index() {
        // show login view
        require __DIR__ . '/../views/login.php';
    }

    public function login() {
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';
        $error = null;

        if (!$login || !$password) {
            $error = 'Podaj login i hasło.';
            require __DIR__ . '/../views/login.php';
            return;
        }

        if (!$this->db->pdo) {
            // fallback demo users if no DB
            $demos = [
                ['id'=>1,'imie'=>'Jan','nazwisko'=>'Kowalski','login'=>'driver1','haslo'=>'dpass','uprawnienie'=>'kierowca'],
                ['id'=>2,'imie'=>'Anna','nazwisko'=>'Nowak','login'=>'dispo1','haslo'=>'dpass','uprawnienie'=>'dyspozytor'],
                ['id'=>3,'imie'=>'Piotr','nazwisko'=>'Zarzad','login'=>'admin1','haslo'=>'dpass','uprawnienie'=>'zarzad']
            ];
            foreach ($demos as $u) {
                if ($u['login'] === $login && $u['haslo'] === $password) {
                    $_SESSION['user'] = $u;
                    header('Location: ?route=dashboard');
                    return;
                }
            }
            $error = 'Nieprawidłowe dane (demo lub brak DB).';
            require __DIR__ . '/../views/login.php';
            return;
        }

        try {
            $stmt = $this->db->pdo->prepare('SELECT p.id, p.imie, p.nazwisko, p.login, p.haslo, u.poziom AS uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.login = :login LIMIT 1');
            $stmt->execute(['login'=>$login]);
            $user = $stmt->fetch();
            if (!$user) {
                $error = 'Nieprawidłowy login lub hasło.';
                require __DIR__ . '/../views/login.php';
                return;
            }
            $stored = $user['haslo'] ?? '';
            $ok = false;
            if (strpos($stored,'$2y$') === 0 || strpos($stored,'$2a$')===0 || strpos($stored,'$2b$')===0) {
                $ok = password_verify($password, $stored);
            } else {
                $ok = ($password === $stored);
            }
            if (!$ok) {
                $error = 'Nieprawidłowy login lub hasło.';
                require __DIR__ . '/../views/login.php';
                return;
            }
            // success
            unset($user['haslo']);
            $_SESSION['user'] = $user;
            header('Location: ?route=dashboard');
            return;
        } catch (\Throwable $e) {
            $error = 'Błąd serwera podczas logowania.';
            require __DIR__ . '/../views/login.php';
            return;
        }
    }

    public function logout() {
        session_destroy();
        header('Location: ?route=home');
    }

    public function dashboard() {
        if (empty($_SESSION['user'])) {
            header('Location: ?route=home');
            return;
        }
        $user = $_SESSION['user'];
        require __DIR__ . '/../views/dashboard.php';
    }
}
