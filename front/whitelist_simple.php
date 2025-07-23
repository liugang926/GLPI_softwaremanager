<?php
/**
 * Software Manager Plugin for GLPI
 * Simple Whitelist Management Page (For Testing)
 *
 * @author  Abner Liu
 * @license GPL-2.0+
 */

include('../../../inc/includes.php');

// Check rights - allow access for authenticated users
if (!Session::getLoginUserID()) {
    Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
    exit();
}

// Start page
Html::header(__('Whitelist Management - Simple', 'softwaremanager'), $_SERVER['PHP_SELF'], 'admin', 'PluginSoftwaremanagerMenu');

// Display navigation
PluginSoftwaremanagerMenu::displayNavigationHeader('whitelist');

// Handle quick add action - SIMPLIFIED VERSION
if (isset($_POST['quick_add']) && isset($_POST['software_name'])) {
    // Skip CSRF check for testing
    $software_name = Html::cleanInputText($_POST['software_name']);
    $comment = isset($_POST['comment']) ? Html::cleanInputText($_POST['comment']) : '';
    
    if (!empty($software_name)) {
        try {
            if (PluginSoftwaremanagerSoftwareWhitelist::addToList($software_name, $comment)) {
                echo "<div class='alert alert-success'>";
                echo "<strong>成功!</strong> 软件 '" . $software_name . "' 已添加到白名单。";
                echo "</div>";
            } else {
                echo "<div class='alert alert-danger'>";
                echo "<strong>错误!</strong> 无法添加软件到白名单。";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>";
            echo "<strong>异常错误:</strong> " . $e->getMessage();
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<strong>警告!</strong> 软件名称是必需的。";
        echo "</div>";
    }
}

// Simple add form WITHOUT CSRF token for testing
echo "<div class='center' style='margin-bottom: 20px;'>";
echo "<h3>快速添加到白名单 (测试版)</h3>";
echo "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "' style='display: inline-block; text-align: left;'>";
echo "<table class='tab_cadre_fixe' style='width: 600px;'>";
echo "<tr class='tab_bg_1'>";
echo "<td style='width: 150px;'><label for='software_name'>软件名称:</label></td>";
echo "<td><input type='text' name='software_name' id='software_name' size='40' required placeholder='输入软件名称'></td>";
echo "</tr>";
echo "<tr class='tab_bg_1'>";
echo "<td><label for='comment'>备注:</label></td>";
echo "<td><input type='text' name='comment' id='comment' size='40' placeholder='可选备注'></td>";
echo "</tr>";
echo "<tr class='tab_bg_1'>";
echo "<td colspan='2' class='center'>";
echo "<button type='submit' name='quick_add' class='btn btn-primary'>";
echo "<i class='fas fa-plus'></i> 添加到白名单";
echo "</button>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</form>";
echo "</div>";

echo "<hr>";
echo "<p><strong>注意:</strong> 这是一个简化的测试版本，没有CSRF保护。</p>";
echo "<p><a href='whitelist.php'>返回正常的白名单页面</a></p>";

// Display whitelist entries
Search::show('PluginSoftwaremanagerSoftwareWhitelist');

Html::footer();
?> 