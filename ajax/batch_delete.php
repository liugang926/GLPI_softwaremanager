<?php
/**
 * AJAX Batch Delete Handler for Software Manager Plugin
 *
 * 这个文件处理批量删除请求，逐条调用GLPI的删除方法
 * 因为GLPI本身不支持批量操作，所以我们需要逐条处理
 *
 * @author  Abner Liu
 * @license GPL-2.0+
 */

// 设置JSON响应头
header('Content-Type: application/json');

// 包含GLPI核心文件
$plugin_root = dirname(__DIR__);
$glpi_root = dirname(dirname($plugin_root));
include_once($glpi_root . "/inc/includes.php");

try {
    // 检查用户认证
    if (!Session::getLoginUserID()) {
        throw new Exception('用户未认证');
    }

    // 检查权限
    if (!Session::haveRight("config", UPDATE)) {
        throw new Exception('权限不足');
    }

    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只允许POST请求');
    }

    // 获取请求参数
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    $items_json = $_POST['items'] ?? '';

    // 验证参数
    if ($action !== 'batch_delete') {
        throw new Exception('无效的操作');
    }

    if (!in_array($type, ['whitelist', 'blacklist'])) {
        throw new Exception('无效的类型');
    }

    // 解析要删除的项目ID列表
    $items = json_decode($items_json, true);
    if (!is_array($items) || empty($items)) {
        throw new Exception('没有选择要删除的项目');
    }

    // 根据类型获取相应的GLPI对象
    if ($type === 'whitelist') {
        $obj = new PluginSoftwaremanagerSoftwareWhitelist();
    } else {
        $obj = new PluginSoftwaremanagerSoftwareBlacklist();
    }

    // 初始化计数器和结果数组
    $deleted_count = 0;
    $failed_count = 0;
    $results = [];

    // 逐条处理删除操作
    foreach ($items as $item_id) {
        $item_id = intval($item_id);

        // 验证ID有效性
        if ($item_id <= 0) {
            $failed_count++;
            $results[] = [
                'id' => $item_id,
                'status' => 'error',
                'message' => '无效的项目ID'
            ];
            continue;
        }

        // 使用GLPI标准删除方法
        // 这与单个删除使用相同的方法：$obj->delete(['id' => $item_id], true)
        try {
            if ($obj->delete(['id' => $item_id], true)) {
                $deleted_count++;
                $results[] = [
                    'id' => $item_id,
                    'status' => 'success',
                    'message' => '删除成功'
                ];
            } else {
                $failed_count++;
                $results[] = [
                    'id' => $item_id,
                    'status' => 'error',
                    'message' => '删除失败'
                ];
            }
        } catch (Exception $e) {
            $failed_count++;
            $results[] = [
                'id' => $item_id,
                'status' => 'error',
                'message' => '删除异常: ' . $e->getMessage()
            ];
        }
    }

    // 返回成功响应
    echo json_encode([
        'success' => true,
        'message' => "批量删除完成：成功 {$deleted_count} 项，失败 {$failed_count} 项",
        'deleted_count' => $deleted_count,
        'failed_count' => $failed_count,
        'total_count' => count($items),
        'results' => $results
    ]);

} catch (Exception $e) {
    // 返回错误响应
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'deleted_count' => 0,
        'failed_count' => 0,
        'total_count' => 0
    ]);
}
?>
