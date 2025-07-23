<?php
/**
 * 批量删除功能测试
 */

// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置响应头
header('Content-Type: application/json');

try {
    // GLPI includes - dynamic path detection
    $glpi_root = dirname(dirname(dirname(__DIR__)));
    if (!file_exists($glpi_root . "/inc/includes.php")) {
        $glpi_root = $_SERVER['DOCUMENT_ROOT'];
        if (!file_exists($glpi_root . "/inc/includes.php")) {
            $glpi_root = dirname($_SERVER['DOCUMENT_ROOT']);
        }
    }
    include ($glpi_root . "/inc/includes.php");

    // 检查认证
    if (!Session::getLoginUserID()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    // 检查权限
    if (!Session::haveRight("config", UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'No permission']);
        exit;
    }

    // 检查数据库
    global $DB;
    if (!isset($DB) || !$DB || !$DB->connected) {
        echo json_encode(['success' => false, 'error' => 'Database not connected']);
        exit;
    }

    // 检查插件表是否存在
    $whitelist_table = 'glpi_plugin_softwaremanager_whitelists';
    $blacklist_table = 'glpi_plugin_softwaremanager_blacklists';
    
    $tables_status = [
        'whitelist' => $DB->tableExists($whitelist_table),
        'blacklist' => $DB->tableExists($blacklist_table)
    ];

    // 如果是POST请求，尝试处理数据
    $action_result = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        if (isset($input['action']) && $input['action'] === 'batch_delete') {
            // 模拟批量删除测试
            $type = $input['type'] ?? 'whitelist';
            $items = $input['items'] ?? [];
            
            $action_result = [
                'action' => 'batch_delete',
                'type' => $type,
                'items_count' => count($items),
                'table_exists' => $tables_status[$type] ?? false,
                'items_received' => $items
            ];
            
            // 如果表存在，尝试查询一些数据
            if ($action_result['table_exists']) {
                $table = ($type === 'blacklist') ? $blacklist_table : $whitelist_table;
                
                // 获取总记录数
                $count_query = $DB->request([
                    'COUNT' => 'id',
                    'FROM' => $table
                ]);
                $count_result = $count_query->current();
                $action_result['total_records'] = $count_result['COUNT'] ?? 0;
                
                // 检查要删除的项目是否存在
                $existing_items = [];
                foreach ($items as $item_id) {
                    $check_query = $DB->request([
                        'SELECT' => ['id', 'software_name'],
                        'FROM' => $table,
                        'WHERE' => ['id' => intval($item_id)]
                    ]);
                    
                    if (count($check_query) > 0) {
                        $item = $check_query->current();
                        $existing_items[] = [
                            'id' => $item['id'],
                            'name' => $item['software_name']
                        ];
                    }
                }
                
                $action_result['existing_items'] = $existing_items;
                $action_result['existing_count'] = count($existing_items);
            }
        }
    }

    // 返回成功响应
    echo json_encode([
        'success' => true,
        'message' => 'Batch test completed',
        'user_id' => Session::getLoginUserID(),
        'database_connected' => true,
        'tables_exist' => $tables_status,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'action_result' => $action_result,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
