<?php
/**
 * Debug version of batch delete
 */

// 清理输出缓冲区
if (ob_get_level()) {
    ob_end_clean();
}

// 设置响应头
header('Content-Type: application/json');
header('Cache-Control: no-cache');

// 记录开始
error_log("=== Batch Delete Debug Started ===");

try {
    // GLPI includes - dynamic path detection
    $glpi_root = dirname(dirname(dirname(__DIR__)));
    if (!file_exists($glpi_root . "/inc/includes.php")) {
        // Try alternative path detection
        $glpi_root = $_SERVER['DOCUMENT_ROOT'];
        if (!file_exists($glpi_root . "/inc/includes.php")) {
            $glpi_root = dirname($_SERVER['DOCUMENT_ROOT']);
        }
    }

    // 捕获include的输出
    ob_start();
    include ($glpi_root . "/inc/includes.php");
    $include_output = ob_get_clean();
    
    if (!empty($include_output)) {
        error_log("GLPI include produced output: " . $include_output);
    }
    
    // 检查认证
    if (!Session::getLoginUserID()) {
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    // 检查权限
    if (!Session::haveRight("config", UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
        exit;
    }

    // 检查数据库连接
    global $DB;
    if (!isset($DB) || !$DB || !$DB->connected) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }

    // 获取POST数据
    $input = $_POST;
    error_log("POST data: " . print_r($input, true));
    
    $action = $input['action'] ?? '';
    $type = $input['type'] ?? '';
    $items = $input['items'] ?? '';
    
    // 解码items
    if (is_string($items)) {
        $items = json_decode($items, true);
    }
    
    error_log("Parsed - Action: $action, Type: $type, Items: " . print_r($items, true));
    
    if ($action !== 'batch_delete') {
        throw new Exception('Invalid action: ' . $action);
    }
    
    if (!in_array($type, ['whitelist', 'blacklist'])) {
        throw new Exception('Invalid type: ' . $type);
    }
    
    if (empty($items) || !is_array($items)) {
        throw new Exception('No items selected');
    }
    
    // 模拟删除操作
    $deleted_count = 0;
    $failed_count = 0;
    $results = [];
    
    foreach ($items as $item_id) {
        $item_id = intval($item_id);
        
        if ($item_id <= 0) {
            $failed_count++;
            $results[] = [
                'id' => $item_id,
                'status' => 'error',
                'message' => 'Invalid item ID'
            ];
            continue;
        }
        
        // 模拟成功删除
        $deleted_count++;
        $results[] = [
            'id' => $item_id,
            'status' => 'success',
            'message' => 'Deleted successfully (debug mode)'
        ];
    }
    
    // 返回成功响应
    $response = [
        'success' => true,
        'deleted_count' => $deleted_count,
        'failed_count' => $failed_count,
        'total_count' => count($items),
        'results' => $results,
        'message' => sprintf('%d items deleted, %d failed (debug mode)', $deleted_count, $failed_count),
        'debug_mode' => true
    ];
    
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'debug_mode' => true,
        'file' => basename(__FILE__),
        'line' => $e->getLine()
    ];
    
    error_log("Error response: " . json_encode($error_response));
    echo json_encode($error_response);
}

error_log("=== Batch Delete Debug Ended ===");
?>
