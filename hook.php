<?php
/**
* Public IP
*
* Copyright (C) 2020-2020 by the MSF OCB.
*
* https://www.msf-azg.be
* https://github.com/msf/glpi-pliguin-msf
*
* ------------------------------------------------------------------------
*
* LICENSE
*
* This file is part of MSF project.
*
* FusionInventory is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with FusionInventory. If not, see <http://www.gnu.org/licenses/>.
*
* ------------------------------------------------------------------------
*
* This file is used to manage the setup / initialize plugin
* FusionInventory.
*
* ------------------------------------------------------------------------
*
* @package   Public IP
* @copyright Copyright (c) 2020-2020 MSF OCB
* @license   AGPL License 3.0 or (at your option) any later version
*            http://www.gnu.org/licenses/agpl-3.0-standalone.html
* @link      https://www.msf-azg.be
* @link      https://github.com/msf/glpi-plugin-msf
*
*/

/**
* Manage the installation process
*
* @return boolean
*/
function plugin_kbrenaming_install() {

    global $DB;


    if (basename(filter_input(INPUT_SERVER, "SCRIPT_NAME")) != "cli_install.php") {
        Html::header(__('Setup'), filter_input(INPUT_SERVER, "PHP_SELF"), "config", "plugins");
        $migrationname = 'Migration';
    } else {
        $migrationname = 'CliMigration';
    }

    $migration = new $migrationname(PLUGIN_MSF_VERSION);
    $migration->displayMessage("creation Table in db ");

    //Create table only if it does not exists yet!
    if (!$DB->tableExists('glpi_plugin_kbrenaming_kbs')) {
        //table creation query
        $query = "CREATE TABLE `glpi_plugin_kbrenaming_kbs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
                  `plugin_kbrenaming_kbgroups_id` int(11) NOT NULL DEFAULT 0,
                  `disabled_update` tinyint(1) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `name_UNIQUE` (`name`),
                  KEY `name` (`name`),
                  KEY `plugin_kbrenaming_kbgroups_id` (`plugin_kbrenaming_kbgroups_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, $DB->error());
    }

    //Create table only if it does not exists yet!
    if (!$DB->tableExists('glpi_plugin_kbrenaming_kbgroups')) {
        //table creation query
        $query = "CREATE TABLE `glpi_plugin_kbrenaming_kbgroups` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
                  `softwarecategories_id` int(11) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `name_UNIQUE` (`name`),
                  KEY `name` (`name`),
                  KEY `softwarecategories_id` (`softwarecategories_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, $DB->error());
    }

    // add display preferences
    $query_display_pref = "SELECT id
      FROM glpi_displaypreferences
      WHERE itemtype = 'PluginKbrenamingKb'";
    $res_display_pref = $DB->query($query_display_pref);
    if ($DB->numrows($res_display_pref) == 0) {
        $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginKbrenamingKb','3002','1','0');");
        $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginKbrenamingKb','16','2','0');");
        $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginKbrenamingKb','3003','3','0');");
    }
    $query_display_pref = "SELECT id
      FROM glpi_displaypreferences
      WHERE itemtype = 'PluginKbrenamingKbGroup'";
    $res_display_pref = $DB->query($query_display_pref);
    if ($DB->numrows($res_display_pref) == 0) {
        $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginKbrenamingKbGroup','3001','1','0');");
        $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginKbrenamingKbGroup','16','2','0');");
    }

    //execute the whole migration
    //$migration->executeMigration();

    return true;
}


/**
* Manage the uninstallation of the plugin
*
* @return boolean
*/
function plugin_kbrenaming_uninstall() {
    global $DB;

    $tables = [
        'kbs',
        'kbgroups'
    ];

    foreach ($tables as $table) {
        $tablename = 'glpi_plugin_kbrenaming_' . $table;
        //Create table only if it does not exists yet!
        if ($DB->tableExists($tablename)) {
            $DB->queryOrDie(
                "DROP TABLE `$tablename`",
                $DB->error()
            );
        }
    }

    // clean display preferences
    $pref = new DisplayPreference;
    $pref->deleteByCriteria([
        'itemtype' => ['LIKE' , 'PluginKbrenaming%']
    ]);
    return true;
}

