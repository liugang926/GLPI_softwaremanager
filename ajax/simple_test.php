<?php
/**
 * Simple test AJAX endpoint
 */

// Try to include GLPI
$glpi_root = dirname(dirname(dirname(__DIR__))) . '/inc/includes.php';
if (file_exists($glpi_root)) {
    include($glpi_root);
} else {
    // Fallback - try different path
    include('../../../inc/includes.php');
}

// Immediately set JSON header to prevent HTML output
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, must-revalidate");

// Suppress any HTML output that might interfere
ob_clean();

try {
    // Check if user is logged in
    if (!isset($_SESSION['glpiID']) || !$_SESSION['glpiID']) {
        echo json_encode([
            'success' => false,
            'error' => 'User not logged in'
        ]);
        exit;
    }

    // Get request method
    $method = $_POST['method'] ?? $_GET['method'] ?? 'unknown';
    
    $response = [
        'success' => true,
        'message' => 'Simple test successful',
        'method' => $method,
        'user_id' => $_SESSION['glpiID'] ?? 'unknown',
        'user_name' => $_SESSION['glpiname'] ?? 'unknown',
        'time' => date('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Fatal Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Ensure no additional output
exit;
?>
