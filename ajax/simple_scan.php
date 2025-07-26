<?php
/**
 * Fixed simple scan based on debug results
 */

include('../../../inc/includes.php');

// Set JSON response header first
header('Content-Type: application/json; charset=UTF-8');

// Clean any previous output
while (ob_get_level()) {
    ob_end_clean();
}

try {
    // 检查用户登录
    if (!Session::getLoginUserID()) {
        echo json_encode([
            'success' => false,
            'error' => 'User not logged in'
        ]);
        exit;
    }

    global $DB;
    if (!$DB) {
        throw new Exception('Database connection not available');
    }

    // 简化的扫描逻辑：基于现有软件列表数据创建审计快照
    $total_software = 0;
    $whitelist_count = 0;
    $blacklist_count = 0;
    $unmanaged_count = 0;

    // 获取软件总数 - 添加错误处理
    $software_query = "SELECT COUNT(*) as total FROM `glpi_softwares` WHERE `is_deleted` = 0";
    $result = $DB->query($software_query);
    if ($result && $row = $DB->fetchAssoc($result)) {
        $total_software = (int)$row['total'];
    } else {
        throw new Exception('Failed to count software: ' . $DB->error());
    }

    // 获取白名单数量 - 添加错误处理
    $whitelist_query = "SELECT COUNT(*) as count FROM `glpi_plugin_softwaremanager_whitelists`";
    $result = $DB->query($whitelist_query);
    if ($result && $row = $DB->fetchAssoc($result)) {
        $whitelist_count = (int)$row['count'];
    } else {
        throw new Exception('Failed to count whitelist: ' . $DB->error());
    }

    // 获取黑名单数量 - 添加错误处理
    $blacklist_query = "SELECT COUNT(*) as count FROM `glpi_plugin_softwaremanager_blacklists`";
    $result = $DB->query($blacklist_query);
    if ($result && $row = $DB->fetchAssoc($result)) {
        $blacklist_count = (int)$row['count'];
    } else {
        throw new Exception('Failed to count blacklist: ' . $DB->error());
    }

    // 计算未管理数量
    $unmanaged_count = $total_software - $whitelist_count - $blacklist_count;
    if ($unmanaged_count < 0) $unmanaged_count = 0;

    // 创建扫描历史记录（审计快照）- 使用与调试版本相同的格式
    $scan_time = date('Y-m-d H:i:s');
    $user_id = Session::getLoginUserID();

    $insert_query = "INSERT INTO `glpi_plugin_softwaremanager_scanhistory`
                     (`user_id`, `scan_date`, `total_software`, `whitelist_count`, `blacklist_count`, `unmanaged_count`, `status`)
                     VALUES ($user_id, '$scan_time', $total_software, $whitelist_count, $blacklist_count, $unmanaged_count, 'completed')";

    $result = $DB->query($insert_query);
    if (!$result) {
        throw new Exception('Failed to insert scan record: ' . $DB->error());
    }
    
    $scan_id = $DB->insertId();
    if (!$scan_id) {
        throw new Exception('Insert succeeded but no ID returned');
    }

    // 验证插入
    $verify_query = "SELECT * FROM `glpi_plugin_softwaremanager_scanhistory` WHERE id = $scan_id";
    $verify_result = $DB->query($verify_query);
    if (!$verify_result || !$DB->fetchAssoc($verify_result)) {
        throw new Exception('Insert verification failed');
    }

    echo json_encode([
        'success' => true,
        'message' => "扫描完成！总计 {$total_software} 个软件，白名单 {$whitelist_count} 个，黑名单 {$blacklist_count} 个，未管理 {$unmanaged_count} 个。",
        'scan_id' => $scan_id,
        'stats' => [
            'total_software' => $total_software,
            'whitelist_count' => $whitelist_count,
            'blacklist_count' => $blacklist_count,
            'unmanaged_count' => $unmanaged_count
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '扫描时发生错误: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
