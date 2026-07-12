<?php
/**
 * admin/delete_user.php — Delete User Action
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/User.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Tokeni ya usalama imekosekana. Jaribu tena.');
        redirect('users.php');
    }

    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $currentUserId = (int)$_SESSION['user_id'];

    if ($targetUserId === $currentUserId) {
        setFlash('danger', 'Huwezi kufuta akaunti yako mwenyewe ukiwa umeingia.');
        redirect('users.php');
    }

    if ($targetUserId > 0) {
        $userObj = new User();
        
        // Log system action before deleting user
        $userObj->logSystemAction($currentUserId, "Amemfuta mtumiaji mwenye ID " . $targetUserId);

        if ($userObj->delete($targetUserId)) {
            setFlash('success', 'Mtumiaji amefutwa kwa mafanikio!');
        } else {
            setFlash('danger', 'Imeshindwa kufuta mtumiaji.');
        }
    } else {
        setFlash('danger', 'ID ya mtumiaji si sahihi.');
    }
} else {
    setFlash('danger', 'Njia ya ombi si sahihi.');
}

redirect('users.php');
