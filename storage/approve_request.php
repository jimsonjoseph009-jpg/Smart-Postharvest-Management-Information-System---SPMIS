<?php
/**
 * storage/approve_request.php — Approve/Complete storage request
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/StorageFacility.php';

requireRole(['storage_provider','admin']);

$idObj = new StorageFacility();
$db = $idObj->getConnection();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Fetch request to verify ownership
    $stmt = $db->prepare("
        SELECT sr.id, sf.owner_id, sr.status
        FROM storage_requests sr
        JOIN storage_facilities sf ON sr.facility_id = sf.id
        WHERE sr.id = ?
    ");
    $stmt->execute([$id]);
    $req = $stmt->fetch();

    if ($req && ($req['owner_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'admin')) {
        $currentStatus = $idObj->decrypt($req['status']);
        $newStatus = $currentStatus === 'pending' ? 'approved' : 'completed';

        $encStatus = $idObj->encrypt($newStatus);
        $upd = $db->prepare("UPDATE storage_requests SET status = ? WHERE id = ?");
        $upd->execute([$encStatus, $id]);

        $idObj->logAudit('storage_requests', $id, 'status', 'ENCRYPT', $encStatus);
        setFlash('success', "Ombi limebadilishwa kuwa: " . statusBadgeLabel($newStatus));
    } else {
        setFlash('danger', "Huna ruhusa ya kufanya tendo hili.");
    }
}

redirect(BASE_URL . '/storage/dashboard.php');
