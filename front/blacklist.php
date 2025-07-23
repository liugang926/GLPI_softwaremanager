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

// -- 处理单个删除请求 --
if (isset($_POST["delete_single"]) && isset($_POST["item_id"])) {
    $item_id = intval($_POST["item_id"]);
    $blacklist_obj = new PluginSoftwaremanagerSoftwareBlacklist();

    // 使用正确的GLPI delete方法调用格式
    if ($blacklist_obj->delete(['id' => $item_id], true)) {
        Session::addMessageAfterRedirect(__('Item has been deleted'), false, INFO);
    } else {
        Session::addMessageAfterRedirect(__('Failed to delete item'), false, ERROR);
    }
    Html::redirect($CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/blacklist.php");
}

// 批量删除现在通过AJAX处理，不需要POST处理逻辑

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
$criteria = [
    'is_deleted' => 0  // 只显示未删除的项目
];

if (!empty($search)) {
    $criteria['OR'] = [
        'name' => ['LIKE', '%' . $search . '%'],
        'comment' => ['LIKE', '%' . $search . '%']
    ];
}

$all_blacklists = $blacklist->find($criteria, ['ORDER' => 'date_creation DESC']);

// GLPI标准筛选组件
echo "<div class='center' style='margin-bottom: 20px;'>";
echo "<form method='get' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_1'>";
echo "<th colspan='4'>" . __('Search options') . "</th>";
echo "</tr>";
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Search') . ":</td>";
echo "<td><input type='text' name='search' value='" . htmlspecialchars($search) . "' placeholder='" . __('Search in name or comment', 'softwaremanager') . "' size='30'></td>";
echo "<td><input type='submit' value='" . __('Search') . "' class='submit'></td>";
if (!empty($search)) {
    echo "<td><a href='" . $_SERVER['PHP_SELF'] . "' class='vsubmit'>" . __('Reset') . "</a></td>";
} else {
    echo "<td></td>";
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
        // 使用简单的HTML checkbox，确保name格式正确
        echo "<input type='checkbox' name='mass_action[" . $id . "]' value='1'>";
        echo "</td>";
        echo "<td>".$item['name']."</td>";
        echo "<td>".($item['comment'] ?: '-')."</td>";
        echo "<td>".Html::convDateTime($item['date_creation'])."</td>";
        echo "<td>";
        // 单个删除按钮
        echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "' style='display: inline;'>";
        echo "<input type='hidden' name='item_id' value='" . $id . "'>";
        echo "<input type='hidden' name='delete_single' value='1'>";
        echo "<input type='submit' value='" . __('Delete') . "' class='submit' onclick='return confirm(\"" . __('Confirm the final deletion?') . "\");'>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr class='tab_bg_1'><td colspan='5' class='center'>".__('No item found')."</td></tr>";
}

echo "</table>";

// AJAX批量操作按钮
if (count($all_blacklists) > 0) {
    echo "<div class='center' style='margin-top: 10px;'>";
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr class='tab_bg_1'>";
    echo "<td class='center'>";
    echo "<button type='button' id='batch-delete-btn' class='submit' onclick='batchDeleteBlacklist();'>" . __('Delete Selected Items') . "</button>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "</div>";
}

// **重要**：Html::closeForm() 会自动关闭表单标签
Html::closeForm();

// 添加JavaScript函数支持全选功能和AJAX批量删除
echo "<script type='text/javascript'>
function checkAll(form, checked, fieldname) {
    var checkboxes = form.querySelectorAll('input[name^=\"' + fieldname + '\"]');
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].type === 'checkbox') {
            checkboxes[i].checked = checked;
        }
    }
}

// 获取选中的项目ID
function getSelectedItems(formName) {
    var items = [];
    var form = document.forms[formName];

    if (!form) {
        return items;
    }

    var elements = form.elements;
    for (var i = 0; i < elements.length; i++) {
        var element = elements[i];
        if (element.type === 'checkbox' &&
            element.name.indexOf('mass_action[') === 0 &&
            element.checked) {

            // Extract ID from name like 'mass_action[123]'
            var matches = element.name.match(/mass_action\[(\d+)\]/);
            if (matches && matches[1]) {
                items.push(parseInt(matches[1]));
            }
        }
    }

    return items;
}

// 简单的进度提示
function showProgress() {
    // 禁用删除按钮，显示加载状态
    var btn = document.getElementById('batch-delete-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '正在删除...';
    }
}

function hideProgress() {
    // 恢复删除按钮
    var btn = document.getElementById('batch-delete-btn');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = 'Delete Selected Items';
    }
}

// 批量删除黑名单项目
function batchDeleteBlacklist() {
    var items = getSelectedItems('form_blacklist');

    if (!items || items.length === 0) {
        alert('请选择要删除的项目');
        return;
    }

    if (!confirm('确认删除选中的 ' + items.length + ' 个项目吗？此操作不可撤销！')) {
        return;
    }

    // 显示进度
    showProgress();

    // 使用原生fetch API替代jQuery.ajax
    fetch('../ajax/batch_delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=batch_delete&type=blacklist&items=' + encodeURIComponent(JSON.stringify(items))
    })
    .then(response => response.json())
    .then(data => {
        hideProgress();
        if (data && data.success) {
            alert('删除完成！成功删除 ' + data.deleted_count + ' 个项目' +
                  (data.failed_count > 0 ? '，失败 ' + data.failed_count + ' 个' : ''));
            window.location.reload();
        } else {
            alert('删除失败：' + (data ? data.error : '未知错误'));
        }
    })
    .catch(error => {
        hideProgress();
        console.log('Fetch Error:', error);
        alert('删除失败：' + error.message);
    });
}
</script>";

// 显示页面底部
Html::footer();
?>
