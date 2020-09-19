<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use NRFramework\HTML;

JFormHelper::loadFieldClass('list');

/*
 * Creates an AJAX-based dropdown
 * https://select2.org/
 */
abstract class JFormFieldAjaxify extends JFormFieldList
{
    /**
     * Textbox placeholder
     *
     * @var string
     */
    protected $placeholder = 'Select Items';

    /**
     * AJAX rows limit
     *
     * @var int
     */
    protected $limit = 50;

    /**
     *  Method to render the input field
     *
     *  @return  string
     */
    protected function getInput()
    {   
        $placeholder = (string) $this->element['placeholder'];
        $this->placeholder = empty($placeholder) ? $this->placeholder : $placeholder;

        HTML::script('plg_system_nrframework/vendor/select2.min.js');
        HTML::stylesheet('plg_system_nrframework/select2.css');

        JFactory::getDocument()->addScriptDeclaration('
            jQuery(function($) {
                $("#' . $this->id . '").select2({
                    templateResult: function(state) {
                        if (!state.id) {
                            return;
                        }

                        return $(\'' . $this->getTemplateResult() . '\');
                    },
                    placeholder: "' . JText::_($this->placeholder) . '",
                    ajax: {
                        url: "' . $this->getAjaxEndpoint() . '",
                        dataType: "json",
                        error: function (xhr, ajaxOptions, thrownError) {
                            if (xhr.statusText == "abort") {
                                return;
                            }

                            alert(xhr.responseText);
                        }
                    },
                    allowClear: true,
                    multiple: true,
                    cache: true
                });
            })
        ');

        $this->class .= ' input-xxlarge select2';

        return parent::getInput();
    }

    protected function getTemplateResult()
    {
        return '<span>\' + state.text + \'</span>';
    }

    protected function getAjaxEndpoint()
    {
        $reflector = new ReflectionClass($this);
        $filename = $reflector->getFileName();
        $file = JFile::stripExt(basename($filename));

        $token = JSession::getFormToken();

        $field_attributes = (array) $this->element->attributes();

        $data = [
            'task'  => 'include',
            'file'  => $file,
            'path'  => 'plugins/system/nrframework/fields/',
            'class' => get_called_class(),
            $token  => 1,
            'field_attributes' => $field_attributes['@attributes']
        ];

        return JURI::base() . '?option=com_ajax&format=raw&plugin=NRFramework&' . http_build_query($data);
    }

    /**
     * Returns data object used by the AJAX request
     *
     * @param   array    $options
     *
     * @return  array
     */
    public function onAjax($options)
    {
        $this->options = new Registry($options);

        // Reinitialize Field
        $this->element = (array) $this->options->get('field_attributes');
        $this->init();

        $this->limit       = $this->options->get('limit', $this->limit);
        $this->page        = $this->options->get('page', 1);
        $this->search_term = $this->options->get('term');

        $rows = $this->getItems();

        $hasMoreRecords = false;

        if ($this->limit > 0)
        {
            $total = $this->getItemsTotal();
            $hasMoreRecords = ($this->page * $this->limit) < $total;
        }

        $data = [
            'results' => $rows,
            'pagination' => ['more' => $hasMoreRecords]
        ];

        echo json_encode($data);
    }

    /**
     * Load selected options
     *
     * @return void
     */
    protected function getOptions()
    {
        $options = $this->value;

        if (empty($options))
        {
            return;
        }
        
        // In case the value is previously saved in a comma separated format.
        if (!is_array($options))
        {
            $options = explode(',', $options);
        }

        if (!method_exists($this, 'validateOptions'))
        {
            return $options;
        }

        // Remove empty and null items
        $options = array_filter($options);

        try
        {
            return $this->validateOptions($options);
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * This method is called by the onAjax method and must return an array of arrays
     *
     * @return void
     */
    abstract protected function getItems();

    abstract protected function getItemsTotal();
}