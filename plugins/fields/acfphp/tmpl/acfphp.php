<?php

/**
 * @package         Advanced Custom Fields
 * @version         1.2.0-RC2 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2019 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die;

if (empty($field->value))
{
	return;
}

// Get plugin params
$plugin = JPluginHelper::getPlugin('fields', 'acfphp');
$params = new JRegistry($plugin->params);

$payload = ['field' => $field];

// Enable buffer output
$executer = new \NRFramework\Executer($field->value, $payload);

$result = $executer
	->setForbiddenPHPFunctions($params->get('forbidden_php_functions'))
	->run();

echo $result;