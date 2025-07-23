<?php
/**
 * Software Manager Plugin for GLPI
 * Whitelist Form Page
 * 
 * @author  Abner Liu
 * @license GPL-2.0+
 */

include('../../../inc/includes.php');

// Check rights
Session::checkRight('plugin_softwaremanager_lists', READ);

$whitelist = new PluginSoftwaremanagerSoftwareWhitelist();

if (isset($_POST["add"])) {
    $whitelist->check(-1, CREATE, $_POST);
    if ($newID = $whitelist->add($_POST)) {
        Event::log($newID, "PluginSoftwaremanagerSoftwareWhitelist", 4, "setup",
                   sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($whitelist->getLinkURL());
        }
    }
    Html::back();

} else if (isset($_POST["delete"])) {
    $whitelist->check($_POST["id"], DELETE);
    $whitelist->delete($_POST);
    Event::log($_POST["id"], "PluginSoftwaremanagerSoftwareWhitelist", 4, "setup",
               sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
    $whitelist->redirectToList();

} else if (isset($_POST["restore"])) {
    $whitelist->check($_POST["id"], DELETE);
    $whitelist->restore($_POST);
    Event::log($_POST["id"], "PluginSoftwaremanagerSoftwareWhitelist", 4, "setup",
               sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
    $whitelist->redirectToList();

} else if (isset($_POST["purge"])) {
    $whitelist->check($_POST["id"], PURGE);
    $whitelist->delete($_POST, 1);
    Event::log($_POST["id"], "PluginSoftwaremanagerSoftwareWhitelist", 4, "setup",
               sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
    $whitelist->redirectToList();

} else if (isset($_POST["update"])) {
    $whitelist->check($_POST["id"], UPDATE);
    $whitelist->update($_POST);
    Event::log($_POST["id"], "PluginSoftwaremanagerSoftwareWhitelist", 4, "setup",
               sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
    Html::back();

} else {
    $menus = ["admin", "PluginSoftwaremanagerMenu"];
    PluginSoftwaremanagerSoftwareWhitelist::displayFullPageForItem($_GET["id"], $menus, [
        'formoptions'  => "method='post'"
    ]);
}
