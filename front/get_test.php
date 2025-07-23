<?php
/**
 * GET请求测试页面 - 避免POST的CSRF检查
 */

// 开启错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>GET请求测试页面</h1>";
echo "<p>当前时间: " . date('Y-m-d H:i:s') . "</p>";

// 加载GLPI环境
try {
    include('../../../inc/includes.php');
    echo "✅ GLPI环境加载成功<br>";
    echo "✅ GLPI版本: " . GLPI_VERSION . "<br>";
    echo "✅ 当前用户ID: " . (isset($_SESSION['glpiID']) ? $_SESSION['glpiID'] : '未登录') . "<br>";
    
    global $DB;
    if (isset($DB) && $DB) {
        echo "✅ 数据库连接已建立<br>";
        
        // 检查插件类
        if (class_exists('PluginSoftwaremanagerSoftwareWhitelist')) {
            echo "✅ 插件白名单类已加载<br>";
        } else {
            echo "❌ 插件白名单类未加载<br>";
        }
        
        // 显示表信息
        $table_name = 'glpi_plugin_softwaremanager_whitelists';
        $query = "SELECT COUNT(*) as count FROM $table_name";
        $result = $DB->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            echo "📊 白名单表现有记录数: " . $row['count'] . "<br>";
        }
        
    } else {
        echo "❌ 无法获取数据库连接<br>";
    }
    
} catch (Exception $e) {
    echo "❌ 加载失败: " . $e->getMessage() . "<br>";
}

// 处理GET请求 - 测试插入
if (isset($_GET['action']) && $_GET['action'] == 'test_add') {
    echo "<hr><h2>执行GET插入测试</h2>";
    
    try {
        if (isset($DB) && $DB) {
            $test_name = 'GetTest_' . time();
            $test_comment = 'GET请求测试 - ' . date('H:i:s');
            
            // 方法1: 直接SQL插入
            echo "<h3>方法1: 直接SQL插入</h3>";
            $query = "INSERT INTO glpi_plugin_softwaremanager_whitelists (name, comment, is_active, date_creation, date_mod) 
                      VALUES ('" . $DB->escape($test_name) . "', '" . $DB->escape($test_comment) . "', 1, NOW(), NOW())";
            
            $result = $DB->query($query);
            
            if ($result) {
                $new_id = $DB->insert_id;
                echo "✅ 直接SQL插入成功！新ID: $new_id<br>";
                echo "插入的软件名: $test_name<br>";
            } else {
                echo "❌ 直接SQL插入失败<br>";
                echo "SQL错误: " . $DB->error . "<br>";
            }
            
            // 方法2: 使用插件类的add方法
            echo "<h3>方法2: 使用插件类add方法</h3>";
            if (class_exists('PluginSoftwaremanagerSoftwareWhitelist')) {
                $whitelist = new PluginSoftwaremanagerSoftwareWhitelist();
                
                $test_data = [
                    'name' => $test_name . '_ClassMethod',
                    'comment' => $test_comment . ' (类方法)',
                    'is_active' => 1
                ];
                
                echo "准备插入的数据: ";
                print_r($test_data);
                echo "<br>";
                
                $result = $whitelist->add($test_data);
                
                echo "add方法返回值类型: " . gettype($result) . "<br>";
                echo "add方法返回值: ";
                var_dump($result);
                echo "<br>";
                
                if ($result === false) {
                    echo "❌ 类方法add返回false - 添加失败<br>";
                } elseif (is_numeric($result) && $result > 0) {
                    echo "✅ 类方法add成功！新ID: " . $result . "<br>";
                } else {
                    echo "⚠️ 类方法add返回了意外的值: " . var_export($result, true) . "<br>";
                }
            }
            
            // 方法3: 使用静态方法addToList
            echo "<h3>方法3: 使用静态方法addToList</h3>";
            if (class_exists('PluginSoftwaremanagerSoftwareWhitelist')) {
                $static_test_name = $test_name . '_Static';
                $static_result = PluginSoftwaremanagerSoftwareWhitelist::addToList($static_test_name, $test_comment . ' (静态方法)');
                
                echo "addToList返回值类型: " . gettype($static_result) . "<br>";
                echo "addToList返回值: ";
                var_dump($static_result);
                echo "<br>";
                
                if ($static_result === false) {
                    echo "❌ 静态方法addToList返回false<br>";
                } elseif ($static_result === true || (is_numeric($static_result) && $static_result > 0)) {
                    echo "✅ 静态方法addToList成功！<br>";
                } else {
                    echo "⚠️ 静态方法addToList返回了意外的值<br>";
                }
            }
            
        } else {
            echo "❌ 数据库连接不可用<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ 执行异常: " . $e->getMessage() . "<br>";
        echo "异常位置: " . $e->getFile() . ":" . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

?>

<hr>
<h2>测试按钮 (GET请求)</h2>

<a href="?action=test_add" style="display: inline-block; padding: 15px 25px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0;">
    🧪 执行插入测试 (GET方式)
</a>

<a href="?" style="display: inline-block; padding: 15px 25px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0;">
    🔄 刷新页面
</a>

<hr>
<h3>最新记录</h3>
<?php
if (isset($DB) && $DB) {
    try {
        $query = "SELECT * FROM glpi_plugin_softwaremanager_whitelists ORDER BY date_creation DESC LIMIT 10";
        $result = $DB->query($query);
        
        if ($result && $result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'><th>ID</th><th>名称</th><th>备注</th><th>创建时间</th></tr>";
            
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
            echo "<p style='color: #666;'>暂无记录</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>查询失败: " . $e->getMessage() . "</p>";
    }
}
?>

<hr>
<p><strong>说明:</strong> 这个页面使用GET请求避免CSRF检查，可以测试三种不同的数据插入方法：</p>
<ol>
<li><strong>直接SQL:</strong> 使用GLPI的$DB对象直接执行SQL</li>
<li><strong>类方法add:</strong> 使用插件类的add方法</li>
<li><strong>静态方法addToList:</strong> 使用插件类的静态方法</li>
</ol>

<p>如果所有方法都工作正常，说明问题确实出在CSRF检查上。</p> 