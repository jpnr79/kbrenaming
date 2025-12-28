<?php
if (!defined('GLPI_ROOT')) { define('GLPI_ROOT', realpath(__DIR__ . '/../..')); }
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

define ("PLUGIN_KBRENAMING_VERSION", "2.0.0");
// Minimal GLPI version, inclusive
define('PLUGIN_KBRENAMING_GLPI_MIN_VERSION', '10.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_KBRENAMING_GLPI_MAX_VERSION', '12.1.0');

define ("PLUGIN_KBRENAMING_OFFICIAL_RELEASE", "1");
define ("PLUGIN_KBRENAMING_REALVERSION", PLUGIN_KBRENAMING_VERSION . "");
define ("PLUGIN_KBRENAMING_PROCEDURE", "glpi_plugin_kbrenaming");

/**
 * Init the hooks of MSF
 *
 * @global array $PLUGIN_HOOKS
 * @global array $CFG_GLPI
 */
function plugin_init_kbrenaming() {
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['kbrenaming'] = true;

    $Plugin = new Plugin();
    $moduleId = 0;

      $debug_mode = true;
      if (isset($_SESSION['glpi_use_mode'])) {
         $debug_mode = ($_SESSION['glpi_use_mode'] === (defined('Session::DEBUG_MODE') ? constant('Session::DEBUG_MODE') : 2));
      }
      $is_installed = method_exists($Plugin, 'isInstalled') ? $Plugin->isInstalled('kbrenaming') : true;
      $is_activated = method_exists($Plugin, 'isActivated') ? $Plugin->isActivated('kbrenaming') : true;
      if ($is_installed && $is_activated) { // check if plugin is active

        Plugin::registerClass('PluginKbrenamingKb');
        Plugin::registerClass('PluginKbrenamingKbGroup');
        $PLUGIN_HOOKS['fusioninventory_addinventoryinfos']['kbrenaming'] = 'plugin_fusioninventory_addinventoryinfos_kbrenaming';


        $PLUGIN_HOOKS['item_add']['kbrenaming'] = [
            'Software'   => 'plugin_item_add_update_kbrenaming'
        ];
        $PLUGIN_HOOKS['item_update']['kbrenaming'] = [
            'Software'   => 'plugin_item_add_update_kbrenaming'
        ];
        $PLUGIN_HOOKS['post_item_form']['kbrenaming'] =  'plugin_post_item_form_kbrenaming';

        $report_list=[];

        if (Session::haveRight('software', READ)) {
            $report_list["report/kb_entities_osversion.php"] = __('Summaries numbers computer by entries by OS version for one KB', 'msf');
        }
        $PLUGIN_HOOKS['reports']['kbrenaming'] = $report_list;

    }
}


/**
 * Manage the version information of the plugin
 *
 * @return array
 */
function plugin_version_kbrenaming() {
   return ['name'           => 'kb Software',
           'shortname'      => 'kbrenaming',
           'version'        => PLUGIN_KBRENAMING_VERSION,
           'license'        => 'AGPLv3+',
           'author'         => '<a href="mailto:sebastien.batteur@brussels.msf.org">SEBASTIEN BATTEUR</a>',
           'homepage'       => 'https://github.com/msf-ocb/glpi-plugin-kbrenaming',
           'requirements'   => [
              'glpi' => [
                  'min' => '11.0',
                  'max' => '12.0',
                  'dev' => PLUGIN_KBRENAMING_OFFICIAL_RELEASE == 0
               ]
            ]
         ];
}


/**
 * Manage / check the prerequisites of the plugin
 *
 * @return boolean
 */
function plugin_kbrenaming_check_prerequisites(){

   // GLPI must be within supported version range
   $min_version = defined('PLUGIN_KBRENAMING_GLPI_MIN_VERSION') ? PLUGIN_KBRENAMING_GLPI_MIN_VERSION : '10.0.0';
   $max_version = defined('PLUGIN_KBRENAMING_GLPI_MAX_VERSION') ? PLUGIN_KBRENAMING_GLPI_MAX_VERSION : '12.1.0';
   $glpi_version = '0.0.0';
   $version_file = defined('GLPI_ROOT') ? GLPI_ROOT . '/version' : __DIR__ . '/../../../version';
   if (file_exists($version_file)) {
      $glpi_version = trim(file_get_contents($version_file));
   }
   $ok = version_compare($glpi_version, $min_version, '>=') && version_compare($glpi_version, $max_version, '<');
   if (!$ok) {
      $msg = sprintf(
         'ERROR [setup.php:plugin_kbrenaming_check_prerequisites] GLPI version %s not in [%s, %s), user=%s',
         $glpi_version,
         $min_version,
         $max_version,
         $_SESSION['glpiname'] ?? 'unknown'
      );
      // Try Toolbox::logInFile, fallback to file log
      if (!class_exists('Toolbox') && defined('GLPI_ROOT') && file_exists(GLPI_ROOT . '/src/Toolbox.php')) {
         require_once GLPI_ROOT . '/src/Toolbox.php';
      }
      if (class_exists('Toolbox') && method_exists('Toolbox', 'logInFile')) {
         Toolbox::logInFile('kbrenaming', $msg);
      } else if (defined('GLPI_ROOT')) {
         $logfile = GLPI_ROOT . '/files/_log/kbrenaming-error.log';
         @file_put_contents($logfile, $msg."\n", FILE_APPEND);
      }
      return false;
   }
   return true;
}


/**
 * Check if the config is ok
 *
 * @return boolean
 */
function plugin_kbrenaming_check_config() {
   return true;
}


/**
 * Check the rights
 *
 * @param string $type
 * @param string $right
 * @return boolean
 */
function plugin_kbrenaming_haveTypeRight($type, $right) {
   return true;
}
