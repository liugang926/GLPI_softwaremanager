<?php
/**
 * AJAX endpoint to run a new software scan.
 */

include('../../../inc/includes.php');

// 检查用户登录
Session::checkLoginUser();

// 检查 CSRF 令牌 - 使用正确的 GLPI 方式
Session::checkCSRF();

// 简化的扫描逻辑：基于现有软件列表数据创建审计快照
try {
    global $DB;

    // 直接计算统计数据（简化版本）
    $total_software = 0;
    $whitelist_count = 0;
    $blacklist_count = 0;
    $unmanaged_count = 0;

    // 获取软件总数
    $software_query = "SELECT COUNT(*) as total FROM `glpi_softwares` WHERE `is_deleted` = 0";
    $result = $DB->query($software_query);
    if ($result && $row = $DB->fetchAssoc($result)) {
        $total_software = (int)$row['total'];
    }

    // 获取白名单数量
    $whitelist_query = "SELECT COUNT(*) as count FROM `glpi_plugin_softwaremanager_softwarewhitelists`";
    $result = $DB->query($whitelist_query);
    if ($result && $row = $DB->fetchAssoc($result)) {
        $whitelist_count = (int)$row['count'];
    }

    // 获取黑名单数量
    $blacklist_query = "SELECT COUNT(*) as count FROM `glpi_plugin_softwaremanager_softwareblacklists`";
    $result = $DB->query($blacklist_query);
    if ($result && $row = $DB->fetchAssoc($result)) {
        $blacklist_count = (int)$row['count'];
    }

    // 计算未管理数量
    $unmanaged_count = $total_software - $whitelist_count - $blacklist_count;
    if ($unmanaged_count < 0) $unmanaged_count = 0;

    // 创建扫描历史记录（审计快照）
    $scan_time = date('Y-m-d H:i:s');
    $user_id = Session::getLoginUserID();

    $insert_query = "INSERT INTO `glpi_plugin_softwaremanager_scanhistories`
                     (`user_id`, `scan_time`, `total_software`, `whitelist_count`, `blacklist_count`, `unmanaged_count`)
                     VALUES ('$user_id', '$scan_time', '$total_software', '$whitelist_count', '$blacklist_count', '$unmanaged_count')";

    $result = $DB->query($insert_query);
    $scan_id = $DB->insertId();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "审计快照已创建！总计 {$total_software} 个软件，白名单 {$whitelist_count} 个，黑名单 {$blacklist_count} 个，未管理 {$unmanaged_count} 个。",
        'scan_id' => $scan_id,
        'stats' => [
            'total_software' => $total_software,
            'whitelist_count' => $whitelist_count,
            'blacklist_count' => $blacklist_count,
            'unmanaged_count' => $unmanaged_count
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => '创建审计快照时发生错误: ' . $e->getMessage()
    ]);
}
?>