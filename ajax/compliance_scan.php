<?php
/**
 * 新的合规性扫描 - 真正的软件合规检查
 */

include('../../../inc/includes.php');

// Set JSON response header first and enable error reporting for debugging
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0); // Don't display errors directly
error_reporting(E_ALL);

// Clean any previous output
while (ob_get_level()) {
    ob_end_clean();
}

// Start output buffering to catch any unwanted output
ob_start();

/**
 * 新的通配符匹配函数
 * @param string $software_name 软件名称
 * @param string $rule_pattern 规则模式 (可能包含*)
 * @return bool 是否匹配
 */
function matchSoftwareRule($software_name, $rule_pattern) {
    $software_lower = strtolower(trim($software_name));
    $pattern_lower = strtolower(trim($rule_pattern));
    
    // 如果规则不包含星号，进行精确匹配（不区分大小写）
    if (strpos($pattern_lower, '*') === false) {
        return $software_lower === $pattern_lower;
    }
    
    // 处理通配符匹配
    if ($pattern_lower === '*') {
        return true; // 匹配所有
    }
    
    // 转换通配符规则为正则表达式
    // * 替换为 .*（匹配任意字符）
    $regex_pattern = str_replace('*', '.*', preg_quote($pattern_lower, '/'));
    $regex = '/^' . $regex_pattern . '$/i';
    
    return preg_match($regex, $software_lower) === 1;
}

/**
 * 提取软件基础名称（去除版本号等）
 * @param string $software_name
 * @return string
 */
