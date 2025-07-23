<?php
/**
 * Software Manager Plugin for GLPI
 * Main Software Inventory List Page
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

include('../../../inc/includes.php');

// Check rights - temporarily allow authenticated users, will add proper rights later
if (!Session::getLoginUserID()) {
    Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
    exit();
}

Html::header(__('Software Manager', 'softwaremanager'), $_SERVER['PHP_SELF'], 'admin', 'PluginSoftwaremanagerMenu');

// Display navigation
PluginSoftwaremanagerMenu::displayNavigationHeader('softwarelist');

// Handle whitelist/blacklist actions
if (isset($_POST['action'])) {
    // 添加CSRF安全检查
    Session::checkCSRF($_POST);
    
    // Handle single software action
    if (isset($_POST['software_name'])) {
        $software_name = Html::cleanInputText($_POST['software_name']);
        
        switch ($_POST['action']) {
            case 'add_to_whitelist':
                if (PluginSoftwaremanagerSoftwareWhitelist::addToList($software_name)) {
                    // Success message handled by the addToList method
                }
                break;
            case 'add_to_blacklist':
                if (PluginSoftwaremanagerSoftwareBlacklist::addToList($software_name)) {
                    // Success message handled by the addToList method
                }
                break;
        }
    }
    
    // Handle batch operations
    if (isset($_POST['software_names']) && is_array($_POST['software_names'])) {
        $software_names = $_POST['software_names'];
        $success_count = 0;
        $total_count = count($software_names);
        
        foreach ($software_names as $software_name) {
            $software_name = Html::cleanInputText(trim($software_name));
            if (!empty($software_name)) {
                                 switch ($_POST['action']) {
                     case 'batch_add_to_whitelist':
                         if (PluginSoftwaremanagerSoftwareWhitelist::addToList($software_name, 'Batch added')) {
                             $success_count++;
                         }
                         break;
                     case 'batch_add_to_blacklist':
                         if (PluginSoftwaremanagerSoftwareBlacklist::addToList($software_name, 'Batch added')) {
                             $success_count++;
                         }
                         break;
                }
            }
        }
        
        if ($success_count > 0) {
            $message = sprintf(__('%d out of %d software items processed successfully', 'softwaremanager'), $success_count, $total_count);
            Session::addMessageAfterRedirect($message, true);
        }
    }
    
    Html::redirect($_SERVER['PHP_SELF']);
}

// Get parameters for search and pagination
$search = isset($_GET['search']) ? Html::cleanInputText($_GET['search']) : '';
$manufacturer = isset($_GET['manufacturer']) ? intval($_GET['manufacturer']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

// Validate parameters
$valid_limits = [10, 25, 50, 100, 250];
if (!in_array($limit, $valid_limits)) {
    $limit = 50;
}

$start = ($page - 1) * $limit;

// Get software inventory data
$software_list = PluginSoftwaremanagerSoftwareInventory::getSoftwareInventory(
    $start, $limit, $search, $manufacturer, $status, $sort, $order
);

// Get total count for pagination
$total_count = PluginSoftwaremanagerSoftwareInventory::getSoftwareInventoryCount(
    $search, $manufacturer, $status
);

// Get dashboard statistics
$stats = PluginSoftwaremanagerSoftwareInventory::getDashboardStats();

// Calculate pagination
$total_pages = ceil($total_count / $limit);
$showing_start = min($start + 1, $total_count);
$showing_end = min($start + $limit, $total_count);

// Display dashboard statistics
echo "<div class='dashboard-stats'>";
echo "<div class='stats-container'>";

// Total software card
echo "<div class='stat-card stat-total clickable-stat' data-filter='all'>";
echo "<h3>" . $stats['total'] . "</h3>";
echo "<p>" . __('Total Software', 'softwaremanager') . "</p>";
echo "</div>";

// Whitelist card
echo "<div class='stat-card stat-whitelist clickable-stat' data-filter='whitelist'>";
echo "<h3>" . $stats['whitelist'] . "</h3>";
echo "<p>" . __('In Whitelist', 'softwaremanager') . "</p>";
echo "</div>";

// Blacklist card
echo "<div class='stat-card stat-blacklist clickable-stat' data-filter='blacklist'>";
echo "<h3>" . $stats['blacklist'] . "</h3>";
echo "<p>" . __('In Blacklist', 'softwaremanager') . "</p>";
echo "</div>";

// Unmanaged card
echo "<div class='stat-card stat-unmanaged clickable-stat' data-filter='unmanaged'>";
echo "<h3>" . $stats['unmanaged'] . "</h3>";
echo "<p>" . __('Unmanaged', 'softwaremanager') . "</p>";
echo "</div>";

echo "</div>"; // stats-container
echo "</div>"; // dashboard-stats

// Search and filter form
echo "<div class='search-form'>";
echo "<form method='GET' action='" . $_SERVER['PHP_SELF'] . "' id='search-form'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_1'>";

// Search field
echo "<td><label>" . __('Software Name') . ":</label></td>";
echo "<td>";
$search = isset($_GET['search']) ? Html::cleanInputText($_GET['search']) : '';
echo "<input type='text' id='search' name='search' value='$search' placeholder='" . __('Search software...', 'softwaremanager') . "' size='20'>";
echo "</td>";

// Manufacturer filter
echo "<td><label>" . __('Manufacturer') . ":</label></td>";
echo "<td>";
$manufacturer = isset($_GET['manufacturer']) ? intval($_GET['manufacturer']) : 0;
Manufacturer::dropdown([
    'name' => 'manufacturer',
    'value' => $manufacturer,
    'emptylabel' => __('All manufacturers'),
    'width' => '150px'
]);
echo "</td>";

// Status filter
echo "<td><label>" . __('Status') . ":</label></td>";
echo "<td>";
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$status_options = [
    'all' => __('All software'),
    'whitelist' => __('In Whitelist'),
    'blacklist' => __('In Blacklist'),
    'unmanaged' => __('Unmanaged')
];
Dropdown::showFromArray('status', $status_options, [
    'value' => $status,
    'width' => '120px'
]);
echo "</td>";

echo "</tr>";
echo "<tr class='tab_bg_1'>";

// Sort options
echo "<td><label>" . __('Sort by') . ":</label></td>";
echo "<td>";
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_options = [
    'name' => __('Software Name'),
    'manufacturer' => __('Manufacturer'),
    'computer_count' => __('Computer Count'),
    'date_creation' => __('Creation Date')
];
Dropdown::showFromArray('sort', $sort_options, [
    'value' => $sort,
    'width' => '120px'
]);
echo "</td>";

// Order options
echo "<td><label>" . __('Order') . ":</label></td>";
echo "<td>";
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$order_options = [
    'ASC' => __('Ascending'),
    'DESC' => __('Descending')
];
Dropdown::showFromArray('order', $order_options, [
    'value' => $order,
    'width' => '100px'
]);
echo "</td>";

// Items per page
echo "<td><label>" . __('Items per page') . ":</label></td>";
echo "<td>";
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$limit_options = [
    10 => '10',
    25 => '25', 
    50 => '50',
    100 => '100',
    250 => '250'
];
Dropdown::showFromArray('limit', $limit_options, [
    'value' => $limit,
    'width' => '80px'
]);
echo "</td>";

echo "</tr>";
echo "<tr class='tab_bg_1'>";
echo "<td colspan='6' class='center'>";
echo "<input type='submit' class='btn btn-primary' value='" . __('Search') . "'>";
echo " <a href='" . $_SERVER['PHP_SELF'] . "' class='btn btn-secondary'>" . __('Reset') . "</a>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</form>";
echo "</div>";

// Results summary
if ($total_count > 0) {
    echo "<div class='results-summary'>";
    echo "<p>" . sprintf(__('Showing %d-%d of %d software entries'), $showing_start, $showing_end, $total_count) . "</p>";
    echo "</div>";
}

// Software list table
if (count($software_list) > 0) {
    echo "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "' id='batch-form'>";
    // 添加CSRF安全令牌
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
    echo Html::hidden('action', ['value' => '', 'id' => 'batch_action']);
    
    echo "<table class='tab_cadre_fixehov'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th width='10'><input type='checkbox' id='select_all' title='Select All'></th>";
    echo "<th>" . __('Software Name', 'softwaremanager') . "</th>";
    echo "<th>" . __('Version', 'softwaremanager') . "</th>";
    echo "<th>" . __('Manufacturer', 'softwaremanager') . "</th>";
    echo "<th>" . __('Status', 'softwaremanager') . "</th>";
    echo "<th>" . __('Installed on Computers', 'softwaremanager') . "</th>";
    echo "<th>" . __('Actions', 'softwaremanager') . "</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($software_list as $software) {
        echo "<tr class='tab_bg_1'>";
        
        // Checkbox for batch operations
        echo "<td>";
        echo "<input type='checkbox' class='software_select' name='software_names[]' value='" . Html::cleanInputText($software['software_name']) . "'>";
        echo "</td>";
        
        // Make software name clickable to GLPI software detail page
        $software_url = $CFG_GLPI["root_doc"] . "/front/software.form.php?id=" . $software['software_id'];
        echo "<td>";
        echo "<a href='" . $software_url . "' target='_blank' style='color: #0066cc; text-decoration: none;'>";
        echo Html::cleanInputText($software['software_name']);
        echo "</a>";
        echo "</td>";
        
        echo "<td>" . Html::cleanInputText($software['version'] ?? '-') . "</td>";
        echo "<td>" . Html::cleanInputText($software['manufacturer'] ?? '-') . "</td>";

        // Status column
        echo "<td>";
        $status = $software['status'] ?? 'unmanaged';
        switch ($status) {
            case 'whitelist':
                echo "<span class='badge badge-success'>";
                echo "<i class='fas fa-check'></i> " . __('Whitelist', 'softwaremanager');
                echo "</span>";
                break;
            case 'blacklist':
                echo "<span class='badge badge-danger'>";
                echo "<i class='fas fa-times'></i> " . __('Blacklist', 'softwaremanager');
                echo "</span>";
                break;
            default:
                echo "<span class='badge badge-secondary'>";
                echo "<i class='fas fa-question'></i> " . __('Unmanaged', 'softwaremanager');
                echo "</span>";
                break;
        }
        echo "</td>";

        echo "<td>";

        // Create clickable computer count with tooltip - use our custom computer list page
        $custom_computer_url = $CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/computer_list.php?software_id=" . $software['software_id'];

        // Determine digit count for adaptive styling
        $count = $software['computer_count'];
        $digit_count = strlen((string)$count);
        $digit_attr = '';
        if ($digit_count == 1) {
            $digit_attr = 'data-digits="1"';
        } elseif ($digit_count == 2) {
            $digit_attr = 'data-digits="2"';
        } elseif ($digit_count == 3) {
            $digit_attr = 'data-digits="3"';
        } elseif ($digit_count == 4) {
            $digit_attr = 'data-digits="4"';
        } else {
            $digit_attr = 'data-digits="5+"';
        }

        echo "<span class='computer-count-badge clickable-count' ";
        echo "data-software-id='" . $software['software_id'] . "' ";
        echo "data-software-name='" . Html::cleanInputText($software['software_name']) . "' ";
        echo "data-glpi-url='" . $custom_computer_url . "' ";
        echo $digit_attr . ">";
        echo $count;
        echo "</span>";

        echo "<br><small>Click number to view in GLPI</small>";
        echo "</td>";
        echo "<td>";

        // Add to whitelist button
        echo "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "' style='display: inline;'>";
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo Html::hidden('action', ['value' => 'add_to_whitelist']);
        echo Html::hidden('software_name', ['value' => $software['software_name']]);
        echo "<button type='submit' class='btn btn-sm btn-success' title='Add to Whitelist'>";
        echo "<i class='fas fa-check'></i>";
        echo "</button>";
        echo "</form> ";

        // Add to blacklist button
        echo "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "' style='display: inline;'>";
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo Html::hidden('action', ['value' => 'add_to_blacklist']);
        echo Html::hidden('software_name', ['value' => $software['software_name']]);
        echo "<button type='submit' class='btn btn-sm btn-danger' title='Add to Blacklist'>";
        echo "<i class='fas fa-times'></i>";
        echo "</button>";
        echo "</form> ";

        // Details button
        echo "<button type='button' class='btn btn-sm btn-info' onclick='showSoftwareDetails(" . $software['software_id'] . ")' title='View Details'>";
        echo "<i class='fas fa-info'></i>";
        echo "</button>";

        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    
    // Batch operations toolbar
    echo "<div class='batch-operations-toolbar' style='margin-top: 10px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<div style='display: flex; align-items: center; gap: 10px;'>";
    echo "<span><strong>" . __('Batch Operations:', 'softwaremanager') . "</strong></span>";
    
    echo "<button type='button' class='btn btn-sm btn-success' onclick='performBatchAction(\"batch_add_to_whitelist\")' disabled id='batch_whitelist_btn'>";
    echo "<i class='fas fa-check'></i> " . __('Add Selected to Whitelist', 'softwaremanager');
    echo "</button>";
    
    echo "<button type='button' class='btn btn-sm btn-danger' onclick='performBatchAction(\"batch_add_to_blacklist\")' disabled id='batch_blacklist_btn'>";
    echo "<i class='fas fa-times'></i> " . __('Add Selected to Blacklist', 'softwaremanager');
    echo "</button>";
    
    echo "<span id='selected_count' style='margin-left: 20px; font-style: italic; color: #666;'>" . __('No items selected', 'softwaremanager') . "</span>";
    echo "</div>";
    echo "</div>";
    
    echo "</form>";

    // Pagination
    if ($total_pages > 1) {
        echo "<div class='pagination-container'>";
        echo "<div class='pagination'>";
        
        $base_url = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['page' => '']));
        $base_url = rtrim($base_url, '=');
        
        // Previous button
        if ($page > 1) {
            echo "<a href='" . $base_url . "=" . ($page - 1) . "' class='pagination-link'>" . __('Previous') . "</a>";
        } else {
            echo "<span class='pagination-link disabled'>" . __('Previous') . "</span>";
        }
        
        // Page numbers
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            echo "<a href='" . $base_url . "=1' class='pagination-link'>1</a>";
            if ($start_page > 2) {
                echo "<span class='pagination-ellipsis'>...</span>";
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $page) {
                echo "<span class='pagination-link current'>$i</span>";
            } else {
                echo "<a href='" . $base_url . "=$i' class='pagination-link'>$i</a>";
            }
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo "<span class='pagination-ellipsis'>...</span>";
            }
            echo "<a href='" . $base_url . "=$total_pages' class='pagination-link'>$total_pages</a>";
        }
        
        // Next button
        if ($page < $total_pages) {
            echo "<a href='" . $base_url . "=" . ($page + 1) . "' class='pagination-link'>" . __('Next') . "</a>";
        } else {
            echo "<span class='pagination-link disabled'>" . __('Next') . "</span>";
        }
        
        echo "</div>";
        echo "<div class='pagination-info'>";
        echo sprintf(__('Page %d of %d'), $page, $total_pages);
        echo "</div>";
        echo "</div>";
    }

} else {
    echo "<div class='alert alert-info'>";
    echo __('No software found matching your criteria.', 'softwaremanager');
    echo "</div>";
}

// Modal for software details
echo "<div id='software_details_modal' class='modal' style='display: none;'>";
echo "<div class='modal-content'>";
echo "<div class='modal-header'>";
echo "<h3>" . __('Software Details', 'softwaremanager') . "</h3>";
echo "<span class='modal-close' onclick='closeSoftwareDetails()'>&times;</span>";
echo "</div>";
echo "<div class='modal-body' id='modal_body'>";
echo "</div>";
echo "</div>";
echo "</div>";

?>

<script>
// 全局变量
var computerPreviewTimeout;
var computerPreviewElement;
var glpiRoot = '<?php echo $CFG_GLPI["root_doc"]; ?>';

// Modal functionality
function showSoftwareDetails(softwareId) {
    var modal = document.getElementById('software_details_modal');
    var modalBody = document.getElementById('modal_body');
    modal.style.display = 'block';
    modalBody.innerHTML = '<div class="loading">Loading...</div>';
    
    fetch('../ajax/software_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'software_id=' + encodeURIComponent(softwareId)
    })
    .then(response => response.json())
    .then(data => displaySoftwareDetails(data))
    .catch(error => {
        modalBody.innerHTML = '<div class="alert alert-danger">Error loading software details.</div>';
    });
}

function displaySoftwareDetails(data) {
    var html = '<div class="software-info">';
    html += '<h4>Software Information</h4>';
    html += '<table class="tab_cadre">';
    html += '<tr><td><strong>Name:</strong></td><td>' + data.software.name + '</td></tr>';
    html += '<tr><td><strong>Version:</strong></td><td>' + (data.software.version || '-') + '</td></tr>';
    html += '<tr><td><strong>Manufacturer:</strong></td><td>' + (data.software.manufacturer || '-') + '</td></tr>';
    html += '<tr><td><strong>Total Installations:</strong></td><td>' + data.computer_count + '</td></tr>';
    html += '</table>';
    html += '</div>';
    document.getElementById('modal_body').innerHTML = html;
}

function closeSoftwareDetails() {
    document.getElementById('software_details_modal').style.display = 'none';
}

// Computer preview functions
function showComputerPreview(element, softwareId, softwareName) {
    console.log('showComputerPreview called with:', softwareId, softwareName);
    if (computerPreviewTimeout) {
        clearTimeout(computerPreviewTimeout);
    }
    
    computerPreviewTimeout = setTimeout(function() {
        createComputerPreview(element, softwareId, softwareName);
    }, 500);
}

function hideComputerPreview() {
    if (computerPreviewTimeout) {
        clearTimeout(computerPreviewTimeout);
    }
    if (computerPreviewElement) {
        computerPreviewElement.remove();
        computerPreviewElement = null;
    }
}

function createComputerPreview(element, softwareId, softwareName) {
    hideComputerPreview();
    
    computerPreviewElement = document.createElement('div');
    computerPreviewElement.className = 'computer-preview-tooltip';
    computerPreviewElement.innerHTML = '<div class="loading">Loading computers...</div>';
    
    var rect = element.getBoundingClientRect();
    computerPreviewElement.style.position = 'fixed';
    computerPreviewElement.style.left = (rect.left + rect.width + 10) + 'px';
    computerPreviewElement.style.top = rect.top + 'px';
    computerPreviewElement.style.zIndex = '1000';
    
    document.body.appendChild(computerPreviewElement);
    
    console.log('Making AJAX request for software ID:', softwareId);
    fetch('../ajax/software_details.php?software_id=' + softwareId)
        .then(response => {
            console.log('AJAX response received:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('AJAX data received:', data);
            if (computerPreviewElement) {
                if (data.success && data.computers) {
                    displayComputerPreview(data);
                } else {
                    computerPreviewElement.innerHTML = '<div class="error">No computer data available</div>';
                }
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
            if (computerPreviewElement) {
                computerPreviewElement.innerHTML = '<div class="error">Error: ' + error.message + '</div>';
            }
        });
}

function displayComputerPreview(data) {
    if (!computerPreviewElement || !data.computers) return;
    
    var html = '<div class="preview-header">';
    html += '<h4>Computers (' + data.computers.length + ')</h4>';
    html += '</div>';
    html += '<div class="preview-content">';
    
    if (data.computers.length === 0) {
        html += '<p>No computers found</p>';
    } else {
        html += '<ul class="computer-list">';
        var maxShow = Math.min(data.computers.length, 10);
        for (var i = 0; i < maxShow; i++) {
            var computer = data.computers[i];
            html += '<li class="computer-item">';
            
            // Computer name (clickable)
            var computerUrl = glpiRoot + '/front/computer.form.php?id=' + computer.id;
            html += '<a href="' + computerUrl + '" target="_blank" style="color: #0066cc; text-decoration: none; font-weight: bold;">';
            html += computer.name;
            html += '</a>';
            
                         // User information
             if (computer.user && computer.user.display_name) {
                 html += '<br><small><i class="fas fa-user"></i> User: ' + computer.user.display_name + '</small>';
             }
             
             // Software version
             if (computer.version) {
                 html += '<br><small><i class="fas fa-tag"></i> Version: ' + computer.version + '</small>';
             }
             
             // Installation date
             if (computer.installation_date) {
                 html += '<br><small><i class="fas fa-download"></i> Installed: ' + computer.installation_date + '</small>';
             }
            
            // Computer last update
            if (computer.computer_last_update) {
                html += '<br><small><i class="fas fa-clock"></i> Last update: ' + computer.computer_last_update + '</small>';
            }
            
            // Location
            if (computer.location) {
                html += '<br><small><i class="fas fa-map-marker-alt"></i> Location: ' + computer.location + '</small>';
            }
            
            html += '</li>';
        }
        html += '</ul>';
        if (data.computers.length > 10) {
            html += '<p class="more-info">... and ' + (data.computers.length - 10) + ' more</p>';
        }
    }
    
    html += '</div>';
    html += '<div class="preview-footer">';
    html += '<small>Click number to view full list in GLPI</small>';
    html += '</div>';
    
    computerPreviewElement.innerHTML = html;
}

// Batch operations functions
function updateBatchButtons() {
    var checkedBoxes = document.querySelectorAll('.software_select:checked');
    var count = checkedBoxes.length;
    var countSpan = document.getElementById('selected_count');
    var whitelistBtn = document.getElementById('batch_whitelist_btn');
    var blacklistBtn = document.getElementById('batch_blacklist_btn');
    
    if (count > 0) {
        countSpan.textContent = count + ' items selected';
        whitelistBtn.disabled = false;
        blacklistBtn.disabled = false;
    } else {
        countSpan.textContent = 'No items selected';
        whitelistBtn.disabled = true;
        blacklistBtn.disabled = true;
    }
}

function performBatchAction(action) {
    var checkedBoxes = document.querySelectorAll('.software_select:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select at least one software item.');
        return;
    }
    
    var actionText = action === 'batch_add_to_whitelist' ? 'whitelist' : 'blacklist';
    if (confirm('Are you sure you want to add ' + checkedBoxes.length + ' selected software items to the ' + actionText + '?')) {
        document.getElementById('batch_action').value = action;
        document.getElementById('batch-form').submit();
    }
}

// DOM ready event handler
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing software manager...');
    
    // Batch operations setup
    var selectAllCheckbox = document.getElementById('select_all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.software_select');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = this.checked;
            }.bind(this));
            updateBatchButtons();
        });
        
        // Individual checkbox handlers
        var softwareCheckboxes = document.querySelectorAll('.software_select');
        softwareCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateBatchButtons();
                
                // Update select all checkbox state
                var totalBoxes = document.querySelectorAll('.software_select').length;
                var checkedBoxes = document.querySelectorAll('.software_select:checked').length;
                selectAllCheckbox.indeterminate = checkedBoxes > 0 && checkedBoxes < totalBoxes;
                selectAllCheckbox.checked = checkedBoxes === totalBoxes;
            });
        });
    }
    
    // Add click handlers for dashboard stat cards
    var statCards = document.querySelectorAll('.clickable-stat');
    console.log('Found stat cards:', statCards.length);
    
    statCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Card clicked!');
            
            var filterType = this.getAttribute('data-filter');
            console.log('Filter type:', filterType);
            
            var baseUrl = window.location.href.split('?')[0];
            var newUrl = baseUrl + '?status=' + filterType;
            console.log('Navigating to:', newUrl);
            
            this.style.transform = 'scale(0.95)';
            
            setTimeout(function() {
                window.location.href = newUrl;
            }, 150);
        });
    });
    
    // Add click handlers for computer count badges
    var computerCountBadges = document.querySelectorAll('.clickable-count');
    console.log('Found computer count badges:', computerCountBadges.length);
    
    computerCountBadges.forEach(function(badge) {
        badge.addEventListener('click', function(e) {
            e.preventDefault();
            var glpiUrl = this.getAttribute('data-glpi-url');
            console.log('Opening GLPI computer list:', glpiUrl);
            window.open(glpiUrl, '_blank');
        });
        
        // Add hover effect for computer list preview
        badge.addEventListener('mouseenter', function() {
            var softwareId = this.getAttribute('data-software-id');
            var softwareName = this.getAttribute('data-software-name');
            console.log('Mouse entered badge, softwareId:', softwareId, 'softwareName:', softwareName);
            showComputerPreview(this, softwareId, softwareName);
        });
        
        badge.addEventListener('mouseleave', function() {
            hideComputerPreview();
        });
    });
    
    // Highlight active filter
    var urlParams = new URLSearchParams(window.location.search);
    var currentStatus = urlParams.get('status');
    if (currentStatus) {
        var activeCard = document.querySelector('.clickable-stat[data-filter="' + currentStatus + '"]');
        if (activeCard) {
            activeCard.classList.add('stat-active');
        }
    }
    
    // Handle dropdown changes for auto-submit
    var limitSelect = document.querySelector('select[name="limit"]') || 
                     document.getElementById('dropdown_limit') || 
                     document.querySelector('select[id*="limit"]');
    
    if (limitSelect) {
        console.log('Found limit select:', limitSelect);
        limitSelect.addEventListener('change', function() {
            console.log('Limit changed to:', this.value);
            document.getElementById('search-form').submit();
        });
    } else {
        console.log('Limit select not found, trying alternative approach');
        var allSelects = document.querySelectorAll('select');
        allSelects.forEach(function(select) {
            var parentTd = select.closest('td');
            if (parentTd && parentTd.previousElementSibling) {
                var label = parentTd.previousElementSibling.textContent;
                if (label.includes('Items per page') || label.includes('每页') || label.includes('per page')) {
                    console.log('Found limit select by context:', select);
                    select.addEventListener('change', function() {
                        console.log('Limit changed to:', this.value);
                        document.getElementById('search-form').submit();
                    });
                }
            }
        });
    }
});
</script>

<?php

// Include CSS for modal and enhanced UI
echo "<style>";

// Dashboard styles
echo ".dashboard-stats { margin: 20px 0; }";
echo ".stats-container { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }";
echo ".stat-card { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 12px; padding: 20px; min-width: 180px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s ease; }";
echo ".stat-card:hover { transform: translateY(-5px); cursor: pointer; }";
echo ".clickable-stat { cursor: pointer; }";
echo ".stat-clicked { transform: scale(0.95); }";
echo ".stat-active { border: 3px solid #ffd700; box-shadow: 0 6px 12px rgba(255,215,0,0.3); }";
echo ".stat-total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }";
echo ".stat-whitelist { background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%); color: white; }";
echo ".stat-blacklist { background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%); color: white; }";
echo ".stat-unmanaged { background: linear-gradient(135deg, #bdc3c7 0%, #2c3e50 100%); color: white; }";
echo ".stat-card h3 { margin: 0 0 5px 0; font-size: 2.2em; font-weight: bold; }";
echo ".stat-card p { margin: 0; font-size: 1em; opacity: 0.9; }";

// Modal styles
echo ".modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }";
echo ".modal-content { background-color: #fefefe; margin: 5% auto; padding: 0; border-radius: 8px; width: 80%; max-width: 800px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }";
echo ".modal-header { padding: 15px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }";
echo ".modal-header h3 { margin: 0; }";
echo ".modal-close { font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; }";
echo ".modal-close:hover { opacity: 0.7; }";
echo ".modal-body { padding: 20px; }";

// Badge styles
echo ".badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }";
echo ".badge-success { background-color: #28a745; color: white; }";
echo ".badge-danger { background-color: #dc3545; color: white; }";
echo ".badge-secondary { background-color: #6c757d; color: white; }";
echo ".badge i { margin-right: 3px; }";
echo ".btn { padding: 4px 8px; margin: 2px; border: none; border-radius: 3px; cursor: pointer; }";
echo ".btn-success { background-color: #28a745; color: white; }";
echo ".btn-danger { background-color: #dc3545; color: white; }";
echo ".btn-info { background-color: #17a2b8; color: white; }";
echo ".btn-sm { font-size: 12px; }";

// Computer count badge styles - Adaptive for different digit lengths
echo ".computer-count-badge { ";
echo "  background: linear-gradient(135deg, #007cba 0%, #0056b3 100%); ";
echo "  color: white; ";
echo "  padding: 8px 12px; ";
echo "  border-radius: 50px; ";
echo "  font-size: 13px; ";
echo "  font-weight: bold; ";
echo "  display: inline-block; ";
echo "  cursor: pointer; ";
echo "  transition: all 0.3s ease; ";
echo "  box-shadow: 0 2px 4px rgba(0,0,0,0.1); ";
echo "  min-width: 24px; ";
echo "  text-align: center; ";
echo "  line-height: 1; ";
echo "  white-space: nowrap; ";
echo "}";
echo "/* Responsive styling for different digit lengths */";
echo ".computer-count-badge:not(:empty) { ";
echo "  padding: 8px calc(6px + 0.3em); ";
echo "}";
echo "/* Specific adaptations for different digit counts */";
echo ".computer-count-badge[data-digits='1'] { min-width: 32px; padding: 8px; }";
echo ".computer-count-badge[data-digits='2'] { min-width: 40px; padding: 8px 10px; }";
echo ".computer-count-badge[data-digits='3'] { min-width: 48px; padding: 8px 12px; }";
echo ".computer-count-badge[data-digits='4'] { min-width: 56px; padding: 8px 14px; font-size: 12px; }";
echo ".computer-count-badge[data-digits='5+'] { min-width: 64px; padding: 8px 16px; font-size: 11px; }";
echo ".computer-count-badge:hover { ";
echo "  background: linear-gradient(135deg, #0056b3 0%, #004085 100%); ";
echo "  transform: translateY(-1px); ";
echo "  box-shadow: 0 4px 8px rgba(0,0,0,0.2); ";
echo "}";

