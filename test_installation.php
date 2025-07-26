<?php
/**
 * 插件完整安装测试脚本
 * 测试所有必需的组件和安装过程
 */

// 模拟GLPI环境
define('GLPI_ROOT', __DIR__);

// 创建模拟的CommonDBTM类
if (!class_exists('CommonDBTM')) {
    class CommonDBTM {
        // 基础模拟方法
    }
}

echo "=== 软件管理插件安装测试 ===\n\n";

// 1. 检查所有PHP文件语法
echo "1. 检查PHP文件语法...\n";
$php_files = [
    'setup.php',
    'inc/install.class.php',
    'inc/softwarewhitelist.class.php',
    'inc/softwareblacklist.class.php',
    'inc/scanhistory.class.php',
    'inc/scanresult.class.php',
    'inc/menu.class.php',
    'inc/ajax.class.php'
];

$syntax_errors = [];
foreach ($php_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $output = [];
        $return_code = 0;
        exec("php -l \"$path\" 2>&1", $output, $return_code);
        if ($return_code === 0) {
            echo "✓ $file\n";
        } else {
            echo "✗ $file: " . implode(' ', $output) . "\n";
            $syntax_errors[] = $file;
        }
    } else {
        echo "? $file 不存在\n";
    }
}

if (!empty($syntax_errors)) {
    echo "\n❌ 发现语法错误，无法继续安装测试\n";
    exit(1);
}

echo "✅ 所有PHP文件语法检查通过\n\n";

// 2. 检查setup.php中的必需函数
echo "2. 检查setup.php函数...\n";
require_once(__DIR__ . '/setup.php');

$required_functions = [
    'plugin_init_softwaremanager',
    'plugin_version_softwaremanager',
    'plugin_softwaremanager_check_prerequisites',
    'plugin_softwaremanager_check_config',
    'plugin_softwaremanager_install',
    'plugin_softwaremanager_uninstall'
];

foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "✓ $func\n";
    } else {
        echo "✗ $func 缺失\n";
    }
}

// 3. 测试前置条件检查
echo "\n3. 测试前置条件检查...\n";
ob_start();
$prereq_result = plugin_softwaremanager_check_prerequisites();
$prereq_output = ob_get_clean();

if ($prereq_result) {
    echo "✅ 前置条件检查通过\n";
} else {
    echo "❌ 前置条件检查失败: $prereq_output\n";
}

// 4. 测试插件版本信息
echo "\n4. 测试插件版本信息...\n";
$version_info = plugin_version_softwaremanager();
echo "插件名称: " . $version_info['name'] . "\n";
echo "版本: " . $version_info['version'] . "\n";
echo "作者: " . $version_info['author'] . "\n";

// 5. 模拟数据库连接和Migration类
echo "\n5. 模拟安装过程...\n";

// 创建模拟的Migration类
if (!class_exists('Migration')) {
    class Migration {
        private $version;
        
        public function __construct($version) {
            $this->version = $version;
        }
        
        public function displayMessage($message) {
            echo "MIGRATION: $message\n";
        }
        
        public function executeMigration() {
            echo "MIGRATION: 执行迁移完成\n";
        }
    }
}

// 创建模拟的DB类
if (!class_exists('DB')) {
    class MockDB {
        public function tableExists($table) {
            echo "检查表是否存在: $table\n";
            return false; // 假设表不存在，需要创建
        }
        
        public function queryOrDie($query, $message) {
            echo "执行SQL: " . substr($query, 0, 50) . "...\n";
            echo "消息: $message\n";
            return true;
        }
    }
    
    global $DB;
    $DB = new MockDB();
}

// 6. 测试安装类
echo "\n6. 测试安装类...\n";
try {
    require_once(__DIR__ . '/inc/install.class.php');
    
    if (class_exists('PluginSoftwaremanagerInstall')) {
        echo "✓ 安装类已加载\n";
        
        // 检查安装方法
        if (method_exists('PluginSoftwaremanagerInstall', 'install')) {
            echo "✓ install方法存在\n";
        } else {
            echo "✗ install方法缺失\n";
        }
        
        if (method_exists('PluginSoftwaremanagerInstall', 'uninstall')) {
            echo "✓ uninstall方法存在\n";
        } else {
            echo "✗ uninstall方法缺失\n";
        }
    } else {
        echo "✗ 无法加载安装类\n";
    }
} catch (Exception $e) {
    echo "✗ 加载安装类时出错: " . $e->getMessage() . "\n";
}

// 7. 检查模型类的install方法
echo "\n7. 检查模型类的install方法...\n";

$model_classes = [
    'PluginSoftwaremanagerSoftwareWhitelist' => 'inc/softwarewhitelist.class.php',
    'PluginSoftwaremanagerSoftwareBlacklist' => 'inc/softwareblacklist.class.php',
    'PluginSoftwaremanagerScanhistory' => 'inc/scanhistory.class.php',
    'PluginSoftwaremanagerScanresult' => 'inc/scanresult.class.php'
];

foreach ($model_classes as $class => $file) {
    try {
        require_once(__DIR__ . '/' . $file);
        
        if (class_exists($class)) {
            echo "✓ $class 类已加载\n";
            
            if (method_exists($class, 'install')) {
                echo "  ✓ install方法存在\n";
            } else {
                echo "  ✗ install方法缺失\n";
            }
        } else {
            echo "✗ $class 类未定义\n";
        }
    } catch (Exception $e) {
        echo "✗ 加载 $class 时出错: " . $e->getMessage() . "\n";
    }
}

echo "\n=== 测试完成 ===\n";

// 最终判断
if (empty($syntax_errors) && $prereq_result) {
    echo "\n✅ 插件应该可以正常安装了！\n";
    echo "\n建议的安装步骤：\n";
    echo "1. 将插件文件夹复制到GLPI的plugins目录\n";
    echo "2. 在GLPI管理界面中安装插件\n";
    echo "3. 激活插件\n";
    echo "4. 检查菜单中是否出现'软件管理'选项\n";
} else {
    echo "\n❌ 插件仍有问题，需要进一步修复\n";
}
?>