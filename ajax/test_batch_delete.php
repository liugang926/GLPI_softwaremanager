<?php
/**
 * Test AJAX handler for debugging
 */

// Turn off error display
ini_set('display_errors', 0);
error_reporting(0);

// Set JSON response header first
header('Content-Type: application/json');

try {
    // Test with GLPI includes - correct path for Y: drive
    $glpi_root = dirname(dirname(dirname(__DIR__)));
    include ($glpi_root . "/inc/includes.php");

    // Check if we can access GLPI functions
    $glpi_available = class_exists('Session');

    // Simple test response
    echo json_encode([
        'success' => true,
        'message' => 'Test successful with GLPI',
        'data' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'post_data' => $_POST,
            'get_data' => $_GET,
            'glpi_available' => $glpi_available,
            'user_logged_in' => $glpi_available ? Session::getLoginUserID() : false,
            'request_method' => $_SERVER['REQUEST_METHOD']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
