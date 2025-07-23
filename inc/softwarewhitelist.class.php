<?php
/**
 * Software Manager Plugin for GLPI
 * Whitelist Management Class
 */

class PluginSoftwaremanagerSoftwareWhitelist extends CommonDBTM
{
    // 这个类可以非常简洁！
    // 我们不需要自己编写 add, update, delete 等方法。
    // 我们会直接从它的父类 CommonDBTM 继承所有功能强大且安全的方法。
    // GLPI 会自动根据您的类名和数据库表名处理一切。
    
    /**
     * Get the database table name for this class
     */
    static function getTable($classname = null) {
        return 'glpi_plugin_softwaremanager_whitelists';
    }
    
    /**
     * Get the type name for this class
     */
    static function getTypeName($nb = 0) {
        return _n('Software Whitelist', 'Software Whitelists', $nb, 'softwaremanager');
    }
    
    /**
     * Install database table for whitelist
     */
    static function install(Migration $migration) {
        global $DB;

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");

            $query = "CREATE TABLE `$table` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `comment` text,
                `is_active` tinyint NOT NULL DEFAULT '1',
                `is_deleted` tinyint NOT NULL DEFAULT '0',
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `name` (`name`),
                KEY `is_active` (`is_active`),
                KEY `is_deleted` (`is_deleted`),
                KEY `date_creation` (`date_creation`),
                KEY `date_mod` (`date_mod`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $DB->queryOrDie($query, "Error creating table $table");
        }

        return true;
    }

    /**
     * Uninstall database table for whitelist
     */
    static function uninstall() {
        global $DB;

        $table = self::getTable();

        if ($DB->tableExists($table)) {
            $query = "DROP TABLE `$table`";
            $DB->queryOrDie($query, "Error dropping table $table");
        }

        return true;
    }

    /**
     * Static method to add software to whitelist
     * 保留这个静态方法用于向后兼容
     */
    static function addToList($software_name, $comment = '') {
        $whitelist = new self();

        // 检查是否已存在 - 使用正确的字段名 'name'
        $existing = $whitelist->find(['name' => $software_name]);
        if (!empty($existing)) {
            return false; // 已存在
        }

        // 使用父类的add方法添加新记录 - 使用正确的字段名 'name'
        $input = [
            'name' => $software_name,
            'comment' => $comment,
            'is_active' => 1,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s')
        ];

        return $whitelist->add($input);
    }
}
?>
