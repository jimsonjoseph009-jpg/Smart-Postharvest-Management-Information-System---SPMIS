<?php
/**
 * Report.php — Report Classes implementing Reportable (Polymorphism)
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/interfaces/Reportable.php';

/* ======================================================================
   Abstract base for all reports — could also be used as non-abstract
   with shared helpers. Here we keep Database as parent.
   ====================================================================== */
abstract class BaseReport extends Database {
    protected $reportData = [];
    protected $title = '';

    public function __construct() { $this->connect(); }
    public function getConnection() { return $this->conn; }

    /** Shared: output a CSV download */
    protected function outputCSV($filename, $headers, $rows) {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}");
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit;
    }

    /** Shared: build a plain-HTML printable PDF page */
    protected function outputPrintPage($title, $headers, $rows) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="sw"><head><meta charset="UTF-8">';
        echo "<title>{$title}</title>";
        echo '<style>
            body{font-family:Arial,sans-serif;font-size:12px;margin:20px;}
            h2{color:#1a5276;}
            table{width:100%;border-collapse:collapse;margin-top:10px;}
            th{background:#1a5276;color:#fff;padding:6px 8px;text-align:left;}
            td{padding:5px 8px;border-bottom:1px solid #ddd;}
            tr:nth-child(even){background:#f2f2f2;}
            .meta{color:#555;margin-bottom:10px;}
            @media print{.no-print{display:none;}}
        </style></head><body>';
        echo "<h2>KILIMO-HIFADHI — {$title}</h2>";
        echo "<p class='meta'>Tarehe: " . date('d/m/Y H:i') . "</p>";
        echo '<button class="no-print" onclick="window.print()">&#128438; Chapisha (Print)</button>';
        echo '<table><thead><tr>';
        foreach ($headers as $h) { echo "<th>" . htmlspecialchars($h) . "</th>"; }
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) { echo '<td>' . htmlspecialchars((string)$cell) . '</td>'; }
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
        exit;
    }
}

/* ======================================================================
   StorageReport — implements Reportable (Polymorphism)
   ====================================================================== */
class StorageReport extends BaseReport implements Reportable {
    private $data = [];
    private $type = '';

    public function generateReport($type, $data) {
        $this->type = $type;
        $db = $this->getConnection();

        $stmt = $db->query("
            SELECT sr.id, sr.quantity_kg, sr.start_date, sr.end_date,
                   sr.total_cost, sr.payment_status, sr.status,
                   sf.name as facility_name, sf.location as facility_location
            FROM `storage_requests` sr
            JOIN `storage_facilities` sf ON sr.facility_id = sf.id
            ORDER BY sr.id DESC
        ");
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'ID'                => $r['id'],
                'Kituo'             => $this->decrypt($r['facility_name']),
                'Eneo'              => $this->decrypt($r['facility_location']),
                'Kiasi (kg)'        => $this->decrypt($r['quantity_kg']),
                'Tarehe Kuanza'     => $this->decrypt($r['start_date']),
                'Tarehe Kumalizika' => $this->decrypt($r['end_date']),
                'Gharama (Tshs)'    => $this->decrypt($r['total_cost']),
                'Hali ya Malipo'    => $this->decrypt($r['payment_status']),
                'Hali'              => $this->decrypt($r['status']),
            ];
        }
        $this->data = $result;
        return $result;
    }

    public function exportPDF() {
        if (empty($this->data)) $this->generateReport('all', []);
        $this->outputPrintPage('Ripoti ya Uhifadhi', array_keys($this->data[0] ?? []), $this->data);
    }

    public function exportCSV() {
        if (empty($this->data)) $this->generateReport('all', []);
        $headers = ['ID','Kituo','Eneo','Kiasi (kg)','Tarehe Kuanza','Tarehe Kumalizika','Gharama','Hali ya Malipo','Hali'];
        $this->outputCSV('storage_report.csv', $headers, $this->data);
    }
}

/* ======================================================================
   FinancialReport — implements Reportable (Polymorphism)
   ====================================================================== */
class FinancialReport extends BaseReport implements Reportable {
    private $data = [];

    public function generateReport($type, $data) {
        $db = $this->getConnection();

        // Summarise orders
        $stmt = $db->query("
            SELECT o.id, o.buyer_id, o.quantity_kg, o.total_price, o.status,
                   p.amount, p.payment_method, p.payment_date
            FROM `orders` o
            LEFT JOIN `payments` p ON p.order_id = o.id
            ORDER BY o.id DESC
        ");
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'Agizo ID'       => $r['id'],
                'Kiasi (kg)'     => $this->decrypt($r['quantity_kg']),
                'Jumla (Tshs)'   => $this->decrypt($r['total_price']),
                'Hali ya Agizo'  => $this->decrypt($r['status']),
                'Kiasi Kilicholipwa' => $this->decrypt($r['amount'] ?? ''),
                'Njia ya Malipo' => $this->decrypt($r['payment_method'] ?? ''),
                'Tarehe ya Malipo' => $this->decrypt($r['payment_date'] ?? ''),
            ];
        }
        $this->data = $result;
        return $result;
    }

    public function exportPDF() {
        if (empty($this->data)) $this->generateReport('all', []);
        $this->outputPrintPage('Ripoti ya Fedha', array_keys($this->data[0] ?? []), $this->data);
    }

    public function exportCSV() {
        if (empty($this->data)) $this->generateReport('all', []);
        $headers = ['Agizo ID','Kiasi (kg)','Jumla (Tshs)','Hali ya Agizo','Kiasi Kilicholipwa','Njia ya Malipo','Tarehe ya Malipo'];
        $this->outputCSV('financial_report.csv', $headers, $this->data);
    }
}

