<?php
/**
 * Reportable.php — Reportable Interface
 */

interface Reportable {
    public function generateReport($type, $data);
    public function exportPDF();
    public function exportCSV();
}
