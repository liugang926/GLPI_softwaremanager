<?php
/**
 * Simple test page to check GLPI environment
 */

include('../../../inc/includes.php');

// Check if user is logged in
if (!Session::getLoginUserID()) {
    die("User not logged in");
}

// Check basic permissions
if (!Session::haveRight("config", READ)) {
    die("No permission");
}

echo "<h1>GLPI Environment Test</h1>";
echo "<p>✅ GLPI core loaded successfully</p>";
echo "<p>✅ User logged in: " . Session::getLoginUserID() . "</p>";
echo "<p>✅ User has config read permission</p>";

// Test database connection
global $DB;
if ($DB && $DB->connected) {
    echo "<p>✅ Database connected</p>";
} else {
    echo "<p>❌ Database not connected</p>";
}

// Test plugin classes
try {
    include_once(__DIR__ . '/../inc/softwareblacklist.class.php');
    echo "<p>✅ SoftwareBlacklist class loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ SoftwareBlacklist class error: " . $e->getMessage() . "</p>";
}

try {
    include_once(__DIR__ . '/../inc/softwareinventory.class.php');
    echo "<p>✅ SoftwareInventory class loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ SoftwareInventory class error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='blacklist.php'>Go to Blacklist Page</a></p>";
?>
