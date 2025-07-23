<?php
/**
 * Software Manager Plugin - Diagnostic Script
 */

echo "<h1>Software Manager Plugin - Diagnostic</h1>";

echo "<h2>1. File Structure Check</h2>";
$required_files = [
    'setup.php',
    'inc/profile.class.php',
    'inc/menu.class.php',
    'inc/softwarewhitelist.class.php',
    'inc/softwareblacklist.class.php',
    'inc/softwareinventory.class.php',
    'front/softwarelist.php',
    'front/whitelist.php',
    'front/blacklist.php'
];

foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✓ $file exists<br>";
    } else {
        echo "✗ $file missing<br>";
    }
}

echo "<h2>2. Setup.php Content Check</h2>";
if (file_exists(__DIR__ . '/setup.php')) {
    include_once(__DIR__ . '/setup.php');
    
    echo "<h3>Functions:</h3>";
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
            echo "✓ $func defined<br>";
        } else {
            echo "✗ $func missing<br>";
        }
    }
    
    echo "<h3>Classes:</h3>";
    $classes = [
        'PluginSoftwaremanagerProfile',
        'PluginSoftwaremanagerMenu',
        'PluginSoftwaremanagerSoftwareWhitelist',
        'PluginSoftwaremanagerSoftwareBlacklist',
        'PluginSoftwaremanagerSoftwareInventory'
    ];
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "✓ $class loaded<br>";
        } else {
            echo "✗ $class not loaded<br>";
        }
    }
    
    echo "<h3>Plugin Version Info:</h3>";
    if (function_exists('plugin_version_softwaremanager')) {
        $version = plugin_version_softwaremanager();
        echo "Name: " . $version['name'] . "<br>";
        echo "Version: " . $version['version'] . "<br>";
        echo "Author: " . $version['author'] . "<br>";
    } else {
        echo "Version function not available<br>";
    }
    
} else {
    echo "✗ setup.php not found<br>";
}

echo "<h2>3. Directory Permissions</h2>";
$dirs = [
    __DIR__,
    __DIR__ . '/inc',
    __DIR__ . '/front',
    __DIR__ . '/ajax'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            echo "✓ $dir is readable<br>";
        } else {
            echo "✗ $dir is not readable<br>";
        }
    } else {
        echo "✗ $dir does not exist<br>";
    }
}

echo "<h2>4. PHP Syntax Check</h2>";
$php_files = [
    'setup.php',
    'inc/profile.class.php',
    'inc/menu.class.php',
    'inc/softwarewhitelist.class.php',
    'inc/softwareblacklist.class.php',
    'inc/softwareinventory.class.php'
];

foreach ($php_files as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        if (strpos($content, '<?php') === 0) {
            echo "✓ $file has correct PHP opening tag<br>";
        } else {
            echo "✗ $file missing or incorrect PHP opening tag<br>";
        }
        
        // Check for syntax errors by trying to parse
        $tokens = @token_get_all($content);
        if ($tokens !== false) {
            echo "✓ $file syntax appears valid<br>";
        } else {
            echo "✗ $file has syntax errors<br>";
        }
    } else {
        echo "✗ $file not found<br>";
    }
}

echo "<h2>5. GLPI Integration Check</h2>";
if (defined('GLPI_ROOT')) {
    echo "✓ GLPI environment detected<br>";
    echo "GLPI Root: " . GLPI_ROOT . "<br>";
    
    if (defined('GLPI_VERSION')) {
        echo "GLPI Version: " . GLPI_VERSION . "<br>";
    }
    
    if (class_exists('Plugin')) {
        echo "✓ Plugin class available<br>";
    } else {
        echo "✗ Plugin class not available<br>";
    }
    
    if (class_exists('Session')) {
        echo "✓ Session class available<br>";
    } else {
        echo "✗ Session class not available<br>";
    }
    
} else {
    echo "✗ GLPI environment not detected<br>";
    echo "This script should be run from within GLPI context<br>";
}

echo "<h2>Summary</h2>";
echo "If all checks show ✓, the plugin should be installable in GLPI.<br>";
echo "If any checks show ✗, those issues need to be resolved first.<br>";

?>
