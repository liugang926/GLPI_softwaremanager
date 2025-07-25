<?php
/**
 * Software Manager Plugin for GLPI
 * Installation Class
 *
 * @author  Abner Liu
 * @license GPL-2.0+
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Installation class for Software Manager Plugin
 */
class PluginSoftwaremanagerInstall {

    /**
     * Install plugin
     *
     * @return boolean
     */
    public static function install() {
        try {
            // Install database tables
            self::installTables();

            // Install plugin rights
            self::installRights();

            return true;
            
        } catch (Exception $e) {
            error_log("Software Manager Plugin installation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Uninstall plugin
     *
     * @return boolean
     */
    public static function uninstall() {
        try {
            // Remove database tables
            self::uninstallTables();

            // Remove plugin rights
            self::uninstallRights();

            return true;
            
        } catch (Exception $e) {
            error_log("Software Manager Plugin uninstallation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Install database tables
     *
     * @return void
     */
    private static function installTables() {
        // Include required class files
        include_once(__DIR__ . '/softwarewhitelist.class.php');
        include_once(__DIR__ . '/softwareblacklist.class.php');
        include_once(__DIR__ . '/scanhistory.class.php');
        include_once(__DIR__ . '/scanresult.class.php');

        // Initialize database tables
        $migration = new Migration(PLUGIN_SOFTWAREMANAGER_VERSION);

        // Create database tables
        PluginSoftwaremanagerSoftwareWhitelist::install($migration);
        PluginSoftwaremanagerSoftwareBlacklist::install($migration);
        PluginSoftwaremanagerScanhistory::install($migration);
        PluginSoftwaremanagerScanresult::install($migration);

        $migration->executeMigration();
    }

    /**
     * Uninstall database tables
     *
     * @return void
     */
    private static function uninstallTables() {
        // Include required class files
        include_once(__DIR__ . '/softwarewhitelist.class.php');
        include_once(__DIR__ . '/softwareblacklist.class.php');
        include_once(__DIR__ . '/scanhistory.class.php');
        include_once(__DIR__ . '/scanresult.class.php');

        // Drop database tables
        PluginSoftwaremanagerSoftwareWhitelist::uninstall();
        PluginSoftwaremanagerSoftwareBlacklist::uninstall();
        PluginSoftwaremanagerScanhistory::uninstall();
        PluginSoftwaremanagerScanresult::uninstall();
    }

    /**
     * Install plugin rights
     *
     * @return void
     */
    private static function installRights() {
        global $DB;

        // Simple approach: Give all logged-in users access to the plugin
        // by adding basic plugin rights to all existing profiles

        $profiles = $DB->request([
            'FROM' => 'glpi_profiles'
        ]);

        foreach ($profiles as $profile) {
            // Add basic plugin access right for all profiles
            $DB->insertOrDie('glpi_profilerights', [
                'profiles_id' => $profile['id'],
                'name'        => 'plugin_softwaremanager',
                'rights'      => READ | UPDATE | CREATE | DELETE
            ]);
        }
    }

    /**
     * Uninstall plugin rights
     *
     * @return void
     */
    private static function uninstallRights() {
        global $DB;

        // Remove plugin rights from all profiles
        $DB->delete('glpi_profilerights', [
            'name' => 'plugin_softwaremanager'
        ]);
    }

}
