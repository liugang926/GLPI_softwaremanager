<?php
/**
 * 简单的AJAX测试文件
 */

// 设置响应头
header('Content-Type: application/json');

// 记录请求信息
error_log("Simple AJAX test called");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

try {
    // 简单的响应
    $response = [
        'success' => true,
        'message' => 'AJAX test successful',
        'timestamp' => date('Y-m-d H:i:s'),
        'post_data' => $_POST
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    echo json_encode($response);
}
?>
