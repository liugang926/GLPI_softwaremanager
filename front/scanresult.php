<?php
/**
 * Software Manager Plugin for GLPI
 * Scan Result Details Page
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

include('../../../inc/includes.php');

// Check rights - using standard GLPI permissions
Session::checkRight('config', READ);

// Check if plugin is activated
$plugin = new Plugin();
if (!$plugin->isInstalled('softwaremanager') || !$plugin->isActivated('softwaremanager')) {
    Html::displayNotFoundError();
}

// Get scan history ID from parameters
$scanhistory_id = isset($_GET['scanhistory_id']) ? intval($_GET['scanhistory_id']) : 0;

if (!$scanhistory_id) {
    Html::displayErrorAndDie(__('Invalid scan history ID', 'softwaremanager'));
}

// Load scan history record
$scanhistory = new PluginSoftwaremanagerScanhistory();
if (!$scanhistory->getFromDB($scanhistory_id)) {
    Html::displayErrorAndDie(__('Scan history record not found', 'softwaremanager'));
}

Html::header(__('Scan Results', 'softwaremanager'), $_SERVER['PHP_SELF'], 'admin', 'PluginSoftwaremanagerMenu');

// Display navigation
PluginSoftwaremanagerMenu::displayNavigationHeader('scanhistory');

// Display scan summary
echo "<div class='scan-summary' style='margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;'>";
echo "<h2>" . __('Scan Summary', 'softwaremanager') . "</h2>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<table class='table table-borderless'>";
echo "<tr><td><strong>" . __('Scan Date:', 'softwaremanager') . "</strong></td><td>" . Html::convDateTime($scanhistory->fields['scan_date']) . "</td></tr>";
echo "<tr><td><strong>" . __('Status:', 'softwaremanager') . "</strong></td><td>" . $scanhistory->fields['status'] . "</td></tr>";
echo "<tr><td><strong>" . __('Duration:', 'softwaremanager') . "</strong></td><td>" . ($scanhistory->fields['scan_duration'] ?? 'N/A') . " " . __('seconds', 'softwaremanager') . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<table class='table table-borderless'>";
echo "<tr><td><strong>" . __('Total Software:', 'softwaremanager') . "</strong></td><td>" . $scanhistory->fields['total_software'] . "</td></tr>";
echo "<tr><td><strong>" . __('Whitelist Count:', 'softwaremanager') . "</strong></td><td><span class='badge badge-success'>" . $scanhistory->fields['whitelist_count'] . "</span></td></tr>";
echo "<tr><td><strong>" . __('Blacklist Violations:', 'softwaremanager') . "</strong></td><td><span class='badge badge-danger'>" . $scanhistory->fields['blacklist_count'] . "</span></td></tr>";
echo "<tr><td><strong>" . __('Unmanaged Software:', 'softwaremanager') . "</strong></td><td><span class='badge badge-warning'>" . $scanhistory->fields['unmanaged_count'] . "</span></td></tr>";
echo "</table>";
echo "</div>";
echo "</div>";
echo "</div>";

// Get scan results
$blacklist_results = PluginSoftwaremanagerScanresult::getResultsForHistory($scanhistory_id, 'blacklist');
$unmanaged_results = PluginSoftwaremanagerScanresult::getResultsForHistory($scanhistory_id, 'unmanaged');

// Display results in tabs
echo "<div class='scan-results'>";
echo "<h2>" . __('Detailed Results', 'softwaremanager') . "</h2>";

// Tab navigation
echo "<ul class='nav nav-tabs' role='tablist'>";
echo "<li class='nav-item'>";
echo "<a class='nav-link active' id='blacklist-tab' data-toggle='tab' href='#blacklist' role='tab'>";
echo __('Blacklist Violations', 'softwaremanager') . " <span class='badge badge-danger'>" . count($blacklist_results) . "</span>";
echo "</a>";
echo "</li>";
echo "<li class='nav-item'>";
echo "<a class='nav-link' id='unmanaged-tab' data-toggle='tab' href='#unmanaged' role='tab'>";
echo __('Unmanaged Software', 'softwaremanager') . " <span class='badge badge-warning'>" . count($unmanaged_results) . "</span>";
echo "</a>";
echo "</li>";
echo "</ul>";

// Tab content
echo "<div class='tab-content'>";

// Blacklist violations tab
echo "<div class='tab-pane fade show active' id='blacklist' role='tabpanel'>";
echo "<div style='margin-top: 15px;'>";
if (count($blacklist_results) > 0) {
    displayResultsTable($blacklist_results, 'blacklist');
} else {
    echo "<div class='alert alert-success'>";
    echo "<i class='fas fa-check-circle'></i> " . __('No blacklist violations found!', 'softwaremanager');
    echo "</div>";
}
echo "</div>";
echo "</div>";

// Unmanaged software tab
echo "<div class='tab-pane fade' id='unmanaged' role='tabpanel'>";
echo "<div style='margin-top: 15px;'>";
if (count($unmanaged_results) > 0) {
    displayResultsTable($unmanaged_results, 'unmanaged');
} else {
    echo "<div class='alert alert-success'>";
    echo "<i class='fas fa-check-circle'></i> " . __('No unmanaged software found!', 'softwaremanager');
    echo "</div>";
}
echo "</div>";
echo "</div>";

echo "</div>"; // tab-content
echo "</div>"; // scan-results

/**
 * Display results table
 */
