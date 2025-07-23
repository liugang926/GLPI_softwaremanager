<?php
/**
 * Software Manager Plugin for GLPI
 * Blacklist Management Page
 *
 * @author  Abner Liu
 * @license GPL-2.0+
 */

include('../../../inc/includes.php'); // 确保在最开始加载核心环境

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

// 获取所有黑名单项目用于显示
$blacklist = new PluginSoftwaremanagerSoftwareBlacklist();
$all_blacklists = $blacklist->find();

// OCS 模式: 使用 Html::form() 来创建表单。这会自动处理 CSRF 令牌！
// 这是一个包裹了整个列表的表单，用于处理批量删除
echo "<form name='form_blacklist' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";

echo "<table class='tab_cadre_fixehov'>";
$header = "<tr class='tab_bg_1'>";
$header .= "<th width='10'><input type='checkbox' name='checkall' title=\"".__s('Check all')."\" onclick=\"checkAll(this.form, this.checked, 'mass_action');\"></th>";
$header .= "<th>".__('Software Name', 'softwaremanager')."</th>";
$header .= "<th>".__('Comment', 'softwaremanager')."</th>";
$header .= "<th>".__('Date Added', 'softwaremanager')."</th>";
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
        echo "</tr>";
    }
} else {
    echo "<tr class='tab_bg_1'><td colspan='4' class='center'>".__('No item found')."</td></tr>";
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


// ----------------- 添加新项目的独立表单 -----------------

echo "<div class='center' style='margin-top: 30px;'>";
echo "<h3>" . __('Quick Add to Blacklist', 'softwaremanager') . "</h3>";

// 使用第二个 Html::form() 创建添加表单，它也会自动获得自己独立的、有效的 CSRF 令牌
echo "<form name='form_add' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<table class='tab_cadre_fixe' style='width: 600px;'>";
echo "<tr class='tab_bg_1'><th colspan='2'>".__('Add a new item to the blacklist', 'softwaremanager')."</th></tr>";

echo "<tr class='tab_bg_1'><td style='width: 150px;'>".__('Software Name', 'softwaremanager')."</td>";
echo "<td><input type='text' name='software_name' class='form-control' style='width: 300px;' required placeholder='" . __('Enter software name', 'softwaremanager') . "'></td></tr>"; 

echo "<tr class='tab_bg_1'><td>".__('Comment', 'softwaremanager')."</td>";
echo "<td><input type='text' name='comment' class='form-control' style='width: 300px;' placeholder='" . __('Optional comment', 'softwaremanager') . "'></td></tr>"; 

echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
// 关键：提交按钮的 name='add' 必须和下面的 POST 处理逻辑对应
echo "<input type='submit' name='add_blacklist_item' value='".__s('Add to Blacklist', 'softwaremanager')."' class='submit'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();

echo "</div>";


// ----------------- POST 请求处理逻辑 -----------------
// (这部分代码放在页面渲染之后是 GLPI 的一个常见模式)

// -- 处理添加请求 --
if (isset($_POST["add_blacklist_item"])) {
    // 检查权限
    Session::checkRight("config", UPDATE);

    // 从 POST 数据中创建新的黑名单对象
    // Html::form() 已经保证了 CSRF 安全，所以我们不需要手动检查
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
    // 检查权限
    Session::checkRight("config", UPDATE);

    // 确认 'delete' 按钮被点击，并且有项目被选中
    if (isset($_POST['massive_action']['delete']) && isset($_POST['mass_action'])) {
        $blacklist_obj = new PluginSoftwaremanagerSoftwareBlacklist();

        // 直接调用从 CommonDBTM 继承来的 delete 方法。
        // 它非常智能，可以接受一个包含所有待删除项目ID的数组。
        // array_keys($_POST['mass_action']) 正好就是这个ID数组。
        $blacklist_obj->delete(array_keys($_POST['mass_action']));
        
        Session::addMessageAfterRedirect(__('Selected items have been deleted'), true, INFO);
    }
    Html::redirect($CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/blacklist.php");
}

// 显示页面底部
Html::footer();
?>
