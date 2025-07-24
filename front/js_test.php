<?php
/**
 * Test JavaScript file loading
 */

include('../../../inc/includes.php');

// Check user permissions
Session::checkRight("config", UPDATE);

// Start HTML output
Html::header(
    'JavaScript Test',
    $_SERVER['PHP_SELF'],
    'plugins',
    'softwaremanager'
);

echo "<h1>JavaScript File Loading Test</h1>";

// Test different ways to include JavaScript
global $CFG_GLPI;

echo "<h2>Testing JavaScript File Paths</h2>";

$js_path1 = $CFG_GLPI['root_doc'] . "/plugins/softwaremanager/js/blacklist.js";
$js_path2 = "../js/blacklist.js";
$js_path3 = "js/blacklist.js";

echo "<p>Method 1 (CFG_GLPI): <code>" . htmlspecialchars($js_path1) . "</code></p>";
echo "<p>Method 2 (relative): <code>" . htmlspecialchars($js_path2) . "</code></p>";
echo "<p>Method 3 (direct): <code>" . htmlspecialchars($js_path3) . "</code></p>";

// Check if file exists
$file_path = __DIR__ . '/../js/blacklist.js';
echo "<p>File exists: " . (file_exists($file_path) ? "✅ Yes" : "❌ No") . "</p>";
echo "<p>File path: <code>" . htmlspecialchars($file_path) . "</code></p>";

if (file_exists($file_path)) {
    echo "<p>File size: " . filesize($file_path) . " bytes</p>";
}

?>

<!-- Test JavaScript loading -->
<script type="text/javascript">
console.log('Inline JavaScript is working');

// Test if we can access the functions
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // Test if functions are available
    if (typeof checkAll === 'function') {
        console.log('✅ checkAll function is available');
    } else {
        console.log('❌ checkAll function is NOT available');
    }
    
    if (typeof getSelectedItems === 'function') {
        console.log('✅ getSelectedItems function is available');
    } else {
        console.log('❌ getSelectedItems function is NOT available');
    }
    
    if (typeof batchDeleteBlacklist === 'function') {
        console.log('✅ batchDeleteBlacklist function is available');
    } else {
        console.log('❌ batchDeleteBlacklist function is NOT available');
    }
});
</script>

<!-- Try different methods to load JavaScript -->
<h3>Loading JavaScript with Method 1 (CFG_GLPI)</h3>
<script type="text/javascript" src="<?php echo $js_path1; ?>"></script>

<h3>Loading JavaScript with Method 2 (relative)</h3>
<script type="text/javascript" src="../js/blacklist.js"></script>

<p><strong>Check the browser console for JavaScript loading results.</strong></p>

<p><a href="blacklist.php">Go back to Blacklist</a></p>

<?php
Html::footer();
?>
