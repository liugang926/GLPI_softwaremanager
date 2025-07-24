/**
 * JavaScript functions for Software Manager Blacklist page
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

/**
 * Check/uncheck all checkboxes in a form
 * @param {HTMLFormElement} form - The form element
 * @param {boolean} checked - Whether to check or uncheck
 * @param {string} fieldname - The field name prefix to match
 */
function checkAll(form, checked, fieldname) {
    var checkboxes = form.querySelectorAll('input[name^="' + fieldname + '"]');
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].type === 'checkbox') {
            checkboxes[i].checked = checked;
        }
    }
}

/**
 * Get selected item IDs from checkboxes
 * @param {string} formName - The name of the form
 * @returns {Array} Array of selected item IDs
 */
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

            // Extract ID from name like 'mass_action[123]'
            var matches = element.name.match(/mass_action\[(\d+)\]/);
            if (matches && matches[1]) {
                items.push(parseInt(matches[1]));
            }
        }
    }

    return items;
}

/**
 * Show progress indicator
 */
function showProgress() {
    // Disable delete button and show loading state
    var btn = document.getElementById('batch-delete-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '正在删除...';
    }
}

/**
 * Hide progress indicator
 */
function hideProgress() {
    // Restore delete button
    var btn = document.getElementById('batch-delete-btn');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = 'Delete Selected Items';
    }
}

/**
 * Batch delete blacklist items using AJAX
 */
function batchDeleteBlacklist() {
    var items = getSelectedItems('form_blacklist');

    if (!items || items.length === 0) {
        alert('请选择要删除的项目');
        return;
    }

    if (!confirm('确认删除选中的 ' + items.length + ' 个项目吗？此操作不可撤销！')) {
        return;
    }

    // Show progress
    showProgress();

    // Get CSRF token
    var csrfTokenElement = document.querySelector('input[name="_glpi_csrf_token"]');
    if (!csrfTokenElement) {
        hideProgress();
        alert('CSRF令牌未找到，请刷新页面重试');
        return;
    }

    var csrfToken = csrfTokenElement.value;

    // Prepare form data with CSRF token
    var formData = new FormData();
    formData.append('action', 'batch_delete');
    formData.append('type', 'blacklist');
    formData.append('items', JSON.stringify(items));
    formData.append('_glpi_csrf_token', csrfToken);

    // Send AJAX request
    fetch('../ajax/batch_delete.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(function(data) {
        hideProgress();
        if (data && data.success) {
            var message = '删除完成！成功删除 ' + data.deleted_count + ' 个项目';
            if (data.failed_count > 0) {
                message += '，失败 ' + data.failed_count + ' 个';
            }
            alert(message);
            window.location.reload();
        } else {
            alert('删除失败：' + (data ? data.error : '未知错误'));
        }
    })
    .catch(function(error) {
        hideProgress();
        console.error('Batch delete error:', error);
        alert('删除失败：' + error.message);
    });
}
