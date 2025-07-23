<?php
/**
 * 最基础的测试页面 - 正确加载GLPI环境
 */

// 开启错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>最基础测试页面 (修复版)</h1>";
echo "<p>当前时间: " . date('Y-m-d H:i:s') . "</p>";

// 正确的方式加载GLPI环境
try {
    // 加载GLPI核心环境
    include('../../../inc/includes.php');
    echo "✅ GLPI环境加载成功<br>";
    
    // 获取数据库连接
    global $DB;
    if (isset($DB) && $DB) {
        echo "✅ 数据库连接已建立<br>";
        
        // 检查白名单表是否存在
        $table_name = 'glpi_plugin_softwaremanager_whitelists';
        $query = "SHOW TABLES LIKE '$table_name'";
        $result = $DB->query($query);
        
        if ($result && $result->num_rows > 0) {
            echo "✅ 白名单表存在: $table_name<br>";
            
            // 获取表结构
            $query = "DESCRIBE $table_name";
            $result = $DB->query($query);
            $columns = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $columns[] = $row['Field'];
                }
                echo "表字段: " . implode(', ', $columns) . "<br>";
            }
            
            // 查询现有数据
            $query = "SELECT COUNT(*) as count FROM $table_name";
            $result = $DB->query($query);
            if ($result) {
                $row = $result->fetch_assoc();
                echo "现有记录数: " . $row['count'] . "<br>";
            }
            
        } else {
            echo "❌ 白名单表不存在<br>";
        }
        
    } else {
        echo "❌ 无法获取数据库连接<br>";
    }
    
} catch (Exception $e) {
    echo "❌ GLPI环境加载失败: " . $e->getMessage() . "<br>";
    echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}

// 处理POST请求 - 使用GLPI的数据库连接
if (isset($_POST['test_insert'])) {
    echo "<h2>执行插入测试</h2>";
    
    try {
        if (isset($DB) && $DB) {
            $test_name = 'DirectTest_' . time();
            $test_comment = '直接SQL测试';
            
            $query = "INSERT INTO glpi_plugin_softwaremanager_whitelists (name, comment, is_active, date_creation, date_mod) 
                      VALUES ('" . $DB->escape($test_name) . "', '" . $DB->escape($test_comment) . "', 1, NOW(), NOW())";
            
            $result = $DB->query($query);
            
            if ($result) {
                $new_id = $DB->insert_id;
                echo "✅ 直接SQL插入成功！新ID: $new_id<br>";
                echo "插入的软件名: $test_name<br>";
                
                // 验证插入
                $query = "SELECT * FROM glpi_plugin_softwaremanager_whitelists WHERE id = $new_id";
                $result = $DB->query($query);
                
                if ($result && $result->num_rows > 0) {
                    $inserted_data = $result->fetch_assoc();
                    echo "验证成功，插入的数据:<br>";
                    echo "<pre>";
                    print_r($inserted_data);
                    echo "</pre>";
                }
            } else {
                echo "❌ 直接SQL插入失败<br>";
                echo "错误: " . $DB->error . "<br>";
            }
        } else {
            echo "❌ 数据库连接不可用<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ SQL执行异常: " . $e->getMessage() . "<br>";
    }
}

?>

<h2>直接测试 (使用GLPI数据库连接)</h2>

<form method="POST" action="">
    <button type="submit" name="test_insert" style="padding: 15px; background: #007cba; color: white; border: none; font-size: 16px;">
        直接SQL插入测试
    </button>
</form>

<h3>最新记录</h3>
<?php
if (isset($DB) && $DB) {
    try {
        $query = "SELECT * FROM glpi_plugin_softwaremanager_whitelists ORDER BY date_creation DESC LIMIT 5";
        $result = $DB->query($query);
        
        if ($result && $result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>名称</th><th>备注</th><th>创建时间</th></tr>";
            
            while ($record = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($record['id']) . "</td>";
                echo "<td>" . htmlspecialchars($record['name']) . "</td>";
                echo "<td>" . htmlspecialchars($record['comment']) . "</td>";
                echo "<td>" . htmlspecialchars($record['date_creation']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "暂无记录";
        }
    } catch (Exception $e) {
        echo "查询失败: " . $e->getMessage();
    }
}
?>

<hr>
<p><strong>说明:</strong> 这个页面使用GLPI的数据库连接，但不涉及CSRF、Session等复杂的安全机制。</p>

<h3>GLPI环境信息</h3>
<?php
if (defined('GLPI_VERSION')) {
    echo "GLPI版本: " . GLPI_VERSION . "<br>";
}
if (isset($_SESSION['glpiID'])) {
    echo "当前用户ID: " . $_SESSION['glpiID'] . "<br>";
} else {
    echo "未登录用户<br>";
}

// 检查插件类是否可用
if (class_exists('PluginSoftwaremanagerSoftwareWhitelist')) {
    echo "✅ 插件白名单类已加载<br>";
} else {
    echo "❌ 插件白名单类未加载<br>";
}
?> 