<?php
/**
 * 白名单功能调试测试页面
 */

// 开启错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>白名单功能调试测试</h1>";
echo "<hr>";

// 第一步：测试基本的GLPI环境
echo "<h2>第一步：测试GLPI环境加载</h2>";
try {
    include('../../../inc/includes.php');
    echo "✅ GLPI核心环境加载成功<br>";
} catch (Exception $e) {
    echo "❌ GLPI环境加载失败: " . $e->getMessage() . "<br>";
    die();
}

// 第二步：测试类文件加载
echo "<h2>第二步：测试插件类加载</h2>";
try {
    $whitelist = new PluginSoftwaremanagerSoftwareWhitelist();
    echo "✅ 白名单类创建成功<br>";
    echo "类名: " . get_class($whitelist) . "<br>";
} catch (Exception $e) {
    echo "❌ 白名单类创建失败: " . $e->getMessage() . "<br>";
}

// 第三步：测试数据库表连接
echo "<h2>第三步：测试数据库表连接</h2>";
try {
    $table_name = $whitelist->getTable();
    echo "✅ 表名获取成功: " . $table_name . "<br>";
    
    global $DB;
    if ($DB->tableExists($table_name)) {
        echo "✅ 数据库表存在<br>";
        
        // 测试查询现有数据
        $existing_items = $whitelist->find();
        echo "✅ 数据查询成功，现有项目数量: " . count($existing_items) . "<br>";
        
        if (count($existing_items) > 0) {
            echo "现有数据示例:<br>";
            echo "<pre>";
            print_r(array_slice($existing_items, 0, 2, true)); // 只显示前2条
            echo "</pre>";
        }
    } else {
        echo "❌ 数据库表不存在: " . $table_name . "<br>";
    }
} catch (Exception $e) {
    echo "❌ 数据库连接测试失败: " . $e->getMessage() . "<br>";
}

// 第四步：测试简单的add操作
echo "<h2>第四步：测试add方法</h2>";

if (isset($_POST['test_add'])) {
    // 添加CSRF检查
    Session::checkCSRF($_POST);
    echo "<strong>=== 开始执行add测试 ===</strong><br>";
    
    try {
        $test_name = "调试测试_" . date('Y-m-d_H-i-s');
        $test_data = [
            'name' => $test_name,
            'comment' => 'add方法调试测试',
            'is_active' => 1
        ];
        
        echo "准备添加的数据:<br>";
        echo "<pre>";
        print_r($test_data);
        echo "</pre>";
        
        echo "执行add方法...<br>";
        $result = $whitelist->add($test_data);
        
        echo "add方法返回值:<br>";
        echo "类型: " . gettype($result) . "<br>";
        echo "值: ";
        var_dump($result);
        echo "<br>";
        
        if ($result === false) {
            echo "❌ add方法返回false<br>";
        } elseif (is_numeric($result) && $result > 0) {
            echo "✅ add方法成功，新记录ID: " . $result . "<br>";
        } else {
            echo "⚠️ add方法返回异常值<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ add方法执行异常: " . $e->getMessage() . "<br>";
        echo "异常类型: " . get_class($e) . "<br>";
        echo "异常文件: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    }
    
    echo "<strong>=== add测试结束 ===</strong><br>";
}

// 第五步：测试静态方法
echo "<h2>第五步：测试addToList静态方法</h2>";

if (isset($_POST['test_add_to_list'])) {
    // 添加CSRF检查
    Session::checkCSRF($_POST);
    echo "<strong>=== 开始执行addToList测试 ===</strong><br>";
    
    try {
        $test_name = "静态方法测试_" . date('Y-m-d_H-i-s');
        echo "调用addToList静态方法...<br>";
        
        $result = PluginSoftwaremanagerSoftwareWhitelist::addToList($test_name, 'addToList静态方法测试');
        
        echo "addToList方法返回值:<br>";
        echo "类型: " . gettype($result) . "<br>";
        echo "值: ";
        var_dump($result);
        echo "<br>";
        
        if ($result === true) {
            echo "✅ addToList方法成功<br>";
        } elseif ($result === false) {
            echo "❌ addToList方法返回false<br>";
        } else {
            echo "⚠️ addToList方法返回异常值<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ addToList方法执行异常: " . $e->getMessage() . "<br>";
        echo "异常类型: " . get_class($e) . "<br>";
        echo "异常文件: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    }
    
    echo "<strong>=== addToList测试结束 ===</strong><br>";
}

?>

<hr>
<h2>手动测试按钮</h2>

<form method="post" action="">
    <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
    <input type="submit" name="test_add" value="测试add方法" style="padding: 10px; margin: 5px; background: #007cba; color: white; border: none;">
</form>

<form method="post" action="">
    <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
    <input type="submit" name="test_add_to_list" value="测试addToList静态方法" style="padding: 10px; margin: 5px; background: #28a745; color: white; border: none;">
</form>

<hr>
<a href="whitelist.php">返回正常的白名单页面</a> 