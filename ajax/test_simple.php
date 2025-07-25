<?php
/**
 * Simple test endpoint
 */

include('../../../inc/includes.php');

// Simple HTML response
echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
echo "<h3 style='color: green;'>✅ Test Successful!</h3>";
echo "<p>AJAX endpoint is working correctly.</p>";
echo "<p>User ID: " . Session::getLoginUserID() . "</p>";
echo "<p>User Name: " . $_SESSION['glpiname'] . "</p>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";
?>
