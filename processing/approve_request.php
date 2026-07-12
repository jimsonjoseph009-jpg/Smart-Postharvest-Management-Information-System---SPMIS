<?php
/**
 * processing/approve_request.php — Approve/Complete processing request
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/ProcessingFacility.php';

requireRole(['processor','admin']);

$pf = new ProcessingFacility();
$db = $pf->getConnection();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Fetch request to verify ownership
    $stmt = $db->prepare("
        SELECT pr.id, pf.owner_id, pr.status
        FROM processing_requests pr
        JOIN processing_facilities pf ON pr.facility_id = pf.id
        WHERE pr.id = ?
    ");
    $stmt->execute([$id]);
    $req = $stmt->fetch();

    if ($req && ($req['owner_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'admin')) {
        $currentStatus = $pf->decrypt($req['status']);
        $newStatus = $currentStatus === 'pending' ? 'approved' : 'completed';

        $encStatus = $pf->encrypt($newStatus);
        $upd = $db->prepare("UPDATE processing_requests SET status = ? WHERE id = ?");
        $upd->execute([$encStatus, $id]);

        $pf->logAudit('processing_requests', $id, 'status', 'ENCRYPT', $encStatus);
        setFlash('success', "Ombi limebadilishwa kuwa: " . statusBadgeLabel($newStatus));
    } else {
        setFlash('danger', "Huna ruhusa ya kufanya tendo hili.");
    }
}

redirect(BASE_URL . '/processing/dashboard.php');