function extractBaseSoftwareName($software_name) {
    $name = strtolower(trim($software_name));
    
    // 移除常见的版本模式
    $patterns = [
        '/\s+\d+(\.\d+)*/',           // 版本号 "2022", "1.0.1" 
        '/\s+\(\d+-bit\)/',           // "(64-bit)", "(32-bit)"
        '/\s+\(x\d+\)/',              // "(x64)", "(x86)"
        '/\s+v\d+(\.\d+)*/',          // "v1.0"
        '/\s+version\s+\d+/',         // "version 2022"
        '/\s+\d{4}/',                 // 年份 "2022", "2023"
        '/\s+(premium|professional|standard|basic|lite)$/i', // 版本类型
    ];
    
    foreach ($patterns as $pattern) {
        $name = preg_replace($pattern, '', $name);
    }
    
    return trim($name);
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

    $scan_start_time = microtime(true);
    $scan_time = date('Y-m-d H:i:s');
    $user_id = Session::getLoginUserID();

    // 步骤1: 获取所有实际的软件安装记录（修复：包含所有有安装记录的软件）
    $installation_query = "
        SELECT 
            s.id as software_id,
            s.name as software_name,
            sv.name as software_version,
            isv.date_install,
            c.id as computer_id,
            c.name as computer_name,
            c.serial as computer_serial,
            u.id as user_id,
            u.name as user_name,
            u.realname as user_realname,
            e.name as entity_name
        FROM `glpi_softwares` s
        LEFT JOIN `glpi_softwareversions` sv ON (sv.softwares_id = s.id)
        LEFT JOIN `glpi_items_softwareversions` isv ON (
            isv.softwareversions_id = sv.id
            AND isv.itemtype = 'Computer'
            AND isv.is_deleted = 0
        )
        LEFT JOIN `glpi_computers` c ON (
            c.id = isv.items_id
            AND c.is_deleted = 0
            AND c.is_template = 0
        )
        LEFT JOIN `glpi_users` u ON (c.users_id = u.id)
        LEFT JOIN `glpi_entities` e ON (c.entities_id = e.id)
        WHERE s.is_deleted = 0 
        AND isv.id IS NOT NULL
        ORDER BY s.name, c.name
    ";

    $installations = [];
    $result = $DB->query($installation_query);
    if ($result) {
        while ($row = $DB->fetchAssoc($result)) {
            $installations[] = $row;
        }
    } else {
        error_log("DEBUG: Installation query failed: " . $DB->error());
        error_log("DEBUG: Query was: " . $installation_query);
    }

    // 步骤2: 获取白名单和黑名单
    $whitelists = [];
    $blacklists = [];
    
    // 获取活跃的白名单（包含通配符模式）
    if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
        $whitelist_result = $DB->query("SELECT name FROM `glpi_plugin_softwaremanager_whitelists` WHERE is_active = 1");
        if ($whitelist_result) {
            while ($row = $DB->fetchAssoc($whitelist_result)) {
                $whitelists[] = trim($row['name']);
            }
        }
    }
    
    // 获取活跃的黑名单（包含通配符模式）
    if ($DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
        $blacklist_result = $DB->query("SELECT name FROM `glpi_plugin_softwaremanager_blacklists` WHERE is_active = 1");
        if ($blacklist_result) {
            while ($row = $DB->fetchAssoc($blacklist_result)) {
                $blacklists[] = trim($row['name']);
            }
        }
    }

    // 调试信息：显示获取到的数据
    error_log("DEBUG: Found " . count($installations) . " installations");
    error_log("DEBUG: Whitelist rules: " . count($whitelists));
    error_log("DEBUG: Blacklist rules: " . count($blacklists));
    
    // 步骤3: 按电脑分组软件安装，进行去重处理
    $installations_by_computer = [];
    foreach ($installations as $installation) {
        $computer_id = $installation['computer_id'];
        $software_base_name = extractBaseSoftwareName($installation['software_name']);
        
        // 使用电脑ID和软件基础名称作为键进行去重
        $key = $computer_id . '_' . $software_base_name;
        
        // 只保留第一个或最新的安装记录
        if (!isset($installations_by_computer[$key]) || 
            $installation['date_install'] > $installations_by_computer[$key]['date_install']) {
            $installations_by_computer[$key] = $installation;
        }
    }
    
    // 转换回数组格式
    $unique_installations = array_values($installations_by_computer);
    
    // 步骤4: 对去重后的安装记录进行合规性检查
    $approved_installations = [];
    $blacklisted_installations = [];
    $unmanaged_installations = [];
    
    foreach ($unique_installations as $installation) {
        $software_name = $installation['software_name'];
        $compliance_status = 'unmanaged';
        $matched_rule = '';
        
        // 检查是否在黑名单中（优先级最高）
        foreach ($blacklists as $blacklist_rule) {
            if (matchSoftwareRule($software_name, $blacklist_rule)) {
                $compliance_status = 'blacklisted';
                $matched_rule = $blacklist_rule;
                break;
            }
        }
        
        // 如果不在黑名单中，检查是否在白名单中
        if ($compliance_status === 'unmanaged') {
            $in_whitelist = false;
            foreach ($whitelists as $whitelist_rule) {
                if (matchSoftwareRule($software_name, $whitelist_rule)) {
                    $compliance_status = 'approved';
                    $matched_rule = $whitelist_rule;
                    $in_whitelist = true;
                    break;
                }
            }
            
            // 根据需求：不在白名单范围内的记录为未登记软件
            if (!$in_whitelist) {
                $compliance_status = 'unmanaged';
                $matched_rule = '';
            }
        }
        
        // 添加合规状态和匹配规则到安装记录
        $installation['compliance_status'] = $compliance_status;
        $installation['matched_rule'] = $matched_rule;
        
        // 分类存储
        switch ($compliance_status) {
            case 'approved':
                $approved_installations[] = $installation;
                break;
            case 'blacklisted':
                $blacklisted_installations[] = $installation;
                break;
            default:
                $unmanaged_installations[] = $installation;
                break;
        }
    }

    // 步骤5: 生成统计数据（基于去重后的数据）
    $total_installations = count($unique_installations);  // 使用去重后的数量
    $approved_count = count($approved_installations);
    $blacklisted_count = count($blacklisted_installations);
    $unmanaged_count = count($unmanaged_installations);
    
    // 调试信息：显示统计结果
    error_log("DEBUG: Total installations: $total_installations");
    error_log("DEBUG: Approved: $approved_count, Blacklisted: $blacklisted_count, Unmanaged: $unmanaged_count");
    
    // 计算扫描持续时间
    $scan_duration = round((microtime(true) - $scan_start_time) * 1000); // 毫秒

    // 步骤5: 创建扫描历史记录（使用传统方式，避免依赖新模型类）
    $insert_query = "INSERT INTO `glpi_plugin_softwaremanager_scanhistory`
                     (`user_id`, `scan_date`, `total_software`, `whitelist_count`, `blacklist_count`, `unmanaged_count`, `status`, `scan_duration`)
                     VALUES ($user_id, '$scan_time', $total_installations, $approved_count, $blacklisted_count, $unmanaged_count, 'completed', $scan_duration)";

    error_log("DEBUG: Insert query: " . $insert_query);

    $result = $DB->query($insert_query);
    if (!$result) {
        throw new Exception('Failed to insert scan record: ' . $DB->error());
    }
    
    $scan_id = $DB->insertId();
    if (!$scan_id) {
        throw new Exception('Insert succeeded but no ID returned');
    }

    // 步骤6: 可选 - 存储详细的违规记录（用于详细报告）
    // 这里可以创建一个详细的违规记录表来存储具体的违规安装

    // 生成摘要报告
    $violation_summary = '';
    if ($blacklisted_count > 0) {
        $violation_summary .= "发现 {$blacklisted_count} 个黑名单违规安装！";
    }
    if ($unmanaged_count > 0) {
        $violation_summary .= ($violation_summary ? ' ' : '') . "发现 {$unmanaged_count} 个未登记软件安装需要审查。";
    }
    if ($blacklisted_count === 0 && $unmanaged_count === 0) {
        $violation_summary = "所有软件安装均符合合规要求。";
    }

    // Clean output buffer before sending JSON
    if (ob_get_level()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => true,
        'message' => "合规性扫描完成！扫描了 {$total_installations} 个软件安装。{$violation_summary}",
        'scan_id' => $scan_id,
        'scan_duration' => $scan_duration,
        'stats' => [
            'total_installations' => $total_installations,
            'approved_count' => $approved_count,
            'blacklisted_count' => $blacklisted_count,
            'unmanaged_count' => $unmanaged_count,
            'whitelist_rules' => count($whitelists),
            'blacklist_rules' => count($blacklists)
        ],
        'violations' => [
            'blacklisted_count' => $blacklisted_count,
            'unmanaged_count' => $unmanaged_count,
            'needs_attention' => ($blacklisted_count > 0 || $unmanaged_count > 0)
        ]
    ]);

} catch (Exception $e) {
    // Clear any output buffer that might contain error messages
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Log the full error for debugging
    error_log("COMPLIANCE_SCAN ERROR: " . $e->getMessage() . " in " . $e->getFile() . " at line " . $e->getLine());
    error_log("STACK TRACE: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '合规性扫描时发生错误: ' . $e->getMessage(),
        'debug_info' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'php_version' => PHP_VERSION,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Error $e) {
    // Catch PHP fatal errors
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    error_log("COMPLIANCE_SCAN FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " at line " . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'PHP致命错误: ' . $e->getMessage(),
        'debug_info' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
} finally {
    // Ensure output buffer is properly handled
    if (ob_get_level()) {
        $unwanted_output = ob_get_contents();
        ob_end_clean();
        
        if (!empty(trim($unwanted_output))) {
            error_log("UNWANTED OUTPUT DETECTED: " . $unwanted_output);
        }
    }
}
?>