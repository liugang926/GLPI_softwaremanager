<?php
/**
 * 简化测试页面 - 无CSRF检查
 */

// 开启错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>简化测试 (无CSRF)</h1>";

// 加载GLPI环境
include('../../../inc/includes.php');

echo "<h2>环境测试</h2>";
echo "✅ GLPI环境加载成功<br>";

// 测试类加载
$whitelist = new PluginSoftwaremanagerSoftwareWhitelist();
echo "✅ 白名单类加载成功<br>";

// 处理POST请求
if (isset($_POST['action'])) {
    echo "<h2>执行测试</h2>";
    
    if ($_POST['action'] == 'test_add') {
        echo "<h3>测试add方法</h3>";
        
        $test_data = [
            'name' => 'TestSoftware_' . time(),
            'comment' => '测试软件',
            'is_active' => 1
        ];
        
        echo "测试数据: ";
        print_r($test_data);
        echo "<br>";
        
        try {
            $result = $whitelist->add($test_data);
            echo "add方法返回: ";
            var_dump($result);
            echo "<br>";
            
            if ($result) {
                echo "✅ 添加成功！<br>";
            } else {
                echo "❌ 添加失败！<br>";
            }
        } catch (Exception $e) {
            echo "❌ 异常: " . $e->getMessage() . "<br>";
        }
    }
    
    if ($_POST['action'] == 'test_static') {
        echo "<h3>测试addToList静态方法</h3>";
        
        try {
            $result = PluginSoftwaremanagerSoftwareWhitelist::addToList('StaticTest_' . time(), '静态方法测试');
            echo "addToList返回: ";
            var_dump($result);
            echo "<br>";
            
            if ($result) {
                echo "✅ 静态方法成功！<br>";
            } else {
                echo "❌ 静态方法失败！<br>";
            }
        } catch (Exception $e) {
            echo "❌ 异常: " . $e->getMessage() . "<br>";
        }
    }
}

?>

<h2>测试按钮</h2>

<form method="POST">
    <input type="hidden" name="action" value="test_add">
    <button type="submit" style="padding: 10px; background: blue; color: white;">测试add方法</button>
</form>

<form method="POST">
    <input type="hidden" name="action" value="test_static">
    <button type="submit" style="padding: 10px; background: green; color: white;">测试addToList方法</button>
</form>

<h3>当前数据</h3>
<?php
try {
    $items = $whitelist->find([], ['LIMIT' => 5]);
    echo "数据库中的记录: " . count($items) . "<br>";
    if (!empty($items)) {
        echo "<pre>";
        print_r($items);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "查询失败: " . $e->getMessage();
}
?> 