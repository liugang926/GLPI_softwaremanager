<?php
/**
 * 路径测试
 */

header('Content-Type: application/json');

$paths_to_test = [
    // 当前目录信息
    'current_dir' => __DIR__,
    'current_file' => __FILE__,
    
    // 尝试不同的GLPI路径
    'path1' => dirname(dirname(dirname(__DIR__))),  // ../../../
    'path2' => dirname(dirname(dirname(dirname(__DIR__)))), // ../../../../
    'path3' => '/var/www/html',  // 常见的Linux路径
    'path4' => 'C:/xampp/htdocs',  // Windows XAMPP路径
    'path5' => 'C:/wamp/www',  // Windows WAMP路径
    'path6' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',  // 文档根目录
];

$results = [];

foreach ($paths_to_test as $name => $path) {
    $results[$name] = [
        'path' => $path,
        'exists' => is_dir($path),
        'glpi_inc_exists' => file_exists($path . '/inc/includes.php'),
        'contents' => []
    ];
    
    if (is_dir($path)) {
        $files = scandir($path);
        $results[$name]['contents'] = array_slice($files, 0, 10); // 只显示前10个文件
    }
}

// 特别检查一些可能的GLPI路径
$possible_glpi_paths = [
    $_SERVER['DOCUMENT_ROOT'],
    $_SERVER['DOCUMENT_ROOT'] . '/glpi',
    dirname($_SERVER['DOCUMENT_ROOT']) . '/glpi',
    '/var/www/html/glpi',
    'C:/xampp/htdocs/glpi',
    'C:/wamp/www/glpi'
];

$glpi_found = [];
foreach ($possible_glpi_paths as $path) {
    if ($path && file_exists($path . '/inc/includes.php')) {
        $glpi_found[] = $path;
    }
}

echo json_encode([
    'success' => true,
    'server_info' => [
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown'
    ],
    'path_tests' => $results,
    'glpi_found_at' => $glpi_found,
    'recommended_path' => count($glpi_found) > 0 ? $glpi_found[0] : 'not_found'
]);
?>
