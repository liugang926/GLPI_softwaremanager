<?php
/**
 * Test script for batch blacklist operations
 */

// Include GLPI
include('../../../inc/includes.php');

// Check if we're logged in
if (!Session::getLoginUserID()) {
    echo "Please log in to GLPI first.\n";
    exit;
}

echo "<h2>Testing Batch Blacklist Operations</h2>";

// Test data
$test_software_names = [
    "Test Batch Software 1",
    "Test Batch Software 2", 
    "Test Batch Software 3"
];

echo "<h3>Testing Individual addToList Method</h3>";

foreach ($test_software_names as $software_name) {
    echo "<p>Testing: $software_name</p>";
    
    try {
        $result = PluginSoftwaremanagerSoftwareBlacklist::addToList($software_name, 'Batch test');
        if ($result) {
            echo "✅ Successfully added '$software_name' to blacklist (ID: $result)<br>";
        } else {
            echo "❌ Failed to add '$software_name' to blacklist<br>";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "<br>";
    }
}

echo "<h3>Testing Batch Processing Logic</h3>";

// Simulate the batch processing logic from softwarelist.php
$software_names_string = implode("\n", $test_software_names);
$software_names = array_filter(array_map('trim', explode("\n", $software_names_string)));

$success_count = 0;
$total_count = count($software_names);

echo "<p>Processing $total_count software items...</p>";

foreach ($software_names as $software_name) {
    $software_name = Html::cleanInputText(trim($software_name));
    if (!empty($software_name)) {
        echo "<p>Processing: '$software_name'</p>";
        
        try {
            if (PluginSoftwaremanagerSoftwareBlacklist::addToList($software_name, 'Batch added')) {
                $success_count++;
                echo "✅ Success<br>";
            } else {
                echo "❌ Failed (possibly already exists)<br>";
            }
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "<br>";
        }
    }
}

echo "<h3>Results</h3>";
echo "<p>Successfully processed: $success_count out of $total_count items</p>";

// Check if items were actually added to database
echo "<h3>Database Verification</h3>";
global $DB;

$blacklist_table = PluginSoftwaremanagerSoftwareBlacklist::getTable();
foreach ($test_software_names as $software_name) {
    $result = $DB->request([
        'FROM' => $blacklist_table,
        'WHERE' => ['name' => $software_name]
    ]);
    
    if (count($result) > 0) {
        echo "✅ '$software_name' found in blacklist database<br>";
    } else {
        echo "❌ '$software_name' NOT found in blacklist database<br>";
    }
}

echo "<h3>Cleanup</h3>";
// Clean up test data
foreach ($test_software_names as $software_name) {
    $blacklist = new PluginSoftwaremanagerSoftwareBlacklist();
    $items = $blacklist->find(['name' => $software_name]);
    foreach ($items as $item) {
        if ($blacklist->delete(['id' => $item['id']])) {
            echo "🗑️ Cleaned up '$software_name'<br>";
        }
    }
}

echo "<p><strong>Test completed!</strong></p>";
?>
