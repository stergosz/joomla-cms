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

require_once JPATH_PLUGINS . '/system/nrframework/helpers/fieldlist.php';

class JFormFieldContentTypes extends NRFormFieldList
{
    /**
     * Method to get a list of options for a list input.
     *
     * @return      array           An array of JHtml options.
     */
    protected function getOptions()
    {
        $contentTypes = GSD\Helper::getContentTypes();

        if ($this->get("showselect", 'false') === 'true')
        {
            $options[] = JHTML::_('select.option', '', '- ' . JText::_('GSD_CONTENT_TYPE_SELECT') . ' -');
        }

        foreach ($contentTypes as $contentType)
        {
            $options[] = JHTML::_('select.option', $contentType, JText::_('GSD_' . strtoupper($contentType)));
        }

        return array_merge(parent::getOptions(), $options);
    }

    protected function getInput()
    {
        if (!$this->get('showhelp', false))
        {
            return parent::getInput();
        }

        $this->doc->addScriptDeclaration('
            jQuery(function($) {
                $("#' . $this->id . '").on("change", function() {
                    href = "http://www.tassos.gr/joomla-extensions/google-structured-data-markup/docs/" + $(this).val().replace("_", "") + "-schema";
                    $(".contentTypeHelp").attr("href", href);
                }).trigger("change");
            })
        ');

        return parent::getInput() . '
            <a class="btn btn-secondary contentTypeHelp" target="_blank" title="' . JText::_('GSD_CONTENTTYPE_HELP') . '">
                <span class="icon-help" style="margin-right:0;"></span>
            </a>
        ';
    }
}