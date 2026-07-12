<?php
/**
 * reports/generate.php — Polymorphic Report Export Handler
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Report.php';
require_once __DIR__ . '/../classes/Farmer.php';

requireLogin();

$type   = trim($_GET['type'] ?? '');
$format = trim($_GET['format'] ?? '');

if (!in_array($type, ['harvest', 'storage', 'financial'])) {
    setFlash('danger', 'Aina ya ripoti haitambuliki.');
    redirect(BASE_URL . '/index.php');
}

// Access Control
if ($_SESSION['role'] !== 'admin') {
    if ($type !== 'harvest') {
        setFlash('danger', 'Huna ruhusa ya kuona ripoti hii.');
        redirect(BASE_URL . '/index.php');
    }
}

// Instantiate the polymorphic class
$report = null;
switch ($type) {
    case 'harvest':
        $report = new HarvestReport();
        if ($_SESSION['role'] === 'farmer') {
            $farmer = new Farmer();
            $farmerData = $farmer->findByUserId($_SESSION['user_id']);
            if ($farmerData) {
                $report->setFarmerId($farmerData->getFarmId());
            }
        }
        break;
    case 'storage':
        $report = new StorageReport();
        break;
    case 'financial':
        $report = new FinancialReport();
        break;
}

// Generate data
$report->generateReport('all', []);

// Trigger export format
if ($format === 'csv') {
    $report->exportCSV();
} else {
    $report->exportPDF();
}
