-- ============================================
-- 应用新的通配符匹配规则 - 修复当前问题
-- ============================================

-- 1. 清空现有错误的规则数据
TRUNCATE TABLE glpi_plugin_softwaremanager_whitelists;
TRUNCATE TABLE glpi_plugin_softwaremanager_blacklists;

-- 2. 创建新的白名单规则（使用星号通配符语法）
INSERT INTO glpi_plugin_softwaremanager_whitelists (name, is_active, date_creation, comment) VALUES
-- 精确匹配规则（不含星号）
('Bonjour', 1, NOW(), '精确匹配测试'),
('Microsoft Visual C++ 2019 Redistributable (x64) - 14.29.30139', 1, NOW(), '精确匹配测试'),

-- 通配符规则（使用星号）
('Microsoft*', 1, NOW(), '匹配所有以Microsoft开头的软件'),
('Adobe*', 1, NOW(), '匹配所有以Adobe开头的软件'),
('*Chrome*', 1, NOW(), '匹配所有包含Chrome的软件');

-- 3. 创建新的黑名单规则（使用星号通配符语法）
INSERT INTO glpi_plugin_softwaremanager_blacklists (name, is_active, date_creation, comment) VALUES
-- 精确匹配规则（不含星号）
('Adobe Genuine Service', 1, NOW(), '精确匹配黑名单 - 应该被识别为违规'),
('WinRAR', 1, NOW(), '精确匹配黑名单'),

-- 通配符规则（使用星号）
('*torrent*', 1, NOW(), '匹配所有包含torrent的软件'),
('Barrier*', 1, NOW(), '匹配所有以Barrier开头的软件'),
('*crack*', 1, NOW(), '匹配所有包含crack的软件');

-- 4. 验证创建的规则
SELECT '=== 新白名单规则 (使用星号通配符) ===' as info;
SELECT name, is_active, comment, 
       CASE WHEN name LIKE '%*%' THEN '通配符' ELSE '精确' END as 匹配类型
FROM glpi_plugin_softwaremanager_whitelists 
ORDER BY name;

SELECT '=== 新黑名单规则 (使用星号通配符) ===' as info;
SELECT name, is_active, comment, 
       CASE WHEN name LIKE '%*%' THEN '通配符' ELSE '精确' END as 匹配类型
FROM glpi_plugin_softwaremanager_blacklists 
ORDER BY name;

-- 5. 预期测试结果说明
SELECT '=== 预期扫描结果 ===' as info;
SELECT '✅ 合规软件: Bonjour, Microsoft开头的软件, Adobe开头的软件(除Adobe Genuine Service)' as 预期;
SELECT '❌ 违规软件: Adobe Genuine Service, Barrier开头的软件' as 预期;
SELECT '❓ 未登记: 其他所有不匹配规则的软件' as 预期;