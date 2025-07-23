<?php
/**
 * Software Manager Plugin for GLPI
 * Whitelist Management Page
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
Html::header(__('Whitelist Management', 'softwaremanager'), $_SERVER['PHP_SELF'], 'admin', 'PluginSoftwaremanagerMenu');

// Display navigation
PluginSoftwaremanagerMenu::displayNavigationHeader('whitelist');

// Handle quick add action with CSRF check
if (isset($_POST['quick_add']) && isset($_POST['software_name'])) {
    Session::checkCSRF($_POST); // 添加CSRF安全检查
    $software_name = Html::cleanInputText($_POST['software_name']);
    $comment = isset($_POST['comment']) ? Html::cleanInputText($_POST['comment']) : '';
    
    if (!empty($software_name)) {
        try {
            if (PluginSoftwaremanagerSoftwareWhitelist::addToList($software_name, $comment)) {
                Session::addMessageAfterRedirect("软件 '$software_name' 已成功添加到白名单", false, INFO);
            } else {
                Session::addMessageAfterRedirect("无法添加软件到白名单，可能已存在", false, WARNING);
            }
        } catch (Exception $e) {
            Session::addMessageAfterRedirect("添加失败: " . $e->getMessage(), false, ERROR);
        }
    } else {
        Session::addMessageAfterRedirect("软件名称不能为空", false, ERROR);
    }
    Html::redirect($_SERVER['PHP_SELF']);
}

// Quick add form with CSRF token
echo "<div class='center' style='margin-bottom: 20px;'>";
echo "<h3>" . __('Quick Add to Whitelist', 'softwaremanager') . "</h3>";
echo "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "' style='display: inline-block; text-align: left;'>";
Session::formToken(); // 添加CSRF安全令牌
echo "<table class='tab_cadre_fixe' style='width: 600px;'>";
echo "<tr class='tab_bg_1'>";
echo "<td style='width: 150px;'><label for='software_name'>" . __('Software Name', 'softwaremanager') . ":</label></td>";
echo "<td><input type='text' name='software_name' id='software_name' size='40' required placeholder='" . __('Enter software name', 'softwaremanager') . "'></td>";
echo "</tr>";
echo "<tr class='tab_bg_1'>";
echo "<td><label for='comment'>" . __('Comment', 'softwaremanager') . ":</label></td>";
echo "<td><input type='text' name='comment' id='comment' size='40' placeholder='" . __('Optional comment', 'softwaremanager') . "'></td>";
echo "</tr>";
echo "<tr class='tab_bg_1'>";
echo "<td colspan='2' class='center'>";
echo "<button type='submit' name='quick_add' class='btn btn-primary'>";
echo "<i class='fas fa-plus'></i> " . __('Add to Whitelist', 'softwaremanager');
echo "</button>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</form>";
echo "</div>";

// Display whitelist entries
Search::show('PluginSoftwaremanagerSoftwareWhitelist');

Html::footer();
?>
