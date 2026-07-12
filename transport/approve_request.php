<?php
/**
 * transport/approve_request.php — Approve/Complete transport request
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/TransportVehicle.php';

requireRole(['transport_provider','admin']);

$tv = new TransportVehicle();
$db = $tv->getConnection();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Fetch request to verify ownership
    $stmt = $db->prepare("
        SELECT tr.id, tv.owner_id, tr.status
        FROM transport_requests tr
        JOIN transport_vehicles tv ON tr.vehicle_id = tv.id
        WHERE tr.id = ?
    ");
    $stmt->execute([$id]);
    $req = $stmt->fetch();

    if ($req && ($req['owner_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'admin')) {
        $currentStatus = $tv->decrypt($req['status']);
        $newStatus = $currentStatus === 'pending' ? 'approved' : 'completed';

        $encStatus = $tv->encrypt($newStatus);
        $upd = $db->prepare("UPDATE transport_requests SET status = ? WHERE id = ?");
        $upd->execute([$encStatus, $id]);

        $tv->logAudit('transport_requests', $id, 'status', 'ENCRYPT', $encStatus);
        setFlash('success', "Safari imebadilishwa kuwa: " . statusBadgeLabel($newStatus));
    } else {
        setFlash('danger', "Huna ruhusa ya kufanya tendo hili.");
    }
}

redirect(BASE_URL . '/transport/dashboard.php');