// Search form styles
echo ".search-form { margin-bottom: 20px; }";
echo ".search-form table { width: 100%; }";
echo ".search-form td { padding: 5px; vertical-align: middle; }";
echo ".search-form label { font-weight: bold; margin-right: 5px; }";
echo ".search-form input[type='text'] { padding: 5px; border: 1px solid #ccc; border-radius: 3px; }";
echo ".search-form select { padding: 5px; border: 1px solid #ccc; border-radius: 3px; }";

// Results summary styles
echo ".results-summary { margin: 15px 0; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007cba; }";
echo ".results-summary p { margin: 0; font-size: 14px; }";

// Pagination styles
echo ".pagination-container { margin: 20px 0; text-align: center; }";
echo ".pagination { display: inline-block; margin-bottom: 10px; }";
echo ".pagination-link { display: inline-block; padding: 8px 12px; margin: 0 2px; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; color: #007cba; }";
echo ".pagination-link:hover { background-color: #f5f5f5; text-decoration: none; }";
echo ".pagination-link.current { background-color: #007cba; color: white; border-color: #007cba; }";
echo ".pagination-link.disabled { color: #999; cursor: not-allowed; }";
echo ".pagination-link.disabled:hover { background-color: transparent; }";
echo ".pagination-ellipsis { display: inline-block; padding: 8px 4px; color: #999; }";
echo ".pagination-info { font-size: 12px; color: #666; margin-top: 5px; }";

