<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

/**
 * LinesController - provides line listing grouped by type
 * Falls back to remote SIL API when DB is empty/unavailable
 */
class LinesController
{
    private $pdo;

    public function __construct(Database $db)
    {
        $this->pdo = $db->pdo;
    }

    /**
     * Return grouped lines as ['tram'=>[lineNum=>[rows...]], 'trol'=>..., 'bus'=>...]
     */
    public function getGroupedLines(): array
    {
        $grouped = ['tram' => [], 'trol' => [], 'bus' => []];

        try {
            $stmt = $this->pdo->query('SELECT nr_linii, typ, start_point, end_point FROM linie ORDER BY typ, nr_linii');
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $type = $row['typ'] ?? 'bus';
                    $num = $row['nr_linii'] ?? '';
                    if (!$num) continue;
                    if (!isset($grouped[$type])) {
                        $grouped[$type] = [];
                    }
                    if (!isset($grouped[$type][$num])) {
                        $grouped[$type][$num] = [];
                    }
                    $grouped[$type][$num][] = $row;
                }
                return $grouped;
            }
        } catch (\Throwable $e) {
            // Fall through to SIL API fallback
        }

        // Fallback: SIL API (legacy)
        $apiUrl = 'https://sil.kanbeq.me/ostrans/api/lines';
        $linesData = @file_get_contents($apiUrl);
        $lines = $linesData ? json_decode($linesData, true) : [];
        foreach ($lines as $line) {
            $num = $line['line'] ?? '';
            $type = $line['type'] ?? 'bus';
            if (!$num) continue;
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            if (!isset($grouped[$type][$num])) {
                $grouped[$type][$num] = [];
            }
            $grouped[$type][$num][] = [
                'nr_linii' => $num,
                'typ' => $type,
                'start_point' => $line['from'] ?? '',
                'end_point' => $line['to'] ?? ''
            ];
        }
        return $grouped;
    }
}
