<?php
/**
 * Software Manager Plugin for GLPI
 * Scan History List Page - Clean Version
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

include('../../../inc/includes.php');
Session::checkRight('config', READ);
// Check rights - using standard GLPI permissions
// GLPI session already authenticated

// Check if plugin is activated
$plugin = new Plugin();
if (!$plugin->isInstalled('softwaremanager') || !$plugin->isActivated('softwaremanager')) {
    Html::displayNotFoundError();
}

Html::header(__('Scan History', 'softwaremanager'), $_SERVER['PHP_SELF'], 'admin');

// Display navigation
PluginSoftwaremanagerMenu::displayNavigationHeader('scanhistory');

// Display scan controls
{
    echo "<div class='scan-controls' style='margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;'>";
    echo "<h3>" . __('软件合规性扫描', 'softwaremanager') . "</h3>";
    echo "<p>" . __('执行深度合规性扫描，检查所有实际的软件安装记录，识别违规软件并关联到具体的计算机和用户。', 'softwaremanager') . "</p>";
    echo "<div class='scan-features' style='background: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 5px; font-size: 13px;'>";
    echo "<strong>✅ 扫描功能：</strong><br>";
    echo "• 检查实际软件安装记录，而非软件库统计<br>";
    echo "• 精确匹配白名单和黑名单规则<br>";
    echo "• 识别具体违规软件及其安装位置<br>";
    echo "• 关联计算机、用户和安装时间信息<br>";
    echo "• 生成详细的合规性报告";
    echo "</div>";

    echo "<div style='display: flex; gap: 10px; align-items: center;'>";

    // Manual scan button with AJAX
    echo "<button type='button' class='btn btn-primary' onclick='startComplianceScan()' id='scan-btn'>";
    echo "<i class='fas fa-shield-alt'></i> " . __('开始合规性扫描', 'softwaremanager');
    echo "</button>";

    // Progress indicator (hidden by default)
    echo "<div id='scan-progress' style='display: none;'>";
    echo "<i class='fas fa-spinner fa-spin'></i> " . __('Scanning in progress...', 'softwaremanager');
    echo "</div>";

    echo "</div>";
    echo "</div>";
}

// Display scan history using direct database query
echo "<div class='scan-history-list'>";
echo "<h2>" . __('Scan History', 'softwaremanager') . "</h2>";

// Get scan history data directly
global $DB;
$query = "SELECT s.*, u.name as user_name 
          FROM `glpi_plugin_softwaremanager_scanhistory` s 
          LEFT JOIN `glpi_users` u ON s.user_id = u.id 
          ORDER BY s.scan_date DESC 
          LIMIT 20";

$result = $DB->query($query);

if ($result && $DB->numrows($result) > 0) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped table-hover'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>" . __('ID') . "</th>";
    echo "<th>" . __('扫描日期', 'softwaremanager') . "</th>";
    echo "<th>" . __('软件安装总数', 'softwaremanager') . "</th>";
    echo "<th>" . __('合规安装', 'softwaremanager') . "</th>";
    echo "<th>" . __('违规安装', 'softwaremanager') . "</th>";
    echo "<th>" . __('未登记安装', 'softwaremanager') . "</th>";
    echo "<th>" . __('Status', 'softwaremanager') . "</th>";
    echo "<th>" . __('User') . "</th>";
    echo "<th>" . __('Actions', 'softwaremanager') . "</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = $DB->fetchAssoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . Html::convDateTime($row['scan_date']) . "</td>";
        echo "<td><span class='badge badge-info'>总计 " . $row['total_software'] . "</span></td>";
        echo "<td><span class='badge badge-success'>✓ " . $row['whitelist_count'] . "</span></td>";
        echo "<td><span class='badge badge-danger'>⚠ " . $row['blacklist_count'] . "</span></td>";
        echo "<td><span class='badge badge-warning'>? " . $row['unmanaged_count'] . "</span></td>";
        
        $status_class = $row['status'] == 'completed' ? 'success' : ($row['status'] == 'test' ? 'info' : 'secondary');
        echo "<td><span class='badge badge-{$status_class}'>" . ucfirst($row['status']) . "</span></td>";
        
        echo "<td>" . ($row['user_name'] ?? 'Unknown') . "</td>";
        
        // Actions column
        echo "<td>";
        echo "<a href='scanresult.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary' title='" . __('View Details', 'softwaremanager') . "'>";
        echo "<i class='fas fa-eye'></i> " . __('Details', 'softwaremanager');
        echo "</a>";
        echo "</td>";
        
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
} else {
    echo "<div class='alert alert-info'>";
    echo "<i class='fas fa-info-circle'></i> " . __('No scan history found. Run a scan to see results here.', 'softwaremanager');
    echo "</div>";
}

echo "</div>";

// Add JavaScript for scan functionality
?>
<script type='text/javascript'>
function startComplianceScan() {
    if (!confirm('<?php echo __('Are you sure you want to start a compliance scan? This may take several minutes.', 'softwaremanager'); ?>')) {
        return;
    }

    // Disable the scan button
    var scanBtn = document.getElementById('scan-btn');
    var progressDiv = document.getElementById('scan-progress');
    var originalText = scanBtn.innerHTML;
    
    scanBtn.disabled = true;
    scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo __('Starting...', 'softwaremanager'); ?>';
    progressDiv.style.display = 'block';

    // Prepare form data
    var formData = new FormData();

    // Start the scan
    fetch('<?php echo $CFG_GLPI['root_doc']; ?>/plugins/softwaremanager/ajax/compliance_scan.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            // Try to parse error as JSON first
            return response.json().then(errorData => {
                throw new Error(errorData.error || 'HTTP ' + response.status + ': ' + response.statusText);
            }).catch(() => {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            });
        }

        // Check if response is JSON or HTML
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text();
        }
    })
    .then(content => {
        progressDiv.style.display = 'none';
        scanBtn.disabled = false;
        scanBtn.innerHTML = originalText;

        // Handle both JSON and HTML responses
        if (typeof content === 'object' && content.error) {
            // JSON error response
            alert('扫描失败: ' + content.error);
        } else if (typeof content === 'object' && content.success) {
            // JSON success response
            alert('扫描成功: ' + content.message);
            window.location.reload(); // Refresh to show new scan history
        } else {
            // HTML response (detailed scan results)
            showScanResultsHTML(content);
        }
    })
    .catch(error => {
        progressDiv.style.display = 'none';
        scanBtn.disabled = false;
        scanBtn.innerHTML = originalText;

        console.error('Scan error:', error);

        // Show error in HTML format
        const errorHTML = `
            <div style='padding: 20px; font-family: Arial, sans-serif;'>
                <h3 style='color: red;'>❌ Network Error</h3>
                <div style='background: #ffe6e6; padding: 15px; margin: 15px 0; border-radius: 5px; border: 1px solid #ffb3b3;'>
                    <strong>Error Message:</strong><br>
                    ${error.message}
                </div>
                <div style='text-align: center; margin-top: 20px;'>
                    <button onclick='window.location.reload()' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;'>
                        Refresh Page
                    </button>
                </div>
            </div>
        `;
        showScanResultsHTML(errorHTML);
    });
}

// Function to show HTML scan results
function showScanResultsHTML(htmlContent) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 9999; display: flex;
        align-items: center; justify-content: center;
    `;

    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white; border-radius: 8px;
        max-width: 90%; max-height: 90%; overflow-y: auto;
        min-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;

    // Add close button to the HTML content
    const contentWithClose = htmlContent + `
        <div style='text-align: center; margin-top: 20px; padding: 20px; border-top: 1px solid #ddd;'>
            <button onclick='this.closest(".modal-overlay").remove()'
                    style='padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;'>
                Close
            </button>
            <button onclick='window.location.reload()'
                    style='padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;'>
                Refresh Page
            </button>
        </div>
    `;

    modalContent.innerHTML = contentWithClose;
    modal.className = 'modal-overlay';
    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // Auto-refresh page after 10 seconds if it's a success message
    if (htmlContent.includes('✅ Scan Completed Successfully')) {
        setTimeout(() => {
            modal.remove();
            window.location.reload();
        }, 10000);
    }
}
</script>

<style>
.scan-controls {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid #007bff;
}

.scan-controls h3 {
    color: #495057;
    margin-bottom: 10px;
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

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

#scan-progress {
    color: #6c757d;
    font-style: italic;
}
</style>

<?php
Html::footer();
?>