// Computer preview tooltip styles
echo ".computer-preview-tooltip { ";
echo "  background: white; ";
echo "  border: 1px solid #ddd; ";
echo "  border-radius: 6px; ";
echo "  box-shadow: 0 3px 10px rgba(0,0,0,0.15); ";
echo "  max-width: 280px; ";
echo "  min-width: 220px; ";
echo "  z-index: 1000; ";
echo "  font-size: 12px; ";
echo "}";
echo ".preview-header { ";
echo "  background: #f8f9fa; ";
echo "  padding: 6px 10px; ";
echo "  border-bottom: 1px solid #ddd; ";
echo "  border-radius: 6px 6px 0 0; ";
echo "}";
echo ".preview-header h4 { ";
echo "  margin: 0; ";
echo "  font-size: 13px; ";
echo "  color: #333; ";
echo "  font-weight: 600; ";
echo "}";
echo ".preview-content { ";
echo "  padding: 8px 10px; ";
echo "  max-height: 180px; ";
echo "  overflow-y: auto; ";
echo "}";
echo ".computer-list { ";
echo "  list-style: none; ";
echo "  padding: 0; ";
echo "  margin: 0; ";
echo "}";
echo ".computer-list li { ";
echo "  padding: 3px 0; ";
echo "  border-bottom: 1px solid #f0f0f0; ";
echo "  line-height: 1.2; ";
echo "}";
echo ".computer-list li:last-child { ";
echo "  border-bottom: none; ";
echo "}";
echo ".preview-footer { ";
echo "  background: #f8f9fa; ";
echo "  padding: 5px 10px; ";
echo "  border-top: 1px solid #ddd; ";
echo "  border-radius: 0 0 6px 6px; ";
echo "  text-align: center; ";
echo "}";
echo ".preview-footer small { ";
echo "  color: #666; ";
echo "  font-style: italic; ";
echo "  font-size: 10px; ";
echo "}";

