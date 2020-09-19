<?php

/**
 * @package         Google Structured Data
 * @version         4.8.0-RC1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2019 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgGSDDJCatalog2InstallerScript extends PlgGSDDJCatalog2InstallerScriptHelper
{
    public $alias = 'djcatalog2';
    public $extension_type = 'plugin';
    public $plugin_folder = 'gsd';
    public $show_message = false;
    public $autopublish = false;
}