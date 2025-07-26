<?php
/**
 * 简化版数据检查脚本
 */

// 基本的错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>数据库连接和表检查</h2>\n";

try {
    // 尝试包含GLPI
    include('../../../inc/includes.php');
    echo "<p>✓ GLPI包含成功</p>\n";
    
    global $DB;
    if (!$DB) {
        echo "<p>❌ 数据库连接失败</p>\n";
        exit;
    }
    echo "<p>✓ 数据库连接成功</p>\n";
    
    // 检查表是否存在
    $tables = [
        'glpi_plugin_softwaremanager_whitelists',
        'glpi_plugin_softwaremanager_blacklists'
    ];
    
    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            echo "<p>✓ 表存在: $table</p>\n";
            
            // 检查数据数量
            try {
                $count_query = "SELECT COUNT(*) as count FROM `$table`";
                $result = $DB->query($count_query);
                if ($result) {
                    $row = $DB->fetchAssoc($result);
                    $count = $row['count'];
                    echo "<p>&nbsp;&nbsp;&nbsp;记录数: $count</p>\n";
                    
                    if ($count > 0) {
                        // 显示前5条记录
                        $sample_query = "SELECT name, exact_match, is_active FROM `$table` LIMIT 5";
                        $sample_result = $DB->query($sample_query);
                        if ($sample_result) {
                            echo "<p>&nbsp;&nbsp;&nbsp;样本数据:</p>\n";
                            echo "<ul>\n";
                            while ($sample_row = $DB->fetchAssoc($sample_result)) {
                                $match_type = $sample_row['exact_match'] ? '精确' : '通配符';
                                $active = $sample_row['is_active'] ? '活跃' : '非活跃';
                                echo "<li>{$sample_row['name']} ($match_type, $active)</li>\n";
                            }
                            echo "</ul>\n";
                        }
                    } else {
                        echo "<p>&nbsp;&nbsp;&nbsp;❌ 表为空！</p>\n";
                    }
                } else {
                    echo "<p>&nbsp;&nbsp;&nbsp;❌ 查询失败: " . $DB->error() . "</p>\n";
                }
            } catch (Exception $e) {
                echo "<p>&nbsp;&nbsp;&nbsp;❌ 查询异常: " . $e->getMessage() . "</p>\n";
            }
        } else {
            echo "<p>❌ 表不存在: $table</p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ 脚本执行错误: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<h2>手动解决方案</h2>\n";
echo "<p>如果上面显示表为空，请在GLPI数据库中执行以下SQL命令：</p>\n";

echo "<h3>1. 检查表状态</h3>\n";
echo "<pre>\n";
echo "SELECT COUNT(*) as count FROM glpi_plugin_softwaremanager_whitelists;\n";
echo "SELECT COUNT(*) as count FROM glpi_plugin_softwaremanager_blacklists;\n";
echo "</pre>\n";

echo "<h3>2. 如果表为空，创建测试数据</h3>\n";
echo "<pre>\n";
echo "-- 创建白名单测试数据\n";
echo "INSERT INTO glpi_plugin_softwaremanager_whitelists (name, exact_match, is_active, date_creation) VALUES\n";
echo "('Bonjour', 1, 1, NOW()),\n";
echo "('Microsoft Visual C++', 0, 1, NOW());\n\n";

echo "-- 创建黑名单测试数据\n";
echo "INSERT INTO glpi_plugin_softwaremanager_blacklists (name, exact_match, is_active, date_creation) VALUES\n";
echo "('Adobe Genuine Service', 1, 1, NOW()),\n";
echo "('barrier', 0, 1, NOW()),\n";
echo "('64 Bit HP CIO', 0, 1, NOW());\n";
echo "</pre>\n";

echo "<h3>3. 如果表有数据但匹配过度，临时禁用通配符规则</h3>\n";
echo "<pre>\n";
echo "-- 禁用所有通配符匹配的白名单规则\n";
echo "UPDATE glpi_plugin_softwaremanager_whitelists SET is_active = 0 WHERE exact_match = 0;\n\n";
echo "-- 只保留精确匹配的规则\n";
echo "UPDATE glpi_plugin_softwaremanager_whitelists SET is_active = 1 WHERE exact_match = 1;\n";
echo "</pre>\n";

echo "<p><strong>执行完SQL后，重新运行合规性扫描！</strong></p>\n";
?>