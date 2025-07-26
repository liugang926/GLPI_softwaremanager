<?php
/**
 * 清理重复的插件权限记录
 * 在重新安装插件前运行此脚本
 */

// 包含GLPI核心文件
include('../../../inc/includes.php');

echo "=== 清理插件权限记录 ===\n\n";

global $DB;

// 1. 检查是否有重复的权限记录
echo "1. 检查重复的权限记录...\n";

$duplicate_query = "
    SELECT profiles_id, name, COUNT(*) as count 
    FROM glpi_profilerights 
    WHERE name = 'plugin_softwaremanager' 
    GROUP BY profiles_id, name 
    HAVING COUNT(*) > 1
";

$duplicates = $DB->query($duplicate_query);
$duplicate_count = $DB->numrows($duplicates);

if ($duplicate_count > 0) {
    echo "发现 {$duplicate_count} 个重复的权限记录\n";
    
    // 删除重复记录，只保留最新的
    echo "2. 清理重复记录...\n";
    
    while ($duplicate = $DB->fetchAssoc($duplicates)) {
        $profile_id = $duplicate['profiles_id'];
        
        // 获取该profile的所有plugin_softwaremanager权限记录
        $all_records = $DB->query("
            SELECT id FROM glpi_profilerights 
            WHERE profiles_id = {$profile_id} AND name = 'plugin_softwaremanager' 
            ORDER BY id ASC
        ");
        
        $record_ids = [];
        while ($record = $DB->fetchAssoc($all_records)) {
            $record_ids[] = $record['id'];
        }
        
        // 保留最后一个，删除其他的
        $keep_id = array_pop($record_ids);
        
        foreach ($record_ids as $delete_id) {
            $DB->query("DELETE FROM glpi_profilerights WHERE id = {$delete_id}");
            echo "  删除重复记录 ID: {$delete_id}\n";
        }
        
        echo "  保留记录 ID: {$keep_id} (profile_id: {$profile_id})\n";
    }
} else {
    echo "未发现重复的权限记录\n";
}

// 3. 检查所有相关的权限记录
echo "\n3. 当前的插件权限记录:\n";

$current_rights = $DB->query("
    SELECT pr.id, pr.profiles_id, pr.rights, p.name as profile_name
    FROM glpi_profilerights pr
    LEFT JOIN glpi_profiles p ON pr.profiles_id = p.id
    WHERE pr.name = 'plugin_softwaremanager'
    ORDER BY pr.profiles_id
");

if ($DB->numrows($current_rights) > 0) {
    echo "ID\tProfile ID\tProfile Name\tRights\n";
    echo "----\t----------\t------------\t------\n";
    
    while ($right = $DB->fetchAssoc($current_rights)) {
        echo "{$right['id']}\t{$right['profiles_id']}\t\t{$right['profile_name']}\t\t{$right['rights']}\n";
    }
} else {
    echo "未找到任何插件权限记录\n";
}

// 4. 可选：完全删除所有插件权限记录
echo "\n4. 如果需要，可以完全清理所有插件权限记录\n";
echo "要完全清理，请手动运行以下SQL命令：\n";
echo "DELETE FROM glpi_profilerights WHERE name = 'plugin_softwaremanager';\n";

echo "\n=== 清理完成 ===\n";
echo "现在可以重新安装插件了\n";
?>