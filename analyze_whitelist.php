<?php
/**
 * 白名单规则分析和调整工具
 */

include('../../../inc/includes.php');

echo "=== 白名单规则分析工具 ===\n\n";

global $DB;

// 1. 分析当前白名单规则
echo "1. 当前白名单规则分析:\n";
if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
    $whitelist_query = "SELECT name, exact_match, is_active FROM `glpi_plugin_softwaremanager_whitelists` ORDER BY LENGTH(name), name";
    $whitelist_result = $DB->query($whitelist_query);
    
    if ($whitelist_result && $DB->numrows($whitelist_result) > 0) {
        echo "规则长度\t精确匹配\t活跃\t规则名称\n";
        echo str_repeat("-", 70) . "\n";
        
        $short_rules = [];
        $problematic_rules = [];
        
        while ($row = $DB->fetchAssoc($whitelist_result)) {
            $rule_length = strlen($row['name']);
            $exact = $row['exact_match'] ? '是' : '否';
            $active = $row['is_active'] ? '是' : '否';
            
            echo sprintf("%d\t\t%s\t\t%s\t%s\n", 
                $rule_length, 
                $exact, 
                $active,
                $row['name']
            );
            
            // 标记可能有问题的规则
            if ($row['is_active'] && !$row['exact_match'] && $rule_length <= 4) {
                $short_rules[] = $row['name'];
            }
            
            if ($row['is_active'] && !$row['exact_match'] && in_array(strtolower($row['name']), ['64', 'bit', 'hp', 'adobe', 'microsoft', 'windows', 'x64'])) {
                $problematic_rules[] = $row['name'];
            }
        }
        
        if (!empty($short_rules)) {
            echo "\n⚠️ 发现可能过于宽泛的短规则:\n";
            foreach ($short_rules as $rule) {
                echo "  - '$rule' (长度: " . strlen($rule) . ")\n";
            }
        }
        
        if (!empty($problematic_rules)) {
            echo "\n❌ 发现可能导致误匹配的通用规则:\n";
            foreach ($problematic_rules as $rule) {
                echo "  - '$rule'\n";
            }
        }
        
    } else {
        echo "没有找到白名单规则\n";
    }
} else {
    echo "白名单表不存在\n";
}

echo "\n" . str_repeat("=", 70) . "\n\n";

// 2. 建议的修复措施
echo "2. 建议的修复措施:\n\n";

echo "A. 设置精确匹配的规则:\n";
echo "UPDATE glpi_plugin_softwaremanager_whitelists SET exact_match = 1 WHERE name IN (\n";
echo "  '64 Bit HP CIO Components Installer',\n";
echo "  'Adobe Acrobat (64-bit)',\n";
echo "  'Adobe Genuine Service',\n";
echo "  'Barrier 2.4.0-release',\n";
echo "  'Bonjour'\n";
echo ");\n\n";

echo "B. 禁用过于通用的规则:\n";
echo "UPDATE glpi_plugin_softwaremanager_whitelists SET is_active = 0 WHERE name IN (\n";
echo "  '64', 'bit', 'hp', 'adobe'\n";
echo ") AND exact_match = 0;\n\n";

echo "C. 添加一些黑名单规则用于测试:\n";
echo "INSERT INTO glpi_plugin_softwaremanager_blacklists (name, exact_match, is_active, date_creation) VALUES\n";
echo "  ('游戏软件', 0, 1, NOW()),\n";
echo "  ('p2p下载', 0, 1, NOW()),\n";
echo "  ('破解工具', 0, 1, NOW()),\n";
echo "  ('测试黑名单软件', 1, 1, NOW());\n\n";

// 3. 提供快速修复选项
echo "3. 快速修复选项:\n\n";

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'fix_exact_match':
            echo "正在设置精确匹配...\n";
            $exact_match_rules = [
                '64 Bit HP CIO Components Installer',
                'Adobe Acrobat (64-bit)',
                'Adobe Genuine Service', 
                'Barrier 2.4.0-release',
                'Bonjour'
            ];
            
            foreach ($exact_match_rules as $rule) {
                $DB->update('glpi_plugin_softwaremanager_whitelists', [
                    'exact_match' => 1
                ], [
                    'name' => $rule
                ]);
                echo "  ✓ 设置 '$rule' 为精确匹配\n";
            }
            break;
            
        case 'disable_generic':
            echo "正在禁用通用规则...\n";
            $generic_rules = ['64', 'bit', 'hp', 'adobe'];
            
            foreach ($generic_rules as $rule) {
                $DB->update('glpi_plugin_softwaremanager_whitelists', [
                    'is_active' => 0
                ], [
                    'name' => $rule,
                    'exact_match' => 0
                ]);
                echo "  ✓ 禁用通用规则 '$rule'\n";
            }
            break;
            
        case 'add_blacklist':
            echo "正在添加测试黑名单规则...\n";
            $blacklist_rules = [
                ['name' => '游戏软件', 'exact_match' => 0],
                ['name' => 'p2p下载', 'exact_match' => 0],
                ['name' => '破解工具', 'exact_match' => 0],
                ['name' => '测试黑名单软件', 'exact_match' => 1]
            ];
            
            foreach ($blacklist_rules as $rule) {
                $DB->insert('glpi_plugin_softwaremanager_blacklists', [
                    'name' => $rule['name'],
                    'exact_match' => $rule['exact_match'],
                    'is_active' => 1,
                    'date_creation' => date('Y-m-d H:i:s')
                ]);
                echo "  ✓ 添加黑名单规则 '{$rule['name']}'\n";
            }
            break;
    }
    echo "\n✅ 修复完成！请重新运行合规性扫描。\n";
} else {
    echo "要执行快速修复，请访问以下链接:\n";
    echo "- 设置精确匹配: ?action=fix_exact_match\n";
    echo "- 禁用通用规则: ?action=disable_generic\n";
    echo "- 添加测试黑名单: ?action=add_blacklist\n";
}

echo "\n=== 分析完成 ===\n";
?>