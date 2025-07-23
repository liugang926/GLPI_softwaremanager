<?php
/**
 * Debug version of batch delete to see what's happening
 */

// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// 设置JSON响应头
header('Content-Type: application/json');

// 记录所有输入
error_log("=== Batch Delete Debug Started ===");
error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
error_log("POST data: " . print_r($_POST, true));
error_log("Raw input: " . file_get_contents('php://input'));

try {
    // 包含GLPI
    $plugin_root = dirname(__DIR__);
    $glpi_root = dirname(dirname($plugin_root));
    
    error_log("Plugin root: " . $plugin_root);
    error_log("GLPI root: " . $glpi_root);
    error_log("GLPI includes exists: " . (file_exists($glpi_root . "/inc/includes.php") ? 'yes' : 'no'));
    
    include_once($glpi_root . "/inc/includes.php");
    
    // 检查用户认证
    $user_id = Session::getLoginUserID();
    error_log("User ID: " . ($user_id ?: 'not logged in'));
    
    if (!$user_id) {
        throw new Exception('用户未认证');
    }

    // 检查权限
    $has_right = Session::haveRight("config", UPDATE);
    error_log("Has config right: " . ($has_right ? 'yes' : 'no'));
    
    if (!$has_right) {
        throw new Exception('权限不足');
    }

    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只允许POST请求');
    }

    // 获取POST数据
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    $items_json = $_POST['items'] ?? '';
    
    error_log("Action: " . $action);
    error_log("Type: " . $type);
    error_log("Items JSON: " . $items_json);

    // 验证参数
    if ($action !== 'batch_delete') {
        throw new Exception('无效的操作: ' . $action);
    }

    if (!in_array($type, ['whitelist', 'blacklist'])) {
        throw new Exception('无效的类型: ' . $type);
    }

    // 解析JSON
    $items = json_decode($items_json, true);
    $json_error = json_last_error();
    
    error_log("JSON decode result: " . print_r($items, true));
    error_log("JSON error: " . $json_error);
    
    if ($json_error !== JSON_ERROR_NONE) {
        throw new Exception('JSON解析错误: ' . json_last_error_msg());
    }
    
    if (!is_array($items) || empty($items)) {
        throw new Exception('没有选择要删除的项目');
    }

    // 测试类加载
    if ($type === 'whitelist') {
        $class_file = $plugin_root . '/inc/softwarewhitelist.class.php';
        error_log("Whitelist class file: " . $class_file);
        error_log("Class file exists: " . (file_exists($class_file) ? 'yes' : 'no'));
        
        if (!class_exists('PluginSoftwaremanagerSoftwareWhitelist')) {
            include_once($class_file);
        }
        
        $obj = new PluginSoftwaremanagerSoftwareWhitelist();
        error_log("Whitelist object created: " . (is_object($obj) ? 'yes' : 'no'));
    } else {
        $class_file = $plugin_root . '/inc/softwareblacklist.class.php';
        error_log("Blacklist class file: " . $class_file);
        error_log("Class file exists: " . (file_exists($class_file) ? 'yes' : 'no'));
        
        if (!class_exists('PluginSoftwaremanagerSoftwareBlacklist')) {
            include_once($class_file);
        }
        
        $obj = new PluginSoftwaremanagerSoftwareBlacklist();
        error_log("Blacklist object created: " . (is_object($obj) ? 'yes' : 'no'));
    }

    // 测试删除第一个项目
    $first_item = intval($items[0]);
    error_log("Testing delete for first item: " . $first_item);
    
    if ($first_item > 0) {
        $delete_result = $obj->delete(['id' => $first_item], true);
        error_log("Delete result: " . ($delete_result ? 'success' : 'failed'));
    }

    // 返回调试信息
    echo json_encode([
        'success' => true,
        'message' => 'Debug completed',
        'debug_info' => [
            'user_id' => $user_id,
            'has_right' => $has_right,
            'action' => $action,
            'type' => $type,
            'items_count' => count($items),
            'first_item' => $first_item ?? 'none',
            'delete_test' => $delete_result ?? 'not tested'
        ]
    ]);

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

error_log("=== Batch Delete Debug Ended ===");
?>
