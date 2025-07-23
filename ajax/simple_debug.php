<?php
/**
 * Simple Debug Test
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置响应头
header('Content-Type: application/json');

// 记录到错误日志
error_log("=== Simple Debug Test Started ===");

try {
    // 测试基本PHP功能
    $test_data = [
        'php_version' => PHP_VERSION,
        'current_time' => date('Y-m-d H:i:s'),
        'post_data' => $_POST,
        'get_data' => $_GET,
        'server_info' => [
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'not set'
        ]
    ];
    
    // 尝试路径检测
    $current_dir = __DIR__;
    $plugin_root = dirname($current_dir);
    $glpi_root = dirname(dirname($plugin_root));
    
    $test_data['paths'] = [
        'current_dir' => $current_dir,
        'plugin_root' => $plugin_root,
        'glpi_root' => $glpi_root,
        'glpi_includes_exists' => file_exists($glpi_root . "/inc/includes.php") ? 'yes' : 'no'
    ];
    
    // 尝试包含GLPI
    if (file_exists($glpi_root . "/inc/includes.php")) {
        ob_start();
        include_once($glpi_root . "/inc/includes.php");
        $include_output = ob_get_clean();
        
        $test_data['glpi_include'] = [
            'success' => true,
            'output' => $include_output,
            'session_exists' => class_exists('Session') ? 'yes' : 'no',
            'db_exists' => isset($GLOBALS['DB']) ? 'yes' : 'no'
        ];
        
        // 检查用户认证
        if (class_exists('Session')) {
            $test_data['auth'] = [
                'user_id' => Session::getLoginUserID() ?? 'not logged in',
                'has_config_right' => Session::haveRight("config", UPDATE) ? 'yes' : 'no'
            ];
        }
    } else {
        $test_data['glpi_include'] = [
            'success' => false,
            'error' => 'GLPI includes.php not found'
        ];
    }
    
    error_log("Test data: " . print_r($test_data, true));
    echo json_encode($test_data, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $error_data = [
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    error_log("Error: " . print_r($error_data, true));
    echo json_encode($error_data, JSON_PRETTY_PRINT);
}

error_log("=== Simple Debug Test Ended ===");
?>
