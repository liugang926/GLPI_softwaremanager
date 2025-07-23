<?php
/**
 * 路径测试页面 - 不依赖GLPI
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Path Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { margin: 10px 0; padding: 10px; border: 1px solid #ccc; background: #f9f9f9; }
        .success { border-color: green; background: #e8f5e8; }
        .error { border-color: red; background: #ffe8e8; }
    </style>
</head>
<body>
    <h1>AJAX路径测试</h1>
    <p>当前页面URL: <span id="current-url"></span></p>
    <p>当前路径: <span id="current-path"></span></p>
    
    <button onclick="testPaths()">测试所有可能的AJAX路径</button>
    <div id="results"></div>
    
    <script>
    document.getElementById('current-url').textContent = window.location.href;
    document.getElementById('current-path').textContent = window.location.pathname;
    
    function testPaths() {
        var resultsDiv = document.getElementById('results');
        resultsDiv.innerHTML = '<p>正在测试...</p>';
        
        // 测试多个可能的路径
        var testUrls = [
            './ajax/test_simple.php',
            '../ajax/test_simple.php', 
            '/plugins/softwaremanager/ajax/test_simple.php',
            'ajax/test_simple.php',
            window.location.pathname.replace('path_test.php', 'ajax/test_simple.php'),
            window.location.href.replace('path_test.php', 'ajax/test_simple.php')
        ];
        
        var results = [];
        var completed = 0;
        
        testUrls.forEach(function(url, index) {
            console.log('Testing URL ' + (index + 1) + ':', url);
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'test=1&message=hello&url_index=' + index
            })
            .then(function(response) {
                console.log('URL ' + (index + 1) + ' Response status:', response.status);
                return response.text().then(function(text) {
                    return { status: response.status, text: text, ok: response.ok };
                });
            })
            .then(function(result) {
                results[index] = {
                    url: url,
                    success: result.ok,
                    status: result.status,
                    response: result.text
                };
                completed++;
                if (completed === testUrls.length) {
                    displayResults(results);
                }
            })
            .catch(function(error) {
                console.log('URL ' + (index + 1) + ' Failed:', error);
                results[index] = {
                    url: url,
                    success: false,
                    status: 'ERROR',
                    response: error.message
                };
                completed++;
                if (completed === testUrls.length) {
                    displayResults(results);
                }
            });
        });
    }
    
    function displayResults(results) {
        var html = '<h2>测试结果:</h2>';
        
        results.forEach(function(result, index) {
            var cssClass = result.success ? 'success' : 'error';
            html += '<div class="result ' + cssClass + '">';
            html += '<h3>URL ' + (index + 1) + ': ' + (result.success ? '成功' : '失败') + '</h3>';
            html += '<p><strong>路径:</strong> ' + result.url + '</p>';
            html += '<p><strong>状态:</strong> ' + result.status + '</p>';
            html += '<p><strong>响应:</strong></p>';
            html += '<pre>' + result.response.substring(0, 500) + '</pre>';
            html += '</div>';
        });
        
        document.getElementById('results').innerHTML = html;
    }
    </script>
</body>
</html>
