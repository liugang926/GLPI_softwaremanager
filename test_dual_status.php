<?php
/**
 * Test script for dual status (whitelist + blacklist) display
 */

// Include GLPI
include('../../../inc/includes.php');

// Check if we're logged in
if (!Session::getLoginUserID()) {
    echo "Please log in to GLPI first.\n";
    exit;
}

echo "<h2>Testing Dual Status Display</h2>";

$test_software = "Adobe Acrobat (64-bit)"; // Use an existing software

echo "<h3>Step 1: Add to Whitelist</h3>";
try {
    $result = PluginSoftwaremanagerSoftwareWhitelist::addToList($test_software, 'Test dual status');
    if ($result) {
        echo "✅ Successfully added '$test_software' to whitelist (ID: $result)<br>";
    } else {
        echo "ℹ️ '$test_software' already in whitelist or failed to add<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<h3>Step 2: Add to Blacklist</h3>";
try {
    $result = PluginSoftwaremanagerSoftwareBlacklist::addToList($test_software, 'Test dual status');
    if ($result) {
        echo "✅ Successfully added '$test_software' to blacklist (ID: $result)<br>";
    } else {
        echo "ℹ️ '$test_software' already in blacklist or failed to add<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<h3>Step 3: Check Database Status</h3>";
global $DB;

// Check whitelist
$whitelist_result = $DB->request([
    'FROM' => PluginSoftwaremanagerSoftwareWhitelist::getTable(),
    'WHERE' => ['name' => $test_software, 'is_active' => 1]
]);

$in_whitelist = count($whitelist_result) > 0;
echo "Whitelist status: " . ($in_whitelist ? "✅ YES" : "❌ NO") . "<br>";

// Check blacklist
$blacklist_result = $DB->request([
    'FROM' => PluginSoftwaremanagerSoftwareBlacklist::getTable(),
    'WHERE' => ['name' => $test_software, 'is_active' => 1]
]);

$in_blacklist = count($blacklist_result) > 0;
echo "Blacklist status: " . ($in_blacklist ? "✅ YES" : "❌ NO") . "<br>";

echo "<h3>Step 4: Test Query Logic</h3>";

// Test the new query logic
$sql = "SELECT DISTINCT
        s.id as software_id,
        s.name as software_name,
        COALESCE(sv.name, '') as version,
        COALESCE(m.name, '') as manufacturer,
        COUNT(DISTINCT isv.items_id) as computer_count,
        CASE
            WHEN w.id IS NOT NULL AND w.is_active = 1 AND b.id IS NOT NULL AND b.is_active = 1 THEN 'both'
            WHEN w.id IS NOT NULL AND w.is_active = 1 THEN 'whitelist'
            WHEN b.id IS NOT NULL AND b.is_active = 1 THEN 'blacklist'
            ELSE 'unmanaged'
        END as status,
        CASE WHEN w.id IS NOT NULL AND w.is_active = 1 THEN 1 ELSE 0 END as is_whitelisted,
        CASE WHEN b.id IS NOT NULL AND b.is_active = 1 THEN 1 ELSE 0 END as is_blacklisted
    FROM glpi_softwares s
    LEFT JOIN glpi_manufacturers m ON (m.id = s.manufacturers_id)
    LEFT JOIN glpi_softwareversions sv ON (sv.softwares_id = s.id)
    LEFT JOIN glpi_items_softwareversions isv ON (
        isv.softwareversions_id = sv.id
        AND isv.itemtype = 'Computer'
        AND isv.is_deleted = 0
    )
    LEFT JOIN glpi_computers c ON (
        c.id = isv.items_id
        AND c.is_deleted = 0
        AND c.is_template = 0
    )
    LEFT JOIN " . PluginSoftwaremanagerSoftwareWhitelist::getTable() . " w ON (
        w.name = s.name AND w.is_active = 1
    )
    LEFT JOIN " . PluginSoftwaremanagerSoftwareBlacklist::getTable() . " b ON (
        b.name = s.name AND b.is_active = 1
    )
    WHERE s.is_deleted = 0 AND s.name = '" . $DB->escape($test_software) . "'
    GROUP BY s.id, s.name, sv.name, m.name, w.id, b.id
    ORDER BY s.name ASC";

$result = $DB->query($sql);
if ($result && $DB->numrows($result) > 0) {
    echo "<h4>Query Results:</h4>";
    while ($row = $DB->fetchAssoc($result)) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
} else {
    echo "❌ No results found for '$test_software'<br>";
    echo "SQL: " . $sql . "<br>";
}

echo "<h3>Step 5: Test Statistics</h3>";
$stats = PluginSoftwaremanagerSoftwareInventory::getStatistics();
echo "<pre>";
print_r($stats);
echo "</pre>";

echo "<p><a href='front/softwarelist.php'>View Software List</a></p>";
echo "<p><strong>Test completed! Check the software list to see if dual status is displayed correctly.</strong></p>";
?>
