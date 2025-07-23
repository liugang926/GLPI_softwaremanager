<?php
/**
 * Software Manager Plugin for GLPI
 * Profile Management Class
 *
 * @author  Abner Liu
 * @license GPL-2.0+
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * PluginSoftwaremanagerProfile class
 */
class PluginSoftwaremanagerProfile {

    static $rightname = 'plugin_softwaremanager';

    /**
     * Get tab name for item
     *
     * @param object  $item         Item
     * @param integer $withtemplate Template option
     *
     * @return string
     */
    function getTabNameForItem($item, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            return 'Software Manager';
        }
        return '';
    }

    /**
     * Display tab content for item
     *
     * @param object  $item         Item
     * @param integer $tabnum       Tab number
     * @param integer $withtemplate Template option
     *
     * @return boolean
     */
    static function displayTabContentForItem($item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            $profile = new self();
            $profile->showForm($item->getID());
        }
        return true;
    }

    /**
     * Show profile form
     *
     * @param integer $ID      Profile ID
     * @param array   $options Options
     *
     * @return boolean
     */
    function showForm($ID, $options = []) {
        echo "<div class='center'>";
        echo "<h3>Software Manager Plugin Rights</h3>";
        echo "<p>This plugin provides software inventory management capabilities.</p>";
        echo "<p>Rights are managed through the standard GLPI profile system.</p>";
        echo "</div>";
        return true;
    }

    /**
     * Get all rights for this plugin
     *
     * @return array
     */
    static function getAllRights() {
        $rights = [
            'plugin_softwaremanager_config' => 'Configure plugin',
            'plugin_softwaremanager_lists' => 'Manage blacklists and whitelists',
            'plugin_softwaremanager_software' => 'View software inventory',
            'plugin_softwaremanager_scan' => 'Run and view compliance scans'
        ];
        return $rights;
    }

    /**
     * Initialize profile during installation
     *
     * @return void
     */
    public static function initProfile() {
        global $DB;

        $rights = self::getAllRights();

        foreach ($rights as $right => $label) {
            // Add right to all existing profiles
            $query = "SELECT id FROM glpi_profiles";
            $result = $DB->query($query);

            while ($profile = $DB->fetchAssoc($result)) {
                $profile_id = $profile['id'];

                // Check if right already exists
                $existing = $DB->request([
                    'FROM' => 'glpi_profilerights',
                    'WHERE' => [
                        'profiles_id' => $profile_id,
                        'name' => $right
                    ]
                ]);

                if (count($existing) == 0) {
                    // Add right with full permissions for Super-Admin, read for others
                    $rights_value = ($profile_id == 4) ? (CREATE | READ | UPDATE | DELETE | PURGE) : READ;

                    $DB->insert('glpi_profilerights', [
                        'profiles_id' => $profile_id,
                        'name' => $right,
                        'rights' => $rights_value
                    ]);
                }
            }
        }
    }

    /**
     * Add rights to current session
     *
     * @return void
     */
    public static function addRightsToSession() {
        // Rights handled by GLPI standard system
    }

    /**
     * Change profile hook
     *
     * @return void
     */
    public static function changeProfile() {
        // Profile change handled by GLPI
    }

    /**
     * Check if current user can view plugin
     *
     * @return boolean
     */
    public static function canView() {
        // Super-Admin can always access
        if (isset($_SESSION['glpiactiveprofile']['name']) && $_SESSION['glpiactiveprofile']['name'] == 'Super-Admin') {
            return true;
        }

        // For now, allow all authenticated users
        return isset($_SESSION['glpiactiveprofile']);
    }
}
