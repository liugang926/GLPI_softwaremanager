<?php
/**
 * Debug access issues - Clean version
 */

// 清理输出缓冲
while (ob_get_level()) {
    ob_end_clean();
}

// 开始新的输出缓冲
ob_start();

try {
    include('../../../inc/includes.php');
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'GLPI include failed: ' . $e->getMessage()]);
    exit;
}

// 清理任何 GLPI 输出
ob_end_clean();

// 收集调试信息
$debug_info = [
    'step1_glpi_loaded' => true,
    'step2_session_id' => session_id(),
    'step3_user_id' => Session::getLoginUserID(),
    'step4_user_name' => $_SESSION['glpiname'] ?? 'Unknown',
    'step5_post_data' => $_POST,
    'step6_csrf_token_present' => isset($_POST['_glpi_csrf_token']),
    'step7_has_config_read' => Session::haveRight('config', READ),
    'step8_has_plugin_right' => Session::haveRight('plugin_softwaremanager', READ),
    'step9_session_keys' => array_keys($_SESSION)
];

// CSRF 检查
if (isset($_POST['_glpi_csrf_token'])) {
    try {
        Session::checkCSRF($_POST);
        $debug_info['step10_csrf_check'] = 'PASSED';
    } catch (Exception $e) {
        $debug_info['step10_csrf_check'] = 'FAILED: ' . $e->getMessage();
    }
} else {
    $debug_info['step10_csrf_check'] = 'NO_TOKEN';
}

// 设置 JSON 头并返回结果
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Debug completed successfully',
    'debug_info' => $debug_info
], JSON_PRETTY_PRINT);
?>
