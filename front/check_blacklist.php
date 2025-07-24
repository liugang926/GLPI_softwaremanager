<?php
/**
 * Simple blacklist check
 */

include('../../../../inc/includes.php');

Session::checkLoginUser();

Html::header('Blacklist Check', $_SERVER['PHP_SELF'], "plugins", "softwaremanager");

echo "<h2>Blacklist Database Check</h2>";

global $DB;

try {
    // Check blacklist table
    $blacklist_table = 'glpi_plugin_softwaremanager_softwareblacklists';
    
    echo "<h3>Blacklist Table: $blacklist_table</h3>";
    
    $result = $DB->request([
        'FROM' => $blacklist_table,
        'ORDER' => 'date_creation DESC'
    ]);
    
    $count = count($result);
    echo "<p><strong>Total blacklist entries: $count</strong></p>";
    
    if ($count > 0) {
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
    } else {
        echo "<p>❌ No blacklist entries found.</p>";
    }
    
    // Check for Microsoft Visual C++ specifically
    echo "<h3>Microsoft Visual C++ Entries:</h3>";
    $microsoft_result = $DB->request([
        'FROM' => $blacklist_table,
        'WHERE' => ['name' => ['LIKE', '%Microsoft Visual C++%']]
    ]);
    
    $microsoft_count = count($microsoft_result);
    if ($microsoft_count > 0) {
        echo "<p>✅ Found $microsoft_count Microsoft Visual C++ entries:</p>";
        echo "<ul>";
        foreach ($microsoft_result as $row) {
            echo "<li><strong>" . htmlspecialchars($row['name']) . "</strong> - " . $row['date_creation'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No Microsoft Visual C++ entries found in blacklist.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='softwarelist.php'>Back to Software List</a></p>";

Html::footer();
?>
