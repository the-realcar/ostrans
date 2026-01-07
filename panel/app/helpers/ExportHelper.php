<?php
namespace App\Helpers;

/**
 * ExportHelper - CSV and simple HTML-to-PDF export utilities
 */
class ExportHelper
{
    /**
     * Generate CSV from array of records
     * @param array $data Array of associative arrays
     * @param array $headers Optional custom headers
     * @return string CSV content
     */
    public static function generateCSV(array $data, array $headers = []): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        // Headers
        if (empty($headers)) {
            $headers = array_keys($data[0]);
        }
        fputcsv($output, $headers);
        
        // Data rows
        foreach ($data as $row) {
            $values = [];
            foreach ($headers as $key) {
                $values[] = $row[$key] ?? '';
            }
            fputcsv($output, $values);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Generate simple PDF from HTML (using basic HTML rendering)
     * @param string $html HTML content
     * @param string $title Document title
     * @return string PDF content (binary)
     */
    public static function generatePDF(string $html, string $title = 'Document'): string
    {
        // Simple HTML-to-PDF conversion using DomPDF-style approach
        // For production, consider using: TCPDF, mPDF, or DomPDF libraries
        
        // Fallback: return HTML wrapped in minimal PDF structure (placeholder)
        // In real implementation, you'd use a proper PDF library
        
        $pdfHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #003366; color: white; }
        h1 { color: #003366; font-size: 18px; }
        h2 { color: #666; font-size: 14px; }
        .meta { color: #999; font-size: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>{$title}</h1>
    <div class="meta">Wygenerowano: {date('Y-m-d H:i:s')}</div>
    {$html}
</body>
</html>
HTML;
        
        // For now, return HTML (can be rendered as PDF by browser print)
        // To enable true PDF, install composer package: composer require dompdf/dompdf
        // Then uncomment below:
        /*
        require_once __DIR__ . '/../../vendor/autoload.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($pdfHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
        */
        
        return $pdfHtml;
    }

    /**
     * Format grafiki data to HTML table
     */
    public static function formatGrafikiHTML(array $grafiki): string
    {
        if (empty($grafiki)) {
            return '<p>Brak danych grafików.</p>';
        }

        $html = '<h2>Grafik</h2><table>';
        $html .= '<thead><tr><th>ID</th><th>Pracownik</th><th>Data</th><th>Brygada</th><th>Pojazd</th><th>Status</th><th>Typ brygady</th></tr></thead><tbody>';
        
        foreach ($grafiki as $g) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($g['id'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($g['pracownik_id'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($g['data'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($g['brygada_nazwa'] ?? $g['brygada_id'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($g['pojazd_id'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($g['status'] ?? 'zaplanowany') . '</td>';
            $html .= '<td>' . htmlspecialchars($g['typ_brygady'] ?? 'dzień') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Format pojazdy data to HTML table
     */
    public static function formatPojazdyHTML(array $pojazdy): string
    {
        if (empty($pojazdy)) {
            return '<p>Brak danych pojazdów.</p>';
        }

        $html = '<h2>Pojazdy</h2><table>';
        $html .= '<thead><tr><th>ID</th><th>Nr rejestracyjny</th><th>Marka</th><th>Model</th><th>Rok</th><th>Sprawny</th></tr></thead><tbody>';
        
        foreach ($pojazdy as $p) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($p['id'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['nr_rejestracyjny'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['marka'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['model'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['rok_produkcji'] ?? '') . '</td>';
            $html .= '<td>' . ($p['sprawny'] ? 'Tak' : 'Nie') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Format brygady data to HTML table
     */
    public static function formatBrygadyHTML(array $brygady): string
    {
        if (empty($brygady)) {
            return '<p>Brak danych brygad.</p>';
        }

        $html = '<h2>Brygady</h2><table>';
        $html .= '<thead><tr><th>ID</th><th>Nazwa</th><th>Linia</th><th>Typ</th><th>Aktywna</th></tr></thead><tbody>';
        
        foreach ($brygady as $b) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($b['id'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($b['nazwa'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($b['linia_id'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($b['typ_brygady'] ?? 'dzień') . '</td>';
            $html .= '<td>' . ($b['is_active'] ? 'Tak' : 'Nie') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }
}
