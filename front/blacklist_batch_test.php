<?php
/**
 * Test page for batch delete functionality
 */

include('../../../inc/includes.php');

// Check user permissions
Session::checkRight("config", UPDATE);

// Include plugin classes
include_once(__DIR__ . '/../inc/menu.class.php');
include_once(__DIR__ . '/../inc/softwareblacklist.class.php');

// Start HTML output
Html::header(
    'Blacklist Batch Delete Test',
    $_SERVER['PHP_SELF'],
    'plugins',
    'softwaremanager'
);

echo "<h1>Blacklist Batch Delete Test</h1>";

// Add some test data first
if (isset($_POST['add_test_data'])) {
    $test_items = [
        'Test Software 1',
        'Test Software 2', 
        'Test Software 3'
    ];
    
    foreach ($test_items as $software) {
        PluginSoftwaremanagerSoftwareBlacklist::addToList($software, 'Test data for batch delete');
    }
    echo "<p>✅ Test data added</p>";
}

// Get blacklist items
$blacklist = new PluginSoftwaremanagerSoftwareBlacklist();
$criteria = ['is_deleted' => 0];
$all_blacklists = $blacklist->find($criteria, ['software_name ASC'], 50);

echo "<h2>Current Blacklist Items</h2>";

if (empty($all_blacklists)) {
    echo "<p>No items found. Add test data first.</p>";
    echo "<form method='post'>";
    echo "<input type='submit' name='add_test_data' value='Add Test Data' class='submit'>";
    echo "</form>";
} else {
    // Create form with CSRF token
    echo "<form name='form_blacklist' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
    
    echo "<table class='tab_cadre_fixehov'>";
    echo "<tr class='tab_bg_1'>";
    echo "<th><input type='checkbox' onclick='checkAll(this.form, this.checked, \"mass_action\");'></th>";
    echo "<th>Software Name</th>";
    echo "<th>Comment</th>";
    echo "<th>Date Added</th>";
    echo "</tr>";
    
    foreach ($all_blacklists as $id => $item) {
        echo "<tr class='tab_bg_2'>";
        echo "<td><input type='checkbox' name='mass_action[" . $id . "]' value='1'></td>";
        echo "<td>" . Html::entities_deep($item['software_name']) . "</td>";
        echo "<td>" . Html::entities_deep($item['comment']) . "</td>";
        echo "<td>" . Html::convDateTime($item['date_creation']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div class='center' style='margin-top: 10px;'>";
    echo "<button type='button' id='batch-delete-btn' class='submit' onclick='testBatchDelete();'>Test Batch Delete</button>";
    echo "</div>";
    
    Html::closeForm();
}

echo "<p><a href='blacklist.php'>Go to Main Blacklist Page</a></p>";

?>
<script type="text/javascript">
function checkAll(form, checked, fieldname) {
    var checkboxes = form.querySelectorAll('input[name^="' + fieldname + '"]');
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].type === 'checkbox') {
            checkboxes[i].checked = checked;
        }
    }
}

function getSelectedItems(formName) {
    var items = [];
    var form = document.forms[formName];
    
    if (!form) {
        console.log('Form not found:', formName);
        return items;
    }
    
    var elements = form.elements;
    for (var i = 0; i < elements.length; i++) {
        var element = elements[i];
        if (element.type === 'checkbox' && 
            element.name.indexOf('mass_action[') === 0 && 
            element.checked) {
            
            var matches = element.name.match(/mass_action\[(\d+)\]/);
            if (matches && matches[1]) {
                items.push(parseInt(matches[1]));
                console.log('Found selected item:', matches[1]);
            }
        }
    }
    
    console.log('Selected items:', items);
    return items;
}

function testBatchDelete() {
    console.log('testBatchDelete called');
    
    var items = getSelectedItems('form_blacklist');
    
    if (!items || items.length === 0) {
        alert('请选择要删除的项目');
        return;
    }
    
    if (!confirm('确认删除选中的 ' + items.length + ' 个项目吗？')) {
        return;
    }
    
    // Get CSRF token
    var csrfToken = document.querySelector('input[name="_glpi_csrf_token"]');
    if (!csrfToken) {
        alert('CSRF token not found');
        return;
    }
    
    console.log('CSRF token found:', csrfToken.value);
    
    // Prepare form data
    var formData = new FormData();
    formData.append('action', 'batch_delete');
    formData.append('type', 'blacklist');
    formData.append('items', JSON.stringify(items));
    formData.append('_glpi_csrf_token', csrfToken.value);
    
    console.log('Sending request to batch_delete.php');
    
    fetch('../ajax/batch_delete.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            var data = JSON.parse(text);
            console.log('Parsed data:', data);
            
            if (data && data.success) {
                alert('删除完成！成功删除 ' + data.deleted_count + ' 个项目' +
                      (data.failed_count > 0 ? '，失败 ' + data.failed_count + ' 个' : ''));
                window.location.reload();
            } else {
                alert('删除失败：' + (data ? data.error : '未知错误'));
            }
        } catch (e) {
            console.log('JSON parse error:', e);
            alert('响应解析错误：' + text);
        }
    })
    .catch(error => {
        console.log('Fetch error:', error);
        alert('请求失败：' + error.message);
    });
}
</script>
<?php
Html::footer();
?>
