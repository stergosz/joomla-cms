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
 * Campaign View Class
 */
class GSDViewItem extends JViewLegacy
{
    /**
     * display method of Item view
     * @return void
     */
    public function display($tpl = null) 
    {
        // Check for errors.
        if (!is_null($this->get('Errors')) && count($errors = $this->get('Errors')))
        {
            JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        // Assign the Data
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->isnew = is_null($this->item->id) ? true : false;

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal')
        {
            $this->addToolbar();
        }

        $this->title = $this->isnew ? JText::_('GSD_NEW_ITEM') : JText::_('GSD_EDIT_ITEM') . ": #" . $this->item->id;

        // Display the template
        parent::display($tpl);
    }

    /**
     * Setting the toolbar
     */
    protected function addToolBar() 
    {
        JFactory::getApplication()->input->set('hidemainmenu', true);

        JToolBarHelper::title(JText::_('GSD') . ': ' . ($this->isnew ? JText::_('GSD_NEW_ITEM') : JText::_('GSD_EDIT_ITEM')));

        if (defined('nrJ4'))
        {
            \JToolbarHelper::saveGroup(
                [
                    ['apply', 'item.apply'],
                    ['save', 'item.save'],
                    ['save2new', 'item.save2new']
                ],
                'btn-success'
            );

            \JToolbarHelper::cancel('item.cancel');
        } else
        {
            JToolbarHelper::apply('item.apply');
            JToolBarHelper::save('item.save');
            JToolbarHelper::save2new('item.save2new');
            JToolBarHelper::cancel('item.cancel', $this->isnew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');      
        }

        JToolbarHelper::help('Help', false, "http://www.tassos.gr/joomla-extensions/google-structured-data-markup/docs");
    }
}