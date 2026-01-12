<?php
namespace App\Controllers;

use App\Core\Database;

class PanelController {
    protected $db;
    public function __construct(Database $db) {
        $this->db = $db;
    }

    protected function requireAuth() {
        if (empty($_SESSION['user'])) {
            header('Location: /panel/index.php?route=home');
            exit;
        }
        return $_SESSION['user'];
    }

    protected function requireZarzad($user) {
        if (($user['uprawnienie'] ?? null) !== 'zarzad') {
            http_response_code(403);
            echo 'Brak uprawnień.';
            exit;
        }
    }

    public function grafik() {
        $user = $this->requireAuth();
        require __DIR__ . '/../views/grafik.php';
    }

    public function wnioski() {
        $user = $this->requireAuth();
        require __DIR__ . '/../views/wnioski.php';
    }

    public function raporty() {
        $user = $this->requireAuth();
        require __DIR__ . '/../views/raporty.php';
    }

    public function zgloszenia() {
        $user = $this->requireAuth();
        require __DIR__ . '/../views/zgloszenia.php';
    }

    public function wyslijRaport() {
        $user = $this->requireAuth();
        require __DIR__ . '/../views/wyslij_raport.php';
    }

    public function admin() {
        $user = $this->requireAuth();
        $this->requireZarzad($user);
        require __DIR__ . '/../views/employees.php';
    }
    
    public function employees() {
        $user = $this->requireAuth();
        $this->requireZarzad($user);
        require __DIR__ . '/../views/employees.php';
    }

    public function pojazdy() {
        $user = $this->requireAuth();
        $this->requireZarzad($user);
        require __DIR__ . '/../views/pojazdy.php';
    }
    
    public function linesManagement() {
        $user = $this->requireAuth();
        if (!in_array($user['uprawnienie'] ?? null, ['zarzad', 'dyspozytor'])) {
            http_response_code(403);
            echo 'Brak uprawnień.';
            exit;
        }
        require __DIR__ . '/../views/lines_management.php';
    }
    
    public function importPracownicy() {
        $user = $this->requireAuth();
        $this->requireZarzad($user);
        require __DIR__ . '/../views/import_pracownicy.php';
    }
}
