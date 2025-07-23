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
     * Static method to add software to blacklist
     * 保留这个静态方法用于向后兼容
     */
    static function addToList($software_name, $comment = '') {
        $blacklist = new self();
        
        // 检查是否已存在
        $existing = $blacklist->find(['software_name' => $software_name]);
        if (!empty($existing)) {
            return false; // 已存在
        }
        
        // 使用父类的add方法添加新记录
        $input = [
            'software_name' => $software_name,
            'comment' => $comment,
            'is_active' => 1,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s')
        ];
        
        return $blacklist->add($input);
    }
}
?>
