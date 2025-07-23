# Software Manager Plugin - Installation Fixes

## 问题描述
在插件安装过程中出现错误：
```
Uncaught Exception Error: Call to undefined method Plugin::registerProfile() in /var/www/html/glpi/plugins/softwaremanager/setup.php at line 152
```

## 修复内容

### 1. 权限注册方式修复
**问题**: 使用了不存在的 `Plugin::registerProfile()` 方法
**修复**: 改为使用 `PluginSoftwaremanagerProfile::initProfile()` 方法

**修改文件**: `setup.php`
- 移除了错误的 `Plugin::registerProfile()` 调用
- 改为调用 `PluginSoftwaremanagerProfile::initProfile()`

### 2. Profile 类方法重构
**问题**: `getAllRights()` 方法返回格式不正确
**修复**: 重构方法以返回 GLPI 期望的格式

**修改文件**: `inc/profile.class.php`
- 重构 `getAllRights()` 方法返回正确的数组格式
- 修复 `initProfile()` 方法以正确初始化权限
- 修复 `addRightsToSession()` 方法以处理新的数组格式
- 使用数字常量替代未定义的权限常量

### 3. 类文件包含
**问题**: 安装过程中类文件可能未被自动加载
**修复**: 在安装和卸载函数中手动包含必要的类文件

**修改文件**: `setup.php`
- 在 `plugin_softwaremanager_install()` 中添加类文件包含
- 在 `plugin_softwaremanager_uninstall()` 中添加类文件包含

## 权限系统说明

插件定义了以下权限：

1. **plugin_softwaremanager_config** (权限值: 3)
   - 配置插件
   - 权限: READ + UPDATE

2. **plugin_softwaremanager_lists** (权限值: 31)
   - 管理黑白名单
   - 权限: READ + UPDATE + CREATE + DELETE + PURGE

3. **plugin_softwaremanager_software** (权限值: 1)
   - 查看软件清单
   - 权限: READ

4. **plugin_softwaremanager_scan** (权限值: 7)
   - 运行和查看合规扫描
   - 权限: READ + UPDATE + CREATE

## 权限值说明
- READ = 1
- UPDATE = 2  
- CREATE = 4
- DELETE = 8
- PURGE = 16

组合权限通过相加计算，例如：
- READ + UPDATE = 1 + 2 = 3
- READ + UPDATE + CREATE = 1 + 2 + 4 = 7
- 全部权限 = 1 + 2 + 4 + 8 + 16 = 31

## 安装流程

1. **权限初始化**: 调用 `PluginSoftwaremanagerProfile::initProfile()`
   - 注册插件权限到 GLPI 系统
   - 为当前用户配置文件分配权限

2. **数据库表创建**: 
   - 调用 `PluginSoftwaremanagerSoftwareWhitelist::install()`
   - 调用 `PluginSoftwaremanagerSoftwareBlacklist::install()`

3. **迁移执行**: 调用 `$migration->executeMigration()`

## 验证安装

安装完成后，可以通过以下方式验证：

1. 检查 GLPI 管理界面中是否出现 "Software Manager" 菜单
2. 检查用户配置文件中是否有插件相关权限设置
3. 检查数据库中是否创建了以下表：
   - `glpi_plugin_softwaremanager_whitelists`
   - `glpi_plugin_softwaremanager_blacklists`

## 故障排除

如果安装仍然失败，请检查：

1. GLPI 版本是否符合要求 (>= 10.0.0)
2. PHP 版本是否符合要求 (>= 8.0)
3. 插件文件权限是否正确
4. GLPI 错误日志中的详细错误信息

## 测试脚本

可以运行以下测试脚本验证修复：
- `test_installation.php` - 测试安装相关功能
- `test_step1.php` - 测试基础框架
- `test_step2.php` - 测试黑白名单功能
- `test_step3.php` - 测试软件清单功能
