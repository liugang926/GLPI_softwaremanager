<?php
/**
 * 插件安装诊断脚本
 */

// 模拟GLPI环境
define('GLPI_ROOT', 'C:/xampp/htdocs/glpi');
define('PLUGIN_SOFTWAREMANAGER_VERSION', '1.0.0');

echo "=== 插件安装诊断 ===\n\n";

// 1. 检查文件存在性
echo "1. 检查关键文件:\n";
$required_files = [
    'setup.php',
    'inc/install.class.php',
    'inc/softwarewhitelist.class.php',
    'inc/softwareblacklist.class.php',
    'inc/scanhistory.class.php',
    'inc/scanresult.class.php',
    'inc/menu.class.php'
];

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✓ $file 存在\n";
    } else {
        echo "✗ $file 不存在\n";
    }
}

echo "\n2. 检查PHP语法:\n";
foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $output = [];
        $return_code = 0;
        exec("php -l \"$path\"", $output, $return_code);
        if ($return_code === 0) {
            echo "✓ $file 语法正确\n";
        } else {
            echo "✗ $file 语法错误: " . implode(' ', $output) . "\n";
        }
    }
}

echo "\n3. 检查模型类定义:\n";

// 包含文件并检查类定义
try {
    require_once(__DIR__ . '/inc/softwarewhitelist.class.php');
    if (class_exists('PluginSoftwaremanagerSoftwareWhitelist')) {
        echo "✓ PluginSoftwaremanagerSoftwareWhitelist 类已定义\n";
        
        // 检查安装方法
        if (method_exists('PluginSoftwaremanagerSoftwareWhitelist', 'install')) {
            echo "✓ 白名单类有install方法\n";
        } else {
            echo "✗ 白名单类缺少install方法\n";
        }
    } else {
        echo "✗ PluginSoftwaremanagerSoftwareWhitelist 类未定义\n";
    }
} catch (Exception $e) {
    echo "✗ 加载白名单类失败: " . $e->getMessage() . "\n";
}

try {
    require_once(__DIR__ . '/inc/softwareblacklist.class.php');
    if (class_exists('PluginSoftwaremanagerSoftwareBlacklist')) {
        echo "✓ PluginSoftwaremanagerSoftwareBlacklist 类已定义\n";
        
        if (method_exists('PluginSoftwaremanagerSoftwareBlacklist', 'install')) {
            echo "✓ 黑名单类有install方法\n";
        } else {
            echo "✗ 黑名单类缺少install方法\n";
        }
    } else {
        echo "✗ PluginSoftwaremanagerSoftwareBlacklist 类未定义\n";
    }
} catch (Exception $e) {
    echo "✗ 加载黑名单类失败: " . $e->getMessage() . "\n";
}

try {
    require_once(__DIR__ . '/inc/scanhistory.class.php');
    if (class_exists('PluginSoftwaremanagerScanhistory')) {
        echo "✓ PluginSoftwaremanagerScanhistory 类已定义\n";
    } else {
        echo "✗ PluginSoftwaremanagerScanhistory 类未定义\n";
    }
} catch (Exception $e) {
    echo "✗ 加载扫描历史类失败: " . $e->getMessage() . "\n";
}

try {
    require_once(__DIR__ . '/inc/scanresult.class.php');
    if (class_exists('PluginSoftwaremanagerScanresult')) {
        echo "✓ PluginSoftwaremanagerScanresult 类已定义\n";
    } else {
        echo "✗ PluginSoftwaremanagerScanresult 类未定义\n";
    }
} catch (Exception $e) {
    echo "✗ 加载扫描结果类失败: " . $e->getMessage() . "\n";
}

echo "\n4. 模拟安装过程:\n";
try {
    require_once(__DIR__ . '/inc/install.class.php');
    if (class_exists('PluginSoftwaremanagerInstall')) {
        echo "✓ PluginSoftwaremanagerInstall 类已定义\n";
        
        if (method_exists('PluginSoftwaremanagerInstall', 'install')) {
            echo "✓ 安装类有install方法\n";
        } else {
            echo "✗ 安装类缺少install方法\n";
        }
    } else {
        echo "✗ PluginSoftwaremanagerInstall 类未定义\n";
    }
} catch (Exception $e) {
    echo "✗ 加载安装类失败: " . $e->getMessage() . "\n";
}

echo "\n5. 检查setup.php函数:\n";
require_once(__DIR__ . '/setup.php');

$functions = [
    'plugin_init_softwaremanager',
    'plugin_version_softwaremanager', 
    'plugin_softwaremanager_check_prerequisites',
    'plugin_softwaremanager_check_config',
    'plugin_softwaremanager_install',
    'plugin_softwaremanager_uninstall'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✓ $func 函数已定义\n";
    } else {
        echo "✗ $func 函数未定义\n";
    }
}

echo "\n6. 测试前置条件检查:\n";
if (function_exists('plugin_softwaremanager_check_prerequisites')) {
    ob_start();
    $result = plugin_softwaremanager_check_prerequisites();
    $output = ob_get_clean();
    
    if ($result) {
        echo "✓ 前置条件检查通过\n";
    } else {
        echo "✗ 前置条件检查失败: $output\n";
    }
}

echo "\n=== 诊断完成 ===\n";
?>