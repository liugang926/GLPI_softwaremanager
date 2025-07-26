<?php
/**
 * Debug script to test scanning data
 */

include("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");

try {
    global $DB;
    
    echo "<h3>Debug Scan Data</h3>";
    
    // Test basic software query
    $software_query = "
        SELECT 
            s.id as software_id,
            s.name as software_name,
            sv.name as software_version,
            isv.date_install,
            c.id as computer_id,
            c.name as computer_name,
            c.serial as computer_serial,
            u.id as user_id,
            u.name as user_name,
            u.realname as user_realname,
            e.name as entity_name
        FROM `glpi_softwares` s
        INNER JOIN `glpi_softwareversions` sv ON (sv.softwares_id = s.id)
        INNER JOIN `glpi_items_softwareversions` isv ON (
            isv.softwareversions_id = sv.id
            AND isv.itemtype = \"Computer\"
            AND isv.is_deleted = 0
        )
        INNER JOIN `glpi_computers` c ON (
            c.id = isv.items_id
            AND c.is_deleted = 0
            AND c.is_template = 0
        )
        LEFT JOIN `glpi_users` u ON (c.users_id = u.id)
        LEFT JOIN `glpi_entities` e ON (c.entities_id = e.id)
        WHERE s.is_deleted = 0 AND sv.is_deleted = 0
        LIMIT 10
    ";
    
    echo "<h4>Software Installation Query Test</h4>";
    $result = $DB->query($software_query);
    if ($result) {
        $count = $DB->numrows($result);
        echo "<p><strong>Found {$count} software installations</strong></p>";
        
        if ($count > 0) {
            echo "<table border=\"1\" style=\"border-collapse: collapse;\">";
            echo "<tr><th>Software</th><th>Version</th><th>Computer</th><th>User</th></tr>";
            
            while ($row = $DB->fetchAssoc($result)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["software_name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["software_version"] ?? "N/A") . "</td>";
                echo "<td>" . htmlspecialchars($row["computer_name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["user_name"] ?? "No user") . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p><strong>Query failed:</strong> " . $DB->error() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
