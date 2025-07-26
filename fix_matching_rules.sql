-- ============================================
-- 快速修复扫描结果问题的SQL脚本
-- ============================================

-- 1. 首先查看当前状态
SELECT '=== 当前白名单状态 ===' as info;
SELECT name, exact_match, is_active FROM glpi_plugin_softwaremanager_whitelists WHERE is_active = 1 LIMIT 10;

SELECT '=== 当前黑名单状态 ===' as info;
SELECT name, exact_match, is_active FROM glpi_plugin_softwaremanager_blacklists WHERE is_active = 1 LIMIT 10;

-- 2. 解决方案A：将关键白名单规则改为精确匹配（推荐）
-- 这样可以避免过度匹配
UPDATE glpi_plugin_softwaremanager_whitelists 
SET exact_match = 1 
WHERE name IN (
    '64 bit hp cio components installer',
    'adobe acrobat (64-bit)',
    'bonjour',
    'barrier 2.4.0-release'
) AND is_active = 1;

-- 3. 确保 Adobe Genuine Service 在黑名单中且为精确匹配
-- 先检查黑名单中是否有这个软件
INSERT IGNORE INTO glpi_plugin_softwaremanager_blacklists 
(name, exact_match, is_active, date_creation) 
VALUES ('adobe genuine service', 1, 1, NOW());

-- 同时从白名单中移除（如果存在）
UPDATE glpi_plugin_softwaremanager_whitelists 
SET is_active = 0 
WHERE name = 'adobe genuine service';

-- 4. 解决方案B（如果方案A无效）：临时禁用大部分白名单规则
-- 只保留几个明确的精确匹配规则
-- UPDATE glpi_plugin_softwaremanager_whitelists SET is_active = 0;
-- UPDATE glpi_plugin_softwaremanager_whitelists 
-- SET is_active = 1, exact_match = 1 
-- WHERE name IN ('bonjour');

-- 5. 添加更多测试黑名单规则
INSERT IGNORE INTO glpi_plugin_softwaremanager_blacklists 
(name, exact_match, is_active, date_creation) 
VALUES 
('barrier', 0, 1, NOW()),  -- 通配符匹配 "Barrier 2.4.0-release"
('64 bit hp cio', 0, 1, NOW());  -- 通配符匹配 "64 Bit HP CIO Components Installer"

-- 6. 验证修改结果
SELECT '=== 修改后白名单状态 ===' as info;
SELECT name, exact_match, is_active FROM glpi_plugin_softwaremanager_whitelists WHERE is_active = 1;

SELECT '=== 修改后黑名单状态 ===' as info;
SELECT name, exact_match, is_active FROM glpi_plugin_softwaremanager_blacklists WHERE is_active = 1;

-- ============================================
-- 预期结果（修改后重新扫描）：
-- ✅ 合规: Bonjour, Adobe Acrobat (64-bit) 等精确匹配的白名单软件
-- ❌ 违规: Adobe Genuine Service, Barrier 2.4.0-release, 64 Bit HP CIO Components Installer
-- ❓ 未登记: 其他所有软件
-- ============================================