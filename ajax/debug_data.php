<?php
/**
 * Debug endpoint to show raw scan history data
 */

include('../../../inc/includes.php');

// Check user login
Session::checkLoginUser();

header('Content-Type: application/json');

try {
    global $DB;
    
    // Get all records from scan history
    $query = "SELECT * FROM `glpi_plugin_softwaremanager_scanhistory` ORDER BY `scan_date` DESC LIMIT 10";
    $result = $DB->query($query);
    
    $records = [];
    if ($result) {
        while ($row = $DB->fetchAssoc($result)) {
            $records[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Raw scan history data',
        'count' => count($records),
        'records' => $records
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Debug error: ' . $e->getMessage()
    ]);
}
?>