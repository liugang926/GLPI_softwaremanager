<?php
/**
 * Check blacklist database data
 */

// Include GLPI
define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include(GLPI_ROOT . '/inc/includes.php');

// Start session if not already started
Session::checkLoginUser();

// Check if we're logged in
if (!Session::getLoginUserID()) {
    echo "Please log in to GLPI first.\n";
    exit;
}

echo "<h2>Checking Blacklist Database</h2>";

global $DB;

// Get all blacklist entries
$blacklist_table = PluginSoftwaremanagerSoftwareBlacklist::getTable();
echo "<h3>Current Blacklist Entries:</h3>";

$result = $DB->request([
    'FROM' => $blacklist_table,
    'ORDER' => 'date_creation DESC'
]);

if (count($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Comment</th><th>Is Active</th><th>Date Created</th></tr>";
    
    foreach ($result as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['comment']) . "</td>";
        echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . $row['date_creation'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Total blacklist entries: " . count($result) . "</strong></p>";
} else {
    echo "<p>❌ No entries found in blacklist table.</p>";
}

// Check for Microsoft Visual C++ entries specifically
echo "<h3>Microsoft Visual C++ Entries:</h3>";
$microsoft_result = $DB->request([
    'FROM' => $blacklist_table,
    'WHERE' => ['name' => ['LIKE', '%Microsoft Visual C++%']],
    'ORDER' => 'date_creation DESC'
]);

if (count($microsoft_result) > 0) {
    echo "<ul>";
    foreach ($microsoft_result as $row) {
        echo "<li><strong>" . htmlspecialchars($row['name']) . "</strong> - " . $row['date_creation'] . " (" . $row['comment'] . ")</li>";
    }
    echo "</ul>";
    echo "<p>✅ Found " . count($microsoft_result) . " Microsoft Visual C++ entries in blacklist.</p>";
} else {
    echo "<p>❌ No Microsoft Visual C++ entries found in blacklist.</p>";
}

// Check whitelist for comparison
echo "<h3>Whitelist Entries for Comparison:</h3>";
$whitelist_table = PluginSoftwaremanagerSoftwareWhitelist::getTable();
$whitelist_result = $DB->request([
    'FROM' => $whitelist_table,
    'ORDER' => 'date_creation DESC',
    'LIMIT' => 10
]);

if (count($whitelist_result) > 0) {
    echo "<ul>";
    foreach ($whitelist_result as $row) {
        echo "<li><strong>" . htmlspecialchars($row['name']) . "</strong> - " . $row['date_creation'] . "</li>";
    }
    echo "</ul>";
    echo "<p>Total whitelist entries: " . count($DB->request(['FROM' => $whitelist_table])) . "</p>";
} else {
    echo "<p>No whitelist entries found.</p>";
}

echo "<p><a href='front/softwarelist.php'>Back to Software List</a></p>";
?>
