<?php
/**
 * 检查数据库表状态并创建测试数据
 */

include('../../../inc/includes.php');

echo "=== 数据库表状态检查和数据创建 ===\n\n";

global $DB;

// 1. 检查表是否存在
echo "1. 检查数据库表状态:\n";

$tables_to_check = [
    'glpi_plugin_softwaremanager_whitelists' => '白名单表',
    'glpi_plugin_softwaremanager_blacklists' => '黑名单表',
    'glpi_plugin_softwaremanager_scanhistory' => '扫描历史表',
    'glpi_plugin_softwaremanager_scanresults' => '扫描结果表'
];

$missing_tables = [];
foreach ($tables_to_check as $table => $description) {
    if ($DB->tableExists($table)) {
        $count = $DB->request(['COUNT' => 'id', 'FROM' => $table])->current()['COUNT'];
        echo "✓ $description ($table): 存在，$count 条记录\n";
    } else {
        echo "✗ $description ($table): 不存在\n";
        $missing_tables[] = $table;
    }
}

// 2. 如果表不存在，创建它们
if (!empty($missing_tables)) {
    echo "\n2. 创建缺失的数据库表:\n";
    
    foreach ($missing_tables as $table) {
        switch ($table) {
            case 'glpi_plugin_softwaremanager_whitelists':
                $query = "CREATE TABLE `$table` (
                    `id` int unsigned NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `version` varchar(100) DEFAULT NULL,
                    `publisher` varchar(255) DEFAULT NULL,
                    `category` varchar(100) DEFAULT NULL,
                    `license_type` varchar(50) DEFAULT 'unknown',
                    `install_path` text,
                    `description` text,
                    `comment` text,
                    `exact_match` tinyint NOT NULL DEFAULT '0',
                    `is_active` tinyint NOT NULL DEFAULT '1',
                    `priority` int NOT NULL DEFAULT '0',
                    `is_deleted` tinyint NOT NULL DEFAULT '0',
                    `date_creation` timestamp NULL DEFAULT NULL,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `name` (`name`),
                    KEY `exact_match` (`exact_match`),
                    KEY `is_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                break;
                
            case 'glpi_plugin_softwaremanager_blacklists':
                $query = "CREATE TABLE `$table` (
                    `id` int unsigned NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `version` varchar(100) DEFAULT NULL,
                    `publisher` varchar(255) DEFAULT NULL,
                    `category` varchar(100) DEFAULT NULL,
                    `risk_level` varchar(50) DEFAULT 'medium',
                    `description` text,
                    `comment` text,
                    `exact_match` tinyint NOT NULL DEFAULT '0',
                    `is_active` tinyint NOT NULL DEFAULT '1',
                    `priority` int NOT NULL DEFAULT '0',
                    `is_deleted` tinyint NOT NULL DEFAULT '0',
                    `date_creation` timestamp NULL DEFAULT NULL,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `name` (`name`),
                    KEY `exact_match` (`exact_match`),
                    KEY `is_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                break;
                
            case 'glpi_plugin_softwaremanager_scanhistory':
                $query = "CREATE TABLE `$table` (
                    `id` int unsigned NOT NULL AUTO_INCREMENT,
                    `user_id` int unsigned NOT NULL DEFAULT '0',
                    `scan_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    `total_software` int NOT NULL DEFAULT '0',
                    `whitelist_count` int NOT NULL DEFAULT '0',
                    `blacklist_count` int NOT NULL DEFAULT '0',
                    `unmanaged_count` int NOT NULL DEFAULT '0',
                    `scan_duration` int NOT NULL DEFAULT '0',
                    `status` varchar(20) NOT NULL DEFAULT 'pending',
                    `notes` text,
                    `date_creation` timestamp NULL DEFAULT NULL,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `user_id` (`user_id`),
                    KEY `scan_date` (`scan_date`),
                    KEY `status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                break;
                
            case 'glpi_plugin_softwaremanager_scanresults':
                $query = "CREATE TABLE `$table` (
                    `id` int unsigned NOT NULL AUTO_INCREMENT,
                    `scanhistory_id` int unsigned NOT NULL,
                    `software_name` varchar(255) NOT NULL,
                    `software_version` varchar(100) DEFAULT NULL,
                    `computer_id` int unsigned DEFAULT NULL,
                    `computer_name` varchar(255) DEFAULT NULL,
                    `user_id` int unsigned DEFAULT NULL,
                    `user_name` varchar(255) DEFAULT NULL,
                    `group_id` int unsigned DEFAULT NULL,
                    `violation_type` varchar(50) NOT NULL,
                    `install_date` timestamp NULL DEFAULT NULL,
                    `matched_rule` varchar(255) DEFAULT NULL,
                    `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `scanhistory_id` (`scanhistory_id`),
                    KEY `software_name` (`software_name`),
                    KEY `computer_id` (`computer_id`),
                    KEY `user_id` (`user_id`),
                    KEY `violation_type` (`violation_type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                break;
        }
        
        if ($DB->query($query)) {
            echo "✓ 创建表: $table\n";
        } else {
            echo "✗ 创建表失败: $table - " . $DB->error() . "\n";
        }
    }
}

