<?php
/**
 * Software Manager Plugin for GLPI
 * Run Compliance Scan AJAX Interface
 *
 * @author  Abner Liu
 * @license GPL-2.0+
 */

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't display errors to prevent HTML output
ini_set('log_errors', 1);      // Log errors instead

// Start output buffering to catch any unexpected output
ob_start();

include('../../../inc/includes.php');

// Clear any unexpected output
ob_clean();

// Set JSON headers for API response
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// 2.1 使用与 software_details.php 相同的简单登录检查
if (!Session::getLoginUserID()) {
    // 如果用户未登录，返回一个标准的JSON错误并退出
    ob_clean(); // Clear output buffer before response
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => '用户未登录 (User not logged in)']);
    exit;
}

// Global variable to store scan logs for real-time display
$scan_logs = [];

/**
 * Log scan events with timestamp and level
 */
function logScanEvent($level, $message) {
    global $scan_logs;

    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$level}] {$message}";

    $scan_logs[] = $log_entry;

    // Also log to GLPI log file for debugging
    Toolbox::logInFile('plugin_softwaremanager_scan', $log_entry);
}

/**
 * Get all scan logs
 */
function getScanLogs() {
    global $scan_logs;
    return $scan_logs;
}

// 2.2 正确处理 CSRF 令牌 (这是解决"请求不允许"的关键！)
// 我们需要检查从前端POST过来的令牌，而不是临时禁用它
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['_glpi_csrf_token']) || !Session::validateCSRF($_POST['_glpi_csrf_token'])) {
        // 如果令牌不存在或无效，返回JSON错误
        ob_clean();
        http_response_code(403); // Forbidden
        echo json_encode(['error' => '无效的安全令牌 (Invalid CSRF token). 请求不允许 (Request not allowed).']);
        exit;
    }
}

