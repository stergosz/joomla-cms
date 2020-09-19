<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

class GSDViewGSD extends JViewLegacy
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$app  = JFactory::getApplication();
		$task = $app->input->get('do');

		switch ($task)
		{
			// Enable/Disable plugin
			case 'pluginState':

				// Abort if no plugin ID is passed.
				if (!$id = $app->input->getInt('plugin_id', null))
				{
					return;
				}

				// Load plugin's row and change it's state
				$table = JTable::getInstance('Extension', 'JTable');
				$table->load($id);
				$table->enabled = $app->input->get('state', '1');
				$table->store();

				// Print plugin's new state
				echo $table->enabled;

				\JFactory::getCache()->clean('com_plugins');

				break;

			// Delete Item
			case 'delete':
				$model = JModelLegacy::getInstance('Item', 'GSDModel', ['ignore_request' => true]);
				if ($id = $app->input->get('pk', null))
				{
					echo $model->publish($id, -2);
				}
				break;

			// Render Items for Fast Edit field
			default:
				require_once JPATH_COMPONENT_ADMINISTRATOR . '/models/forms/fields/fastedit.php';
				$items = (new JFormFieldFastEdit())->getRows();
				echo JLayoutHelper::render('fastedit', ['items' => $items]);
				break;
		}
	}
}