function plugin_item_add_update_kbrenaming(Software $parm): Software {
    global $DB;
    Toolbox::logDebug('-------------------- Start item_add/update : '. get_class($parm) .'--------------------');
    $kbName = $parm->fields['name'];
    if (preg_match('/^kb[0-9]{6,}$/i', $kbName,) !== 1) {
        return $parm;
    }
    Toolbox::logDebug('$parm : ' . print_r($parm, true));
    Toolbox::logDebug('$kbName : ' . $kbName);
    Toolbox::logDebug('$parm : ' . print_r($parm, true));
    $kb = new PluginKbrenamingKb();
    $kbData = $kb->getByName($kbName);
    if ($kbData === false){
        return $parm;
    }
    $old_field = $parm->fields;
    Toolbox::logDebug('$kbData : ' . print_r($kbData, true));
    $software = new Software();
    $condition = ['name' => $kbData->fields['plugin_kbrenaming_kbgroup']['name']];
    Toolbox::logDebug('$condition : ' . print_r($condition, true));
    $softs = $software->find($condition, [], 1);
    Toolbox::logDebug('$soft : ' . print_r($softs, true));
    if (empty($softs)){

        $manufacturer_db = new Manufacturer();
        $input_manufacturer = ['name' => 'Microsoft'];
        $manufacturers_id = $manufacturer_db->findID($input_manufacturer);

        $input = $kbData->fields['plugin_kbrenaming_kbgroup'];
        unset($input['id']);
        unset($input['softwarecategory']);
        $input['entities_id'] = $old_field['entities_id'];
        $input['is_recursive'] = 1;
        $input['manufacturers_id'] = $manufacturers_id;
        $soft_id = $software->add($input);
    }else{
        $soft = array_shift($softs);
        $soft_id = $soft['id'];
        $software->getFromDB($soft_id);
    }
    $parm->fields = $software->fields;

    $operatingsystem_db = new OperatingSystem();
    $input_operatingsystem = ['name' => 'Windows'];
    $operatingsystems_id = $operatingsystem_db->findID($input_operatingsystem);

    $softwareversion = new SoftwareVersion();
    $condition = ['name' => $kbData->fields['name']];
    $soft_versions = $softwareversion->find($condition,[],1);
    if (empty($soft_versions)) {
        $input = [
            'name' => $kbData->fields['name'],
            'comment' => $kbData->fields['comment'],
            'entities_id' => $old_field['entities_id'],
            'is_recursive' => 1,
            'softwares_id' => $soft_id,
            'operatingsystems_id' => $operatingsystems_id
        ];
        $soft_version_id = $softwareversion->add($input);
    }else{
        $soft_version = array_shift($soft_versions);
        $soft_version_id = $soft_version['id'];
        $softwareversion->getFromDB($soft_version_id);
    }
    if ($soft_version_id>0){
        $condition = ['softwares_id' => $old_field['id']];
        $softwareversions =  $softwareversion->find($condition);
        foreach ($softwareversions as $id => $softwareversion){
            PluginKbrenamingToolbox::change_softwareversion($id, $soft_version_id);
        }
    }
    if ($old_field['id'] !== $parm->fields['id'] ){
        $result = $DB->query("
                DELETE FROM `" . SoftwareVersion::getTable() . "`
                WHERE `" . SoftwareVersion::getTable() . "`.`softwares_id` = '" . $old_field['id'] . "' ;
        ");
        if($result!== false){
            $condition = ['id' => $old_field['id']];
            $software->delete($condition);
        }
    }
    return $parm ;
}

function plugin_post_item_form_kbrenaming($params){
    if (isset($params['item']) && $params['item'] instanceof CommonDBTM && get_class($params['item']) == 'Software') {
        Toolbox::logDebug('-------------------- Start post_item_form : '. get_class($params['item']) .'--------------------');
        $software = $params['item'];
        if ($software->fields['is_deleted']==1 && preg_match('/^kb[0-9]{6,}$/i', $software->fields['name'],) === 1){
            $softwareversion = new SoftwareVersion();
            $condition = ['name' => $software->fields['name']];
            $soft_versions = $softwareversion->find($condition,[],1);
            if (!empty($soft_versions)){
                $soft_version = array_shift($soft_versions);
                if(!empty($soft_version['softwares_id'])) {
                    $condition = ['id' => $software->fields['id']];
                    $software->delete($condition, true);
                    Html::redirect($software->getFormURLWithID($soft_version['softwares_id']));
                }
            }

        }

    }
}

/**
 * Extra MMODEL and ENVS and copy in msf section in inventory
 *
 * @params object $parms
 * @return object
 */
function plugin_fusioninventory_addinventoryinfos_kbrenaming($params = []){
    Toolbox::logDebug('-------------------- Start plugin_fusioninventory_addinventoryinfos_kbrenaming : --------------------');
    foreach ($params['inventory']['SOFTWARES'] as &$software){
        $kbName = trim($software['NAME']);
        if (preg_match('/^kb[0-9]{6,}$/i', $kbName,) !== 1) {
            continue;
        }
        $kb = new PluginKbrenamingKb();
        $kbData = $kb->getByName($kbName);
        if ($kbData === false){
            continue;
        }
        $software['COMMENTS'] = $kbData->fields['comment'];
        $software['NAME'] = $kbData->fields['plugin_kbrenaming_kbgroup']['name'];
        $software['VERSION'] = $kbName;
        $software['PUBLISHER'] = 'Microsoft';
        $software['SYSTEM_CATEGORY'] = $kbData->fields['plugin_kbrenaming_kbgroup']['softwarecategory']['name'];
    }
    return $params;
}
/**
 * Define Dropdown tables to be manage in GLPI
 *
 * @return array
 */
function plugin_kbrenaming_getDropdown()
{
    //    error_log('function plugin_kbrenaming_getDropdown');
    $plugin = new Plugin();


    if ($plugin->isActivated("kbrenaming")) {
        return [
            'PluginKbrenamingKb' => PluginKbrenamingKb::getTypeName(2),
            'PluginKbrenamingKbGroup' => PluginKbrenamingKbGroup::getTypeName(2),
        ];
    } else {
        return [];
    }
}

// Define dropdown relations
function plugin_kbrenaming_getDatabaseRelations() {
    $plugin = new Plugin();

    if ($plugin->isActivated("kbrenaming")) {
        return ["glpi_plugin_kbrenaming_kbgroups" => ["glpi_plugin_kbrenaming_kbs" => "plugin_kbrenaming_kbgroups_id"],
            "glpi_softwarecategories" => ["glpi_plugin_kbrenaming_kbgroups" => "softwarecategories_id"]];
    } else {
        return [];
    }
}
