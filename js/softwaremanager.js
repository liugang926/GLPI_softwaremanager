/**
 * Software Manager Plugin for GLPI
 * JavaScript Functions
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

// Plugin namespace
var SoftwareManager = {
    
    /**
     * Initialize plugin
     */
    init: function() {
        console.log('Software Manager Plugin initialized');
        
        // Bind events
        this.bindEvents();
    },
    
    /**
     * Bind events
     */
    bindEvents: function() {
        // Add event listeners here
        $(document).ready(function() {
            // Initialize tooltips if available
            if (typeof $().tooltip === 'function') {
                $('[data-toggle="tooltip"]').tooltip();
            }
        });
    },
    
    /**
     * Show loading indicator
     */
    showLoading: function(container) {
        if (typeof container === 'string') {
            container = $(container);
        }
        container.html('<div class="softwaremanager-loading">Loading...</div>');
    },
    
    /**
     * Hide loading indicator
     */
    hideLoading: function(container) {
        if (typeof container === 'string') {
            container = $(container);
        }
        container.find('.softwaremanager-loading').remove();
    },
    
    /**
     * Show success message
     */
    showSuccess: function(message) {
        if (typeof displayAjaxMessageAfterRedirect === 'function') {
            displayAjaxMessageAfterRedirect();
        } else {
            alert(message);
        }
    },
    
    /**
     * Show error message
     */
    showError: function(message) {
        if (typeof displayAjaxMessageAfterRedirect === 'function') {
            displayAjaxMessageAfterRedirect();
        } else {
            alert('Error: ' + message);
        }
    },
    
    /**
     * Confirm action
     */
    confirm: function(message, callback) {
        if (confirm(message)) {
            if (typeof callback === 'function') {
                callback();
            }
        }
    }
};

// Initialize when document is ready
$(document).ready(function() {
    SoftwareManager.init();
});
