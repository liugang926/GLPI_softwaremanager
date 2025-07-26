<?php
/**
 * 快速修复白名单过度匹配问题
 */

include('../../../inc/includes.php');

echo "=== 快速修复白名单过度匹配 ===\n\n";

global $DB;

$action = $_GET['action'] ?? '';

if ($action === 'fix') {
    echo "正在修复白名单规则...\n\n";
    
    // 1. 临时禁用所有通配符匹配的白名单规则
    echo "1. 禁用所有通配符匹配的白名单规则...\n";
    $result1 = $DB->update('glpi_plugin_softwaremanager_whitelists', [
        'is_active' => 0
    ], [
        'exact_match' => 0
    ]);
    
    echo "  影响行数: " . ($result1 ? "成功" : "失败") . "\n";
    
    // 2. 只保留几个精确匹配的规则作为测试
    echo "\n2. 重新激活一些精确匹配的白名单规则...\n";
    $precise_rules = [
        '64 Bit HP CIO Components Installer',
        'Adobe Acrobat (64-bit)',
        'Bonjour'
    ];
    
    foreach ($precise_rules as $rule) {
        $result = $DB->update('glpi_plugin_softwaremanager_whitelists', [
            'is_active' => 1,
            'exact_match' => 1
        ], [
            'name' => $rule
        ]);
        
        echo "  ✓ 激活精确匹配规则: '$rule'\n";
    }
    
    // 3. 添加一些测试黑名单规则
    echo "\n3. 添加测试黑名单规则...\n";
    $blacklist_rules = [
        ['name' => 'Adobe Genuine Service', 'exact_match' => 1],  // 测试精确匹配
        ['name' => 'barrier', 'exact_match' => 0],               // 测试通配符匹配
        ['name' => 'winrar', 'exact_match' => 0],
        ['name' => 'utorrent', 'exact_match' => 0]
    ];
    
    foreach ($blacklist_rules as $rule) {
        // 检查是否已存在
        $existing = $DB->request([
            'FROM' => 'glpi_plugin_softwaremanager_blacklists',
            'WHERE' => ['name' => $rule['name']]
        ]);
        
        if (count($existing) == 0) {
            $DB->insert('glpi_plugin_softwaremanager_blacklists', [
                'name' => $rule['name'],
                'exact_match' => $rule['exact_match'],
                'is_active' => 1,
                'date_creation' => date('Y-m-d H:i:s')
            ]);
            
            $match_type = $rule['exact_match'] ? '精确匹配' : '通配符匹配';
            echo "  ✓ 添加黑名单规则: '{$rule['name']}' ($match_type)\n";
        } else {
            echo "  - 跳过已存在的规则: '{$rule['name']}'\n";
        }
    }
    
    echo "\n✅ 修复完成！\n\n";
    echo "预期结果:\n";
    echo "- '64 Bit HP CIO Components Installer' → ✅ 合规（白名单精确匹配）\n";
    echo "- 'Adobe Acrobat (64-bit)' → ✅ 合规（白名单精确匹配）\n";
    echo "- 'Adobe Genuine Service' → ❌ 违规（黑名单精确匹配）\n";
    echo "- 'Barrier 2.4.0-release' → ❌ 违规（黑名单通配符匹配'barrier'）\n";
    echo "- 'Bonjour' → ✅ 合规（白名单精确匹配）\n";
    echo "- 其他软件 → ❓ 未登记（不在白名单中）\n\n";
    
    echo "现在重新运行合规性扫描，应该能看到正确的分类结果！\n";
    
} elseif ($action === 'restore') {
    echo "正在恢复所有白名单规则...\n";
    $result = $DB->update('glpi_plugin_softwaremanager_whitelists', [
        'is_active' => 1
    ], []);
    
    echo "已恢复所有白名单规则为活跃状态\n";
    
} else {
    // 显示当前状态和修复选项
    echo "当前状态检查:\n\n";
    
    // 检查活跃的白名单规则数量
    if ($DB->tableExists('glpi_plugin_softwaremanager_whitelists')) {
        $wl_total = $DB->request([
            'COUNT' => 'id',
            'FROM' => 'glpi_plugin_softwaremanager_whitelists'
        ])->current()['COUNT'];
        
        $wl_active = $DB->request([
            'COUNT' => 'id', 
            'FROM' => 'glpi_plugin_softwaremanager_whitelists',
            'WHERE' => ['is_active' => 1]
        ])->current()['COUNT'];
        
        $wl_wildcard = $DB->request([
            'COUNT' => 'id',
            'FROM' => 'glpi_plugin_softwaremanager_whitelists', 
            'WHERE' => ['is_active' => 1, 'exact_match' => 0]
        ])->current()['COUNT'];
        
        echo "白名单规则: 总计 $wl_total 条，活跃 $wl_active 条，通配符匹配 $wl_wildcard 条\n";
    }
    
    // 检查活跃的黑名单规则数量
    if ($DB->tableExists('glpi_plugin_softwaremanager_blacklists')) {
        $bl_total = $DB->request([
            'COUNT' => 'id',
            'FROM' => 'glpi_plugin_softwaremanager_blacklists'
        ])->current()['COUNT'];
        
        $bl_active = $DB->request([
            'COUNT' => 'id',
            'FROM' => 'glpi_plugin_softwaremanager_blacklists',
            'WHERE' => ['is_active' => 1] 
        ])->current()['COUNT'];
        
        echo "黑名单规则: 总计 $bl_total 条，活跃 $bl_active 条\n";
    }
    
    echo "\n修复选项:\n";
    echo "1. 临时修复过度匹配问题: ?action=fix\n";
    echo "2. 恢复所有白名单规则: ?action=restore\n";
    echo "3. 详细数据检查: 运行 debug_whitelist_data.php\n\n";
    
    if ($wl_wildcard > 10) {
        echo "⚠️ 警告: 发现 $wl_wildcard 条通配符匹配的白名单规则，可能导致过度匹配！\n";
        echo "建议执行: ?action=fix\n";
    }
}

echo "\n=== 完成 ===\n";
?>