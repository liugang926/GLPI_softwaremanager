<?php
/**
 * 检查白名单和黑名单数据以及匹配逻辑
 */

include('../../../inc/includes.php');

echo "=== 合规性规则诊断 ===\n\n";

global $DB;

// 1. 检查白名单数据
echo "1. 白名单规则:\n";
if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
    $whitelist_query = "SELECT name, exact_match, is_active FROM `glpi_plugin_softwaremanager_whitelists` WHERE is_active = 1 ORDER BY name";
    $whitelist_result = $DB->query($whitelist_query);
    
    if ($whitelist_result && $DB->numrows($whitelist_result) > 0) {
        echo "ID\t精确匹配\t规则名称\n";
        echo "----\t--------\t--------\n";
        $wl_count = 0;
        while ($row = $DB->fetchAssoc($whitelist_result)) {
            $wl_count++;
            $exact = $row['exact_match'] ? '是' : '否';
            echo "$wl_count\t$exact\t\t" . $row['name'] . "\n";
        }
        echo "\n总计白名单规则: $wl_count 条\n";
    } else {
        echo "❌ 没有找到活跃的白名单规则\n";
    }
} else {
    echo "❌ 白名单表不存在\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// 2. 检查黑名单数据
echo "2. 黑名单规则:\n";
if ($DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
    $blacklist_query = "SELECT name, exact_match, is_active FROM `glpi_plugin_softwaremanager_blacklists` WHERE is_active = 1 ORDER BY name";
    $blacklist_result = $DB->query($blacklist_query);
    
    if ($blacklist_result && $DB->numrows($blacklist_result) > 0) {
        echo "ID\t精确匹配\t规则名称\n";
        echo "----\t--------\t--------\n";
        $bl_count = 0;
        while ($row = $DB->fetchAssoc($blacklist_result)) {
            $bl_count++;
            $exact = $row['exact_match'] ? '是' : '否';
            echo "$bl_count\t$exact\t\t" . $row['name'] . "\n";
        }
        echo "\n总计黑名单规则: $bl_count 条\n";
    } else {
        echo "❌ 没有找到活跃的黑名单规则\n";
    }
} else {
    echo "❌ 黑名单表不存在\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// 3. 获取软件样本进行匹配测试
echo "3. 软件样本匹配测试:\n";
$software_sample_query = "
    SELECT DISTINCT s.name as software_name
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
    WHERE s.is_deleted = 0 AND c.id IS NOT NULL
    ORDER BY s.name
    LIMIT 10
";

$software_sample = $DB->query($software_sample_query);
if ($software_sample && $DB->numrows($software_sample) > 0) {
    // 获取白名单和黑名单规则用于测试
    $whitelists = [];
    $blacklists = [];
    
    if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
        $wl_result = $DB->query("SELECT name FROM `glpi_plugin_softwaremanager_whitelists` WHERE is_active = 1");
        if ($wl_result) {
            while ($row = $DB->fetchAssoc($wl_result)) {
                $whitelists[] = strtolower(trim($row['name']));
            }
        }
    }
    
    if ($DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
        $bl_result = $DB->query("SELECT name FROM `glpi_plugin_softwaremanager_blacklists` WHERE is_active = 1");
        if ($bl_result) {
            while ($row = $DB->fetchAssoc($bl_result)) {
                $blacklists[] = strtolower(trim($row['name']));
            }
        }
    }
    
    echo "软件名称 → 匹配结果\n";
    echo str_repeat("-", 60) . "\n";
    
    while ($software = $DB->fetchAssoc($software_sample)) {
        $software_name = $software['software_name'];
        $software_name_lower = strtolower(trim($software_name));
        $compliance_status = 'unmanaged';
        $matched_rule = '';
        
        // 检查黑名单（优先级最高）
        foreach ($blacklists as $blacklist_item) {
            if (stripos($software_name_lower, $blacklist_item) !== false || 
                stripos($blacklist_item, $software_name_lower) !== false ||
                $software_name_lower === $blacklist_item) {
                $compliance_status = 'blacklisted';
                $matched_rule = $blacklist_item;
                break;
            }
        }
        
        // 如果不在黑名单中，检查白名单
        if ($compliance_status === 'unmanaged') {
            foreach ($whitelists as $whitelist_item) {
                if (stripos($software_name_lower, $whitelist_item) !== false || 
                    stripos($whitelist_item, $software_name_lower) !== false ||
                    $software_name_lower === $whitelist_item) {
                    $compliance_status = 'approved';
                    $matched_rule = $whitelist_item;
                    break;
                }
            }
        }
        
        $status_text = [
            'approved' => '✅ 合规',
            'blacklisted' => '❌ 违规', 
            'unmanaged' => '❓ 未登记'
        ];
        
        echo sprintf("%-40s → %s", 
            substr($software_name, 0, 40), 
            $status_text[$compliance_status] . ($matched_rule ? " (规则: $matched_rule)" : "")
        ) . "\n";
    }
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// 4. 分析问题
echo "4. 问题分析:\n";

echo "如果所有软件都显示为'合规'，可能的原因：\n";
echo "• 白名单规则过于宽泛（如包含通用词汇）\n";
echo "• 匹配逻辑使用了部分匹配（stripos），导致误匹配\n";
echo "• 缺少黑名单规则或未登记的软件\n\n";

echo "建议的解决方案：\n";
echo "1. 检查白名单规则是否包含过于通用的词汇\n";
echo "2. 考虑使用精确匹配模式\n";
echo "3. 添加一些黑名单规则进行测试\n";
echo "4. 调整匹配逻辑的严格程度\n";

echo "\n=== 诊断完成 ===\n";
?>