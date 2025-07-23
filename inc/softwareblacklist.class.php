<?php
/**
 * Software Manager Plugin for GLPI
 * Software Blacklist Management Class
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * PluginSoftwaremanagerSoftwareBlacklist class
 */
class PluginSoftwaremanagerSoftwareBlacklist extends CommonDBTM {

    static $rightname = 'plugin_softwaremanager_lists';

    /**
     * Get type name
     *
     * @param integer $nb Number of items
     *
     * @return string
     */
    static function getTypeName($nb = 0) {
        return _n('Software Blacklist', 'Software Blacklists', $nb, 'softwaremanager');
    }

    /**
     * Get search options
     *
     * @return array
     */
    function getSearchOptions() {
        $tab = [];

        $tab['common'] = __('Characteristics');

        $tab[1]['table']         = $this->getTable();
        $tab[1]['field']         = 'name';
        $tab[1]['name']          = __('Name');
        $tab[1]['datatype']      = 'itemlink';
        $tab[1]['massiveaction'] = false;

        $tab[2]['table']         = $this->getTable();
        $tab[2]['field']         = 'id';
        $tab[2]['name']          = __('ID');
        $tab[2]['massiveaction'] = false;
        $tab[2]['datatype']      = 'number';

        $tab[3]['table']         = $this->getTable();
        $tab[3]['field']         = 'comment';
        $tab[3]['name']          = __('Comments');
        $tab[3]['datatype']      = 'text';

        $tab[4]['table']         = $this->getTable();
        $tab[4]['field']         = 'is_active';
        $tab[4]['name']          = __('Active');
        $tab[4]['datatype']      = 'bool';

        $tab[5]['table']         = $this->getTable();
        $tab[5]['field']         = 'date_creation';
        $tab[5]['name']          = __('Creation date');
        $tab[5]['datatype']      = 'datetime';
        $tab[5]['massiveaction'] = false;

        $tab[6]['table']         = $this->getTable();
        $tab[6]['field']         = 'date_mod';
        $tab[6]['name']          = __('Last update');
        $tab[6]['datatype']      = 'datetime';
        $tab[6]['massiveaction'] = false;

        return $tab;
    }

    /**
     * Define tabs to display
     *
     * @param array $options Options
     *
     * @return array
     */
    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    /**
     * Check if current user can view item
     *
     * @return boolean
     */
    static function canView() {
        // Simplified permission check
        return isset($_SESSION['glpiactiveprofile']);
    }

    /**
     * Check if current user can create item
     *
     * @return boolean
     */
    static function canCreate() {
        // Simplified permission check
        return isset($_SESSION['glpiactiveprofile']);
    }

    /**
     * Check if current user can update item
     *
     * @return boolean
     */
    function canUpdateItem() {
        // Simplified permission check
        return isset($_SESSION['glpiactiveprofile']);
    }

    /**
     * Check if current user can delete item
     *
     * @return boolean
     */
    function canDeleteItem() {
        // Simplified permission check
        return isset($_SESSION['glpiactiveprofile']);
    }

    /**
     * Show form for add/edit
     *
     * @param integer $ID      Item ID
     * @param array   $options Options
     *
     * @return boolean
     */
    function showForm($ID, $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, "name");
        echo "</td>";
        echo "<td>" . __('Active') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("is_active", $this->fields["is_active"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Comments') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='comment' rows='3' cols='80'>" . $this->fields["comment"] . "</textarea>";
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }

    /**
     * Prepare input for add
     *
     * @param array $input Input data
     *
     * @return array
     */
    function prepareInputForAdd($input) {
        if (isset($input['name']) && empty($input['name'])) {
            Session::addMessageAfterRedirect(__('Name is required', 'softwaremanager'), false, ERROR);
            return false;
        }

        $input['date_creation'] = $_SESSION['glpi_currenttime'];
        return $input;
    }

    /**
     * Prepare input for update
     *
     * @param array $input Input data
     *
     * @return array
     */
    function prepareInputForUpdate($input) {
        if (isset($input['name']) && empty($input['name'])) {
            Session::addMessageAfterRedirect(__('Name is required', 'softwaremanager'), false, ERROR);
            return false;
        }

        return $input;
    }

    /**
     * Get table name
     *
     * @return string
     */
    static function getTable($classname = null) {
        return 'glpi_plugin_softwaremanager_blacklists';
    }

    /**
     * Add software to blacklist (static method)
     *
     * @param string $software_name Software name
     * @param string $comment       Optional comment
     *
     * @return bool Success status
     */
    static function addToList($software_name, $comment = '') {
        global $DB;

        // Clean input
        $software_name = Html::cleanInputText(trim($software_name));
        $comment = Html::cleanInputText(trim($comment));

        if (empty($software_name)) {
            return false;
        }

        // Check if already exists
        $existing = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => ['name' => $software_name, 'is_active' => 1]
        ]);

        if (count($existing) > 0) {
            Session::addMessageAfterRedirect(__('Software already in blacklist', 'softwaremanager'), false, WARNING);
            return false;
        }

        // Remove from whitelist if exists
        $whitelist_table = PluginSoftwaremanagerSoftwareWhitelist::getTable();
        $DB->update($whitelist_table, 
            ['is_active' => 0], 
            ['name' => $software_name]
        );

        // Add to blacklist
        $result = $DB->insert(self::getTable(), [
            'name' => $software_name,
            'comment' => $comment,
            'is_active' => 1,
            'date_creation' => $_SESSION['glpi_currenttime']
        ]);

        if ($result) {
            Session::addMessageAfterRedirect(__('Software added to blacklist successfully', 'softwaremanager'), true);
            return true;
        }

        return false;
    }

    /**
     * Install database table
     *
     * @param Migration $migration Migration instance
     *
     * @return boolean
     */
    static function install(Migration $migration) {
        global $DB;

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");

            $query = "CREATE TABLE `$table` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL DEFAULT '',
                `comment` text,
                `is_active` tinyint NOT NULL DEFAULT '1',
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `name` (`name`),
                KEY `is_active` (`is_active`),
                KEY `date_creation` (`date_creation`),
                KEY `date_mod` (`date_mod`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $DB->queryOrDie($query, $DB->error());
        }

        return true;
    }

    /**
     * Uninstall database table
     *
     * @return boolean
     */
    static function uninstall() {
        global $DB;

        $table = self::getTable();
        if ($DB->tableExists($table)) {
            $DB->queryOrDie("DROP TABLE `$table`", $DB->error());
        }

        return true;
    }
}
