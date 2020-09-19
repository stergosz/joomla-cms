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
 
/**
 * Config Model Class
 */
class GSDModelConfig extends JModelAdmin
{
    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param       type    The table type to instantiate
     * @param       string  A prefix for the table class name. Optional.
     * @param       array   Configuration array for model. Optional.
     * @return      JTable  A database object
     * @since       2.5
     */
    public function getTable($type = 'Config', $prefix = 'GSDTable', $config = array()) 
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Method to allow derived classes to preprocess the form.
     *
     * @param   JForm   $form   A JForm object.
     * @param   mixed   $data   The data expected for the form.
     * @param   string  $group  The name of the plugin group to import (defaults to "content").
     *
     * @return  void
     *
     * @see     JFormField
     * @since   1.6
     * @throws  Exception if there is an error in the form event.
     */
    protected function preprocessForm(JForm $form, $data, $group = 'content')
    {
        $form->addFieldPath(__DIR__ . '/forms/fields');
        $form->addRulePath(__DIR__ . '/forms/rules');
        $form->addFieldPath(JPATH_PLUGINS . '/system/nrframework/fields');

        parent::preprocessForm($form, $data, $group);
    }

    /**
     * Method to get the record form.
     *
     * @param       array   $data           Data for the form.
     * @param       boolean $loadData       True if the form is to load its own data (default case), false if not.
     * @return      mixed   A JForm object on success, false on failure
     * @since       2.5
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_gsd.config', 'config', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form))
        {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return    mixed    The data for the form.
     */
    protected function loadFormData()
    {
        return GSD\Helper::getParams();
    }

    /**
     *  Method to validate form data.
     */
    public function validate($form, $data, $group = null)
    {
        $name = $data['name'];
        unset($data["name"]);

        return array(
            'name'   => $name,
            'params' => json_encode($data)
        );
    }
}

