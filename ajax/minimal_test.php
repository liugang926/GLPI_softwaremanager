<?php
/**
 * 最简单的AJAX测试
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置响应头
header('Content-Type: application/json');

// 记录请求
error_log("=== Minimal AJAX Test Started ===");
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));

try {
    // 简单响应
    $response = [
        'success' => true,
        'message' => 'Minimal AJAX test successful',
        'timestamp' => date('Y-m-d H:i:s'),
        'post_data' => $_POST,
        'get_data' => $_GET,
        'server_info' => [
            'php_version' => PHP_VERSION,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => $e->getLine()
    ];
    
    echo json_encode($error_response);
}

error_log("=== Minimal AJAX Test Ended ===");
?>
