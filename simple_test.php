<?php
/**
 * 简单测试页面 - 不需要GLPI权限
 */

// 不加载GLPI，直接测试
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Test</title>
</head>
<body>
    <h1>Simple Test Page</h1>
    <p>If you can see this, PHP is working.</p>
    
    <button onclick="testAjax()">Test AJAX</button>
    <div id="result"></div>
    
    <script>
    function testAjax() {
        // 使用相对路径
        fetch('./ajax/batch_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=batch_delete&type=whitelist&items=' + encodeURIComponent(JSON.stringify([1, 2]))
        })
        .then(response => response.text())
        .then(text => {
            document.getElementById('result').innerHTML = '<pre>' + text + '</pre>';
            console.log('Response:', text);
        })
        .catch(error => {
            document.getElementById('result').innerHTML = '<pre>Error: ' + error.message + '</pre>';
            console.error('Error:', error);
        });
    }
    </script>
</body>
</html>
