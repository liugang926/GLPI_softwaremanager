<?php
/**
 * Simple Blacklist Test Page
 */

include('../../../inc/includes.php');

// Check user permissions
Session::checkRight("config", UPDATE);

// Include plugin classes
include_once(__DIR__ . '/../inc/menu.class.php');
include_once(__DIR__ . '/../inc/softwareblacklist.class.php');

// Start HTML output
Html::header(
    __('Software Blacklist', 'softwaremanager'),
    $_SERVER['PHP_SELF'],
    'plugins',
    'softwaremanager'
);

echo "<h1>Software Blacklist - Simple Test</h1>";

try {
    // Test class instantiation
    $blacklist = new PluginSoftwaremanagerSoftwareBlacklist();
    echo "<p>✅ SoftwareBlacklist class instantiated successfully</p>";
    
    // Test basic database query
    $criteria = ['is_deleted' => 0];
    $all_blacklists = $blacklist->find($criteria, ['software_name ASC'], 10);
    echo "<p>✅ Database query successful. Found " . count($all_blacklists) . " items</p>";
    
    // Display simple table
    echo "<table class='tab_cadre_fixehov'>";
    echo "<tr class='tab_bg_1'>";
    echo "<th>Software Name</th>";
    echo "<th>Comment</th>";
    echo "<th>Date Added</th>";
    echo "</tr>";
    
    foreach ($all_blacklists as $item) {
        echo "<tr class='tab_bg_2'>";
        echo "<td>" . Html::entities_deep($item['software_name']) . "</td>";
        echo "<td>" . Html::entities_deep($item['comment']) . "</td>";
        echo "<td>" . Html::convDateTime($item['date_creation']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

echo "<p><a href='blacklist.php'>Go to Full Blacklist Page</a></p>";

Html::footer();
?>
