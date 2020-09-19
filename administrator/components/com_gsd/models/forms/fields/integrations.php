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

require_once JPATH_PLUGINS . '/system/nrframework/helpers/fieldlist.php';

class JFormFieldIntegrations extends NRFormFieldList
{
    /**
     * Method to get a list of options for a list input.
     *
     * @return      array           An array of JHtml options.
     */
    protected function getOptions()
    {
        // Get a list with all available plugins
        $plugins = Helper::getPlugins();

        if ($this->get('showselect', 'true') == 'true')
        {
            $options[] = JHTML::_('select.option', '', '- ' . JText::_('GSD_INTEGRATION_SELECT') . ' -');
        } else 
        {
            // If we don't have a value get the default plugin
            $this->value = empty($this->value) ? Helper::getDefaultPlugin() : $this->value;
        }

        // Sort alphabetically
        asort($plugins);
        
        foreach ($plugins as $option)
        {
            $options[] = JHTML::_('select.option', $option["alias"], $option["name"]);
        }

        return array_merge(parent::getOptions(), $options);
    }
}