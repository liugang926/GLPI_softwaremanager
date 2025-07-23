<?php
/**
 * Test batch delete with simulated POST data
 */

// Turn off error display
ini_set('display_errors', 0);
error_reporting(0);

// Set JSON response header first
header('Content-Type: application/json');

try {
    // GLPI includes - correct path for Y: drive
    $glpi_root = dirname(dirname(dirname(__DIR__)));
    include ($glpi_root . "/inc/includes.php");
    
    // Check authentication
    if (!Session::getLoginUserID()) {
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    // Simulate POST data for testing
    $_POST['action'] = 'batch_delete';
    $_POST['type'] = 'whitelist';
    $_POST['items'] = json_encode([1, 2]); // Test with some IDs
    
    // Now include the actual batch delete logic
    $action = $_POST['action'] ?? null;
    $type = $_POST['type'] ?? null;
    $items = $_POST['items'] ?? null;
    
    if ($items && is_string($items)) {
        $items = json_decode($items, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data for items');
        }
    }

    if ($action !== 'batch_delete') {
        throw new Exception('Invalid action');
    }

    if (!in_array($type, ['whitelist', 'blacklist'])) {
        throw new Exception('Invalid type');
    }

    if (empty($items) || !is_array($items)) {
        throw new Exception('No items selected');
    }

    // Load the appropriate class
    if ($type === 'whitelist') {
        include_once(__DIR__ . '/../inc/softwarewhitelist.class.php');
        $obj = new PluginSoftwaremanagerSoftwareWhitelist();
    } else {
        include_once(__DIR__ . '/../inc/softwareblacklist.class.php');
        $obj = new PluginSoftwaremanagerSoftwareBlacklist();
    }
    
    global $DB;
    $table = $obj->getTable();
    
    // Check if table exists
    if (!$DB->tableExists($table)) {
        throw new Exception("Table $table does not exist");
    }
    
    $deleted_count = 0;
    $failed_count = 0;
    $results = [];
    
    // Process each item
    foreach ($items as $item_id) {
        $item_id = intval($item_id);
        
        if ($item_id <= 0) {
            $failed_count++;
            $results[] = [
                'id' => $item_id,
                'status' => 'error',
                'message' => 'Invalid item ID'
            ];
            continue;
        }
        
        // Try to delete the item using direct database query
        try {
            // First check if item exists
            $query = "SELECT id FROM `$table` WHERE id = " . intval($item_id);
            $result = $DB->query($query);

            if (!$result || $DB->numrows($result) == 0) {
                $failed_count++;
                $results[] = [
                    'id' => $item_id,
                    'status' => 'error',
                    'message' => 'Item not found'
                ];
                continue;
            }

            // Delete the item
            $delete_query = "DELETE FROM `$table` WHERE id = " . intval($item_id);
            $delete_result = $DB->query($delete_query);

            if ($delete_result && $DB->affectedRows() > 0) {
                $deleted_count++;
                $results[] = [
                    'id' => $item_id,
                    'status' => 'success',
                    'message' => 'Deleted successfully'
                ];
            } else {
                $failed_count++;
                $results[] = [
                    'id' => $item_id,
                    'status' => 'error',
                    'message' => 'Failed to delete - no rows affected'
                ];
            }
        } catch (Exception $e) {
            $failed_count++;
            $results[] = [
                'id' => $item_id,
                'status' => 'error',
                'message' => 'Delete error: ' . $e->getMessage()
            ];
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'deleted_count' => $deleted_count,
        'failed_count' => $failed_count,
        'total_count' => count($items),
        'results' => $results,
        'table_name' => $table,
        'table_exists' => $DB->tableExists($table),
        'debug_info' => [
            'action' => $action,
            'type' => $type,
            'items_received' => $items
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
