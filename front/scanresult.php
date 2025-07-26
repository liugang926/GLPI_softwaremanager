<?php
/**
 * Software Manager Plugin for GLPI
 * Scan Result Details Page
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

include('../../../inc/includes.php');

// Check rights - using standard GLPI permissions
Session::checkRight('config', READ);

// Check if plugin is activated
$plugin = new Plugin();
if (!$plugin->isInstalled('softwaremanager') || !$plugin->isActivated('softwaremanager')) {
    Html::displayNotFoundError();
}

// Get scan history ID from parameters
$scanhistory_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$scanhistory_id) {
    Html::displayErrorAndDie(__('Invalid scan ID', 'softwaremanager'));
}

// Load scan history record directly from database
global $DB;
$query = "SELECT s.*, u.name as user_name 
          FROM `glpi_plugin_softwaremanager_scanhistory` s 
          LEFT JOIN `glpi_users` u ON s.user_id = u.id 
          WHERE s.id = $scanhistory_id";

$result = $DB->query($query);
if (!$result || !($scan_data = $DB->fetchAssoc($result))) {
    Html::displayErrorAndDie(__('Scan record not found', 'softwaremanager'));
}

Html::header(__('Scan Result Details', 'softwaremanager'), $_SERVER['PHP_SELF'], 'admin');

// Display navigation
PluginSoftwaremanagerMenu::displayNavigationHeader('scanhistory');

// Display scan summary
echo "<div class='scan-summary' style='margin-bottom: 20px; padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px;'>";
echo "<h3><i class='fas fa-chart-bar'></i> " . __('合规性扫描摘要', 'softwaremanager') . "</h3>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<table class='table table-borderless'>";
echo "<tr><td><strong>" . __('Scan Date:', 'softwaremanager') . "</strong></td><td>" . Html::convDateTime($scan_data['scan_date']) . "</td></tr>";
echo "<tr><td><strong>" . __('Status:', 'softwaremanager') . "</strong></td><td>";
$status_class = $scan_data['status'] == 'completed' ? 'success' : ($scan_data['status'] == 'test' ? 'info' : 'secondary');
echo "<span class='badge badge-{$status_class}'>" . ucfirst($scan_data['status']) . "</span>";
echo "</td></tr>";
echo "<tr><td><strong>" . __('Executed by:', 'softwaremanager') . "</strong></td><td>" . ($scan_data['user_name'] ?? 'Unknown') . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<table class='table table-borderless'>";
echo "<tr><td><strong>软件安装总数:</strong></td><td><span class='badge badge-info badge-lg'>" . $scan_data['total_software'] . "</span></td></tr>";
echo "<tr><td><strong>合规安装数量:</strong></td><td><span class='badge badge-success badge-lg'>" . $scan_data['whitelist_count'] . "</span></td></tr>";
echo "<tr><td><strong>违规安装数量:</strong></td><td><span class='badge badge-danger badge-lg'>" . $scan_data['blacklist_count'] . "</span></td></tr>";
echo "<tr><td><strong>未登记安装数量:</strong></td><td><span class='badge badge-warning badge-lg'>" . $scan_data['unmanaged_count'] . "</span></td></tr>";
echo "</table>";
echo "</div>";
echo "</div>";

echo "</div>";

// Display software details
echo "<div class='software-details'>";
echo "<h3><i class='fas fa-list'></i> " . __('详细合规性安装报告', 'softwaremanager') . "</h3>";

// Get detailed software installations with computer and user information
// Using the same structure as the software inventory class

// 首先检查白名单和黑名单表是否存在以及数据情况
$whitelist_debug = [];
$blacklist_debug = [];

if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
    $wl_result = $DB->query("SELECT COUNT(*) as count FROM `glpi_plugin_softwaremanager_whitelists` WHERE is_active = 1");
    if ($wl_result && $row = $DB->fetchAssoc($wl_result)) {
        $whitelist_debug['count'] = $row['count'];
    }
    
    $wl_sample = $DB->query("SELECT name FROM `glpi_plugin_softwaremanager_whitelists` WHERE is_active = 1 LIMIT 3");
    $whitelist_debug['samples'] = [];
    if ($wl_sample) {
        while ($row = $DB->fetchAssoc($wl_sample)) {
            $whitelist_debug['samples'][] = $row['name'];
        }
    }
} else {
    $whitelist_debug['error'] = 'Table does not exist';
}

if ($DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
    $bl_result = $DB->query("SELECT COUNT(*) as count FROM `glpi_plugin_softwaremanager_blacklists` WHERE is_active = 1");
    if ($bl_result && $row = $DB->fetchAssoc($bl_result)) {
        $blacklist_debug['count'] = $row['count'];
    }
    
    $bl_sample = $DB->query("SELECT name FROM `glpi_plugin_softwaremanager_blacklists` WHERE is_active = 1 LIMIT 3");
    $blacklist_debug['samples'] = [];
    if ($bl_sample) {
        while ($row = $DB->fetchAssoc($bl_sample)) {
            $blacklist_debug['samples'][] = $row['name'];
        }
    }
} else {
    $blacklist_debug['error'] = 'Table does not exist';
}

echo "<div class='alert alert-info'>";
echo "<strong>合规规则调试信息:</strong><br>";
echo "白名单规则: " . ($whitelist_debug['count'] ?? 0) . " 条";
if (!empty($whitelist_debug['samples'])) {
    echo " (示例: " . implode(', ', $whitelist_debug['samples']) . ")";
}
echo "<br>黑名单规则: " . ($blacklist_debug['count'] ?? 0) . " 条";
if (!empty($blacklist_debug['samples'])) {
    echo " (示例: " . implode(', ', $blacklist_debug['samples']) . ")";
}
echo "</div>";

// 使用与compliance_scan.php完全相同的查询逻辑来获取扫描数据
$software_query = "SELECT 
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
                   e.name as entity_name,
                   '{$scan_data['scan_date']}' as scan_date
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
                   ORDER BY s.name, c.name";

$software_result = $DB->query($software_query);

// 添加与compliance_scan.php相同的去重逻辑
$installations = [];
if ($software_result) {
    while ($row = $DB->fetchAssoc($software_result)) {
        $installations[] = $row;
    }
}

// 按电脑分组软件安装，进行去重处理（与compliance_scan.php相同逻辑）
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

/**
 * 提取软件基础名称（去除版本号等） - 与compliance_scan.php相同的函数
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

// 手动进行合规性检查，使用去重后的数据
$installations_with_compliance = [];
if (count($unique_installations) > 0) {
    // 获取白名单和黑名单规则
    $whitelists = [];
    $blacklists = [];
    
    // 使用与compliance_scan.php相同的表名和逻辑
    if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
        $wl_result = $DB->query("SELECT name FROM `glpi_plugin_softwaremanager_whitelists` WHERE is_active = 1");
        if ($wl_result) {
            while ($row = $DB->fetchAssoc($wl_result)) {
                $whitelists[] = trim($row['name']);
            }
        }
    }
    
    if ($DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
        $bl_result = $DB->query("SELECT name FROM `glpi_plugin_softwaremanager_blacklists` WHERE is_active = 1");
        if ($bl_result) {
            while ($row = $DB->fetchAssoc($bl_result)) {
                $blacklists[] = trim($row['name']);
            }
        }
    }
    
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
    
    // 调试信息：显示实际获取的规则数量
    echo "<div class='alert alert-info'>";
    echo "<strong>规则检查调试:</strong><br>";
    echo "获取到白名单规则: " . count($whitelists) . " 条<br>";
    echo "获取到黑名单规则: " . count($blacklists) . " 条<br>";
    if (count($whitelists) > 0) {
        $wl_samples = [];
        foreach (array_slice($whitelists, 0, 3) as $rule) {
            $match_type = (strpos($rule, '*') !== false) ? '通配符' : '精确';
            $wl_samples[] = "'$rule' ($match_type)";
        }
        echo "白名单示例: " . implode(', ', $wl_samples) . "<br>";
    }
    if (count($blacklists) > 0) {
        $bl_samples = [];
        foreach (array_slice($blacklists, 0, 3) as $rule) {
            $match_type = (strpos($rule, '*') !== false) ? '通配符' : '精确';
            $bl_samples[] = "'$rule' ($match_type)";
        }
        echo "黑名单示例: " . implode(', ', $bl_samples) . "<br>";
    }
    echo "</div>";
    
    // 重置结果指针并处理每条记录
    $compliance_debug = ['approved' => 0, 'blacklisted' => 0, 'unmanaged' => 0];
    $sample_matches = ['approved' => [], 'blacklisted' => [], 'unmanaged' => []];
    
    foreach ($unique_installations as $installation) {
        $software_name_lower = strtolower(trim($installation['software_name']));
        $compliance_status = 'unmanaged';
        $matched_rule = '';
        
        // 检查黑名单（优先级最高）
        foreach ($blacklists as $blacklist_rule) {
            if (matchSoftwareRule($software_name_lower, $blacklist_rule)) {
                $compliance_status = 'blacklisted';
                $matched_rule = $blacklist_rule;
                break;
            }
        }
        
        // 如果不在黑名单中，检查白名单
        if ($compliance_status === 'unmanaged') {
            $in_whitelist = false;
            foreach ($whitelists as $whitelist_rule) {
                if (matchSoftwareRule($software_name_lower, $whitelist_rule)) {
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
        
        $installation['compliance_status'] = $compliance_status;
        $installation['matched_rule'] = $matched_rule;
        $installations_with_compliance[] = $installation;
        $compliance_debug[$compliance_status]++;
        
        // 收集每个分类的示例（最多5个）
        if (count($sample_matches[$compliance_status]) < 5) {
            $sample_matches[$compliance_status][] = [
                'software' => $installation['software_name'],
                'rule' => $matched_rule ?: '无匹配规则',
                'computer' => $installation['computer_name']
            ];
        }
    }
    
    // 显示合规性检查结果调试信息
    echo "<div class='alert alert-success'>";
    echo "<strong>合规性检查结果:</strong><br>";
    echo "合规安装: " . $compliance_debug['approved'] . " 条<br>";
    echo "违规安装: " . $compliance_debug['blacklisted'] . " 条<br>";
    echo "未登记安装: " . $compliance_debug['unmanaged'] . " 条<br>";
    echo "总计处理: " . count($installations_with_compliance) . " 条<br><br>";
    
    // 显示匹配示例
    foreach(['approved' => '✅ 合规安装示例', 'blacklisted' => '❌ 违规安装示例', 'unmanaged' => '❓ 未登记安装示例'] as $status => $title) {
        if (count($sample_matches[$status]) > 0) {
            echo "<strong>{$title}:</strong><br>";
            foreach($sample_matches[$status] as $sample) {
                echo "• <strong>{$sample['software']}</strong> (计算机: {$sample['computer']}) → 匹配规则: <em>{$sample['rule']}</em><br>";
            }
            echo "<br>";
        } else {
            echo "<strong>{$title}:</strong> 无<br><br>";
        }
    }
    echo "</div>";
}

echo "<div class='alert alert-warning'>";
echo "<strong>Debug Info:</strong> Query executed. ";
if (count($unique_installations) > 0) {
    $result_count = count($unique_installations);
    echo "Found {$result_count} unique installation records after deduplication.";
    echo "<br><strong>Original records:</strong> " . count($installations) . " → <strong>After deduplication:</strong> " . count($unique_installations);
} else {
    echo "No installation records found.";
}
echo "</div>";

if (count($unique_installations) > 0) {
    $total_installations = count($installations_with_compliance);
    
    // Count by status using the processed compliance data
    $status_counts = ['blacklisted' => 0, 'unmanaged' => 0, 'approved' => 0];
    foreach ($installations_with_compliance as $installation) {
        if (isset($status_counts[$installation['compliance_status']])) {
            $status_counts[$installation['compliance_status']]++;
        }
    }
    
    // Filter tabs - 始终显示所有标签，即使数量为0
    echo "<ul class='nav nav-tabs' id='softwareTabs' role='tablist' style='margin-bottom: 20px;'>";
    echo "<li class='nav-item'>";
    echo "<a class='nav-link active' id='all-tab' data-toggle='tab' href='#all' role='tab'>" . __('全部安装', 'softwaremanager') . " ({$total_installations})</a>";
    echo "</li>";
    
    // 违规安装标签 - 始终显示
    echo "<li class='nav-item'>";
    $blacklist_class = $status_counts['blacklisted'] > 0 ? 'text-danger' : 'text-muted';
    echo "<a class='nav-link {$blacklist_class}' id='blacklist-tab' data-toggle='tab' href='#blacklist' role='tab'>" . __('违规安装', 'softwaremanager') . " ({$status_counts['blacklisted']})</a>";
    echo "</li>";
    
    // 未登记安装标签 - 始终显示
    echo "<li class='nav-item'>";
    $unmanaged_class = $status_counts['unmanaged'] > 0 ? 'text-warning' : 'text-muted';
    echo "<a class='nav-link {$unmanaged_class}' id='unmanaged-tab' data-toggle='tab' href='#unmanaged' role='tab'>" . __('未登记安装', 'softwaremanager') . " ({$status_counts['unmanaged']})</a>";
    echo "</li>";
    
    // 合规安装标签 - 始终显示
    echo "<li class='nav-item'>";
    $approved_class = $status_counts['approved'] > 0 ? 'text-success' : 'text-muted';
    echo "<a class='nav-link {$approved_class}' id='approved-tab' data-toggle='tab' href='#approved' role='tab'>" . __('合规安装', 'softwaremanager') . " ({$status_counts['approved']})</a>";
    echo "</li>";
    
    echo "</ul>";

    echo "<div class='tab-content' id='softwareTabsContent'>";

    // All installations tab
    echo "<div class='tab-pane fade show active' id='all' role='tabpanel'>";
    displayInstallationTable($installations_with_compliance, 'all');
    echo "</div>";

    // Blacklist tab
    echo "<div class='tab-pane fade' id='blacklist' role='tabpanel'>";
    if ($status_counts['blacklisted'] > 0) {
        echo "<div class='alert alert-danger'>";
        echo "<i class='fas fa-exclamation-triangle'></i> <strong>⚠️ 安全警告:</strong> ";
        echo "以下软件安装违反了公司安全策略，应立即处理或卸载。";
        echo "</div>";
        
        // 直接显示违规安装表格
        $blacklisted_installations = array_filter($installations_with_compliance, function($installation) {
            return $installation['compliance_status'] === 'blacklisted';
        });
        displayInstallationTable($blacklisted_installations, 'blacklist');
    } else {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle'></i> <strong>✅ 太好了！</strong> ";
        echo "当前扫描未发现任何违规软件安装。所有软件都符合安全策略要求。";
        echo "<br><br><strong>这意味着：</strong>";
        echo "<ul><li>没有发现黑名单中禁止的软件</li>";
        echo "<li>系统合规性良好</li>";
        echo "<li>无需立即处理违规问题</li></ul>";
        echo "</div>";
    }
    echo "</div>";

    // Unmanaged tab
    echo "<div class='tab-pane fade' id='unmanaged' role='tabpanel'>";
    if ($status_counts['unmanaged'] > 0) {
        echo "<div class='alert alert-warning'>";
        echo "<i class='fas fa-question-circle'></i> <strong>📋 需要审查:</strong> ";
        echo "以下软件安装尚未登记分类，需要审查并决定是否批准或限制使用。";
        echo "</div>";
        
        // 直接显示未登记安装表格
        $unmanaged_installations = array_filter($installations_with_compliance, function($installation) {
            return $installation['compliance_status'] === 'unmanaged';
        });
        displayInstallationTable($unmanaged_installations, 'unmanaged');
    } else {
        echo "<div class='alert alert-info'>";
        echo "<i class='fas fa-clipboard-check'></i> <strong>📋 管理良好！</strong> ";
        echo "当前扫描未发现未登记的软件安装。所有软件都已经过分类管理。";
        echo "<br><br><strong>这意味着：</strong>";
        echo "<ul><li>所有软件都在白名单或黑名单中有明确分类</li>";
        echo "<li>软件管理策略覆盖完整</li>";
        echo "<li>无需额外的人工审查</li></ul>";
        echo "</div>";
    }
    echo "</div>";

    // Approved tab
    echo "<div class='tab-pane fade' id='approved' role='tabpanel'>";
    if ($status_counts['approved'] > 0) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle'></i> <strong>✅ 合规软件:</strong> ";
        echo "以下软件安装已获得批准，符合公司安全策略要求。";
        echo "</div>";
        
        // 直接显示合规安装表格
        $approved_installations = array_filter($installations_with_compliance, function($installation) {
            return $installation['compliance_status'] === 'approved';
        });
        displayInstallationTable($approved_installations, 'approved');
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<i class='fas fa-exclamation-circle'></i> <strong>⚠️ 注意：</strong> ";
        echo "当前扫描未发现已批准的软件安装。请检查白名单配置是否正确。";
        echo "</div>";
    }
    echo "</div>";

    echo "</div>"; // tab-content
} else {
    echo "<div class='alert alert-info'>";
    echo "<i class='fas fa-info-circle'></i> " . __('此次扫描未发现软件安装数据。这可能是因为系统中没有软件安装记录，或者扫描时发生了问题。', 'softwaremanager');
    echo "</div>";
}

echo "</div>"; // software-details

/**
 * Display installation table
 */
