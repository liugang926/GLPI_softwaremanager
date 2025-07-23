<?php
/**
 * Software Manager Plugin for GLPI
 * Whitelist Management Page
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
    // 从 POST 数据中创建新的白名单对象
    $software_name = Html::cleanInputText($_POST['software_name']);
    $comment = isset($_POST['comment']) ? Html::cleanInputText($_POST['comment']) : '';

    if (!empty($software_name)) {
        try {
            if (PluginSoftwaremanagerSoftwareWhitelist::addToList($software_name, $comment)) {
                Session::addMessageAfterRedirect("软件 '$software_name' 已成功添加到白名单", false, INFO);
            } else {
                Session::addMessageAfterRedirect("无法添加软件到白名单，可能已存在", false, WARNING);
            }
        } catch (Exception $e) {
            Session::addMessageAfterRedirect("添加失败: " . $e->getMessage(), false, ERROR);
        }
    } else {
        Session::addMessageAfterRedirect("软件名称不能为空", false, ERROR);
    }
    // 重定向以防止重复提交
    Html::redirect($CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/whitelist.php");
}

// -- 处理单个删除请求 --
if (isset($_POST["delete_single"]) && isset($_POST["item_id"])) {
    $item_id = intval($_POST["item_id"]);
    $whitelist_obj = new PluginSoftwaremanagerSoftwareWhitelist();

    // 使用正确的GLPI delete方法调用格式
    if ($whitelist_obj->delete(['id' => $item_id], true)) {
        Session::addMessageAfterRedirect(__('Item has been deleted'), false, INFO);
    } else {
        Session::addMessageAfterRedirect(__('Failed to delete item'), false, ERROR);
    }
    Html::redirect($CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/whitelist.php");
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
PluginSoftwaremanagerMenu::displayNavigationHeader('whitelist');

// ----------------- 添加新项目的表单 (放在列表上方) -----------------
echo "<div class='center' style='margin-bottom: 30px;'>";
echo "<h3>" . __('Quick Add to Whitelist', 'softwaremanager') . "</h3>";

echo "<form name='form_add' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<table class='tab_cadre_fixe' style='width: 600px;'>";
echo "<tr class='tab_bg_1'><th colspan='2'>".__('Add a new item to the whitelist', 'softwaremanager')."</th></tr>";

echo "<tr class='tab_bg_1'><td style='width: 150px;'>".__('Software Name', 'softwaremanager')."</td>";
echo "<td><input type='text' name='software_name' class='form-control' style='width: 300px;' required placeholder='" . __('Enter software name', 'softwaremanager') . "'></td></tr>";

echo "<tr class='tab_bg_1'><td>".__('Comment', 'softwaremanager')."</td>";
echo "<td><input type='text' name='comment' class='form-control' style='width: 300px;' placeholder='" . __('Optional comment', 'softwaremanager') . "'></td></tr>";

echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
echo "<input type='submit' name='add_item' value='".__s('Add to Whitelist', 'softwaremanager')."' class='submit'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();
echo "</div>";

// 获取所有白名单项目用于显示
$whitelist = new PluginSoftwaremanagerSoftwareWhitelist();

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

$all_whitelists = $whitelist->find($criteria, ['ORDER' => 'date_creation DESC']);

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
echo "<form name='form_whitelist' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";

echo "<table class='tab_cadre_fixehov'>";
$header = "<tr class='tab_bg_1'>";
$header .= "<th width='10'><input type='checkbox' name='checkall' title=\"".__s('Check all')."\" onclick=\"checkAll(this.form, this.checked, 'mass_action');\"></th>";
$header .= "<th>".__('Software Name', 'softwaremanager')."</th>";
$header .= "<th>".__('Comment', 'softwaremanager')."</th>";
$header .= "<th>".__('Date Added', 'softwaremanager')."</th>";
$header .= "<th>".__('Actions', 'softwaremanager')."</th>";
$header .= "</tr>";
echo $header;

if (count($all_whitelists) > 0) {
    foreach ($all_whitelists as $id => $item) {
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

// AJAX批量操作按钮 - 必须在表单内部，在closeForm()之前
if (count($all_whitelists) > 0) {
    echo "<div class='center' style='margin-top: 10px;'>";
    echo "<button type='button' id='batch-delete-btn' class='submit' onclick='batchDeleteWhitelist(); return false;'>" . __('Delete Selected Items') . "</button>";
    echo "</div>";
}

// **重要**：Html::closeForm() 会自动关闭表单标签
Html::closeForm();

// 添加JavaScript函数支持全选功能和AJAX批量删除
?>
<script type="text/javascript">
function batchDeleteWhitelist() {
    console.log('batchDeleteWhitelist called');

    // 阻止表单默认提交
    event.preventDefault();

    // 获取选中的项目
    var selectedItems = getSelectedItems();
    console.log('Selected items:', selectedItems);

    if (selectedItems.length === 0) {
        alert('请选择要删除的项目');
        return false;
    }

    if (!confirm('确认要删除选中的 ' + selectedItems.length + ' 个项目吗？')) {
        return false;
    }

    // 显示进度
    showProgress();

    // 开始批量删除
    batchDeleteItems(selectedItems, 0);

    return false;
}

function checkAll(form, checked, fieldname) {
    var checkboxes = form.querySelectorAll('input[name^="' + fieldname + '"]');
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].type === 'checkbox') {
            checkboxes[i].checked = checked;
        }
    }
}

function getSelectedItems() {
    var items = [];

    // 直接查找所有选中的mass_action checkbox
    var checkboxes = document.querySelectorAll('input[name^="mass_action["]:checked');
    console.log('Found checkboxes:', checkboxes.length);

    for (var i = 0; i < checkboxes.length; i++) {
        var checkbox = checkboxes[i];
        var nameMatch = checkbox.name.match(/mass_action\[(\d+)\]/);
        if (nameMatch && nameMatch[1]) {
            items.push(parseInt(nameMatch[1]));
            console.log('Added item:', nameMatch[1]);
        }
    }

    return items;
}

function showProgress() {
    var btn = document.getElementById('batch-delete-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '正在处理批量删除...';
    }
}

function hideProgress() {
    var btn = document.getElementById('batch-delete-btn');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = 'Delete Selected Items';
    }
}

function batchDeleteItems(items, currentIndex) {
    // 使用AJAX批量删除 - 一次性发送所有数据到后端处理
    console.log('Starting batch delete for items:', items);

    // 发送批量删除请求到AJAX处理器
    fetch('../ajax/batch_delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=batch_delete&type=whitelist&items=' + encodeURIComponent(JSON.stringify(items))
    })
    .then(response => {
        console.log('AJAX response status:', response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('AJAX response data:', data);
        hideProgress();

        if (data.success) {
            var message = '✅ 批量删除操作完成！\n\n';
            message += '📊 处理结果：\n';
            message += '• 总计项目: ' + (data.total_count || 0) + ' 个\n';
            message += '• 成功删除: ' + (data.deleted_count || 0) + ' 个\n';
            message += '• 删除失败: ' + (data.failed_count || 0) + ' 个\n';

            if (data.message) {
                message += '\n📝 详细信息: ' + data.message;
            }

            alert(message);

            // 刷新页面显示最新数据
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('❌ 批量删除失败！\n\n错误信息: ' + (data.error || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Batch delete error:', error);
        hideProgress();
        alert('❌ 网络请求失败！\n\n错误详情: ' + error.message + '\n\n请检查网络连接或联系管理员。');
    });
}

function updateProgress(current, total, percentage) {
    var btn = document.getElementById('batch-delete-btn');
    if (btn) {
        btn.innerHTML = '删除中... (' + current + '/' + total + ') ' + percentage + '%';
    }
}


</script>
<?php
// 显示页面底部
Html::footer();
?>
