-- ============================================
-- 新的通配符匹配规则测试数据
-- ============================================

-- 1. 清空现有规则（重新开始）
TRUNCATE TABLE glpi_plugin_softwaremanager_whitelists;
TRUNCATE TABLE glpi_plugin_softwaremanager_blacklists;

-- 2. 创建新的白名单规则（使用新的通配符语法）
INSERT INTO glpi_plugin_softwaremanager_whitelists (name, is_active, date_creation, comment) VALUES
-- 精确匹配规则（不含星号）
('Bonjour', 1, NOW(), '精确匹配测试'),
('Microsoft Visual C++ 2019 Redistributable (x64) - 14.29.30139', 1, NOW(), '精确匹配测试'),

-- 通配符规则
('Adobe*', 1, NOW(), '匹配所有以Adobe开头的软件'),
('*Chrome*', 1, NOW(), '匹配所有包含Chrome的软件'),
('Microsoft*', 1, NOW(), '匹配所有Microsoft软件');

-- 3. 创建新的黑名单规则（使用新的通配符语法）
INSERT INTO glpi_plugin_softwaremanager_blacklists (name, is_active, date_creation, comment) VALUES
-- 精确匹配规则
('Adobe Genuine Service', 1, NOW(), '精确匹配黑名单'),
('WinRAR', 1, NOW(), '精确匹配黑名单'),

-- 通配符规则
('*torrent*', 1, NOW(), '匹配所有包含torrent的软件'),
('Barrier*', 1, NOW(), '匹配所有以Barrier开头的软件'),
('*crack*', 1, NOW(), '匹配所有包含crack的软件'),
('*keygen*', 1, NOW(), '匹配所有包含keygen的软件');

-- 4. 验证创建的规则
SELECT '=== 白名单规则 ===' as info;
SELECT name, is_active, comment FROM glpi_plugin_softwaremanager_whitelists ORDER BY name;

SELECT '=== 黑名单规则 ===' as info;
SELECT name, is_active, comment FROM glpi_plugin_softwaremanager_blacklists ORDER BY name;

-- ============================================
-- 新规则说明：
-- 
-- 1. 精确匹配（不含*）：
--    'Bonjour' 只匹配 'Bonjour'（不区分大小写）
--
-- 2. 前缀通配符：
--    'Adobe*' 匹配 'Adobe', 'Adobe Acrobat', 'Adobe Photoshop' 等
--
-- 3. 后缀通配符：
--    '*Chrome' 匹配 'Chrome', 'Google Chrome', 'Super Chrome' 等
--
-- 4. 包含通配符：
--    '*Chrome*' 匹配任何包含'Chrome'的软件名
--
-- 预期扫描结果：
-- ✅ 合规: Bonjour, Adobe Acrobat (64-bit), Microsoft 软件
-- ❌ 违规: Adobe Genuine Service, Barrier 2.4.0-release
-- ❓ 未登记: 其他软件
-- ============================================