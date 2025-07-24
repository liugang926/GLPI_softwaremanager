<?php
/**
 * 简单的AJAX测试文件
 */

// 设置JSON响应头
header('Content-Type: application/json');

// 简单的测试响应
$response = array(
    'success' => true,
    'message' => 'AJAX连接测试成功',
    'timestamp' => date('Y-m-d H:i:s'),
    'received_data' => $_POST
);

echo json_encode($response);
?>
