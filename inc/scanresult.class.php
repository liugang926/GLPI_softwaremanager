<?php
/**
 * Software Manager Plugin for GLPI
 * Scan Result Management Class
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginSoftwaremanagerScanresult extends CommonDBTM {
    
    // Rights management - using standard GLPI config rights
    static $rightname = 'config';
    
    /**
     * Get the database table name for this class
     */
    static function getTable($classname = null) {
        return 'glpi_plugin_softwaremanager_scanresult';
    }
    
    /**
     * Get the type name for this class
     */
    static function getTypeName($nb = 0) {
        return _n('Scan Result', 'Scan Results', $nb, 'softwaremanager');
    }
    
    /**
     * Check if user can view this item type
     */
    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }
    
    /**
     * Check if user can create this item type
     */
    static function canCreate() {
        return Session::haveRight(self::$rightname, CREATE);
    }
    
    /**
     * Check if user can update this item type
     */
    function canUpdateItem() {
        return Session::haveRight(self::$rightname, UPDATE);
    }
    
    /**
     * Check if user can delete this item type
     */
    function canDeleteItem() {
        return Session::haveRight(self::$rightname, DELETE);
    }
    
    /**
     * Install database table for scan results
     */
    static function install(Migration $migration) {
        global $DB;

        $table = self::getTable();

        if (!$DB->tableExists($table)) {

            $query = "CREATE TABLE `$table` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `scanhistory_id` int unsigned NOT NULL,
                `software_name` varchar(255) NOT NULL,
                `software_version` varchar(100) DEFAULT NULL,
                `computer_id` int unsigned NOT NULL,
                `computer_name` varchar(255) NOT NULL,
                `user_id` int unsigned DEFAULT NULL,
                `user_name` varchar(255) DEFAULT NULL,
                `violation_type` enum('blacklist','unmanaged') NOT NULL,
                `date_found` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `scanhistory_id` (`scanhistory_id`),
                KEY `violation_type` (`violation_type`),
                KEY `computer_id` (`computer_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $DB->queryOrDie($query, $DB->error());
        }
    }
    
    /**
     * Uninstall database table for scan results
     */
    static function uninstall() {
        global $DB;

        $table = self::getTable();

        if ($DB->tableExists($table)) {
            $query = "DROP TABLE `$table`";
            $DB->queryOrDie($query, $DB->error());
        }
    }
    
    /**
     * Add a blacklist violation record
     * 
     * @param int $historyId Scan history ID
     * @param string $softwareName Software name
     * @param int $computerId Computer ID
     * @param int $userId User ID
     * @param string $computerName Computer name
     * @param string $userName User name
     * @param string $softwareVersion Software version
     * @return int|false Record ID or false on failure
     */
    function addBlacklistRecord($historyId, $softwareName, $computerId, $userId, $computerName = '', $userName = '', $softwareVersion = '') {
        $input = [
            'scanhistory_id' => $historyId,
            'software_name' => $softwareName,
            'software_version' => $softwareVersion,
            'computer_id' => $computerId,
            'computer_name' => $computerName,
            'user_id' => $userId,
            'user_name' => $userName,
            'violation_type' => 'blacklist',
            'date_found' => $_SESSION["glpi_currenttime"],
            'date_creation' => $_SESSION["glpi_currenttime"]
        ];
        
        return $this->add($input);
    }
    
    /**
     * Add an unmanaged software record
     * 
     * @param int $historyId Scan history ID
     * @param string $softwareName Software name
     * @param int $computerId Computer ID
     * @param int $userId User ID
     * @param string $computerName Computer name
     * @param string $userName User name
     * @param string $softwareVersion Software version
     * @return int|false Record ID or false on failure
     */
    function addUnregisteredRecord($historyId, $softwareName, $computerId, $userId, $computerName = '', $userName = '', $softwareVersion = '') {
        $input = [
            'scanhistory_id' => $historyId,
            'software_name' => $softwareName,
            'software_version' => $softwareVersion,
            'computer_id' => $computerId,
            'computer_name' => $computerName,
            'user_id' => $userId,
            'user_name' => $userName,
            'violation_type' => 'unmanaged',
            'date_found' => $_SESSION["glpi_currenttime"],
            'date_creation' => $_SESSION["glpi_currenttime"]
        ];
        
        return $this->add($input);
    }
    
    /**
     * Get results for a specific scan history
     * 
     * @param int $scanhistory_id Scan history ID
     * @param string $violation_type Violation type filter (optional)
     * @return array Results array
     */
    static function getResultsForHistory($scanhistory_id, $violation_type = null) {
        global $DB;
        
        $table = self::getTable();
        $where = "scanhistory_id = " . intval($scanhistory_id);
        
        if ($violation_type) {
            $where .= " AND violation_type = '" . $DB->escape($violation_type) . "'";
        }
        
        $query = "SELECT * FROM `$table` WHERE $where ORDER BY software_name, computer_name";
        $result = $DB->query($query);
        
        $results = [];
        if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
                $results[] = $row;
            }
        }
        
        return $results;
    }
    
    /**
     * Get search options for the class
     */
    function getSearchOptions() {
        $tab = [];

        $tab['common'] = __('Characteristics');

        $tab[1]['table']         = $this->getTable();
        $tab[1]['field']         = 'software_name';
        $tab[1]['name']          = __('Software Name', 'softwaremanager');
        $tab[1]['datatype']      = 'text';
        $tab[1]['massiveaction'] = false;

        $tab[2]['table']         = $this->getTable();
        $tab[2]['field']         = 'software_version';
        $tab[2]['name']          = __('Version', 'softwaremanager');
        $tab[2]['datatype']      = 'text';
        $tab[2]['massiveaction'] = false;

        $tab[3]['table']         = $this->getTable();
        $tab[3]['field']         = 'computer_name';
        $tab[3]['name']          = __('Computer', 'softwaremanager');
        $tab[3]['datatype']      = 'text';
        $tab[3]['massiveaction'] = false;

        $tab[4]['table']         = $this->getTable();
        $tab[4]['field']         = 'user_name';
        $tab[4]['name']          = __('User', 'softwaremanager');
        $tab[4]['datatype']      = 'text';
        $tab[4]['massiveaction'] = false;

        $tab[5]['table']         = $this->getTable();
        $tab[5]['field']         = 'violation_type';
        $tab[5]['name']          = __('Violation Type', 'softwaremanager');
        $tab[5]['datatype']      = 'specific';
        $tab[5]['massiveaction'] = false;

        $tab[6]['table']         = $this->getTable();
        $tab[6]['field']         = 'date_found';
        $tab[6]['name']          = __('Date Found', 'softwaremanager');
        $tab[6]['datatype']      = 'datetime';
        $tab[6]['massiveaction'] = false;

        return $tab;
    }
    
    /**
     * Display specific column values
     */
    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        
        switch ($field) {
            case 'violation_type':
                $type_labels = [
                    'blacklist' => __('Blacklist Violation', 'softwaremanager'),
                    'unmanaged' => __('Unmanaged Software', 'softwaremanager')
                ];
                return $type_labels[$values[$field]] ?? $values[$field];
        }
        
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
    
    /**
     * Get search URL for this item type
     */
    static function getSearchURL($full = true) {
        global $CFG_GLPI;
        
        $itemtype = get_called_class();
        $link = $CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/scanresult.php";
        
        if ($full) {
            $link .= "?itemtype=" . $itemtype;
        }
        
        return $link;
    }
}
?>
