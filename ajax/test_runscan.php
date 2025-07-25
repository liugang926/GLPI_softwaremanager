<?php
/**
 * Test runscan endpoint
 */

include('../../../inc/includes.php');

// Simple response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'runscan.php is accessible',
    'user_id' => Session::getLoginUserID(),
    'user_name' => $_SESSION['glpiname'] ?? 'unknown',
    'time' => date('Y-m-d H:i:s'),
    'post_data' => $_POST
]);
?>