/* ======================================================================
   HarvestReport — implements Reportable (Polymorphism)
   ====================================================================== */
class HarvestReport extends BaseReport implements Reportable {
    private $data = [];
    private $farmerId = null;

    public function setFarmerId($id) { $this->farmerId = (int)$id; }

    public function generateReport($type, $data) {
        $db = $this->getConnection();
        $where = $this->farmerId ? "WHERE h.farmer_id = {$this->farmerId}" : '';
        $stmt = $db->query("
            SELECT h.id, h.quantity_kg, h.harvest_date, h.quality_grade, h.unit_price,
                   h.harvest_location, c.name as crop_enc
            FROM `harvests` h
            JOIN `crops` c ON h.crop_id = c.id
            {$where}
            ORDER BY h.id DESC
        ");
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $qty   = $this->decrypt($r['quantity_kg']);
            $price = $this->decrypt($r['unit_price']);
            $value = round((float)$qty * (float)$price, 2);
            $result[] = [
                'ID'            => $r['id'],
                'Zao'           => $this->decrypt($r['crop_enc']),
                'Kiasi (kg)'    => $qty,
                'Tarehe'        => $this->decrypt($r['harvest_date']),
                'Daraja'        => $this->decrypt($r['quality_grade']),
                'Bei/kg (Tshs)' => $price,
                'Thamani (Tshs)'=> $value,
                'Mahali'        => $this->decrypt($r['harvest_location']),
            ];
        }
        $this->data = $result;
        return $result;
    }

    public function exportPDF() {
        if (empty($this->data)) $this->generateReport('all', []);
        $this->outputPrintPage('Ripoti ya Mavuno', array_keys($this->data[0] ?? []), $this->data);
    }

    public function exportCSV() {
        if (empty($this->data)) $this->generateReport('all', []);
        $headers = ['ID','Zao','Kiasi (kg)','Tarehe','Daraja','Bei/kg','Thamani','Mahali'];
        $this->outputCSV('harvest_report.csv', $headers, $this->data);
    }
}
