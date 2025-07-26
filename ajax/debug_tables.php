<?php
/**
 * Debug script to check database tables and data
 */

include('../../../inc/includes.php');

// Set JSON response header
header('Content-Type: application/json; charset=UTF-8');

try {
    global $DB;
    
    // Check table existence
    $tables_to_check = [
        'glpi_softwares',
        'glpi_softwareinstalls', 
        'glpi_computers',
        'glpi_users',
        'glpi_entities',
        'glpi_plugin_softwaremanager_whitelists',
        'glpi_plugin_softwaremanager_blacklists',
        'glpi_plugin_softwaremanager_scanhistory'
    ];
    
    $table_info = [];
    foreach ($tables_to_check as $table) {
        $exists = $DB->tableExists($table);
        $count = 0;
        if ($exists) {
            $result = $DB->query("SELECT COUNT(*) as count FROM `$table`");
            if ($result && $row = $DB->fetchAssoc($result)) {
                $count = $row['count'];
            }
        }
        $table_info[$table] = [
            'exists' => $exists,
            'count' => $count
        ];
    }
    
    // Test the software query
    $software_query = "SELECT s.name as software_name, 
                       s.version as software_version,
                       si.date_install,
                       c.name as computer_name,
                       c.serial as computer_serial,
                       u.name as user_name,
                       u.realname as user_realname,
                       e.name as entity_name
                       FROM `glpi_softwares` s
                       INNER JOIN `glpi_softwareinstalls` si ON s.id = si.softwares_id
                       INNER JOIN `glpi_computers` c ON si.computers_id = c.id
                       LEFT JOIN `glpi_users` u ON c.users_id = u.id
                       LEFT JOIN `glpi_entities` e ON c.entities_id = e.id
                       WHERE s.is_deleted = 0 AND c.is_deleted = 0
                       LIMIT 5";
    
    $test_result = $DB->query($software_query);
    $sample_data = [];
    if ($test_result) {
        $row_count = $DB->numrows($test_result);
        while ($row = $DB->fetchAssoc($test_result)) {
            $sample_data[] = $row;
        }
    } else {
        $row_count = 0;
        $sample_data = ['error' => $DB->error()];
    }
    
    echo json_encode([
        'success' => true,
        'tables' => $table_info,
        'software_query_result' => [
            'row_count' => $row_count,
            'sample_data' => $sample_data
        ],
        'query' => $software_query
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>