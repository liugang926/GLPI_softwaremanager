<?php
/**
 * Simple test endpoint to verify AJAX functionality
 */

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Clear any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Simple test response
echo json_encode([
    'success' => true,
    'message' => 'AJAX endpoint is working correctly',
    'timestamp' => date('Y-m-d H:i:s'),
    'test' => 'This is a test response'
], JSON_UNESCAPED_UNICODE);
?>
