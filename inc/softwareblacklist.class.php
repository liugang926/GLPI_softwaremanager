<?php
/**
 * Software Manager Plugin for GLPI
 * Blacklist Management Class
 */

class PluginSoftwaremanagerSoftwareBlacklist extends CommonDBTM
{
    // 这个类可以非常简洁！
    // 我们不需要自己编写 add, update, delete 等方法。
    // 我们会直接从它的父类 CommonDBTM 继承所有功能强大且安全的方法。
    // GLPI 会自动根据您的类名和数据库表名处理一切。
    
    /**
     * Get the database table name for this class
     */
    static function getTable($classname = null) {
        return 'glpi_plugin_softwaremanager_blacklists';
    }
    
    /**
     * Get the type name for this class
     */
    static function getTypeName($nb = 0) {
        return _n('Software Blacklist', 'Software Blacklists', $nb, 'softwaremanager');
    }
    
    /**
     * Install database table for blacklist
     */
    static function install(Migration $migration) {
        global $DB;

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");

            $query = "CREATE TABLE `$table` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `version` varchar(100) DEFAULT NULL,
                `publisher` varchar(255) DEFAULT NULL,
                `category` varchar(100) DEFAULT NULL,
                `license_type` varchar(50) DEFAULT 'unknown',
                `install_path` text,
                `description` text,
                `comment` text,
                `exact_match` tinyint NOT NULL DEFAULT '0',
                `is_active` tinyint NOT NULL DEFAULT '1',
                `priority` int NOT NULL DEFAULT '0',
                `is_deleted` tinyint NOT NULL DEFAULT '0',
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `name` (`name`),
                KEY `publisher` (`publisher`),
                KEY `category` (`category`),
                KEY `license_type` (`license_type`),
                KEY `exact_match` (`exact_match`),
                KEY `is_active` (`is_active`),
                KEY `priority` (`priority`),
                KEY `is_deleted` (`is_deleted`),
                KEY `date_creation` (`date_creation`),
                KEY `date_mod` (`date_mod`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $DB->queryOrDie($query, "Error creating table $table");
        }

        return true;
    }

    /**
     * Uninstall database table for blacklist
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
     * Static method to add software to blacklist
     * 保留这个静态方法用于向后兼容
     *
     * @param string $software_name 软件名称
     * @param string $comment 备注
     * @return array 返回操作结果 ['success' => bool, 'action' => string, 'id' => int|null]
     */
    static function addToList($software_name, $comment = '') {
        $blacklist = new self();

        // 检查是否已存在 - 使用正确的字段名 'name'
        $existing = $blacklist->find(['name' => $software_name]);

        if (!empty($existing)) {
            // 记录存在，检查其状态
            $record = reset($existing); // 获取第一条记录
            $record_id = $record['id'];

            // 检查记录是否被删除或非活动状态
            if ($record['is_deleted'] == 1 || $record['is_active'] == 0) {
                // 恢复记录：设置为活动状态且未删除
                $update_data = [
                    'id' => $record_id,
                    'is_active' => 1,
                    'is_deleted' => 0,
                    'comment' => $comment, // 更新备注
                    'date_mod' => date('Y-m-d H:i:s')
                ];

                if ($blacklist->update($update_data)) {
                    return ['success' => true, 'action' => 'restored', 'id' => $record_id];
                } else {
                    return ['success' => false, 'action' => 'restore_failed', 'id' => $record_id];
                }
            } else {
                // 记录存在且处于活动状态
                return ['success' => false, 'action' => 'already_exists', 'id' => $record_id];
            }
        }

        // 记录不存在，创建新记录
        $input = [
            'name' => $software_name,
            'comment' => $comment,
            'is_active' => 1,
            'is_deleted' => 0,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s')
        ];

        $new_id = $blacklist->add($input);
        if ($new_id) {
            return ['success' => true, 'action' => 'created', 'id' => $new_id];
        } else {
            return ['success' => false, 'action' => 'create_failed', 'id' => null];
        }
    }

    /**
     * 扩展的添加方法，支持对象管理
     */
    static function addToListExtended($data) {
        $blacklist = new self();

        // 设置默认值
        $input = [
            'name' => $data['name'],
            'version' => $data['version'] ?? null,
            'publisher' => $data['publisher'] ?? null,
            'category' => $data['category'] ?? null,
            'license_type' => $data['license_type'] ?? 'unknown',
            'install_path' => $data['install_path'] ?? null,
            'description' => $data['description'] ?? null,
            'comment' => $data['comment'] ?? '',
            'exact_match' => $data['exact_match'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'priority' => $data['priority'] ?? 0,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s')
        ];

        return $blacklist->add($input);
    }

    /**
     * 显示表单
     */
    function showForm($ID, $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Software Name', 'softwaremanager') . " *</td>";
        echo "<td>";
        Html::autocompletionTextField($this, "name", ['required' => true]);
        echo "</td>";
        echo "<td>" . __('Version', 'softwaremanager') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, "version");
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Publisher', 'softwaremanager') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, "publisher");
        echo "</td>";
        echo "<td>" . __('Category', 'softwaremanager') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, "category");
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('License Type', 'softwaremanager') . "</td>";
        echo "<td>";
        $license_types = [
            'unknown' => __('Unknown', 'softwaremanager'),
            'free' => __('Free', 'softwaremanager'),
            'commercial' => __('Commercial', 'softwaremanager'),
            'trial' => __('Trial', 'softwaremanager'),
            'open_source' => __('Open Source', 'softwaremanager')
        ];
        Dropdown::showFromArray('license_type', $license_types, [
            'value' => $this->fields['license_type'] ?? 'unknown'
        ]);
        echo "</td>";
        echo "<td>" . __('Priority', 'softwaremanager') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, "priority", ['value' => $this->fields['priority'] ?? 0]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Exact Match', 'softwaremanager') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('exact_match', $this->fields['exact_match'] ?? 0);
        echo "</td>";
        echo "<td>" . __('Active', 'softwaremanager') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->fields['is_active'] ?? 1);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Installation Path', 'softwaremanager') . "</td>";
        echo "<td colspan='3'>";
        Html::autocompletionTextField($this, "install_path", ['size' => 80]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Description', 'softwaremanager') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='description' rows='3' cols='80'>" .
             Html::cleanInputText($this->fields['description'] ?? '') . "</textarea>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Comment', 'softwaremanager') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='comment' rows='3' cols='80'>" .
             Html::cleanInputText($this->fields['comment'] ?? '') . "</textarea>";
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * 准备输入数据
     */
    function prepareInputForAdd($input) {
        // 设置默认值
        if (!isset($input['exact_match'])) {
            $input['exact_match'] = 0;
        }
        if (!isset($input['is_active'])) {
            $input['is_active'] = 1;
        }
        if (!isset($input['priority'])) {
            $input['priority'] = 0;
        }
        if (!isset($input['license_type'])) {
            $input['license_type'] = 'unknown';
        }

        return $input;
    }
}
?>
