# 解决插件权限重复错误

## 错误信息
```
数据库查询时遇到错误: INSERT INTO `glpi_profilerights` (`profiles_id`, `name`, `rights`) VALUES ('1', 'plugin_softwaremanager', '15') - 错误是 Duplicate entry '1-plugin_softwaremanager' for key 'unicity'
```

## 解决方案

### 方案1：手动清理数据库（推荐）

在GLPI数据库中运行以下SQL命令：

```sql
-- 删除所有现有的插件权限记录
DELETE FROM glpi_profilerights WHERE name = 'plugin_softwaremanager';
```

### 方案2：使用清理脚本

1. 将 `cleanup_rights.php` 文件放在GLPI的plugins/softwaremanager/目录下
2. 通过浏览器访问：`http://your-glpi-url/plugins/softwaremanager/cleanup_rights.php`
3. 脚本会自动清理重复记录

### 方案3：完全重新安装

1. **卸载插件**
   - 在GLPI管理界面中卸载插件
   
2. **手动清理数据库**
   ```sql
   DELETE FROM glpi_profilerights WHERE name = 'plugin_softwaremanager';
   DROP TABLE IF EXISTS glpi_plugin_softwaremanager_whitelists;
   DROP TABLE IF EXISTS glpi_plugin_softwaremanager_blacklists;
   DROP TABLE IF EXISTS glpi_plugin_softwaremanager_scanhistory;
   DROP TABLE IF EXISTS glpi_plugin_softwaremanager_scanresults;
   ```

3. **重新安装插件**
   - 现在安装脚本已经修复，会正确处理已存在的权限记录

## 修复的改进

✅ **智能权限管理**
- 安装前检查权限是否已存在
- 如果存在则更新，不存在则创建
- 避免重复插入错误

✅ **更安全的数据库操作**
- 使用 `$DB->insert()` 替代 `$DB->insertOrDie()`
- 添加错误处理和重复检查

## 验证安装成功

安装完成后，检查：
1. GLPI管理菜单中出现"软件管理"选项
2. 可以访问白名单/黑名单管理
3. 可以执行软件合规性扫描

如果仍有问题，请检查GLPI错误日志：`files/_log/php-errors.log`