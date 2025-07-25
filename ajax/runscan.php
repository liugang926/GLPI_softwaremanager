<?php
/**
 * AJAX endpoint to run a new software scan.
 * --- FINAL VERSION WITH GLPI_AJAX DECLARED ---
 */

// 1. 在加载任何GLPI文件之前，首先定义这是一个AJAX请求
define('GLPI_AJAX', true);

// 2. 现在，正常加载GLPI的标准环境
include ('../../../inc/includes.php');
global $DB;

// 2. 调试日志记录 (我们暂时保留它以确认问题解决)
$sent_token   = $_POST['_glpi_csrf_token'] ?? 'TOKEN NOT SENT BY CLIENT';
$server_token = $_SESSION['glpi_csrf_token'] ?? 'SERVER TOKEN NOT SET';

// -- 这里是修正过的关键行 --
$user_id = Session::getLoginUserID() ?? 'USER NOT LOGGED IN'; // 使用 getLoginUserID

$log_message = sprintf(
    "[%s] RUNSCAN DEBUG: UserID=%s | SentToken=%s | ServerToken=%s\n",
    date('Y-m-d H:i:s'),
    $user_id,
    $sent_token,
    $server_token
);

if (is_writable(GLPI_LOG_DIR) && is_dir(GLPI_LOG_DIR)) {
    file_put_contents(GLPI_LOG_DIR . '/php-errors.log', $log_message, FILE_APPEND);
}

// 3. 执行CSRF检查
if (!Session::checkCSRFToken($sent_token)) {
    http_response_code(403); // 使用 403 Forbidden 更准确
    header('Content-Type: application/json');
    // 在返回的JSON中也包含这两个令牌，方便在浏览器中直接对比
    echo json_encode([
        'error'        => 'CSRF Token Mismatch. See server log for details (files/_log/php-errors.log).',
        'sent_token'   => $sent_token,
        'server_token' => $server_token
    ]);
    exit();
}

// 4. 如果检查通过，执行核心逻辑 (这部分代码不变)
try {
    $scan_query = "SELECT * FROM `glpi_computers` WHERE `is_deleted` = 0";
    $computers_to_scan = $DB->request($scan_query);
    $count = count($computers_to_scan);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "CSRF Check Passed! Scan task started for " . $count . " computers."
    ]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred during the scan: ' . $e->getMessage()
    ]);
}

exit();