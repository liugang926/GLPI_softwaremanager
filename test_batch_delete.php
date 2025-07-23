<?php
/**
 * 测试批量删除功能
 */

// 暂时注释掉GLPI加载，避免权限问题
// include('../../../inc/includes.php');
// Session::checkRight("config", UPDATE);

// 使用简单的HTML而不是GLPI的Html::header
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Batch Delete</title>
</head>
<body>
<?php

echo "<div class='center'>";
echo "<h2>Test Batch Delete Functionality</h2>";

// 测试表单
echo "<form id='test-form'>";
echo "<h3>Test Data:</h3>";
echo "<p>Action: batch_delete</p>";
echo "<p>Type: whitelist</p>";
echo "<p>Items: [1, 2, 3]</p>";
echo "<button type='button' onclick='testBatchDelete()'>Test Batch Delete</button>";
echo "</form>";

echo "<div id='result' style='margin-top: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;'>";
echo "<h4>Result will appear here:</h4>";
echo "</div>";

echo "</div>";

?>

<script type="text/javascript">
function testBatchDelete() {
    const items = [1, 2, 3]; // Test with some IDs
    
    console.log('Sending request with items:', items);
    
    // Show loading
    document.getElementById('result').innerHTML = '<h4>Sending request...</h4><p>Please wait...</p>';
    
    // Use the same method as the actual code
    fetch('/plugins/softwaremanager/ajax/batch_delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=batch_delete&type=whitelist&items=' + encodeURIComponent(JSON.stringify(items))
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text(); // Get text first to see if it's HTML error
    })
    .then(text => {
        console.log('Raw response:', text);
        
        let resultHtml = '<h4>Response received:</h4>';
        resultHtml += '<p><strong>Status:</strong> ' + (text.includes('success') ? 'Success' : 'Error') + '</p>';
        resultHtml += '<pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow: auto;">' + text + '</pre>';
        
        document.getElementById('result').innerHTML = resultHtml;
        
        // Try to parse as JSON
        try {
            const data = JSON.parse(text);
            console.log('Parsed JSON:', data);
            
            if (data.success) {
                document.getElementById('result').innerHTML += '<p style="color: green;"><strong>✓ Test Successful!</strong></p>';
            } else {
                document.getElementById('result').innerHTML += '<p style="color: red;"><strong>✗ Test Failed:</strong> ' + (data.error || 'Unknown error') + '</p>';
            }
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            document.getElementById('result').innerHTML += '<p style="color: orange;"><strong>⚠ Warning:</strong> Response is not valid JSON</p>';
        }
    })
    .catch(error => {
        console.error('Request failed:', error);
        document.getElementById('result').innerHTML = '<h4>Request Failed:</h4><p style="color: red;">Error: ' + error.message + '</p>';
    });
}
</script>

</body>
</html>