function displayResultsTable($results, $type) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped table-hover'>";
    echo "<thead class='thead-dark'>";
    echo "<tr>";
    echo "<th>" . __('Software Name', 'softwaremanager') . "</th>";
    echo "<th>" . __('Version', 'softwaremanager') . "</th>";
    echo "<th>" . __('Computer', 'softwaremanager') . "</th>";
    echo "<th>" . __('User', 'softwaremanager') . "</th>";
    echo "<th>" . __('Date Found', 'softwaremanager') . "</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($results as $result) {
        echo "<tr>";
        echo "<td><strong>" . Html::cleanInputText($result['software_name']) . "</strong></td>";
        echo "<td>" . Html::cleanInputText($result['software_version'] ?? 'N/A') . "</td>";
        echo "<td>" . Html::cleanInputText($result['computer_name']) . "</td>";
        echo "<td>" . Html::cleanInputText($result['user_name'] ?? 'N/A') . "</td>";
        echo "<td>" . Html::convDateTime($result['date_found']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    
    // Export functionality
    echo "<div style='margin-top: 15px;'>";
    echo "<button type='button' class='btn btn-secondary' onclick='exportResults(\"" . $type . "\")'>";
    echo "<i class='fas fa-download'></i> " . __('Export to CSV', 'softwaremanager');
    echo "</button>";
    echo "</div>";
}

?>

<script type='text/javascript'>
// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle tab clicks
    var tabLinks = document.querySelectorAll('.nav-link');
    tabLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and panes
            document.querySelectorAll('.nav-link').forEach(function(l) { l.classList.remove('active'); });
            document.querySelectorAll('.tab-pane').forEach(function(p) { p.classList.remove('show', 'active'); });
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding pane
            var targetId = this.getAttribute('href').substring(1);
            var targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
});

function exportResults(type) {
    // Simple CSV export functionality
    var table = document.querySelector('#' + type + ' table');
    if (!table) return;
    
    var csv = [];
    var rows = table.querySelectorAll('tr');
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV
    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'scan_results_' + type + '_<?php echo date('Y-m-d_H-i-s'); ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<style>
.scan-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid #28a745;
}

.nav-tabs .nav-link {
    color: #495057;
    border: 1px solid transparent;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.badge {
    font-size: 0.75em;
    padding: 0.25em 0.4em;
    border-radius: 0.25rem;
}

.badge-success { background-color: #28a745; }
.badge-danger { background-color: #dc3545; }
.badge-warning { background-color: #ffc107; color: #212529; }

.table th {
    background-color: #343a40;
    color: white;
    border-color: #454d55;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}
</style>

<?php
Html::footer();
?>
