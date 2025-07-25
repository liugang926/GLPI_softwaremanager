<?php
/**
 * Software Manager Plugin for GLPI
 * Reinstall Scan Tables Script
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

// Include GLPI
include('../../../inc/includes.php');

// Check if user is logged in and has admin rights
if (!Session::getLoginUserID()) {
    die("Please log in to GLPI first.");
}

// Include required classes
include_once('inc/scanhistory.class.php');
include_once('inc/scanresult.class.php');

echo "<h2>Software Manager - Installing Scan Tables</h2>";

try {
    // Initialize migration
    $migration = new Migration('1.0.0');
    
    echo "<p>Installing scan history table...</p>";
    PluginSoftwaremanagerScanhistory::install($migration);
    
    echo "<p>Installing scan results table...</p>";
    PluginSoftwaremanagerScanresult::install($migration);
    
    echo "<p>Executing migration...</p>";
    $migration->executeMigration();
    
    echo "<div style='color: green; font-weight: bold;'>";
    echo "<p>✓ Scan tables installed successfully!</p>";
    echo "</div>";
    
    echo "<p><a href='front/softwarelist.php'>Go to Software List</a></p>";
    echo "<p><a href='front/scanhistory.php'>Go to Scan History</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>";
    echo "<p>✗ Error installing scan tables: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