echo "\n3. 创建测试数据:\n";

$action = $_GET['action'] ?? '';

if ($action === 'create_test_data') {
    
    // 创建白名单测试数据
    echo "正在创建白名单测试数据...\n";
    
    $whitelist_data = [
        // 精确匹配的规则
        ['name' => '64 Bit HP CIO Components Installer', 'exact_match' => 1, 'comment' => '精确匹配测试'],
        ['name' => 'Adobe Acrobat (64-bit)', 'exact_match' => 1, 'comment' => '精确匹配测试'],
        ['name' => 'Bonjour', 'exact_match' => 1, 'comment' => '精确匹配测试'],
        
        // 通配符匹配的规则（谨慎使用）
        ['name' => 'Microsoft Office', 'exact_match' => 0, 'comment' => '通配符匹配，包含Office系列'],
        ['name' => 'Google Chrome', 'exact_match' => 0, 'comment' => '通配符匹配，包含Chrome系列'],
    ];
    
    foreach ($whitelist_data as $data) {
        // 检查是否已存在
        $existing = $DB->request([
            'FROM' => 'glpi_plugin_softwaremanager_whitelists',
            'WHERE' => ['name' => $data['name']]
        ]);
        
        if (count($existing) == 0) {
            $result = $DB->insert('glpi_plugin_softwaremanager_whitelists', [
                'name' => $data['name'],
                'exact_match' => $data['exact_match'],
                'is_active' => 1,
                'comment' => $data['comment'],
                'date_creation' => date('Y-m-d H:i:s'),
                'date_mod' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $match_type = $data['exact_match'] ? '精确匹配' : '通配符匹配';
                echo "  ✓ 添加白名单: '{$data['name']}' ($match_type)\n";
            }
        } else {
            echo "  - 跳过已存在: '{$data['name']}'\n";
        }
    }
    
    // 创建黑名单测试数据
    echo "\n正在创建黑名单测试数据...\n";
    
    $blacklist_data = [
        // 精确匹配的规则
        ['name' => 'Adobe Genuine Service', 'exact_match' => 1, 'comment' => '测试精确匹配黑名单'],
        ['name' => 'WinRAR', 'exact_match' => 1, 'comment' => '测试精确匹配黑名单'],
        
        // 通配符匹配的规则
        ['name' => 'barrier', 'exact_match' => 0, 'comment' => '测试通配符匹配，应该匹配 Barrier 2.4.0-release'],
        ['name' => 'utorrent', 'exact_match' => 0, 'comment' => '下载工具，通配符匹配'],
        ['name' => 'bittorrent', 'exact_match' => 0, 'comment' => '下载工具，通配符匹配'],
        ['name' => 'teamviewer', 'exact_match' => 0, 'comment' => '远程控制软件，通配符匹配'],
    ];
    
    foreach ($blacklist_data as $data) {
        // 检查是否已存在
        $existing = $DB->request([
            'FROM' => 'glpi_plugin_softwaremanager_blacklists',
            'WHERE' => ['name' => $data['name']]
        ]);
        
        if (count($existing) == 0) {
            $result = $DB->insert('glpi_plugin_softwaremanager_blacklists', [
                'name' => $data['name'],
                'exact_match' => $data['exact_match'],
                'is_active' => 1,
                'comment' => $data['comment'],
                'risk_level' => 'medium',
                'date_creation' => date('Y-m-d H:i:s'),
                'date_mod' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $match_type = $data['exact_match'] ? '精确匹配' : '通配符匹配';
                echo "  ✓ 添加黑名单: '{$data['name']}' ($match_type)\n";
            }
        } else {
            echo "  - 跳过已存在: '{$data['name']}'\n";
        }
    }
    
    echo "\n✅ 测试数据创建完成！\n\n";
    
    echo "预期扫描结果:\n";
    echo "- '64 Bit HP CIO Components Installer' → ✅ 合规（白名单精确匹配）\n";
    echo "- 'Adobe Acrobat (64-bit)' → ✅ 合规（白名单精确匹配）\n";
    echo "- 'Adobe Genuine Service' → ❌ 违规（黑名单精确匹配）\n";
    echo "- 'Barrier 2.4.0-release' → ❌ 违规（黑名单通配符匹配'barrier'）\n";
    echo "- 'Bonjour' → ✅ 合规（白名单精确匹配）\n";
    echo "- 其他软件 → ❓ 未登记（不在白名单中）\n\n";
    
    echo "现在可以重新运行合规性扫描！\n";
    
} else {
    echo "要创建测试数据，请访问: ?action=create_test_data\n\n";
    
    echo "测试数据将包括:\n";
    echo "白名单规则:\n";
    echo "- 3个精确匹配规则（与扫描结果中的软件对应）\n";
    echo "- 2个通配符匹配规则（谨慎使用）\n\n";
    echo "黑名单规则:\n";
    echo "- 2个精确匹配规则\n";
    echo "- 4个通配符匹配规则\n\n";
}

echo "\n=== 完成 ===\n";
?>