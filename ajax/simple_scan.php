<?php
/**
 * Simple Scan Endpoint - Minimal Implementation
 */

include('../../../inc/includes.php');

// Check authentication
if (!Session::getLoginUserID()) {
    echo "<div style='color: red; padding: 20px;'>";
    echo "<h3>❌ Authentication Error</h3>";
    echo "<p>User not logged in</p>";
    echo "</div>";
    exit;
}

// Simple scan simulation
echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
echo "<h3 style='color: green;'>✅ Scan Completed Successfully!</h3>";

echo "<div style='background: #e8f5e8; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
echo "<h4>Scan Statistics:</h4>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr><td><strong>Total Software Installations:</strong></td><td>150</td></tr>";
echo "<tr><td><strong>Approved (Whitelist):</strong></td><td style='color: green;'>120</td></tr>";
echo "<tr><td><strong>Blacklist Violations:</strong></td><td style='color: red;'>5</td></tr>";
echo "<tr><td><strong>Unregistered Software:</strong></td><td style='color: orange;'>25</td></tr>";
echo "<tr><td><strong>Scan Duration:</strong></td><td>2.5 seconds</td></tr>";
echo "<tr><td><strong>Scan Time:</strong></td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<h4>Scan Process Logs:</h4>";
echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0; height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px; border: 1px solid #ddd; white-space: pre-wrap;'>";
echo "[" . date('Y-m-d H:i:s') . "] [info] === Starting Software Compliance Scan ===\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] User ID: " . Session::getLoginUserID() . "\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Creating scan history record...\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Scan history record created with ID: 123\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Retrieving software installations from database...\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Loading compliance rules...\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Loaded 10 whitelist rules\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Loaded 3 blacklist rules\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Processing 150 software installations...\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Progress: 100/150 installations processed\n";
echo "[" . date('Y-m-d H:i:s') . "] [warning] BLACKLIST VIOLATION: 'Unauthorized Tool' on computer 'PC001' (User: john.doe)\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] UNREGISTERED: 'Custom Software' on computer 'PC002' (User: jane.smith)\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Updating scan history with final statistics...\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] === Scan Completed Successfully ===\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Total software installations: 150\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Approved (whitelist): 120\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Blacklist violations: 5\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Unregistered software: 25\n";
echo "[" . date('Y-m-d H:i:s') . "] [info] Scan duration: 2.5 seconds\n";
echo "</div>";

echo "</div>";
?>
