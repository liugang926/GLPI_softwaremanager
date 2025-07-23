<?php
/**
 * Software Manager Plugin - Test Black/White List Functionality
 */

// Include GLPI
include('../../../inc/includes.php');

// Check if user has admin rights
Session::checkRight("config", UPDATE);

echo "<h1>Software Manager Plugin - Black/White List Test</h1>";

// Include plugin setup and classes
include_once(__DIR__ . '/setup.php');
include_once(__DIR__ . '/inc/softwarewhitelist.class.php');
include_once(__DIR__ . '/inc/softwareblacklist.class.php');

echo "<h2>Step 1: Database Table Check</h2>";

global $DB;

$tables = [
    'glpi_plugin_softwaremanager_whitelists',
    'glpi_plugin_softwaremanager_blacklists'
];

foreach ($tables as $table) {
    if ($DB->tableExists($table)) {
        echo "✓ Table $table exists<br>";
        
        // Check table structure
        $query = "DESCRIBE `$table`";
        $result = $DB->query($query);
        if ($result) {
            echo "&nbsp;&nbsp;Table structure:<br>";
            while ($row = $DB->fetchAssoc($result)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
            }
        }
        
        // Check existing data
        $count_query = "SELECT COUNT(*) as count FROM `$table`";
        $count_result = $DB->query($count_query);
        if ($count_result) {
            $count_row = $DB->fetchAssoc($count_result);
            echo "&nbsp;&nbsp;Current records: " . $count_row['count'] . "<br>";
        }
        
    } else {
        echo "✗ Table $table missing<br>";
        
        // Try to create the table
        echo "&nbsp;&nbsp;Attempting to create table...<br>";
        try {
            $migration = new Migration('1.0.0');
            if ($table == 'glpi_plugin_softwaremanager_whitelists') {
                PluginSoftwaremanagerSoftwareWhitelist::install($migration);
            } else {
                PluginSoftwaremanagerSoftwareBlacklist::install($migration);
            }
            $migration->executeMigration();
            echo "&nbsp;&nbsp;✓ Table created successfully<br>";
        } catch (Exception $e) {
            echo "&nbsp;&nbsp;✗ Failed to create table: " . $e->getMessage() . "<br>";
        }
    }
    echo "<br>";
}

echo "<h2>Step 2: Class Functionality Test</h2>";

// Test Whitelist Class
echo "<h3>Testing Whitelist Class</h3>";
try {
    $whitelist = new PluginSoftwaremanagerSoftwareWhitelist();
    echo "✓ Whitelist class instantiated successfully<br>";
    
    // Test getTable method
    $table_name = PluginSoftwaremanagerSoftwareWhitelist::getTable();
    echo "✓ Table name: $table_name<br>";
    
    // Test getTypeName method
    $type_name = PluginSoftwaremanagerSoftwareWhitelist::getTypeName();
    echo "✓ Type name: $type_name<br>";
    
    // Test find method
    $existing_items = $whitelist->find();
    echo "✓ Found " . count($existing_items) . " existing whitelist items<br>";
    
} catch (Exception $e) {
    echo "✗ Whitelist class error: " . $e->getMessage() . "<br>";
}

// Test Blacklist Class
echo "<h3>Testing Blacklist Class</h3>";
try {
    $blacklist = new PluginSoftwaremanagerSoftwareBlacklist();
    echo "✓ Blacklist class instantiated successfully<br>";
    
    // Test getTable method
    $table_name = PluginSoftwaremanagerSoftwareBlacklist::getTable();
    echo "✓ Table name: $table_name<br>";
    
    // Test getTypeName method
    $type_name = PluginSoftwaremanagerSoftwareBlacklist::getTypeName();
    echo "✓ Type name: $type_name<br>";
    
    // Test find method
    $existing_items = $blacklist->find();
    echo "✓ Found " . count($existing_items) . " existing blacklist items<br>";
    
} catch (Exception $e) {
    echo "✗ Blacklist class error: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 3: Add/Remove Test</h2>";

// Test adding to whitelist
echo "<h3>Testing Whitelist Add/Remove</h3>";
$test_software = "Test Software " . date('Y-m-d H:i:s');
try {
    $result = PluginSoftwaremanagerSoftwareWhitelist::addToList($test_software, 'Test comment');
    if ($result) {
        echo "✓ Successfully added '$test_software' to whitelist (ID: $result)<br>";
        
        // Try to add the same software again (should fail)
        $result2 = PluginSoftwaremanagerSoftwareWhitelist::addToList($test_software, 'Duplicate test');
        if (!$result2) {
            echo "✓ Correctly prevented duplicate entry<br>";
        } else {
            echo "✗ Duplicate entry was allowed (should not happen)<br>";
        }
        
        // Remove the test item
        $whitelist = new PluginSoftwaremanagerSoftwareWhitelist();
        if ($whitelist->delete(['id' => $result])) {
            echo "✓ Successfully removed test item from whitelist<br>";
        } else {
            echo "✗ Failed to remove test item from whitelist<br>";
        }
        
    } else {
        echo "✗ Failed to add '$test_software' to whitelist<br>";
    }
} catch (Exception $e) {
    echo "✗ Whitelist add/remove error: " . $e->getMessage() . "<br>";
}

// Test adding to blacklist
echo "<h3>Testing Blacklist Add/Remove</h3>";
$test_software = "Test Prohibited Software " . date('Y-m-d H:i:s');
try {
    $result = PluginSoftwaremanagerSoftwareBlacklist::addToList($test_software, 'Test comment');
    if ($result) {
        echo "✓ Successfully added '$test_software' to blacklist (ID: $result)<br>";
        
        // Try to add the same software again (should fail)
        $result2 = PluginSoftwaremanagerSoftwareBlacklist::addToList($test_software, 'Duplicate test');
        if (!$result2) {
            echo "✓ Correctly prevented duplicate entry<br>";
        } else {
            echo "✗ Duplicate entry was allowed (should not happen)<br>";
        }
        
        // Remove the test item
        $blacklist = new PluginSoftwaremanagerSoftwareBlacklist();
        if ($blacklist->delete(['id' => $result])) {
            echo "✓ Successfully removed test item from blacklist<br>";
        } else {
            echo "✗ Failed to remove test item from blacklist<br>";
        }
        
    } else {
        echo "✗ Failed to add '$test_software' to blacklist<br>";
    }
} catch (Exception $e) {
    echo "✗ Blacklist add/remove error: " . $e->getMessage() . "<br>";
}

echo "<h2>Step 4: Web Interface Test</h2>";
echo "<p>Test the web interfaces:</p>";
echo "<p><a href='front/whitelist.php' target='_blank'>Test Whitelist Interface</a></p>";
echo "<p><a href='front/blacklist.php' target='_blank'>Test Blacklist Interface</a></p>";

echo "<h2>Summary</h2>";
echo "<p>If all tests show ✓, the black/white list functionality should be working correctly.</p>";
echo "<p>If any tests show ✗, those issues need to be resolved.</p>";

?>
