<?php
/**
 * Software Manager Plugin for GLPI
 * Blacklist Management Page
 *
 * @author  Abner Liu
 * @license GPL-2.0+
 */

include('../../../inc/includes.php'); // 确保在最开始加载核心环境

// 检查用户权限
Session::checkRight("config", UPDATE);

// ----------------- POST 请求处理逻辑 -----------------
// 必须在页面渲染之前处理POST请求

// -- 处理添加请求 --
if (isset($_POST["add_item"])) {
    // 从 POST 数据中创建新的黑名单对象
    $software_name = Html::cleanInputText($_POST['software_name']);
    $comment = isset($_POST['comment']) ? Html::cleanInputText($_POST['comment']) : '';

    if (!empty($software_name)) {
        try {
            if (PluginSoftwaremanagerSoftwareBlacklist::addToList($software_name, $comment)) {
                Session::addMessageAfterRedirect("软件 '$software_name' 已成功添加到黑名单", false, INFO);
            } else {
                Session::addMessageAfterRedirect("无法添加软件到黑名单，可能已存在", false, WARNING);
            }
        } catch (Exception $e) {
            Session::addMessageAfterRedirect("添加失败: " . $e->getMessage(), false, ERROR);
        }
    } else {
        Session::addMessageAfterRedirect("软件名称不能为空", false, ERROR);
    }
    // 重定向以防止重复提交
    Html::redirect($CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/blacklist.php");
}

// -- 处理批量删除请求 --
if (isset($_POST["massive_action"])) {
    // 确认 'delete' 按钮被点击，并且有项目被选中
    if (isset($_POST['massive_action']['delete']) && isset($_POST['mass_action'])) {
        $blacklist_obj = new PluginSoftwaremanagerSoftwareBlacklist();
        $blacklist_obj->delete(array_keys($_POST['mass_action']));
        Session::addMessageAfterRedirect(__('Selected items have been deleted'), true, INFO);
    }
    Html::redirect($CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/blacklist.php");
}

// ----------------- 页面显示和表单处理 -----------------

// 显示页面标题和导航
Html::header(
    __('Software Manager', 'softwaremanager'), // 插件名称
    $_SERVER['PHP_SELF'],
    'config',
    'plugins',
    'softwaremanager'
);

// 显示导航菜单
PluginSoftwaremanagerMenu::displayNavigationHeader('blacklist');

// ----------------- 添加新项目的表单 (放在列表上方) -----------------
echo "<div class='center' style='margin-bottom: 30px;'>";
echo "<h3>" . __('Quick Add to Blacklist', 'softwaremanager') . "</h3>";

echo "<form name='form_add' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<table class='tab_cadre_fixe' style='width: 600px;'>";
echo "<tr class='tab_bg_1'><th colspan='2'>".__('Add a new item to the blacklist', 'softwaremanager')."</th></tr>";

echo "<tr class='tab_bg_1'><td style='width: 150px;'>".__('Software Name', 'softwaremanager')."</td>";
echo "<td><input type='text' name='software_name' class='form-control' style='width: 300px;' required placeholder='" . __('Enter software name', 'softwaremanager') . "'></td></tr>";

echo "<tr class='tab_bg_1'><td>".__('Comment', 'softwaremanager')."</td>";
echo "<td><input type='text' name='comment' class='form-control' style='width: 300px;' placeholder='" . __('Optional comment', 'softwaremanager') . "'></td></tr>";

echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
echo "<input type='submit' name='add_item' value='".__s('Add to Blacklist', 'softwaremanager')."' class='submit'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();
echo "</div>";

// 获取所有黑名单项目用于显示
$blacklist = new PluginSoftwaremanagerSoftwareBlacklist();

// 处理搜索过滤
$search = isset($_GET['search']) ? Html::cleanInputText($_GET['search']) : '';
$criteria = [];
if (!empty($search)) {
    $criteria = [
        'OR' => [
            'name' => ['LIKE', '%' . $search . '%'],
            'comment' => ['LIKE', '%' . $search . '%']
        ]
    ];
}

$all_blacklists = $blacklist->find($criteria, ['ORDER' => 'date_creation DESC']);

// 搜索表单
echo "<div class='center' style='margin-bottom: 20px;'>";
echo "<form method='get' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<table class='tab_cadre_fixe' style='width: 400px;'>";
echo "<tr class='tab_bg_1'>";
echo "<td style='width: 100px;'>" . __('Search') . ":</td>";
echo "<td><input type='text' name='search' value='" . htmlspecialchars($search) . "' placeholder='" . __('Search in name or comment', 'softwaremanager') . "' style='width: 200px;'></td>";
echo "<td><input type='submit' value='" . __('Search') . "' class='submit'></td>";
if (!empty($search)) {
    echo "<td><a href='" . $_SERVER['PHP_SELF'] . "' class='vsubmit'>" . __('Reset') . "</a></td>";
}
echo "</tr>";
echo "</table>";
echo "</form>";
echo "</div>";

// 使用标准表单创建方式，这会自动处理 CSRF 令牌！
// 这是一个包裹了整个列表的表单，用于处理批量删除
echo "<form name='form_blacklist' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";

echo "<table class='tab_cadre_fixehov'>";
$header = "<tr class='tab_bg_1'>";
$header .= "<th width='10'><input type='checkbox' name='checkall' title=\"".__s('Check all')."\" onclick=\"checkAll(this.form, this.checked, 'mass_action');\"></th>";
$header .= "<th>".__('Software Name', 'softwaremanager')."</th>";
$header .= "<th>".__('Comment', 'softwaremanager')."</th>";
$header .= "<th>".__('Date Added', 'softwaremanager')."</th>";
$header .= "<th>".__('Actions', 'softwaremanager')."</th>";
$header .= "</tr>";
echo $header;

if (count($all_blacklists) > 0) {
    foreach ($all_blacklists as $id => $item) {
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        // 注意：这里的 checkbox name 需要和下面的批量操作按钮对应
        Html::showMassiveActionCheckBox('PluginSoftwaremanagerSoftwareBlacklist', $id, ['name' => 'mass_action']);
        echo "</td>";
        echo "<td>".$item['name']."</td>";
        echo "<td>".($item['comment'] ?: '-')."</td>";
        echo "<td>".Html::convDateTime($item['date_creation'])."</td>";
        echo "<td>";
        // 单个删除按钮
        echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "' style='display: inline;'>";
        echo "<input type='hidden' name='mass_action[" . $id . "]' value='1'>";
        echo "<input type='hidden' name='massive_action[delete]' value='1'>";
        echo "<input type='submit' value='" . __('Delete') . "' class='submit' onclick='return confirm(\"" . __('Confirm the final deletion?') . "\");'>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr class='tab_bg_1'><td colspan='5' class='center'>".__('No item found')."</td></tr>";
}

echo "</table>";

// 批量操作按钮栏
if (count($all_blacklists) > 0) {
    $massive_actions = [
        // 关键：这里的 key 'delete' 必须和下面的 POST 处理逻辑对应
        'delete' => __('Delete')
    ];
    Html::showMassiveActions($massive_actions);
}

// **重要**：Html::closeForm() 会自动关闭表单标签
Html::closeForm();




// 显示页面底部
Html::footer();
?>
