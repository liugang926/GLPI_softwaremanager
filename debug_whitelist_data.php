<?php
/**
 * 检查当前白名单和黑名单数据，找出过度匹配的原因
 */

include('../../../inc/includes.php');

echo "=== 白名单和黑名单数据检查 ===\n\n";

global $DB;

// 1. 检查白名单数据
echo "1. 当前白名单数据:\n";
if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
    $whitelist_query = "SELECT name, exact_match, is_active FROM `glpi_plugin_softwaremanager_whitelists` ORDER BY is_active DESC, LENGTH(name), name";
    $whitelist_result = $DB->query($whitelist_query);
    
    if ($whitelist_result && $DB->numrows($whitelist_result) > 0) {
        $active_count = 0;
        $total_count = $DB->numrows($whitelist_result);
        
        echo "序号\t活跃\t精确\t长度\t规则名称\n";
        echo str_repeat("-", 70) . "\n";
        
        $problematic_rules = [];
        $very_short_rules = [];
        
        $i = 1;
        while ($row = $DB->fetchAssoc($whitelist_result)) {
            $active = $row['is_active'] ? '✓' : '✗';
            $exact = $row['exact_match'] ? '✓' : '✗';
            $length = strlen($row['name']);
            
            echo sprintf("%d\t%s\t%s\t%d\t%s\n", 
                $i++, 
                $active, 
                $exact, 
                $length, 
                $row['name']
            );
            
            if ($row['is_active']) {
                $active_count++;
                
                // 标记可能有问题的规则
                if (!$row['exact_match'] && $length <= 3) {
                    $very_short_rules[] = $row['name'];
                }
                
                if (!$row['exact_match'] && in_array(strtolower($row['name']), 
                    ['64', 'bit', 'hp', 'adobe', 'microsoft', 'windows', 'x64', 'installer', 'service'])) {
                    $problematic_rules[] = $row['name'];
                }
            }
        }
        
        echo "\n总计: $total_count 条，活跃: $active_count 条\n";
        
        if (!empty($very_short_rules)) {
            echo "\n⚠️ 发现极短规则（≤3字符，通配符匹配）:\n";
            foreach ($very_short_rules as $rule) {
                echo "  - '$rule'\n";
            }
        }
        
        if (!empty($problematic_rules)) {
            echo "\n❌ 发现通用词汇规则（可能导致过度匹配）:\n";
            foreach ($problematic_rules as $rule) {
                echo "  - '$rule'\n";
            }
        }
        
    } else {
        echo "没有找到白名单数据\n";
    }
} else {
    echo "白名单表不存在\n";
}

echo "\n" . str_repeat("=", 70) . "\n\n";

// 2. 检查黑名单数据
echo "2. 当前黑名单数据:\n";
if ($DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
    $blacklist_query = "SELECT name, exact_match, is_active FROM `glpi_plugin_softwaremanager_blacklists` ORDER BY is_active DESC, LENGTH(name), name";
    $blacklist_result = $DB->query($blacklist_query);
    
    if ($blacklist_result && $DB->numrows($blacklist_result) > 0) {
        $active_count = 0;
        $total_count = $DB->numrows($blacklist_result);
        
        echo "序号\t活跃\t精确\t长度\t规则名称\n";
        echo str_repeat("-", 70) . "\n";
        
        $i = 1;
        while ($row = $DB->fetchAssoc($blacklist_result)) {
            $active = $row['is_active'] ? '✓' : '✗';
            $exact = $row['exact_match'] ? '✓' : '✗';
            $length = strlen($row['name']);
            
            echo sprintf("%d\t%s\t%s\t%d\t%s\n", 
                $i++, 
                $active, 
                $exact, 
                $length, 
                $row['name']
            );
            
            if ($row['is_active']) {
                $active_count++;
            }
        }
        
        echo "\n总计: $total_count 条，活跃: $active_count 条\n";
        
    } else {
        echo "没有找到黑名单数据\n";
    }
} else {
    echo "黑名单表不存在\n";
}

echo "\n" . str_repeat("=", 70) . "\n\n";

// 3. 模拟匹配测试
echo "3. 模拟匹配测试（使用扫描结果中的软件）:\n";

$test_software = [
    '64 Bit HP CIO Components Installer',
    'Adobe Acrobat (64-bit)', 
    'Adobe Genuine Service',
    'Barrier 2.4.0-release',
    'Bonjour'
];

// 获取实际的白名单和黑名单数据
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

echo "使用 " . count($whitelists) . " 条白名单规则和 " . count($blacklists) . " 条黑名单规则进行测试:\n\n";

foreach ($test_software as $software) {
    echo "测试软件: '$software'\n";
    $software_name_lower = strtolower(trim($software));
    $compliance_status = 'unmanaged';
    $matched_rule = '';
    $match_details = [];
    
    // 检查黑名单
    foreach ($blacklists as $blacklist_rule) {
        $blacklist_item = $blacklist_rule['name'];
        $exact_match = $blacklist_rule['exact_match'];
        
        $is_match = false;
        if ($exact_match) {
            $is_match = (strcasecmp($software_name_lower, $blacklist_item) === 0);
            if ($is_match) $match_details[] = "  黑名单严格匹配: '$blacklist_item'";
        } else {
            $is_match = (stripos($software_name_lower, $blacklist_item) !== false);
            if ($is_match) $match_details[] = "  黑名单通配匹配: '$blacklist_item'";
        }
        
        if ($is_match) {
            $compliance_status = 'blacklisted';
            $matched_rule = $blacklist_item;
            break;
        }
    }
    
    // 如果不在黑名单，检查白名单
    if ($compliance_status === 'unmanaged') {
        $in_whitelist = false;
        foreach ($whitelists as $whitelist_rule) {
            $whitelist_item = $whitelist_rule['name'];
            $exact_match = $whitelist_rule['exact_match'];
            
            $is_match = false;
            if ($exact_match) {
                $is_match = (strcasecmp($software_name_lower, $whitelist_item) === 0);
                if ($is_match) $match_details[] = "  白名单严格匹配: '$whitelist_item'";
            } else {
                $is_match = (stripos($software_name_lower, $whitelist_item) !== false);
                if ($is_match) $match_details[] = "  白名单通配匹配: '$whitelist_item'";
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
            $match_details[] = "  不在任何白名单规则中";
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
    if (!empty($match_details)) {
        echo "  匹配详情:\n";
        foreach ($match_details as $detail) {
            echo "$detail\n";
        }
    }
    echo "\n";
}

echo "\n=== 检查完成 ===\n";

// 4. 提供修复建议
echo "\n4. 修复建议:\n";
echo "如果所有软件都被标记为合规，请执行以下操作:\n\n";

echo "A. 临时禁用所有通配符白名单规则:\n";
echo "UPDATE glpi_plugin_softwaremanager_whitelists SET is_active = 0 WHERE exact_match = 0;\n\n";

echo "B. 只保留精确匹配的白名单规则:\n";
echo "UPDATE glpi_plugin_softwaremanager_whitelists SET is_active = 1 WHERE exact_match = 1;\n\n";

echo "C. 添加一些测试黑名单规则:\n";
echo "INSERT INTO glpi_plugin_softwaremanager_blacklists (name, exact_match, is_active, date_creation) VALUES\n";
echo "('winrar', 0, 1, NOW()),\n";
echo "('utorrent', 0, 1, NOW()),\n";
echo "('bittorrent', 0, 1, NOW());\n\n";

echo "执行这些SQL后重新扫描，应该能看到未登记和违规的软件。\n";
?>