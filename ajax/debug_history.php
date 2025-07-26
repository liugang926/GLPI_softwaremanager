<?php
/**
 * Debug script to check scan history table
 */

include("../../../inc/includes.php");

// Set content type
header("Content-Type: text/html; charset=UTF-8");

// Clean any previous output
while (ob_get_level()) {
    ob_end_clean();
}

try {
    global $DB;
    
    if (!$DB) {
        throw new Exception('Database connection not available');
    }
    
    echo "<h3>扫描历史表调试</h3>";
    
    // Check table structure
    $table_name = 'glpi_plugin_softwaremanager_scanhistory';
    echo "<h4>表结构检查: {$table_name}</h4>";
    
    if ($DB->tableExists($table_name)) {
        echo "<p>✅ 表存在</p>";
        
        // Show recent records with simple query
        echo "<h4>最近的扫描记录</h4>";
        $data_result = $DB->query("SELECT * FROM `{$table_name}` ORDER BY id DESC LIMIT 5");
        if ($data_result) {
            $count = $DB->numrows($data_result);
            echo "<p><strong>找到 {$count} 条记录</strong></p>";
            
            if ($count > 0) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background: #f0f0f0;'>";
                echo "<th>ID</th><th>用户ID</th><th>扫描日期</th><th>总软件数</th><th>白名单数</th><th>黑名单数</th><th>未管理数</th><th>状态</th>";
                echo "</tr>";
                
                while ($row = $DB->fetchAssoc($data_result)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['user_id'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['scan_date'] ?? '') . "</td>";
                    echo "<td style='font-weight: bold; color: blue;'>" . htmlspecialchars($row['total_software'] ?? '0') . "</td>";
                    echo "<td style='font-weight: bold; color: green;'>" . htmlspecialchars($row['whitelist_count'] ?? '0') . "</td>";
                    echo "<td style='font-weight: bold; color: red;'>" . htmlspecialchars($row['blacklist_count'] ?? '0') . "</td>";
                    echo "<td style='font-weight: bold; color: orange;'>" . htmlspecialchars($row['unmanaged_count'] ?? '0') . "</td>";
                    echo "<td>" . htmlspecialchars($row['status'] ?? '') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>❌ 没有找到扫描记录</p>";
            }
        } else {
            echo "<p><strong>查询失败:</strong> " . $DB->error() . "</p>";
        }
        
    } else {
        echo "<p>❌ 表不存在</p>";
    }
    
    // Show current user info
    echo "<h4>当前用户信息</h4>";
    $current_user_id = Session::getLoginUserID();
    echo "<p>当前用户ID: " . $current_user_id . "</p>";
    
    // Show recent error logs if available
    echo "<h4>最近的PHP错误 (如果有)</h4>";
    $error_log_path = ini_get('error_log');
    echo "<p>错误日志路径: " . ($error_log_path ?: '未设置') . "</p>";
    
} catch (Exception $e) {
    echo "<h3>错误信息</h3>";
    echo "<p style='color: red;'><strong>错误:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>文件:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>行号:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><small>调试脚本执行完成 - " . date('Y-m-d H:i:s') . "</small></p>";
?>