<?php
/**
 * AJAX连接测试页面
 */

include_once('../../../inc/includes.php');

// 检查权限
Session::checkRight("plugin_softwaremanager", READ);

Html::header(__('Software Manager - AJAX Test', 'softwaremanager'), $_SERVER['PHP_SELF'], "plugins", "softwaremanager");

echo "<div class='center'>";
echo "<h2>AJAX连接测试</h2>";

echo "<button id='test-ajax' class='btn btn-primary'>测试AJAX连接</button>";
echo "<div id='result' style='margin-top: 20px; padding: 10px; border: 1px solid #ccc;'></div>";

echo "</div>";

// JavaScript
echo "<script>
document.getElementById('test-ajax').addEventListener('click', function() {
    var resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '正在测试...';

    // 测试多个可能的路径
    var testUrls = [
        './ajax/test_simple.php',
        '../ajax/test_simple.php',
        '/plugins/softwaremanager/ajax/test_simple.php',
        window.location.pathname.replace('ajax_test.php', 'ajax/test_simple.php')
    ];

    console.log('Current URL:', window.location.href);
    console.log('Current pathname:', window.location.pathname);

    function testUrl(index) {
        if (index >= testUrls.length) {
            resultDiv.innerHTML += '<h3>所有路径都测试失败</h3>';
            return;
        }

        var url = testUrls[index];
        console.log('Testing URL ' + (index + 1) + ':', url);

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'test=1&message=hello&url_index=' + index
        })
        .then(response => {
            console.log('URL ' + (index + 1) + ' Response status:', response.status);
            if (response.ok) {
                return response.text();
            } else {
                throw new Error('HTTP ' + response.status);
            }
        })
        .then(data => {
            console.log('URL ' + (index + 1) + ' Success:', data);
            resultDiv.innerHTML = '<h3>成功的URL (' + (index + 1) + '):</h3><p>' + url + '</p><h3>响应:</h3><pre>' + data + '</pre>';
        })
        .catch(error => {
            console.log('URL ' + (index + 1) + ' Failed:', error);
            resultDiv.innerHTML += '<p>URL ' + (index + 1) + ' (' + url + ') 失败: ' + error.message + '</p>';
            testUrl(index + 1);
        });
    }

    testUrl(0);
});
</script>";

Html::footer();
?>