function displayInstallationTable($installations_data, $filter = 'all') {
    echo "<div class='table-responsive' style='max-height: 600px; overflow-y: auto;'>";
    echo "<table class='table table-striped table-sm installation-table' data-filter='{$filter}'>";
    echo "<thead class='thead-dark'>";
    echo "<tr>";
    echo "<th><i class='fas fa-laptop'></i> " . __('Computer') . "</th>";
    echo "<th><i class='fas fa-user'></i> " . __('User') . "</th>";
    echo "<th><i class='fas fa-cube'></i> " . __('Software') . "</th>";
    echo "<th><i class='fas fa-tag'></i> " . __('Version') . "</th>";
    echo "<th><i class='fas fa-calendar'></i> " . __('Install Date') . "</th>";
    echo "<th><i class='fas fa-clock'></i> " . __('Scan Date') . "</th>";
    echo "<th><i class='fas fa-shield-alt'></i> " . __('Status') . "</th>";
    echo "<th><i class='fas fa-building'></i> " . __('Entity') . "</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    $row_count = 0;
    
    foreach ($installations_data as $installation) {
        $row_count++;
        
        echo "<tr data-status='{$installation['compliance_status']}'>";
        
        // Computer name with serial
        echo "<td>";
        echo "<strong>" . htmlspecialchars($installation['computer_name']) . "</strong>";
        if ($installation['computer_serial']) {
            echo "<br><small class='text-muted'>SN: " . htmlspecialchars($installation['computer_serial']) . "</small>";
        }
        echo "</td>";
        
        // User information
        echo "<td>";
        if ($installation['user_name']) {
            echo "<strong>" . htmlspecialchars($installation['user_name']) . "</strong>";
            if ($installation['user_realname']) {
                echo "<br><small>" . htmlspecialchars($installation['user_realname']) . "</small>";
            }
        } else {
            echo "<span class='text-muted'>" . __('No user assigned') . "</span>";
        }
        echo "</td>";
        
        // Software name
        echo "<td><strong>" . htmlspecialchars($installation['software_name']) . "</strong></td>";
        
        // Version
        echo "<td>" . htmlspecialchars($installation['software_version'] ?? 'N/A') . "</td>";
        
        // Install date
        echo "<td>";
        if ($installation['date_install']) {
            echo Html::convDateTime($installation['date_install']);
        } else {
            echo "<span class='text-muted'>" . __('Unknown') . "</span>";
        }
        echo "</td>";
        
        // Scan date
        echo "<td>";
        echo Html::convDateTime($installation['scan_date']);
        echo "</td>";
        
        // Compliance status
        echo "<td>";
        switch($installation['compliance_status']) {
            case 'approved':
                echo "<span class='badge badge-success'><i class='fas fa-check'></i> " . __('Approved') . "</span>";
                break;
            case 'blacklisted':
                echo "<span class='badge badge-danger'><i class='fas fa-ban'></i> " . __('Blacklisted') . "</span>";
                break;
            default:
                echo "<span class='badge badge-warning'><i class='fas fa-question'></i> " . __('Unmanaged') . "</span>";
        }
        echo "</td>";
        
        // Entity
        echo "<td>" . htmlspecialchars($installation['entity_name'] ?? 'N/A') . "</td>";
        
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    
    // Export button
    echo "<div style='margin-top: 15px; text-align: center;'>";
    echo "<button type='button' class='btn btn-primary' onclick='exportTableToCSV(\"{$filter}\")'>";
    echo "<i class='fas fa-download'></i> " . __('Export to CSV', 'softwaremanager');
    echo "</button>";
    echo "<span class='ml-3 text-muted'>" . __('Total installations:', 'softwaremanager') . " {$row_count}</span>";
    echo "</div>";
}

// Back button
echo "<div class='text-center' style='margin: 30px 0;'>";
echo "<a href='scanhistory.php' class='btn btn-secondary'>";
echo "<i class='fas fa-arrow-left'></i> " . __('Back to Scan History', 'softwaremanager');
echo "</a>";
echo "</div>";

Html::footer();
?>

<script type='text/javascript'>
// Tab switching functionality with data filtering
document.addEventListener('DOMContentLoaded', function() {
    // Handle tab clicks
    var tabLinks = document.querySelectorAll('.nav-link');
    tabLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and panes
            document.querySelectorAll('.nav-link').forEach(function(l) { l.classList.remove('active'); });
            document.querySelectorAll('.tab-pane').forEach(function(p) { p.classList.remove('show', 'active'); });
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding pane
            var targetId = this.getAttribute('href').substring(1);
            var targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
                
                // Load filtered content for specific tabs
                if (targetId !== 'all' && !targetPane.querySelector('table')) {
                    loadFilteredContent(targetId);
                }
            }
        });
    });
});

