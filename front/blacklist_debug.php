<?php
/**
 * Debug version of blacklist page
 */

echo "Step 1: Starting...<br>";

include('../../../inc/includes.php');
echo "Step 2: GLPI includes loaded<br>";

// Check user permissions
Session::checkRight("config", UPDATE);
echo "Step 3: Permissions checked<br>";

// Include plugin classes
include_once(__DIR__ . '/../inc/menu.class.php');
include_once(__DIR__ . '/../inc/softwareblacklist.class.php');
echo "Step 4: Plugin classes included<br>";

// Start HTML output
Html::header(
    __('Software Blacklist', 'softwaremanager'),
    $_SERVER['PHP_SELF'],
    'plugins',
    'softwaremanager'
);
echo "Step 5: HTML header displayed<br>";

try {
    // Test navigation menu
    PluginSoftwaremanagerMenu::displayNavigationHeader('blacklist');
    echo "Step 6: Navigation menu displayed<br>";
} catch (Exception $e) {
    echo "Error in Step 6: " . $e->getMessage() . "<br>";
}

try {
    // Test form creation
    echo "<div class='center' style='margin-bottom: 30px;'>";
    echo "<h3>Quick Add to Blacklist</h3>";
    echo "<form name='form_add' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
    Html::openArrowMassives('form_add', true);
    echo "Step 7: Form started<br>";
} catch (Exception $e) {
    echo "Error in Step 7: " . $e->getMessage() . "<br>";
}

try {
    // Test table creation
    echo "<table class='tab_cadre_fixe'>";
    echo "<tr class='tab_bg_1'>";
    echo "<td>Software Name:</td>";
    echo "<td><input type='text' name='software_name' size='50' required /></td>";
    echo "</tr>";
    echo "<tr class='tab_bg_1'>";
    echo "<td>Comment:</td>";
    echo "<td><input type='text' name='comment' size='50' /></td>";
    echo "</tr>";
    echo "<tr class='tab_bg_1'>";
    echo "<td colspan='2' class='center'>";
    echo "<input type='submit' name='add_item' value='Add to Blacklist' class='submit' />";
    echo "</td>";
    echo "</tr>";
    echo "</table>";
    Html::closeForm();
    echo "</div>";
    echo "Step 8: Add form completed<br>";
} catch (Exception $e) {
    echo "Error in Step 8: " . $e->getMessage() . "<br>";
}

try {
    // Test blacklist object creation
    $blacklist = new PluginSoftwaremanagerSoftwareBlacklist();
    echo "Step 9: Blacklist object created<br>";
} catch (Exception $e) {
    echo "Error in Step 9: " . $e->getMessage() . "<br>";
}

try {
    // Test database query
    $criteria = ['is_deleted' => 0];
    $all_blacklists = $blacklist->find($criteria, ['software_name ASC'], 50);
    echo "Step 10: Database query completed. Found " . count($all_blacklists) . " items<br>";
} catch (Exception $e) {
    echo "Error in Step 10: " . $e->getMessage() . "<br>";
}

echo "<p><a href='blacklist.php'>Go to Original Blacklist Page</a></p>";

Html::footer();
?>
