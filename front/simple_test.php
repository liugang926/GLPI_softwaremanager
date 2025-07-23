<!DOCTYPE html>
<html>
<head>
    <title>Simple Batch Delete Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { margin: 10px 0; padding: 10px; border: 1px solid #ccc; background: #f9f9f9; }
        .success { border-color: green; background: #e8f5e8; }
        .error { border-color: red; background: #ffe8e8; }
        button { padding: 10px 20px; font-size: 16px; margin: 5px; }
    </style>
</head>
<body>
    <h1>简单批量删除测试</h1>
    
    <button onclick="testMinimal()">测试最简单版本</button>
    <button onclick="testPathTest()">测试路径检查</button>
    <button onclick="testGlpiDiagnostic()">GLPI诊断</button>
    <button onclick="testBatchDelete()">测试批量删除</button>
    <button onclick="testDebugVersion()">测试Debug版本</button>
    <button onclick="testBatchFunction()">测试批量功能</button>
    <button onclick="testSimpleDebug()">简单调试测试</button>
    <button onclick="testPostRequest()">测试POST请求</button>

    <div id="result"></div>
    
    <script>
    function testMinimal() {
        var resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>正在测试最简单版本...</p>';

        console.log('Testing minimal_test.php');

        fetch('../ajax/minimal_test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test&message=hello'
        })
        .then(response => {
            console.log('Minimal response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Minimal raw response:', text);

            var html = '<div class="result">';
            html += '<h3>最简单版本测试结果:</h3>';

            try {
                var data = JSON.parse(text);
                html += '<p><strong>状态:</strong> ' + (data.success ? '成功' : '失败') + '</p>';
                html += '<p><strong>JSON响应:</strong></p>';
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                if (data.success) {
                    html = '<div class="result success">' + html.substring(22);
                } else {
                    html = '<div class="result error">' + html.substring(22);
                }
            } catch (e) {
                html += '<p><strong>状态:</strong> JSON解析失败</p>';
                html += '<p><strong>错误:</strong> ' + e.message + '</p>';
                html += '<p><strong>原始响应:</strong></p>';
                html += '<pre>' + text.substring(0, 1000) + '</pre>';
                html = '<div class="result error">' + html.substring(22);
            }

            html += '</div>';
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Minimal error:', error);
            resultDiv.innerHTML = '<div class="result error"><h3>最简单版本请求失败:</h3><pre>' + error.toString() + '</pre></div>';
        });
    }

    function testPathTest() {
        var resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>正在测试路径检查...</p>';

        console.log('Testing path_test.php');

        fetch('../ajax/path_test.php', {
            method: 'GET'
        })
        .then(response => {
            console.log('Path test response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Path test raw response:', text);

            var html = '<div class="result">';
            html += '<h3>路径检查测试结果:</h3>';

            try {
                var data = JSON.parse(text);
                html += '<p><strong>状态:</strong> ' + (data.success ? '成功' : '失败') + '</p>';

                if (data.glpi_found_at && data.glpi_found_at.length > 0) {
                    html += '<p><strong>找到GLPI:</strong> ' + data.glpi_found_at.join(', ') + '</p>';
                    html += '<p><strong>推荐路径:</strong> ' + data.recommended_path + '</p>';
                } else {
                    html += '<p><strong>GLPI状态:</strong> 未找到GLPI安装</p>';
                }

                html += '<p><strong>服务器信息:</strong></p>';
                html += '<pre>' + JSON.stringify(data.server_info, null, 2) + '</pre>';

                html += '<p><strong>路径测试结果:</strong></p>';
                html += '<pre>' + JSON.stringify(data.path_tests, null, 2) + '</pre>';

                if (data.success) {
                    html = '<div class="result success">' + html.substring(22);
                } else {
                    html = '<div class="result error">' + html.substring(22);
                }
            } catch (e) {
                html += '<p><strong>状态:</strong> JSON解析失败</p>';
                html += '<p><strong>错误:</strong> ' + e.message + '</p>';
                html += '<p><strong>原始响应:</strong></p>';
                html += '<pre>' + text.substring(0, 1000) + '</pre>';
                html = '<div class="result error">' + html.substring(22);
            }

            html += '</div>';
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Path test error:', error);
            resultDiv.innerHTML = '<div class="result error"><h3>路径检查请求失败:</h3><pre>' + error.toString() + '</pre></div>';
        });
    }

    function testGlpiDiagnostic() {
        var resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>正在运行GLPI诊断...</p>';

        console.log('Testing GLPI diagnostic');

        fetch('../ajax/glpi_diagnostic.php', {
            method: 'GET'
        })
        .then(response => {
            console.log('GLPI diagnostic response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('GLPI diagnostic raw response:', text);

            var html = '<div class="result">';
            html += '<h3>GLPI诊断结果:</h3>';

            try {
                var data = JSON.parse(text);
                html += '<p><strong>状态:</strong> ' + (data.success ? '成功' : '失败') + '</p>';
                html += '<p><strong>GLPI状态:</strong> ' + data.glpi_status + '</p>';
                html += '<p><strong>时间:</strong> ' + data.timestamp + '</p>';

                if (data.errors && data.errors.length > 0) {
                    html += '<p><strong>错误:</strong></p>';
                    html += '<ul>';
                    data.errors.forEach(function(error) {
                        html += '<li>' + error + '</li>';
                    });
                    html += '</ul>';
                }

                if (data.steps && data.steps.length > 0) {
                    html += '<p><strong>执行步骤:</strong></p>';
                    html += '<ul>';
                    data.steps.forEach(function(step) {
                        html += '<li>' + step + '</li>';
                    });
                    html += '</ul>';
                }

                html += '<p><strong>详细信息:</strong></p>';
                html += '<pre style="max-height: 400px; overflow-y: auto;">' + JSON.stringify(data, null, 2) + '</pre>';

                if (data.success) {
                    html = '<div class="result success">' + html.substring(22);
                } else {
                    html = '<div class="result error">' + html.substring(22);
                }
            } catch (e) {
                html += '<p><strong>状态:</strong> JSON解析失败</p>';
                html += '<p><strong>错误:</strong> ' + e.message + '</p>';
                html += '<p><strong>原始响应:</strong></p>';
                html += '<pre>' + text.substring(0, 2000) + '</pre>';
                html = '<div class="result error">' + html.substring(22);
            }

            html += '</div>';
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('GLPI diagnostic error:', error);
            resultDiv.innerHTML = '<div class="result error"><h3>GLPI诊断请求失败:</h3><pre>' + error.toString() + '</pre></div>';
        });
    }

    function testBatchDelete() {
        var resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>正在测试原版批量删除...</p>';
        
        console.log('Testing original batch_delete.php');
        
        fetch('../ajax/batch_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=batch_delete&type=whitelist&items=' + encodeURIComponent(JSON.stringify([1, 2, 3]))
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            
            var html = '<div class="result">';
            html += '<h3>原版batch_delete.php测试结果:</h3>';
            html += '<p><strong>状态:</strong> ' + (text.includes('"success"') ? '可能成功' : '可能失败') + '</p>';
            html += '<p><strong>原始响应:</strong></p>';
            html += '<pre>' + text.substring(0, 1000) + '</pre>';
            html += '</div>';
            
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = '<div class="result error"><h3>请求失败:</h3><pre>' + error.toString() + '</pre></div>';
        });
    }
    
    function testDebugVersion() {
        var resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>正在测试Debug版本...</p>';
        
        console.log('Testing debug batch_delete.php');
        
        fetch('../ajax/batch_delete_debug.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=batch_delete&type=whitelist&items=' + encodeURIComponent(JSON.stringify([1, 2, 3]))
        })
        .then(response => {
            console.log('Debug response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Debug raw response:', text);
            
            var html = '<div class="result">';
            html += '<h3>Debug版本测试结果:</h3>';
            
            try {
                var data = JSON.parse(text);
                html += '<p><strong>状态:</strong> ' + (data.success ? '成功' : '失败') + '</p>';
                html += '<p><strong>JSON响应:</strong></p>';
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                if (data.success) {
                    html = '<div class="result success">' + html.substring(22);
                } else {
                    html = '<div class="result error">' + html.substring(22);
                }
            } catch (e) {
                html += '<p><strong>状态:</strong> JSON解析失败</p>';
                html += '<p><strong>原始响应:</strong></p>';
                html += '<pre>' + text.substring(0, 1000) + '</pre>';
                html = '<div class="result error">' + html.substring(22);
            }
            
            html += '</div>';
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Debug error:', error);
            resultDiv.innerHTML = '<div class="result error"><h3>Debug请求失败:</h3><pre>' + error.toString() + '</pre></div>';
        });
    }

    function testBatchFunction() {
        var resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>正在测试批量功能...</p>';

        console.log('Testing batch function');

        fetch('../ajax/batch_test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'batch_delete',
                type: 'whitelist',
                items: [1, 2, 3]
            })
        })
        .then(response => {
            console.log('Batch function response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Batch function raw response:', text);

            var html = '<div class="result">';
            html += '<h3>批量功能测试结果:</h3>';

            try {
                var data = JSON.parse(text);
                html += '<p><strong>状态:</strong> ' + (data.success ? '成功' : '失败') + '</p>';
                html += '<p><strong>JSON响应:</strong></p>';
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                if (data.success) {
                    html = '<div class="result success">' + html.substring(22);
                } else {
                    html = '<div class="result error">' + html.substring(22);
                }
            } catch (e) {
                html += '<p><strong>状态:</strong> JSON解析失败</p>';
                html += '<p><strong>原始响应:</strong></p>';
                html += '<pre>' + text.substring(0, 1000) + '</pre>';
                html = '<div class="result error">' + html.substring(22);
            }

            html += '</div>';
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Batch function error:', error);
            resultDiv.innerHTML = '<div class="result error"><h3>批量功能请求失败:</h3><pre>' + error.toString() + '</pre></div>';
        });
    }

    function testSimpleDebug() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="loading">正在测试简单调试...</div>';

        fetch('../ajax/simple_debug.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.text();
        })
        .then(data => {
            let html = '<div class="result"><h3>简单调试测试结果:</h3>';

            try {
                // 尝试解析JSON
                const jsonData = JSON.parse(data);
                html += '<p>状态: 成功</p>';
                html += '<p>原始响应:</p><pre>' + JSON.stringify(jsonData, null, 2) + '</pre>';
            } catch (e) {
                html += '<p>状态: JSON解析失败</p>';
                html += '<p>原始响应:</p><pre>' + data + '</pre>';
            }

            html += '</div>';
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Simple debug error:', error);
            resultDiv.innerHTML = '<div class="result error"><h3>简单调试请求失败:</h3><pre>' + error.toString() + '</pre></div>';
        });
    }

    function testPostRequest() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="loading">正在测试POST请求...</div>';

        // 准备测试数据
        const testData = {
            action: 'batch_delete',
            type: 'whitelist',
            items: ['1', '2', '3']
        };

        // 发送POST请求
        fetch('../ajax/post_test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(testData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.text();
        })
        .then(data => {
            let html = '<div class="result"><h3>POST请求测试结果:</h3>';

            try {
                // 尝试解析JSON
                const jsonData = JSON.parse(data);
                html += '<p>状态: 成功</p>';
                html += '<p>原始响应:</p><pre>' + JSON.stringify(jsonData, null, 2) + '</pre>';
            } catch (e) {
                html += '<p>状态: JSON解析失败</p>';
                html += '<p>原始响应:</p><pre>' + data + '</pre>';
            }

            html += '</div>';
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('POST test error:', error);
            resultDiv.innerHTML = '<div class="result error"><h3>POST请求测试失败:</h3><pre>' + error.toString() + '</pre></div>';
        });
    }
    </script>
</body>
</html>
