<?php
/**
 * Test CSRF token validation
 */

// 清理输出缓冲
while (ob_get_level()) {
    ob_end_clean();
}

// 设置错误处理
error_reporting(E_ALL);
ini_set('display_errors', 0); // 不显示错误到页面

try {
    // 包含 GLPI
    include('../../../inc/includes.php');
    
    $result = [
        'success' => true,
        'message' => 'GLPI loaded successfully',
        'user_id' => Session::getLoginUserID(),
        'user_name' => $_SESSION['glpiname'] ?? 'Unknown',
        'csrf_token_received' => isset($_POST['_glpi_csrf_token']) ? 'YES' : 'NO',
        'csrf_token_value' => $_POST['_glpi_csrf_token'] ?? 'NOT_PROVIDED',
        'post_data' => $_POST
    ];
    
    // 尝试 CSRF 检查
    if (isset($_POST['_glpi_csrf_token'])) {
        try {
            Session::checkCSRF();
            $result['csrf_check'] = 'PASSED';
        } catch (Exception $e) {
            $result['csrf_check'] = 'FAILED: ' . $e->getMessage();
        }
    } else {
        $result['csrf_check'] = 'NO_TOKEN_PROVIDED';
    }
    
    // 检查权限
    $result['has_config_right'] = Session::haveRight('config', READ);
    $result['has_plugin_right'] = Session::haveRight('plugin_softwaremanager', READ);
    
} catch (Exception $e) {
    $result = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

// 输出 JSON
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
exit;
?>
