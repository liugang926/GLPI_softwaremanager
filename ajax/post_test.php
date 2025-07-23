<?php
/**
 * Simple POST Test
 */

// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// 设置响应头
header('Content-Type: application/json');

// 记录到错误日志
error_log("=== POST Test Started ===");
error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));

// 获取POST数据
$post_data = $_POST;
error_log("Raw POST data: " . print_r($post_data, true));

// 获取原始POST数据
$raw_post = file_get_contents('php://input');
error_log("Raw input: " . $raw_post);

// 尝试解析JSON
$json_data = null;
if (!empty($raw_post)) {
    try {
        $json_data = json_decode($raw_post, true);
        error_log("JSON decoded: " . print_r($json_data, true));
    } catch (Exception $e) {
        error_log("JSON decode error: " . $e->getMessage());
    }
}

// 准备响应
$response = [
    'success' => true,
    'message' => 'POST test completed',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'post_data' => $post_data,
    'raw_post' => $raw_post,
    'json_data' => $json_data,
    'timestamp' => date('Y-m-d H:i:s')
];

// 发送响应
echo json_encode($response, JSON_PRETTY_PRINT);

// 记录结束
error_log("=== POST Test Ended ===");
?>
