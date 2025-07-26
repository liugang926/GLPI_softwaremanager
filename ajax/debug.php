<?php
/**
 * Debug AJAX endpoint to test database connectivity and table existence
 */

include('../../../inc/includes.php');

// Check user login
Session::checkLoginUser();

header('Content-Type: application/json');

try {
    global $DB;
    
    $debug_info = [
        'success' => true,
        'message' => 'Debug information',
        'user_id' => Session::getLoginUserID(),
        'user_name' => $_SESSION['glpiname'] ?? 'unknown',
        'time' => date('Y-m-d H:i:s'),
        'tables' => []
    ];
    
    // Check if tables exist
    $tables_to_check = [
        'glpi_plugin_softwaremanager_scanhistory',
        'glpi_plugin_softwaremanager_whitelists', 
        'glpi_plugin_softwaremanager_blacklists',
        'glpi_softwares'
    ];
    
    foreach ($tables_to_check as $table) {
        $exists = $DB->tableExists($table);
        $debug_info['tables'][$table] = [
            'exists' => $exists,
            'count' => 0
        ];
        
        if ($exists) {
            $result = $DB->query("SELECT COUNT(*) as count FROM `$table`");
            if ($result && $row = $DB->fetchAssoc($result)) {
                $debug_info['tables'][$table]['count'] = (int)$row['count'];
            }
        }
    }
    
    // Test basic database connectivity
    $test_query = "SELECT VERSION() as version";
    $result = $DB->query($test_query);
    if ($result && $row = $DB->fetchAssoc($result)) {
        $debug_info['database_version'] = $row['version'];
    }
    
    echo json_encode($debug_info, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Debug error: ' . $e->getMessage()
    ]);
}
?>
