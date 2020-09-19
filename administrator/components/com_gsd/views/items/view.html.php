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
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
 
// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * Items View
 */
class GSDViewItems extends JViewLegacy
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
        $this->items         = $this->get('Items');
        $this->state         = $this->get('State');
        $this->pagination    = $this->get('Pagination');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->config        = Helper::getParams();
        $this->sidebar       = Helper::renderSideBar();

        if (defined('nrJ4'))
        {
            $tpl = '4';
        }

        // Check for errors.
        if (!is_null($this->get('Errors')) && count($errors = $this->get('Errors')))
        {
            JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

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
        $state = $this->get('State');
        $viewLayout = JFactory::getApplication()->input->get('layout', 'default');

        JToolBarHelper::title(JText::_('GSD') . ': ' . JText::_('GSD_ITEMS'));

        // Joomla J4
        if (defined('nrJ4'))
        {
            $toolbar = Toolbar::getInstance('toolbar');

            if ($canDo->get('core.create'))
            {
                $toolbar->addNew('item.add');
            }

            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('fas fa-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            
            if ($canDo->get('core.edit.state'))
            {
                $childBar->publish('items.publish')->listCheck(true);
                $childBar->unpublish('items.unpublish')->listCheck(true);
                $childBar->standardButton('copy')->text('JTOOLBAR_DUPLICATE')->task('items.duplicate')->listCheck(true);
                $childBar->trash('items.trash')->listCheck(true);
            }

            if ($this->state->get('filter.state') == -2)
            {
                $toolbar->delete('items.delete')
                    ->text('JTOOLBAR_EMPTY_TRASH')
                    ->message('JGLOBAL_CONFIRM_DELETE')
                    ->listCheck(true);
            }

            if ($canDo->get('core.admin'))
            {
                $toolbar->preferences('com_gsd');
            }

            $toolbar->help('JHELP', false, 'http://www.tassos.gr/joomla-extensions/google-structured-data-markup/docs');

            return;
        }

        if ($canDo->get('core.create'))
        {
            JToolbarHelper::addNew('item.add');
        }
        
        if ($canDo->get('core.edit'))
        {
            JToolbarHelper::editList('item.edit');
        }

        if ($canDo->get('core.create'))
        {
            JToolbarHelper::custom('items.duplicate', 'copy', 'copy', 'JTOOLBAR_DUPLICATE', true);
        }

        if ($canDo->get('core.edit.state') && $state->get('filter.state') != 2)
        {
            JToolbarHelper::publish('items.publish', 'JTOOLBAR_PUBLISH', true);
            JToolbarHelper::unpublish('items.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }

        if ($canDo->get('core.delete') && $state->get('filter.state') == -2)
        {
            JToolbarHelper::deleteList('', 'items.delete', 'JTOOLBAR_EMPTY_TRASH');
        }
        else if ($canDo->get('core.edit.state'))
        {
            JToolbarHelper::trash('items.trash');
        }

        if ($canDo->get('core.admin'))
        {
            JToolbarHelper::preferences('com_gsd');
        }

        JToolbarHelper::help("Help", false, "http://www.tassos.gr/joomla-extensions/google-structured-data-markup/docs");
    }
}