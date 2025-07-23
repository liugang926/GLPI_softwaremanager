<?php
/**
 * Software Manager Plugin for GLPI
 * Compliance Scan History Page
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

include('../../../inc/includes.php');

// Check rights - allow access for authenticated users
if (!Session::getLoginUserID()) {
    Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
    exit();
}

// Start page
Html::header(__('Compliance Scan History', 'softwaremanager'), $_SERVER['PHP_SELF'], 'admin', 'PluginSoftwaremanagerMenu');

// Display navigation
PluginSoftwaremanagerMenu::displayNavigationHeader('scanhistory');

echo "<div class='center'>";
echo "<h2>" . __('Compliance Scan History', 'softwaremanager') . "</h2>";
echo "<p>" . __('This page will display the history of compliance scans.', 'softwaremanager') . "</p>";
echo "<p><em>" . __('Feature will be implemented in step 4.', 'softwaremanager') . "</em></p>";
echo "</div>";

Html::footer();
