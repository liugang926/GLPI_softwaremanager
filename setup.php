<?php
/**
 * Software Manager Plugin for GLPI
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 * @link    https://github.com/liugang926/GLPI_softwarecompliance.git
 */

define('PLUGIN_SOFTWAREMANAGER_VERSION', '1.0.0');
define('PLUGIN_SOFTWAREMANAGER_MIN_GLPI', '10.0.0');
define('PLUGIN_SOFTWAREMANAGER_MAX_GLPI', '10.1.0');

/**
 * Init the hooks of the plugins - Needed
 *
 * @return void
 */
function plugin_init_softwaremanager() {
    global $PLUGIN_HOOKS;

    // Include required class files first
    include_once(__DIR__ . '/inc/profile.class.php');
    include_once(__DIR__ . '/inc/menu.class.php');
    include_once(__DIR__ . '/inc/softwarewhitelist.class.php');
    include_once(__DIR__ . '/inc/softwareblacklist.class.php');
    include_once(__DIR__ . '/inc/softwareinventory.class.php');

    // Required for CSRF protection
    $PLUGIN_HOOKS['csrf_compliant']['softwaremanager'] = true;

    // Plugin information - register profile class according to official documentation
    Plugin::registerClass('PluginSoftwaremanagerProfile', [
        'addtabon' => 'Profile'
    ]);

    // Register software list class for search interface
    Plugin::registerClass('PluginSoftwaremanagerSoftwareList');

    // Add rights to session when profile changes
    $PLUGIN_HOOKS['change_profile']['softwaremanager'] = ['PluginSoftwaremanagerProfile', 'changeProfile'];

    // Check if user can access plugin - temporarily allow all authenticated users
    if (isset($_SESSION['glpiID']) && $_SESSION['glpiID']) {
        $can_access = true;  // Temporarily allow all authenticated users
        
        // TODO: Implement proper permission checking later
        // For now, all authenticated users can access the plugin

        if ($can_access) {
            // Add to menu
            $PLUGIN_HOOKS['menu_toadd']['softwaremanager'] = [
                'admin' => 'PluginSoftwaremanagerMenu'
            ];

            // Add CSS and JS
            $PLUGIN_HOOKS['add_css']['softwaremanager'] = 'css/softwaremanager.css';
            $PLUGIN_HOOKS['add_javascript']['softwaremanager'] = 'js/softwaremanager.js';

            // Register cron tasks (will be implemented later)
            // $PLUGIN_HOOKS['cron']['softwaremanager'] = [];
        }
    }
}

/**
 * Get the name and the version of the plugin - Needed
 *
 * @return array
 */
function plugin_version_softwaremanager() {
    return [
        'name'           => 'Software Manager',
        'version'        => PLUGIN_SOFTWAREMANAGER_VERSION,
        'author'         => 'Abner Liu',
        'license'        => 'GPL-2.0+',
        'homepage'       => 'https://github.com/liugang926/GLPI_softwarecompliance.git',
        'requirements'   => [
            'glpi'   => [
                'min' => PLUGIN_SOFTWAREMANAGER_MIN_GLPI,
                'max' => PLUGIN_SOFTWAREMANAGER_MAX_GLPI
            ],
            'php'    => [
                'min' => '8.0'
            ]
        ]
    ];
}

/**
 * Check prerequisites before install
 *
 * @return boolean
 */
function plugin_softwaremanager_check_prerequisites() {
    // Check GLPI version
    if (defined('GLPI_VERSION') && version_compare(GLPI_VERSION, PLUGIN_SOFTWAREMANAGER_MIN_GLPI, 'lt')) {
        echo 'This plugin requires GLPI >= ' . PLUGIN_SOFTWAREMANAGER_MIN_GLPI;
        return false;
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, '8.0', 'lt')) {
        echo 'This plugin requires PHP >= 8.0';
        return false;
    }

    return true;
}

/**
 * Check configuration process for plugin
 *
 * @param boolean $verbose Enable verbosity. Default to false
 *
 * @return boolean
 */
function plugin_softwaremanager_check_config($verbose = false) {
    if (true) { // Your configuration check
        return true;
    }

    if ($verbose) {
        echo 'Installed, but not configured';
    }
    return false;
}

/**
 * Plugin installation function
 *
 * @return boolean
 */
function plugin_softwaremanager_install() {
    // Include required class files
    include_once(__DIR__ . '/inc/profile.class.php');
    include_once(__DIR__ . '/inc/softwarewhitelist.class.php');
    include_once(__DIR__ . '/inc/softwareblacklist.class.php');

    // Initialize database tables
    $migration = new Migration(PLUGIN_SOFTWAREMANAGER_VERSION);

    // Initialize profile rights
    PluginSoftwaremanagerProfile::initProfile();
    
    // Create database tables
    PluginSoftwaremanagerSoftwareWhitelist::install($migration);
    PluginSoftwaremanagerSoftwareBlacklist::install($migration);

    $migration->executeMigration();
    return true;
}

/**
 * Plugin uninstallation function
 *
 * @return boolean
 */
function plugin_softwaremanager_uninstall() {
    // Include required class files
    include_once(__DIR__ . '/inc/softwarewhitelist.class.php');
    include_once(__DIR__ . '/inc/softwareblacklist.class.php');

    // Drop database tables
    PluginSoftwaremanagerSoftwareWhitelist::uninstall();
    PluginSoftwaremanagerSoftwareBlacklist::uninstall();

    // Remove rights
    $plugin_name = 'softwaremanager';
    $rights = ['config', 'lists', 'software', 'scan'];
    
    foreach ($rights as $right) {
        ProfileRight::deleteProfileRights([$plugin_name . '_' . $right]);
    }
    
    return true;
}
