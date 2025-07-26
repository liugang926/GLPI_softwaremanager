<?php
/**
 * 一键解决扫描结果问题：检查表并创建测试数据
 */

include('../../../inc/includes.php');

echo "=== 一键修复扫描结果问题 ===\n\n";

global $DB;

// 检查表是否存在并创建测试数据
if (!$DB->tableExists('glpi_plugin_softwaremanager_whitelists') || 
    !$DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
    echo "❌ 数据库表不存在，请重新安装插件或检查安装状态\n";
    exit;
}

// 检查是否已有数据
$wl_count = $DB->request(['COUNT' => 'id', 'FROM' => 'glpi_plugin_softwaremanager_whitelists'])->current()['COUNT'];
$bl_count = $DB->request(['COUNT' => 'id', 'FROM' => 'glpi_plugin_softwaremanager_blacklists'])->current()['COUNT'];

echo "当前数据状态:\n";
echo "- 白名单规则: $wl_count 条\n";
echo "- 黑名单规则: $bl_count 条\n\n";

if ($wl_count == 0 && $bl_count == 0) {
    echo "🔧 正在创建测试数据...\n\n";
    
    // 创建针对扫描结果的精确白名单规则
    $whitelist_rules = [
        'Bonjour',
        'Microsoft Visual C++ 2019 Redistributable'  // 假设这是一个应该合规的软件
    ];
    
    echo "创建白名单规则:\n";
    foreach ($whitelist_rules as $rule) {
        $result = $DB->insert('glpi_plugin_softwaremanager_whitelists', [
            'name' => $rule,
            'exact_match' => 1,  // 精确匹配
            'is_active' => 1,
            'comment' => '测试白名单规则',
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s')
        ]);
        
        echo "  ✓ $rule (精确匹配)\n";
    }
    
    // 创建黑名单规则（针对扫描结果中的特定软件）
    $blacklist_rules = [
        ['name' => 'Adobe Genuine Service', 'exact_match' => 1],
        ['name' => '64 Bit HP CIO Components', 'exact_match' => 0], // 通配符匹配，应该匹配 "64 Bit HP CIO Components Installer"
        ['name' => 'barrier', 'exact_match' => 0] // 通配符匹配，应该匹配 "Barrier 2.4.0-release"
    ];
    
    echo "\n创建黑名单规则:\n";
    foreach ($blacklist_rules as $rule) {
        $result = $DB->insert('glpi_plugin_softwaremanager_blacklists', [
            'name' => $rule['name'],
            'exact_match' => $rule['exact_match'],
            'is_active' => 1,
            'comment' => '测试黑名单规则',
            'risk_level' => 'medium',
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s')
        ]);
        
        $match_type = $rule['exact_match'] ? '精确匹配' : '通配符匹配';
        echo "  ✓ {$rule['name']} ($match_type)\n";
    }
    
} else {
    echo "📊 数据已存在，正在分析规则...\n\n";
    
    // 分析现有规则
    echo "活跃的白名单规则:\n";
    $active_wl = $DB->request([
        'FROM' => 'glpi_plugin_softwaremanager_whitelists',
        'WHERE' => ['is_active' => 1],
        'ORDER' => 'name'
    ]);
    
    foreach ($active_wl as $rule) {
        $match_type = $rule['exact_match'] ? '精确' : '通配符';
        echo "  - {$rule['name']} ($match_type)\n";
    }
    
    echo "\n活跃的黑名单规则:\n";
    $active_bl = $DB->request([
        'FROM' => 'glpi_plugin_softwaremanager_blacklists', 
        'WHERE' => ['is_active' => 1],
        'ORDER' => 'name'
    ]);
    
    foreach ($active_bl as $rule) {
        $match_type = $rule['exact_match'] ? '精确' : '通配符';
        echo "  - {$rule['name']} ($match_type)\n";
    }
    
    // 如果所有规则都是通配符且过于宽泛，给出警告
    $broad_wl = $DB->request([
        'FROM' => 'glpi_plugin_softwaremanager_whitelists',
        'WHERE' => [
            'is_active' => 1,
            'exact_match' => 0,
            ['OR' => [
                ['name' => ['LIKE', '%64%']],
                ['name' => ['LIKE', '%bit%']],
                ['name' => ['LIKE', '%adobe%']],
                ['name' => ['LIKE', '%hp%']]
            ]]
        ]
    ]);
    
    if (count($broad_wl) > 0) {
        echo "\n⚠️ 警告：发现可能过于宽泛的白名单规则！\n";
        echo "建议临时禁用通配符匹配规则，只使用精确匹配进行测试。\n\n";
        
        echo "执行以下SQL来临时修复:\n";
        echo "UPDATE glpi_plugin_softwaremanager_whitelists SET is_active = 0 WHERE exact_match = 0;\n";
    }
}

echo "\n✅ 数据设置完成！\n\n";

echo "预期的扫描结果分布:\n";
echo "- ✅ 合规: Bonjour, Microsoft Visual C++ (白名单精确匹配)\n";
echo "- ❌ 违规: Adobe Genuine Service, 64 Bit HP CIO Components Installer, Barrier 2.4.0-release (黑名单匹配)\n"; 
echo "- ❓ 未登记: 其他所有软件 (不在白名单中)\n\n";

echo "现在重新运行合规性扫描，应该能看到正确的分类！\n";

echo "\n=== 完成 ===\n";
?>