function loadFilteredContent(filter) {
    var allTable = document.querySelector('#all table');
    if (!allTable) return;
    
    var targetDiv = document.getElementById(filter + '-content');
    if (!targetDiv) return;
    
    // Clone the all table structure
    var filteredTable = allTable.cloneNode(true);
    var tbody = filteredTable.querySelector('tbody');
    tbody.innerHTML = '';
    
    // Filter rows based on status
    var allRows = allTable.querySelectorAll('tbody tr');
    allRows.forEach(function(row) {
        if (row.getAttribute('data-status') === filter) {
            tbody.appendChild(row.cloneNode(true));
        }
    });
    
    // Update table class
    filteredTable.setAttribute('data-filter', filter);
    
    // Add to target div
    targetDiv.innerHTML = '';
    var tableWrapper = document.createElement('div');
    tableWrapper.className = 'table-responsive';
    tableWrapper.style.maxHeight = '600px';
    tableWrapper.style.overflowY = 'auto';
    tableWrapper.appendChild(filteredTable);
    targetDiv.appendChild(tableWrapper);
    
    // Add export button
    var exportDiv = document.createElement('div');
    exportDiv.style.marginTop = '15px';
    exportDiv.style.textAlign = 'center';
    exportDiv.innerHTML = '<button type="button" class="btn btn-primary" onclick="exportTableToCSV(\'' + filter + '\')"><i class="fas fa-download"></i> Export to CSV</button>' +
                         '<span class="ml-3 text-muted">Total installations: ' + tbody.children.length + '</span>';
    targetDiv.appendChild(exportDiv);
}

