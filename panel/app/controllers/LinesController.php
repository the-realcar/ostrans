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
     * Priority: SIL API → MySQL Database → Fallback sample data
     */
    public function getGroupedLines(): array
    {
        $grouped = ['tram' => [], 'trol' => [], 'bus' => []];

        // Try SIL API first (with cache, 5 min TTL)
        $silLines = $this->fetchFromSIL();
        if (!empty($silLines)) {
            return $this->formatGroupedLines($silLines, 'sil');
        }

        // Try MySQL database
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->query('SELECT nr_linii, typ, start_point, end_point FROM linie ORDER BY typ, nr_linii');
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                if (!empty($rows)) {
                    return $this->formatGroupedLines($rows, 'db');
                }
            } catch (\Throwable $e) {
                // Fall through to fallback
            }
        }

        // Fallback: Sample data
        $fallbackLines = [
            ['nr_linii' => '1', 'typ' => 'tram', 'start_point' => 'Centrum', 'end_point' => 'Dworzec'],
            ['nr_linii' => '2', 'typ' => 'tram', 'start_point' => 'Dworzec', 'end_point' => 'Nowy Świat'],
            ['nr_linii' => '1', 'typ' => 'bus', 'start_point' => 'Centrum', 'end_point' => 'Lotnisko'],
            ['nr_linii' => '2', 'typ' => 'bus', 'start_point' => 'Dworzec', 'end_point' => 'Fabryka'],
            ['nr_linii' => '3', 'typ' => 'bus', 'start_point' => 'Centrum', 'end_point' => 'Park'],
            ['nr_linii' => '10', 'typ' => 'trol', 'start_point' => 'Centrum', 'end_point' => 'Terminal'],
        ];
        return $this->formatGroupedLines($fallbackLines, 'fallback');
    }

    /**
     * Fetch lines from SIL API with cache (5 min TTL)
     */
    private function fetchFromSIL(): array
    {
        $cacheFile = sys_get_temp_dir() . '/ostrans_lines_cache.json';
        $cacheTime = file_exists($cacheFile) ? filemtime($cacheFile) : 0;
        
        // Try cache first
        if (time() - $cacheTime < 300) {
            $cached = @file_get_contents($cacheFile);
            if ($cached) {
                $lines = json_decode($cached, true);
                if (!empty($lines)) {
                    return $lines;
                }
            }
        }
        
        // Try SIL API with timeout
        $context = stream_context_create(['http' => ['timeout' => 3]]);
        $response = @file_get_contents('https://sil.kanbeq.me/ostrans/api/lines', false, $context);
        
        if ($response) {
            $lines = json_decode($response, true);
            if (!empty($lines)) {
                @file_put_contents($cacheFile, $response);
                return $lines;
            }
        }
        
        return [];
    }

    /**
     * Format lines array into grouped structure
     */
    private function formatGroupedLines(array $lines, string $source = 'sil'): array
    {
        $grouped = ['tram' => [], 'trol' => [], 'bus' => []];
        
        foreach ($lines as $line) {
            // Handle both SIL format (line/type/from/to) and DB format (nr_linii/typ/start_point/end_point)
            $num = $line['line'] ?? $line['nr_linii'] ?? '';
            $type = $line['type'] ?? $line['typ'] ?? 'bus';
            
            if (!$num) continue;
            
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            if (!isset($grouped[$type][$num])) {
                $grouped[$type][$num] = [];
            }
            
            // Normalize to DB format
            $grouped[$type][$num][] = [
                'nr_linii' => $num,
                'typ' => $type,
                'start_point' => $line['from'] ?? $line['start_point'] ?? '',
                'end_point' => $line['to'] ?? $line['end_point'] ?? ''
            ];
        }
        
        return $grouped;
    }
}
