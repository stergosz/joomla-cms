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
 
use GSD\Helper;

// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * Config View
 */
class GSDViewConfig extends JViewLegacy
{
    /**
     * Items view display method
     * 
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * 
     * @return  mixed  A string if successful, otherwise a JError object.
     */
    function display($tpl = null)
    {
        // Check for errors.
        if (!is_null($this->get('Errors')) && count($errors = $this->get('Errors')))
        {
            JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        $this->form    = $this->get('Form');
        $this->config  = JComponentHelper::getParams('com_gsd');
        $this->sidebar = Helper::renderSideBar();

        // Set the toolbar
        $this->addToolBar();

        // Display the template
        parent::display($tpl);
    }

    /**
     *  Add Toolbar to layout
     */
    protected function addToolBar() 
    {
        $canDo = Helper::getActions();

        JToolBarHelper::title(JText::_('GSD') . ': ' . JText::_('GSD_CONFIG'));

        if ($canDo->get('core.admin'))
        {
            JToolbarHelper::preferences('com_gsd');
        }

        JToolbarHelper::apply('config.apply');

        JToolbarHelper::help('Help', false, 'https://www.tassos.gr/joomla-extensions/google-structured-data-markup/docs');
    }
}