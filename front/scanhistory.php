<?php
/**
 * Software Manager Plugin for GLPI
 * Scan History List Page
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

Html::header(__('Scan History', 'softwaremanager'), $_SERVER['PHP_SELF'], 'admin', 'PluginSoftwaremanagerMenu');

// Display navigation
PluginSoftwaremanagerMenu::displayNavigationHeader('scanhistory');

// Handle manual scan trigger
if (isset($_POST['action']) && $_POST['action'] === 'start_scan') {
    // CSRF check
    Session::checkCSRF($_POST);
    
    // Check write permissions for scan
    if (Session::haveRight('config', UPDATE)) {
        echo "<div class='alert alert-info'>";
        echo "<i class='fas fa-spinner fa-spin'></i> " . __('Scan started in background. Please refresh the page in a few moments to see the results.', 'softwaremanager');
        echo "</div>";
        
        // Note: In a real implementation, you might want to trigger the scan via AJAX
        // or use a background job system. For now, we'll just show the message.
    } else {
        echo "<div class='alert alert-danger'>";
        echo __('Insufficient permissions to start scan.', 'softwaremanager');
        echo "</div>";
    }
}

// Display scan controls if user has write permissions
if (Session::haveRight('config', UPDATE)) {
    echo "<div class='scan-controls' style='margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;'>";
    echo "<h3>" . __('Compliance Scan Controls', 'softwaremanager') . "</h3>";
    echo "<p>" . __('Run a manual compliance scan to check all software installations against whitelist and blacklist policies.', 'softwaremanager') . "</p>";

    echo "<div style='display: flex; gap: 10px; align-items: center;'>";

    // Manual scan button with AJAX
    echo "<button type='button' class='btn btn-primary' onclick='startComplianceScan()' id='scan-btn'>";
    echo "<i class='fas fa-search'></i> " . __('Start New Scan', 'softwaremanager');
    echo "</button>";

    // Test button
    echo "<button type='button' class='btn btn-secondary' onclick='testAjax()' id='test-btn' style='margin-left: 10px;'>";
    echo "<i class='fas fa-flask'></i> Test AJAX";
    echo "</button>";

    // Progress indicator (hidden by default)
    echo "<div id='scan-progress' style='display: none;'>";
    echo "<i class='fas fa-spinner fa-spin'></i> " . __('Scanning in progress...', 'softwaremanager');
    echo "</div>";

    echo "</div>";
    echo "</div>";
}

// Display scan history using GLPI's standard search interface
echo "<div class='scan-history-list'>";
echo "<h2>" . __('Scan History', 'softwaremanager') . "</h2>";

// Use GLPI's standard search interface
Search::show('PluginSoftwaremanagerScanhistory');

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

    // Prepare form data with CSRF token - following security best practices
    var formData = new FormData();
    formData.append('action', 'start_scan');

    // Generate and add CSRF token
    var csrfToken = '<?php echo Session::getNewCSRFToken(); ?>';
    console.log('Generated CSRF token:', csrfToken);
    formData.append('_glpi_csrf_token', csrfToken);

    // Debug: Log all form data
    for (var pair of formData.entries()) {
        console.log('FormData:', pair[0] + ' = ' + pair[1]);
    }

    // Start the scan - use fixed endpoint with GLPI standard queries
    fetch('<?php echo $CFG_GLPI['root_doc']; ?>/plugins/softwaremanager/ajax/runscan.php', {
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

// Test AJAX function
function testAjax() {
    console.log('Testing AJAX...');

    fetch('<?php echo $CFG_GLPI['root_doc']; ?>/plugins/softwaremanager/ajax/test_simple.php', {
        method: 'GET'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(htmlContent => {
        console.log('Response content:', htmlContent);
        showScanResultsHTML(htmlContent);
    })
    .catch(error => {
        console.error('Test error:', error);
        alert('Test failed: ' + error.message);
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

// Add CSRF token to the page for AJAX requests
document.addEventListener('DOMContentLoaded', function() {
    if (!document.querySelector('input[name="_glpi_csrf_token"]')) {
        var csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_glpi_csrf_token';
        csrfInput.value = '<?php echo Session::getNewCSRFToken(); ?>';
        document.body.appendChild(csrfInput);
    }
});
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
