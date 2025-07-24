<?php
/**
 * Check database tables
 */

include('../../../../inc/includes.php');

Session::checkLoginUser();

Html::header('Database Tables Check', $_SERVER['PHP_SELF'], "plugins", "softwaremanager");

echo "<h2>Database Tables Check</h2>";

global $DB;

// Check all plugin tables
$plugin_tables = [
    'glpi_plugin_softwaremanager_whitelists',
    'glpi_plugin_softwaremanager_blacklists',
    'glpi_plugin_softwaremanager_softwarewhitelists',
    'glpi_plugin_softwaremanager_softwareblacklists'
];

echo "<h3>Table Existence Check:</h3>";
foreach ($plugin_tables as $table) {
    $exists = $DB->tableExists($table);
    echo "<p><strong>$table:</strong> " . ($exists ? "✅ EXISTS" : "❌ NOT EXISTS") . "</p>";
    
    if ($exists) {
        $count = $DB->request(['FROM' => $table, 'COUNT' => 'id as count'])->current()['count'];
        echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;Records: $count</p>";
        
        // Show first few records
        if ($count > 0) {
            $result = $DB->request(['FROM' => $table, 'LIMIT' => 3]);
            echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;Sample records:</p>";
            echo "<ul>";
            foreach ($result as $row) {
                echo "<li>" . htmlspecialchars($row['name'] ?? 'N/A') . " - " . ($row['date_creation'] ?? 'N/A') . "</li>";
            }
            echo "</ul>";
        }
    }
}

// Check what the classes think their table names are
echo "<h3>Class Table Names:</h3>";
echo "<p><strong>PluginSoftwaremanagerSoftwareWhitelist::getTable():</strong> " . PluginSoftwaremanagerSoftwareWhitelist::getTable() . "</p>";
echo "<p><strong>PluginSoftwaremanagerSoftwareBlacklist::getTable():</strong> " . PluginSoftwaremanagerSoftwareBlacklist::getTable() . "</p>";

echo "<p><a href='softwarelist.php'>Back to Software List</a></p>";

Html::footer();
?>
