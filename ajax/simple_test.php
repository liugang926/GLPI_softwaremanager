<?php
/**
 * Simple test without any GLPI checks
 */

// 清理输出缓冲
while (ob_get_level()) {
    ob_end_clean();
}

// 设置 JSON 头
header('Content-Type: application/json');

// 简单的测试响应
echo json_encode([
    'success' => true,
    'message' => 'Simple test successful - no GLPI checks',
    'timestamp' => date('Y-m-d H:i:s'),
    'post_data' => $_POST,
    'get_data' => $_GET,
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
    ]
]);
exit;
?>
