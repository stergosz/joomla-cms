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
 
// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

/**
 * Items controller class.
 */
class GSDControllerItems extends JControllerAdmin
{
	protected $text_prefix = 'GSD';

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JModelLegacy  The model.
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Item', $prefix = 'GSDModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 *  Copy items specified by array cid and set Redirection to the list of items
	 *
	 *  @return  void
	 */
	public function duplicate()
	{
		$app = JFactory::getApplication();
		$ids = $app->input->get('cid', array(), 'array');

		// Get the model.
		$model = $this->getModel('Item');

  		foreach ($ids as $id)
        {
            $model->copy($id);
        }

		$app->enqueueMessage(JText::sprintf('GSD_CAMPAIGN_N_ITEMS_COPIED', count($ids)));
        $app->redirect('index.php?option=com_gsd&view=items');
	}
}