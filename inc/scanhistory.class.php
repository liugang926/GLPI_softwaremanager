<?php
/**
 * Software Manager Plugin for GLPI
 * Scan History Management Class
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginSoftwaremanagerScanhistory extends CommonDBTM {

    // Rights management - using standard GLPI config rights
    static $rightname = 'config';

    /**
     * Get the database table name for this class
     */
    static function getTable($classname = null) {
        return 'glpi_plugin_softwaremanager_scanhistory';
    }

    /**
     * Get the type name for this class
     */
    static function getTypeName($nb = 0) {
        return _n('Scan History', 'Scan Histories', $nb, 'softwaremanager');
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
     * Install database table for scan history
     */
    static function install(Migration $migration) {
        global $DB;

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");

            $query = "CREATE TABLE `$table` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `scan_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `total_software` int unsigned NOT NULL DEFAULT 0,
                `whitelist_count` int unsigned NOT NULL DEFAULT 0,
                `blacklist_count` int unsigned NOT NULL DEFAULT 0,
                `unmanaged_count` int unsigned NOT NULL DEFAULT 0,
                `status` varchar(20) NOT NULL DEFAULT 'running',
                `scan_duration` int unsigned DEFAULT NULL,
                `report_sent` tinyint NOT NULL DEFAULT 0,
                `user_id` int unsigned NOT NULL DEFAULT 0,
                `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `date_mod` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `scan_date` (`scan_date`),
                KEY `status` (`status`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $DB->queryOrDie($query, $DB->error());
        }
    }
    
    /**
     * Uninstall database table for scan history
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
     * Create a new scan record
     * 
     * @param int $user_id User ID who initiated the scan
     * @return int|false Scan history ID or false on failure
     */
    function createScanRecord($user_id) {
        $input = [
            'scan_date' => $_SESSION["glpi_currenttime"],
            'total_software' => 0,
            'whitelist_count' => 0,
            'blacklist_count' => 0,
            'unmanaged_count' => 0,
            'status' => 'running',
            'scan_duration' => null,
            'report_sent' => 0,
            'user_id' => $user_id,
            'date_creation' => $_SESSION["glpi_currenttime"],
            'date_mod' => $_SESSION["glpi_currenttime"]
        ];
        
        return $this->add($input);
    }
    
    /**
     * Update scan record with final statistics
     * 
     * @param int $scan_id Scan ID
     * @param array $stats Statistics array
     * @param float $scan_duration Scan duration in seconds
     * @return bool Update success
     */
    function updateScanRecord($scan_id, $stats, $scan_duration) {
        $input = [
            'id' => $scan_id,
            'total_software' => $stats['total_software'],
            'whitelist_count' => $stats['whitelist_count'],
            'blacklist_count' => $stats['blacklist_count'],
            'unmanaged_count' => $stats['unmanaged_count'],
            'status' => 'completed',
            'scan_duration' => $scan_duration,
            'date_mod' => $_SESSION["glpi_currenttime"]
        ];
        
        return $this->update($input);
    }
    
    /**
     * Get search options for the class
     */
    function getSearchOptions() {
        $tab = [];

        $tab['common'] = __('Characteristics');

        $tab[1]['table']         = $this->getTable();
        $tab[1]['field']         = 'scan_date';
        $tab[1]['name']          = __('Scan Date', 'softwaremanager');
        $tab[1]['datatype']      = 'datetime';
        $tab[1]['massiveaction'] = false;

        $tab[2]['table']         = $this->getTable();
        $tab[2]['field']         = 'total_software';
        $tab[2]['name']          = __('Total Software', 'softwaremanager');
        $tab[2]['datatype']      = 'number';
        $tab[2]['massiveaction'] = false;

        $tab[3]['table']         = $this->getTable();
        $tab[3]['field']         = 'whitelist_count';
        $tab[3]['name']          = __('Whitelist Count', 'softwaremanager');
        $tab[3]['datatype']      = 'number';
        $tab[3]['massiveaction'] = false;

        $tab[4]['table']         = $this->getTable();
        $tab[4]['field']         = 'blacklist_count';
        $tab[4]['name']          = __('Blacklist Count', 'softwaremanager');
        $tab[4]['datatype']      = 'number';
        $tab[4]['massiveaction'] = false;

        $tab[5]['table']         = $this->getTable();
        $tab[5]['field']         = 'unmanaged_count';
        $tab[5]['name']          = __('Unmanaged Count', 'softwaremanager');
        $tab[5]['datatype']      = 'number';
        $tab[5]['massiveaction'] = false;

        $tab[6]['table']         = $this->getTable();
        $tab[6]['field']         = 'status';
        $tab[6]['name']          = __('Status', 'softwaremanager');
        $tab[6]['datatype']      = 'specific';
        $tab[6]['massiveaction'] = false;

        $tab[7]['table']         = $this->getTable();
        $tab[7]['field']         = 'scan_duration';
        $tab[7]['name']          = __('Duration (seconds)', 'softwaremanager');
        $tab[7]['datatype']      = 'number';
        $tab[7]['massiveaction'] = false;

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
            case 'status':
                $status_labels = [
                    'running' => __('Running', 'softwaremanager'),
                    'completed' => __('Completed', 'softwaremanager'),
                    'failed' => __('Failed', 'softwaremanager')
                ];
                return $status_labels[$values[$field]] ?? $values[$field];
        }
        
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Get search URL for this item type
     */
    static function getSearchURL($full = true) {
        global $CFG_GLPI;

        $itemtype = get_called_class();
        $link = $CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/scanhistory.php";

        if ($full) {
            $link .= "?itemtype=" . $itemtype;
        }

        return $link;
    }

    /**
     * Get form URL for this item type
     */
    static function getFormURL($full = true) {
        global $CFG_GLPI;

        $itemtype = get_called_class();
        $link = $CFG_GLPI["root_doc"] . "/plugins/softwaremanager/front/scanresult.php";

        if ($full) {
            $link .= "?itemtype=" . $itemtype;
        }

        return $link;
    }

    /**
     * Add a new scan record - alias for createScanRecord for consistency
     */
    static function addRecord($user_id) {
        $scanhistory = new self();
        return $scanhistory->createScanRecord($user_id);
    }

    /**
     * Update record stats - alias for updateScanRecord for consistency
     */
    static function updateRecordStats($historyId, $stats, $scan_duration = 0) {
        $scanhistory = new self();
        return $scanhistory->updateScanRecord($historyId, $stats, $scan_duration);
    }
}
?>