function exportTableToCSV(filter) {
    var table = document.querySelector('[data-filter="' + filter + '"] table');
    if (!table) {
        table = document.querySelector('#' + filter + ' table');
    }
    if (!table) return;
    
    var csv = [];
    var rows = table.querySelectorAll('tr');
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
            // Clean text content and handle line breaks
            var cellText = cols[j].innerText.replace(/\n/g, ' ').replace(/"/g, '""');
            row.push('"' + cellText + '"');
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV
    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'software_installations_' + filter + '_<?php echo date('Y-m-d_H-i-s'); ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<style>
.scan-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid #28a745;
}

.software-details {
    margin-top: 20px;
}

.nav-tabs .nav-link {
    color: #495057;
    border: 1px solid transparent;
    transition: all 0.2s;
}

.nav-tabs .nav-link:hover {
    background-color: #f8f9fa;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.badge {
    font-size: 0.75em;
    padding: 0.35em 0.6em;
    border-radius: 0.25rem;
    font-weight: 500;
}

.badge-lg {
    font-size: 1em;
    padding: 0.5em 0.8em;
}

.badge-success { 
    background-color: #28a745; 
    color: white;
}

.badge-danger { 
    background-color: #dc3545; 
    color: white;
}

.badge-warning { 
    background-color: #ffc107; 
    color: #212529; 
}

.badge-info {
    background-color: #17a2b8;
    color: white;
}

.table th {
    background-color: #343a40;
    color: white;
    border-color: #454d55;
    font-weight: 600;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
}

.table-sm td {
    padding: 0.5rem;
}

.installation-table tbody tr:hover {
    background-color: #f5f5f5;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

.alert {
    border-radius: 8px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    border: 1px solid transparent;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.text-muted {
    color: #6c757d !important;
}

small {
    font-size: 0.875em;
}
</style>

<?php
