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

JLoader::register('NRFormField', JPATH_PLUGINS . '/system/nrframework/helpers/field.php');

class JFormFieldFastEdit extends NRFormField
{
    /**
     *  Disable field label
     *
     *  @return  boolean
     */
    protected function getLabel()
    {
        return false;
    }

    /**
     * Method to get a list of options for a list input.
     *
     * @return      array           An array of JHtml options.
     */
    protected function getInput()
    {
        // Load framework language file
        NRFramework\Functions::loadLanguage();

        $thing = $this->get('thing', 0);
        $error = $this->get('error', 'GSD_SAVE_FIRST');
        $html  = '';

        if (!defined('nrJ4'))
        {
            JHtml::stylesheet('com_gsd/joomla3.css', ['relative' => true, 'version' => 'auto']);
        }

        JHtml::_('stylesheet', 'com_gsd/fastedit.css', array('version' => 'auto', 'relative' => true));

        // In order to be able to assosiate a snippet the item must be saved first.
        if (!$thing)
        {
            return $html . '<div class="alert alert-info gsdFastEdit">' . JText::_($error) . '</div>';
        }

        // Cool. The item is saved.
        $plugin = $this->get('plugin', null);
        $contentTypes = GSD\Helper::getContentTypes();

        $query = [
            'override_item' => true,
            'title' => $this->get('thing_title'),
            'plugin' => $plugin,
            'contenttype' => 'article',
            'assignments' => [
                $this->get('plugin_assignment_name') => [
                    'assignment_state' => 1,
                    'selection' => $thing
                ]
            ]
        ];

        $addURL = JRoute::_('index.php?option=com_gsd&tmpl=component&layout=modal&view=item&' . http_build_query($query));

        // Add Media
        JHtml::_('script', 'com_gsd/fastedit.js', ['version' => 'auto', 'relative' => true]);

        // Add language strings used by the JS plugin
        JText::script('GSD_ADD_SNIPPET');
        JText::script('GSD_EDIT_SNIPPET');
        JText::script('NR_ARE_YOU_SURE');

        JFactory::getDocument()->addScriptOptions('gsd_fastedit', [
            'base_url'    => JURI::base(true),
            'add_url'     => $addURL,
            'plugin'      => $plugin,
            'thing_id'    => $thing,
            'thing_catid' => $this->get('thing_catid'),
            'plugin_assignment_name' => $this->get('plugin_assignment_name')
        ]);

        $html .= '
            <div class="gsdFastEdit" data-src="'. $addURL . '">
                ' . $this->renderModal() . '

                <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="icon-new"></span>'
                        . JText::_('GSD_ADD_SNIPPET') .'
                    </button>

                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
             ';

        foreach ($contentTypes as $contentType) 
        {
            $html .= '<a data-contenttype="' . $contentType . '" data-toggle="modal" href="#gsdModal">' . JText::_('GSD_' . strtoupper($contentType)) . '</a>';
        }

        $html .= '</div></div><div class="items"></div></div>';

        return $html;
    }

    /**
     *  Render the modal is going to be used by all buttons
     *
     *  @return  string
     */
    private function renderModal()
    {
        $options = array(
            'title'       => JText::_('GSD_EDIT_SNIPPET'),
            'url'         => '#',
            'height'      => '400px',
            'width'       => '800px',
            'backdrop'    => 'static',
            'bodyHeight'  => '70',
            'modalWidth'  => '70',
            'footer'      => '<a type="button" class="btn btn-secondary" data-dismiss="modal" aria-hidden="true"'
                    . ' onclick="jQuery(\'#gsdModal iframe\').contents().find(\'#closeBtn\').click();">'
                    . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</a>'
                    . '<button type="button" class="btn btn-primary" aria-hidden="true"'
                    . ' onclick="jQuery(\'#gsdModal iframe\').contents().find(\'#saveBtn\').click();">'
                    . JText::_('JSAVE') . '</button>'
                    . '<button type="button" class="btn btn-success" aria-hidden="true"'
                    . ' onclick="jQuery(\'#gsdModal iframe\').contents().find(\'#applyBtn\').click();">'
                    . JText::_('JAPPLY') . '</button>',
        );

        return JHtml::_('bootstrap.renderModal', 'gsdModal', $options);
    }

    public function getRows()
    {
        $input = JFactory::getApplication()->input;

        $thing      = $input->getInt('thing');
        $plugin     = $input->get('plugin');
        $assignment = $input->get('assignment');

        $model = JModelLegacy::getInstance('Items', 'GSDModel', ['ignore_request' => true]);
        $model->setState('list.limit', 0);
        $model->setState('filter.thing', $thing);
        $model->setState('filter.plugin', $plugin);

        $items = $model->getItems();

        foreach ($items as $key => &$item)
        {
            if (!isset($item->assignments) || 
                !isset($item->assignments->$assignment) || 
                !isset($item->assignments->$assignment->selection) ||
                $item->assignments->$assignment->assignment_state == '0' ||
                !in_array($thing, (array) $item->assignments->$assignment->selection))
            {
                unset($items[$key]);
            }
        }

        return $items;
    }
}