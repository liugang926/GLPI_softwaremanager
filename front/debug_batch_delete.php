<?php
/**
 * Debug batch delete test page
 */

include_once('../../../inc/includes.php');

Session::checkRight("plugin_softwaremanager", READ);

Html::header(__('Debug Batch Delete Test', 'softwaremanager'), $_SERVER['PHP_SELF'], "plugins", "softwaremanager");

echo "<div class='center'>";
echo "<h2>Debug批量删除测试</h2>";

echo "<button id='test-batch-delete' class='btn btn-primary'>测试批量删除</button>";
echo "<div id='result' style='margin-top: 20px; padding: 10px; border: 1px solid #ccc;'></div>";

echo "</div>";

// JavaScript
echo "<script>
document.getElementById('test-batch-delete').addEventListener('click', function() {
    var resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '正在测试批量删除...';
    
    console.log('Starting batch delete test');
    
    fetch('../ajax/batch_delete_debug.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=batch_delete&type=whitelist&items=' + encodeURIComponent(JSON.stringify([1, 2, 3]))
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        
        try {
            var data = JSON.parse(text);
            console.log('Parsed JSON:', data);
            
            if (data.success) {
                resultDiv.innerHTML = '<h3>成功!</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } else {
                resultDiv.innerHTML = '<h3>失败:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            resultDiv.innerHTML = '<h3>JSON解析错误:</h3><pre>' + text + '</pre>';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        resultDiv.innerHTML = '<h3>请求错误:</h3><pre>' + error.toString() + '</pre>';
    });
});
</script>";

Html::footer();
?>