// Computer item styles in preview
echo ".computer-item { ";
echo "  margin-bottom: 5px; ";
echo "  padding: 5px 0; ";
echo "  border-bottom: 1px solid #f0f0f0; ";
echo "}";
echo ".computer-item:last-child { ";
echo "  border-bottom: none; ";
echo "  margin-bottom: 0; ";
echo "}";
echo ".computer-item i { ";
echo "  margin-right: 4px; ";
echo "  color: #666; ";
echo "  width: 10px; ";
echo "  text-align: center; ";
echo "  font-size: 10px; ";
echo "}";
echo ".computer-item small { ";
echo "  display: block; ";
echo "  margin-top: 1px; ";
echo "  color: #666; ";
echo "  font-size: 10px; ";
echo "  line-height: 1.2; ";
echo "}";
echo ".computer-item a { ";
echo "  font-size: 12px; ";
echo "  font-weight: 600; ";
echo "}";

// Loading and error states
echo ".loading, .error { ";
echo "  padding: 10px; ";
echo "  text-align: center; ";
echo "  color: #666; ";
echo "  font-size: 11px; ";
echo "}";
echo ".error { ";
echo "  color: #dc3545; ";
echo "}";

// More info indicator
echo ".more-info { ";
echo "  text-align: center; ";
echo "  color: #666; ";
echo "  font-style: italic; ";
echo "  font-size: 10px; ";
echo "  margin: 5px 0 3px 0; ";
echo "}";

// Table enhancements
echo ".tab_cadre_fixehov th { background-color: #f8f9fa; font-weight: bold; }";
echo ".tab_cadre_fixehov tr:hover { background-color: #f5f5f5; }";

echo "</style>";

Html::footer();
?>
