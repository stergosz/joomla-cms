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
 * Item Model Class
 */
class GSDModelItem extends JModelAdmin
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
    public function getTable($type = 'Item', $prefix = 'GSDTable', $config = array()) 
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
        // Add some useful field paths
        $form->addFieldPath(__DIR__ . '/forms/fields');
        $form->addFieldPath(JPATH_PLUGINS . '/system/nrframework/fields');
        $form->addFieldPath(JPATH_PLUGINS . '/system/nrframework/NRFramework/Fields');

        if ($data->contenttype)
        {
            // Add snippet form
            $form->loadFile(JPATH_COMPONENT_ADMINISTRATOR . '/models/forms/contenttypes/' . $data->contenttype . '.xml');
            
            // Changing Integration when editing an existing item is not allowed
            $form->setFieldAttribute('plugin', 'readonly', true);
            $form->setFieldAttribute('contenttype', 'readonly', true);
        }

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
        $form = $this->loadForm('com_gsd.item', 'item', array('control' => 'jform', 'load_data' => $loadData));

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
        $app = JFactory::getApplication();

        // Check the session for previously entered form data.
        $data = $app->getUserState('com_gsd.edit.item.data', array());

        if (empty($data))
        {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Method to validate form data.
     */
    public function validate($form, $data, $group = null)
    {
        $newdata = array();
        $params  = array();

        $this->_db->setQuery('SHOW COLUMNS FROM #__gsd');

        $dbkeys = $this->_db->loadObjectList('Field');
        $dbkeys = array_keys($dbkeys);

        foreach ($data as $key => $val)
        {
            if (in_array($key, $dbkeys))
            {
                $newdata[$key] = $val;
            }
            else
            {
                $params[$key] = $val;
            }
        }

        if (!isset($newdata['params']))
        {
            $newdata['params'] = json_encode($params);
        }

        return $newdata;
    }

    /**
     *  [getItem description]
     *
     *  @param   [type]  $pk  [description]
     *
     *  @return  [type]       [description]
     */
    public function getItem($pk = null)
    {
        if ($item = parent::getItem($pk))
        {
            $params = $item->params;

            if (is_array($params) && count($params))
            {
                foreach ($params as $key => $value)
                {
                    if (!isset($item->$key) && !is_object($value))
                    {
                        $item->$key = $value;
                    }
                }

                unset($item->params);
            }
        }

        $input = JFactory::getApplication()->input;

        if ($input->get('override_item'))
        {
            $item->title       = $input->getString('title');
            $item->contenttype = $input->get('contenttype');
            $item->plugin      = $input->get('plugin');
            $item->assignments = $input->get('assignments', '', 'Array');
        }

        return $item;
    }

    /**
     * Method to copy an item
     *
     * @access    public
     * @return    boolean    True on success
     */
    public function copy($id)
    {
        $item = $this->getItem($id);

        unset($item->_errors);
        $item->id = 0;
        $item->state = 0;

        $item = $this->validate(null, (array) $item);

        return ($this->save($item));
    }
}

