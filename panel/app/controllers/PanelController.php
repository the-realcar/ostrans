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
}
