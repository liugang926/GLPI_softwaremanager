<?php
/**
 * Simple debug scan that saves results to a log file
 */

include('../../../inc/includes.php');

// Set JSON response header first
header('Content-Type: application/json; charset=UTF-8');

try {
    // Create log file
    $log_file = __DIR__ . '/scan_debug.log';
    $log_content = "=== Scan Debug Log - " . date('Y-m-d H:i:s') . " ===\n";
    
    // Check user login
    if (!Session::getLoginUserID()) {
        $log_content .= "ERROR: User not logged in\n";
        file_put_contents($log_file, $log_content);
        echo json_encode(['success' => false, 'error' => 'User not logged in', 'log_file' => $log_file]);
        exit;
    }
    
    $user_id = Session::getLoginUserID();
    $log_content .= "User ID: $user_id\n";
    
    global $DB;
    if (!$DB) {
        $log_content .= "ERROR: Database not available\n";
        file_put_contents($log_file, $log_content);
        echo json_encode(['success' => false, 'error' => 'Database not available', 'log_file' => $log_file]);
        exit;
    }
    
    $log_content .= "Database connection: OK\n";
    
    // Test simple query first
    $test_query = "SELECT 1 as test";
    $result = $DB->query($test_query);
    if ($result) {
        $log_content .= "Simple query test: PASSED\n";
    } else {
        $log_content .= "Simple query test: FAILED - " . $DB->error() . "\n";
    }
    
    // Check if scan history table exists
    $table_check = "SHOW TABLES LIKE 'glpi_plugin_softwaremanager_scanhistory'";
    $result = $DB->query($table_check);
    if ($result && $DB->numrows($result) > 0) {
        $log_content .= "Scan history table: EXISTS\n";
    } else {
        $log_content .= "Scan history table: NOT FOUND\n";
    }
    
    // Try to insert a simple test record
    $scan_time = date('Y-m-d H:i:s');
    $simple_insert = "INSERT INTO `glpi_plugin_softwaremanager_scanhistory` 
                      (`user_id`, `scan_date`, `total_software`, `whitelist_count`, `blacklist_count`, `unmanaged_count`, `status`) 
                      VALUES ($user_id, '$scan_time', 999, 0, 0, 999, 'test')";
    
    $log_content .= "Insert query: $simple_insert\n";
    
    $result = $DB->query($simple_insert);
    if ($result) {
        $scan_id = $DB->insertId();
        $log_content .= "Insert result: SUCCESS - ID: $scan_id\n";
        
        // Verify the insert
        $verify_query = "SELECT * FROM `glpi_plugin_softwaremanager_scanhistory` WHERE id = $scan_id";
        $verify_result = $DB->query($verify_query);
        if ($verify_result && $row = $DB->fetchAssoc($verify_result)) {
            $log_content .= "Verification: SUCCESS\n";
            $log_content .= "Record: " . json_encode($row) . "\n";
        } else {
            $log_content .= "Verification: FAILED\n";
        }
    } else {
        $log_content .= "Insert result: FAILED - " . $DB->error() . "\n";
    }
    
    // Save log
    file_put_contents($log_file, $log_content);
    
    echo json_encode([
        'success' => true,
        'message' => 'Debug completed - check log file',
        'log_file' => $log_file,
        'scan_id' => $scan_id ?? null
    ]);
    
} catch (Exception $e) {
    $log_content .= "EXCEPTION: " . $e->getMessage() . "\n";
    $log_content .= "TRACE: " . $e->getTraceAsString() . "\n";
    file_put_contents($log_file ?? (__DIR__ . '/scan_debug.log'), $log_content);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'log_file' => $log_file ?? (__DIR__ . '/scan_debug.log')
    ]);
}
?>