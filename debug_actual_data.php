<?php
/**
 * 详细检查白名单和黑名单的实际数据内容
 */

include('../../../inc/includes.php');

echo "=== 详细数据内容检查 ===\n\n";

global $DB;

// 1. 检查白名单数据的具体内容
echo "1. 白名单详细内容检查:\n";
echo str_repeat("-", 80) . "\n";

if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
    $whitelist_query = "SELECT id, name, exact_match, is_active, LENGTH(name) as name_length FROM `glpi_plugin_softwaremanager_whitelists` ORDER BY is_active DESC, name_length ASC, name";
    $whitelist_result = $DB->query($whitelist_query);
    
    if ($whitelist_result && $DB->numrows($whitelist_result) > 0) {
        $total_count = $DB->numrows($whitelist_result);
        $active_count = 0;
        $exact_count = 0;
        $wildcard_count = 0;
        $short_rules = [];
        
        echo "ID\t活跃\t匹配\t长度\t规则名称\n";
        echo str_repeat("-", 80) . "\n";
        
        while ($row = $DB->fetchAssoc($whitelist_result)) {
            $active_status = $row['is_active'] ? '✓' : '✗';
            $match_type = $row['exact_match'] ? '精确' : '通配';
            $name_length = $row['name_length'];
            
            echo sprintf("%d\t%s\t%s\t%d\t%s\n", 
                $row['id'], 
                $active_status, 
                $match_type, 
                $name_length, 
                substr($row['name'], 0, 50)
            );
            
            if ($row['is_active']) {
                $active_count++;
                if ($row['exact_match']) {
                    $exact_count++;
                } else {
                    $wildcard_count++;
                    if ($name_length <= 4) {
                        $short_rules[] = $row['name'];
                    }
                }
            }
        }
        
        echo "\n统计:\n";
        echo "- 总计: $total_count 条\n";
        echo "- 活跃: $active_count 条\n";
        echo "- 精确匹配: $exact_count 条\n";
        echo "- 通配符匹配: $wildcard_count 条\n";
        
        if (!empty($short_rules)) {
            echo "\n⚠️ 短规则（≤4字符，通配符匹配）:\n";
            foreach ($short_rules as $rule) {
                echo "  - '$rule'\n";
            }
        }
        
    } else {
        echo "白名单表为空\n";
    }
} else {
    echo "白名单表不存在\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// 2. 检查黑名单数据的具体内容
echo "2. 黑名单详细内容检查:\n";
echo str_repeat("-", 80) . "\n";

if ($DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
    $blacklist_query = "SELECT id, name, exact_match, is_active, LENGTH(name) as name_length FROM `glpi_plugin_softwaremanager_blacklists` ORDER BY is_active DESC, name_length ASC, name";
    $blacklist_result = $DB->query($blacklist_query);
    
    if ($blacklist_result && $DB->numrows($blacklist_result) > 0) {
        $total_count = $DB->numrows($blacklist_result);
        $active_count = 0;
        
        echo "ID\t活跃\t匹配\t长度\t规则名称\n";
        echo str_repeat("-", 80) . "\n";
        
        while ($row = $DB->fetchAssoc($blacklist_result)) {
            $active_status = $row['is_active'] ? '✓' : '✗';
            $match_type = $row['exact_match'] ? '精确' : '通配';
            
            echo sprintf("%d\t%s\t%s\t%d\t%s\n", 
                $row['id'], 
                $active_status, 
                $match_type, 
                $row['name_length'], 
                substr($row['name'], 0, 50)
            );
            
            if ($row['is_active']) {
                $active_count++;
            }
        }
        
        echo "\n统计:\n";
        echo "- 总计: $total_count 条\n";
        echo "- 活跃: $active_count 条\n";
        
    } else {
        echo "黑名单表为空\n";
    }
} else {
    echo "黑名单表不存在\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// 3. 模拟实际的匹配过程
echo "3. 模拟匹配过程（使用实际数据）:\n";

// 测试软件（从你的扫描结果）
$test_software = [
    '64 Bit HP CIO Components Installer',
    'Adobe Acrobat (64-bit)',
    'Adobe Genuine Service',
    'Barrier 2.4.0-release',
    'Bonjour'
];

// 获取实际的白名单和黑名单规则
$whitelists = [];
$blacklists = [];

if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
    $wl_result = $DB->query("SELECT name, exact_match FROM `glpi_plugin_softwaremanager_whitelists` WHERE is_active = 1");
    if ($wl_result) {
        while ($row = $DB->fetchAssoc($wl_result)) {
            $whitelists[] = [
                'name' => strtolower(trim($row['name'])),
                'exact_match' => $row['exact_match']
            ];
        }
    }
}

if ($DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
    $bl_result = $DB->query("SELECT name, exact_match FROM `glpi_plugin_softwaremanager_blacklists` WHERE is_active = 1");
    if ($bl_result) {
        while ($row = $DB->fetchAssoc($bl_result)) {
            $blacklists[] = [
                'name' => strtolower(trim($row['name'])),
                'exact_match' => $row['exact_match']
            ];
        }
    }
}

echo "使用规则: " . count($whitelists) . " 条白名单, " . count($blacklists) . " 条黑名单\n\n";

foreach ($test_software as $software) {
    echo "🔍 测试软件: '$software'\n";
    $software_name_lower = strtolower(trim($software));
    $compliance_status = 'unmanaged';
    $matched_rule = '';
    $all_matches = [];
    
    // 检查黑名单（优先级最高）
    foreach ($blacklists as $blacklist_rule) {
        $blacklist_item = $blacklist_rule['name'];
        $exact_match = $blacklist_rule['exact_match'];
        
        $is_match = false;
        if ($exact_match) {
            $is_match = (strcasecmp($software_name_lower, $blacklist_item) === 0);
            if ($is_match) $all_matches[] = "黑名单精确匹配: '$blacklist_item'";
        } else {
            $is_match = (stripos($software_name_lower, $blacklist_item) !== false);
            if ($is_match) $all_matches[] = "黑名单通配匹配: '$blacklist_item'";
        }
        
        if ($is_match) {
            $compliance_status = 'blacklisted';
            $matched_rule = $blacklist_item;
            break;
        }
    }
    
    // 如果不在黑名单中，检查白名单
    if ($compliance_status === 'unmanaged') {
        $in_whitelist = false;
        foreach ($whitelists as $whitelist_rule) {
            $whitelist_item = $whitelist_rule['name'];
            $exact_match = $whitelist_rule['exact_match'];
            
            $is_match = false;
            if ($exact_match) {
                $is_match = (strcasecmp($software_name_lower, $whitelist_item) === 0);
                if ($is_match) $all_matches[] = "白名单精确匹配: '$whitelist_item'";
            } else {
                $is_match = (stripos($software_name_lower, $whitelist_item) !== false);
                if ($is_match) $all_matches[] = "白名单通配匹配: '$whitelist_item'";
            }
            
            if ($is_match) {
                $compliance_status = 'approved';
                $matched_rule = $whitelist_item;
                $in_whitelist = true;
                break;
            }
        }
        
        if (!$in_whitelist) {
            $compliance_status = 'unmanaged';
            $all_matches[] = "不在任何白名单规则中";
        }
    }
    
    $status_text = [
        'approved' => '✅ 合规',
        'blacklisted' => '❌ 违规',
        'unmanaged' => '❓ 未登记'
    ];
    
    echo "  结果: {$status_text[$compliance_status]}\n";
    if ($matched_rule) {
        echo "  匹配规则: '$matched_rule'\n";
    }
    
    if (!empty($all_matches)) {
        echo "  所有匹配项:\n";
        foreach ($all_matches as $match) {
            echo "    - $match\n";
        }
    }
    echo "\n";
}

echo "=== 检查完成 ===\n";

// 4. 如果发现问题，提供解决建议
if (count($whitelists) > 20 || (isset($wildcard_count) && $wildcard_count > 10)) {
    echo "\n🔧 解决建议:\n";
    echo "发现大量规则，可能导致过度匹配。建议:\n";
    echo "1. 临时禁用所有通配符规则: UPDATE glpi_plugin_softwaremanager_whitelists SET is_active = 0 WHERE exact_match = 0;\n";
    echo "2. 只保留必要的精确匹配规则\n";
    echo "3. 重新扫描验证结果\n";
}
?>