try {
    logScanEvent('info', '=== Starting Software Compliance Scan ===');
    logScanEvent('info', 'User ID: ' . Session::getLoginUserID());

    // Start scan timing
    $scan_start_time = microtime(true);

    // Create new scan history record
    logScanEvent('info', 'Creating scan history record...');
    $scanhistory = new PluginSoftwaremanagerScanhistory();
    $scan_id = $scanhistory->createScanRecord(Session::getLoginUserID());

    if (!$scan_id) {
        throw new Exception('Failed to create scan history record');
    }
    logScanEvent('info', "Scan history record created with ID: {$scan_id}");

    // Initialize scan statistics
    $stats = [
        'total_software' => 0,
        'whitelist_count' => 0,
        'blacklist_count' => 0,
        'unmanaged_count' => 0
    ];

    // Get all software installations with computer and user information
    logScanEvent('info', 'Retrieving software installations from database...');
    $software_installations = getSoftwareInstallations();

    if (empty($software_installations)) {
        logScanEvent('warning', 'No software installations found in database');
        throw new Exception('No software installations found');
    }

    // Load compliance rules
    logScanEvent('info', 'Loading compliance rules...');
    $whitelist_rules = getWhitelistRules();
    $blacklist_rules = getBlacklistRules();

    logScanEvent('info', "Loaded " . count($whitelist_rules) . " whitelist rules");
    logScanEvent('info', "Loaded " . count($blacklist_rules) . " blacklist rules");
    
    // Process each software installation
    logScanEvent('info', "Processing " . count($software_installations) . " software installations...");
    $scanresult = new PluginSoftwaremanagerScanresult();

    $processed = 0;
    foreach ($software_installations as $installation) {
        $stats['total_software']++;
        $processed++;

        $software_name = $installation['software_name'];
        $software_version = $installation['software_version'] ?? '';
        $computer_id = $installation['computer_id'];
        $computer_name = $installation['computer_name'];
        $user_id = $installation['user_id'] ?? 0;
        $user_name = $installation['user_name'] ?? '';

        // Log progress every 100 items
        if ($processed % 100 == 0) {
            logScanEvent('info', "Progress: {$processed}/" . count($software_installations) . " installations processed");
        }

        // Check against blacklist first
        if (isInBlacklist($software_name, $blacklist_rules)) {
            $stats['blacklist_count']++;
            logScanEvent('warning', "BLACKLIST VIOLATION: '{$software_name}' on computer '{$computer_name}' (User: {$user_name})");
            $scanresult->addBlacklistRecord(
                $scan_id, $software_name, $computer_id, $user_id,
                $computer_name, $user_name, $software_version
            );
        }
        // Check against whitelist
        elseif (isInWhitelist($software_name, $whitelist_rules)) {
            $stats['whitelist_count']++;
            // Only log first few whitelist items to avoid spam
            if ($stats['whitelist_count'] <= 5) {
                logScanEvent('debug', "APPROVED: '{$software_name}' on computer '{$computer_name}'");
            }
        }
        // Unmanaged software
        else {
            $stats['unmanaged_count']++;
            logScanEvent('info', "UNREGISTERED: '{$software_name}' on computer '{$computer_name}' (User: {$user_name})");
            $scanresult->addUnregisteredRecord(
                $scan_id, $software_name, $computer_id, $user_id,
                $computer_name, $user_name, $software_version
            );
        }
    }
    
    // Calculate scan duration
    $scan_duration = round(microtime(true) - $scan_start_time, 2);

    logScanEvent('info', 'Updating scan history with final statistics...');
    // Update scan history with final statistics
    $scanhistory->updateScanRecord($scan_id, $stats, $scan_duration);

    logScanEvent('info', '=== Scan Completed Successfully ===');
    logScanEvent('info', "Total software installations: {$stats['total_software']}");
    logScanEvent('info', "Approved (whitelist): {$stats['whitelist_count']}");
    logScanEvent('info', "Blacklist violations: {$stats['blacklist_count']}");
    logScanEvent('info', "Unregistered software: {$stats['unmanaged_count']}");
    logScanEvent('info', "Scan duration: {$scan_duration} seconds");

    // Display HTML results
    echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
    echo "<h3 style='color: green;'>✅ Scan Completed Successfully!</h3>";

    echo "<div style='background: #e8f5e8; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<h4>Scan Statistics:</h4>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><td><strong>Total Software Installations:</strong></td><td>{$stats['total_software']}</td></tr>";
    echo "<tr><td><strong>Approved (Whitelist):</strong></td><td style='color: green;'>{$stats['whitelist_count']}</td></tr>";
    echo "<tr><td><strong>Blacklist Violations:</strong></td><td style='color: red;'>{$stats['blacklist_count']}</td></tr>";
    echo "<tr><td><strong>Unregistered Software:</strong></td><td style='color: orange;'>{$stats['unmanaged_count']}</td></tr>";
    echo "<tr><td><strong>Scan Duration:</strong></td><td>{$scan_duration} seconds</td></tr>";
    echo "<tr><td><strong>Scan Time:</strong></td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
    echo "</table>";
    echo "</div>";

    // Display logs
    $logs = getScanLogs();
    if (!empty($logs)) {
        echo "<h4>Scan Process Logs:</h4>";
        echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0; height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; border: 1px solid #ddd; white-space: pre-wrap;'>";
        echo implode("\n", $logs);
        echo "</div>";
    }

    echo "<div style='text-align: center; margin-top: 20px;'>";
    echo "<button onclick='window.location.reload()' style='padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;'>";
    echo "Refresh Page";
    echo "</button>";
    echo "</div>";
    echo "</div>";

} catch (Exception $e) {
    logScanEvent('error', 'SCAN FAILED: ' . $e->getMessage());
    logScanEvent('error', 'Stack trace: ' . $e->getTraceAsString());

    // Log error for debugging
    Toolbox::logInFile('softwaremanager', 'Scan failed: ' . $e->getMessage());

    // Display HTML error
    echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
    echo "<h3 style='color: red;'>❌ Scan Failed</h3>";

    echo "<div style='background: #ffe6e6; padding: 15px; margin: 15px 0; border-radius: 5px; border: 1px solid #ffb3b3;'>";
    echo "<strong>Error Message:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";

    // Display error logs
    $logs = getScanLogs();
    if (!empty($logs)) {
        echo "<h4>Error Logs:</h4>";
        echo "<div style='background: #fff5f5; padding: 10px; margin: 10px 0; height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; border: 1px solid #fbb; white-space: pre-wrap; color: #d00;'>";
        echo implode("\n", $logs);
        echo "</div>";
    }

    echo "<div style='text-align: center; margin-top: 20px;'>";
    echo "<button onclick='window.location.reload()' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;'>";
    echo "Refresh Page";
    echo "</button>";
    echo "</div>";
    echo "</div>";
}

