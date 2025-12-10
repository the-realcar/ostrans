<?php
namespace App\Core;

class Database {
    public $pdo;

    public function __construct() {
        $env = $this->loadEnv(__DIR__ . '/../../env.txt'); // env.txt provided in repo root
        $dsn = $env['DATABASE_URL'] ?? ($env['PG_DSN'] ?? null);

        // support DATABASE_URL in form postgresql://user:pass@host:port/dbname
        if ($dsn && strpos($dsn, 'postgresql://') === 0) {
            $parts = parse_url($dsn);
            $user = $parts['user'] ?? null;
            $pass = $parts['pass'] ?? null;
            $host = $parts['host'] ?? 'localhost';
            $port = $parts['port'] ?? 5432;
            $dbname = ltrim($parts['path'] ?? '', '/');
            $pdo_dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        } elseif ($env['PG_DSN'] ?? false) {
            $pdo_dsn = $env['PG_DSN'];
            $user = $env['PG_USER'] ?? null;
            $pass = $env['PG_PASS'] ?? null;
        } else {
            // fallback to local defaults
            $pdo_dsn = "pgsql:host=localhost;port=5432;dbname=ostrans";
            $user = 'postgres';
            $pass = '';
        }

        try {
            $this->pdo = new \PDO($pdo_dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\Throwable $e) {
            // for now: fail gracefully, views can detect missing pdo
            $this->pdo = null;
        }
    }

    protected function loadEnv($path) {
        $data = [];
        if (!file_exists($path)) return $data;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            [$k,$v] = explode('=', $line, 2);
            $data[trim($k)] = trim($v);
        }
        return $data;
    }
}
