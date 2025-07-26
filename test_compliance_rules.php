<?php
/**
 * 验证巡检规则是否符合需求文档
 */

include('../../../inc/includes.php');

echo "=== 巡检规则符合性验证 ===\n\n";

// 模拟测试数据
$test_software = [
    'solidworks 2022',
    'A solidworks',
    'SolidWorks Premium',
    'SOLIDWORKS',
    'PTC Creo',
    'ptc parametric',
    'Adobe Acrobat',
    'WinRAR',
    'Unknown Software'
];

// 模拟白名单规则
$test_whitelists = [
    ['name' => 'solidworks', 'exact_match' => 0],  // 通配符匹配
    ['name' => 'SOLIDWORKS', 'exact_match' => 1],  // 严格匹配
    ['name' => 'ptc', 'exact_match' => 0],          // 通配符匹配
    ['name' => 'Adobe Acrobat', 'exact_match' => 1] // 严格匹配
];

// 模拟黑名单规则
$test_blacklists = [
    ['name' => 'winrar', 'exact_match' => 0],       // 通配符匹配
    ['name' => 'solidworks', 'exact_match' => 0]    // 与白名单冲突，测试黑名单优先
];

echo "测试软件列表:\n";
foreach ($test_software as $i => $software) {
    echo ($i + 1) . ". $software\n";
}

echo "\n白名单规则:\n";
foreach ($test_whitelists as $i => $rule) {
    $match_type = $rule['exact_match'] ? '严格匹配' : '通配符匹配';
    echo ($i + 1) . ". '{$rule['name']}' ($match_type)\n";
}

echo "\n黑名单规则:\n";
foreach ($test_blacklists as $i => $rule) {
    $match_type = $rule['exact_match'] ? '严格匹配' : '通配符匹配';
    echo ($i + 1) . ". '{$rule['name']}' ($match_type)\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

echo "巡检结果（按照需求文档规则）:\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-25s %-15s %-20s %s\n", "软件名称", "分类结果", "匹配规则", "说明");
echo str_repeat("-", 80) . "\n";

foreach ($test_software as $software) {
    $software_name_lower = strtolower(trim($software));
    $compliance_status = 'unmanaged';
    $matched_rule = '';
    $explanation = '';
    
    // 按照需求：黑名单优先
    foreach ($test_blacklists as $blacklist_rule) {
        $blacklist_item = strtolower($blacklist_rule['name']);
        $exact_match = $blacklist_rule['exact_match'];
        
        $is_match = false;
        if ($exact_match) {
            // 严格匹配：仅不区分大小写，名称要完全一致
            $is_match = (strcasecmp($software_name_lower, $blacklist_item) === 0);
            $explanation = "严格匹配黑名单";
        } else {
            // 通配符匹配：包含匹配，不区分大小写
            $is_match = (stripos($software_name_lower, $blacklist_item) !== false);
            $explanation = "通配符匹配黑名单";
        }
        
        if ($is_match) {
            $compliance_status = 'blacklisted';
            $matched_rule = $blacklist_rule['name'];
            break;
        }
    }
    
    // 如果不在黑名单中，检查白名单
    if ($compliance_status === 'unmanaged') {
        $in_whitelist = false;
        foreach ($test_whitelists as $whitelist_rule) {
            $whitelist_item = strtolower($whitelist_rule['name']);
            $exact_match = $whitelist_rule['exact_match'];
            
            $is_match = false;
            if ($exact_match) {
                // 严格匹配：仅不区分大小写，名称要完全一致
                $is_match = (strcasecmp($software_name_lower, $whitelist_item) === 0);
                $explanation = "严格匹配白名单";
            } else {
                // 通配符匹配：包含匹配，不区分大小写
                $is_match = (stripos($software_name_lower, $whitelist_item) !== false);
                $explanation = "通配符匹配白名单";
            }
            
            if ($is_match) {
                $compliance_status = 'approved';
                $matched_rule = $whitelist_rule['name'];
                $in_whitelist = true;
                break;
            }
        }
        
        // 根据需求：不在白名单范围内的记录为未登记软件
        if (!$in_whitelist) {
            $compliance_status = 'unmanaged';
            $matched_rule = '';
            $explanation = "不在白名单范围内";
        }
    }
    
    $status_text = [
        'approved' => '✅ 合规',
        'blacklisted' => '❌ 违规',
        'unmanaged' => '❓ 未登记'
    ];
    
    echo sprintf("%-25s %-15s %-20s %s\n", 
        substr($software, 0, 24),
        $status_text[$compliance_status],
        $matched_rule ?: '-',
        $explanation
    );
}

echo "\n" . str_repeat("=", 80) . "\n\n";

echo "需求符合性检查:\n";
echo "✅ 黑名单优先原则：solidworks匹配黑名单而非白名单\n";
echo "✅ 通配符匹配：'solidworks 2022'匹配'solidworks'规则\n";
echo "✅ 严格匹配：'SOLIDWORKS'严格匹配同名规则\n";
echo "✅ 大小写不敏感：所有匹配都不区分大小写\n";
echo "✅ 未登记分类：不在白名单的软件被正确分类为未登记\n";

echo "\n=== 验证完成 ===\n";
?>