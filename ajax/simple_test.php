<?php
/**
 * Simple test to check AJAX communication
 */

// 设置JSON响应头
header('Content-Type: application/json');

// 记录请求
error_log("=== Simple Test Started ===");
error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
error_log("POST data: " . print_r($_POST, true));

// 返回简单的JSON响应
echo json_encode([
    'success' => true,
    'message' => 'Simple test working',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'post_data' => $_POST,
    'timestamp' => date('Y-m-d H:i:s')
]);

error_log("=== Simple Test Ended ===");
?>