/**
 * Get all software installations with computer and user information
 * Using GLPI standard request method - same as softwarelist.php
 */
function getSoftwareInstallations() {
    global $DB;

    // Log scan start
    logScanEvent('info', 'Starting software installations query');

    try {
        // Use GLPI standard request method - same as software list
        $scan_query = [
            'SELECT' => [
                'c.id AS computer_id',
                'c.name AS computer_name',
                'c.users_id AS user_id',
                'u.name AS username',
                'u.realname AS user_realname',
                'u.firstname AS user_firstname',
                's.id AS software_id',
                's.name AS software_name',
                'sv.name AS software_version',
                'isv.date_install'
            ],
            'FROM' => 'glpi_items_softwareversions AS isv',
            'INNER JOIN' => [
                'glpi_computers AS c' => [
                    'ON' => [
                        'isv' => 'items_id',
                        'c' => 'id'
                    ]
                ],
                'glpi_softwareversions AS sv' => [
                    'ON' => [
                        'isv' => 'softwareversions_id',
                        'sv' => 'id'
                    ]
                ],
                'glpi_softwares AS s' => [
                    'ON' => [
                        'sv' => 'softwares_id',
                        's' => 'id'
                    ]
                ]
            ],
            'LEFT JOIN' => [
                'glpi_users AS u' => [
                    'ON' => [
                        'c' => 'users_id',
                        'u' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'isv.itemtype' => 'Computer',
                'isv.is_deleted' => 0,
                'c.is_deleted' => 0,
                'c.is_template' => 0,
                's.is_deleted' => 0
            ],
            'ORDER' => ['c.name', 's.name'],
            'LIMIT' => 1000  // Add reasonable limit
        ];

        logScanEvent('debug', 'Executing GLPI request query');

        $result = $DB->request($scan_query);
        $installations = [];

        $count = 0;
        foreach ($result as $row) {
            $installations[] = $row;
            $count++;
        }

        logScanEvent('info', "Found {$count} software installations");
        return $installations;

    } catch (Exception $e) {
        logScanEvent('error', 'Database query failed: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Get whitelist rules - using GLPI standard request method
 */
function getWhitelistRules() {
    global $DB;

    try {
        $result = $DB->request([
            'SELECT' => ['name'],
            'FROM' => 'glpi_plugin_softwaremanager_whitelists',
            'WHERE' => ['is_deleted' => 0]
        ]);

        $rules = [];
        foreach ($result as $row) {
            $rules[] = strtolower(trim($row['name']));
        }

        return $rules;
    } catch (Exception $e) {
        logScanEvent('error', 'Failed to load whitelist rules: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get blacklist rules - using GLPI standard request method
 */
function getBlacklistRules() {
    global $DB;

    try {
        $result = $DB->request([
            'SELECT' => ['name'],
            'FROM' => 'glpi_plugin_softwaremanager_blacklists',
            'WHERE' => ['is_deleted' => 0]
        ]);

        $rules = [];
        foreach ($result as $row) {
            $rules[] = strtolower(trim($row['name']));
        }

        return $rules;
    } catch (Exception $e) {
        logScanEvent('error', 'Failed to load blacklist rules: ' . $e->getMessage());
        return [];
    }
}

/**
 * Check if software is in whitelist
 */
function isInWhitelist($software_name, $whitelist_rules) {
    $software_name_lower = strtolower(trim($software_name));
    
    foreach ($whitelist_rules as $rule) {
        if (strpos($software_name_lower, $rule) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if software is in blacklist
 */
function isInBlacklist($software_name, $blacklist_rules) {
    $software_name_lower = strtolower(trim($software_name));
    
    foreach ($blacklist_rules as $rule) {
        if (strpos($software_name_lower, $rule) !== false) {
            return true;
        }
    }
    
    return false;
}

exit;
?>
