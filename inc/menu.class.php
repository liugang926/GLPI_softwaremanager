<?php
/**
 * Software Manager Plugin for GLPI
 * Menu Management Class
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * PluginSoftwaremanagerMenu class
 */
class PluginSoftwaremanagerMenu extends CommonGLPI {

    static $rightname = 'plugin_softwaremanager_menu';

    /**
     * Get menu name
     *
     * @return string Menu name
     */
    static function getMenuName() {
        return 'Software Manager';
    }

    /**
     * Get menu content
     *
     * @return array Menu content
     */
    static function getMenuContent() {
        global $CFG_GLPI;

        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page']  = '/plugins/softwaremanager/front/softwarelist.php';
        $menu['icon']  = 'fas fa-laptop';

        // Check if user has access
        if (self::canView()) {
            // Software inventory
            $menu['options']['softwarelist'] = [
                'title' => 'Software Inventory',
                'page'  => '/plugins/softwaremanager/front/softwarelist.php',
                'icon'  => 'fas fa-list'
            ];

            // Compliance scan history
            $menu['options']['scanhistory'] = [
                'title' => 'Compliance Scan History',
                'page'  => '/plugins/softwaremanager/front/scanhistory.php',
                'icon'  => 'fas fa-history'
            ];

            // Whitelist management
            $menu['options']['whitelist'] = [
                'title' => 'Whitelist Management',
                'page'  => '/plugins/softwaremanager/front/whitelist.php',
                'icon'  => 'fas fa-check-circle',
                'links' => [
                    'search' => '/plugins/softwaremanager/front/whitelist.php',
                    'add'    => '/plugins/softwaremanager/front/whitelist.form.php'
                ]
            ];

            // Blacklist management
            $menu['options']['blacklist'] = [
                'title' => 'Blacklist Management',
                'page'  => '/plugins/softwaremanager/front/blacklist.php',
                'icon'  => 'fas fa-times-circle',
                'links' => [
                    'search' => '/plugins/softwaremanager/front/blacklist.php',
                    'add'    => '/plugins/softwaremanager/front/blacklist.form.php'
                ]
            ];

            // Import/Export functionality
            $menu['options']['import'] = [
                'title' => 'Import/Export',
                'page'  => '/plugins/softwaremanager/front/import.php',
                'icon'  => 'fas fa-file-import'
            ];

            // Plugin configuration
            $menu['options']['config'] = [
                'title' => 'Plugin Configuration',
                'page'  => '/plugins/softwaremanager/front/config.php',
                'icon'  => 'fas fa-cog'
            ];
        }

        return $menu;
    }

    /**
     * Check if user can view the menu
     *
     * @return boolean
     */
    static function canView() {
        // Super-Admin can always access
        if (isset($_SESSION['glpiactiveprofile']['name']) && $_SESSION['glpiactiveprofile']['name'] == 'Super-Admin') {
            return true;
        }

        // Check if user has config rights (standard GLPI permission)
        return Session::haveRight('config', READ);
    }

    /**
     * Display navigation header
     *
     * @param string $current_page Current page identifier
     *
     * @return void
     */
    public static function displayNavigationHeader($current_page = '') {
        global $CFG_GLPI;

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='6'>Software Manager</th></tr>";
        echo "<tr class='tab_bg_1'>";

        $menu_items = [
            'softwarelist' => ['Software Inventory', 'fas fa-list'],
            'scanhistory'  => ['Scan History', 'fas fa-history'],
            'whitelist'    => ['Whitelist', 'fas fa-check-circle'],
            'blacklist'    => ['Blacklist', 'fas fa-times-circle'],
            'import'       => ['Import/Export', 'fas fa-file-import'],
            'config'       => ['Configuration', 'fas fa-cog']
        ];

        foreach ($menu_items as $key => $item) {
            $class = ($current_page == $key) ? 'tab_bg_2' : 'tab_bg_1';
            echo "<td class='$class center'>";

            $url = $CFG_GLPI['root_doc'] . "/plugins/softwaremanager/front/" . $key . ".php";

            echo "<a href='$url'>";
            echo "<i class='" . $item[1] . "'></i> " . $item[0];
            echo "</a>";
            echo "</td>";
        }

        echo "</tr>";
        echo "</table>";
        echo "</div><br>";
    }
}
