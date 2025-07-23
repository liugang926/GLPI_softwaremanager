<?php
/**
 * 简化的批量删除AJAX处理器 - 用于调试
 */

// 设置JSON响应头
header('Content-Type: application/json');

// 记录调试信息
error_log("batch_delete_simple.php called");
error_log("POST data: " . print_r($_POST, true));

try {
    // 基本参数检查
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    $items_json = $_POST['items'] ?? '';
    
    error_log("Action: $action, Type: $type, Items: $items_json");
    
    if ($action !== 'batch_delete') {
        throw new Exception('Invalid action: ' . $action);
    }
    
    if ($type !== 'whitelist') {
        throw new Exception('Invalid type: ' . $type);
    }
    
    $items = json_decode($items_json, true);
    if (!is_array($items)) {
        throw new Exception('Invalid items data');
    }
    
    error_log("Parsed items: " . print_r($items, true));
    
    // 返回成功响应（暂时不实际删除）
    echo json_encode([
        'success' => true,
        'message' => '调试模式：收到 ' . count($items) . ' 个项目的删除请求',
        'deleted_count' => 0,
        'failed_count' => 0,
        'total_count' => count($items),
        'debug' => [
            'action' => $action,
            'type' => $type,
            'items' => $items
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in batch_delete_simple.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'post_data' => $_POST,
            'error_line' => $e->getLine()
        ]
    ]);
}
?>
