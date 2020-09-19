<?php

/**
 * @package         Google Structured Data
 * @version         4.8.0-RC1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

// Load Framework
if (!@include_once(JPATH_PLUGINS . '/system/nrframework/autoload.php'))
{
	throw new RuntimeException('Novarain Framework is not installed', 500);
}

$app = JFactory::getApplication();

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_gsd'))
{
	$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
	return;
}

use NRFramework\Functions;
use NRFramework\Extension;

// Load framework's and component's language files
Functions::loadLanguage();
Functions::loadLanguage('plg_system_gsd');	

// Initialize component's library
require_once JPATH_ADMINISTRATOR . '/components/com_gsd/autoload.php';

// Check required extensions
if (!Extension::pluginIsEnabled('nrframework'))
{
	$app->enqueueMessage(JText::sprintf('NR_EXTENSION_REQUIRED', JText::_('GSD'), JText::_('PLG_SYSTEM_NRFRAMEWORK')), 'error');
}

if (!Extension::pluginIsEnabled('gsd'))
{
	$app->enqueueMessage(JText::sprintf('NR_EXTENSION_REQUIRED', JText::_('GSD'), JText::_('PLG_SYSTEM_GSD')), 'error');
}

if (defined('nrJ4'))
{
	JHtml::stylesheet('plg_system_nrframework/joomla4.css', ['relative' => true, 'version' => 'auto']);
} else 
{
	JHtml::stylesheet('plg_system_nrframework/joomla3.css', ['relative' => true, 'version' => 'auto']);
	JHtml::stylesheet('com_gsd/joomla3.css', ['relative' => true, 'version' => 'auto']);
}

GSD\Helper::event('onGSDGetNames');

// Perform the Request task
$controller = JControllerLegacy::getInstance('GSD');
$controller->execute($app->input->get('task'));
$controller->redirect();