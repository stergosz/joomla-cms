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

jimport('joomla.database.table');

class GSDTableConfig extends JTable
{
    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct(&$db) 
    {
        parent::__construct('#__gsd_config', 'name', $db);
    }

    /**
     *  Store method
     *
     *  @param   string  $key  The config name
     */
    public function store($key = 'config')
    {
        $db    = JFactory::getDBo();
        $table = $this->_tbl;
        $key   = empty($this->name) ? $key : $this->name;

        // Check if key exists
        $result = $db->setQuery(
            $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName($this->_tbl))
                ->where($db->quoteName('name') . ' = ' . $db->quote($key))
        )->loadResult();

        $exists = $result > 0 ? true : false;

        // Prepare object to be saved
        $data = new \stdClass();
        $data->name   = $key;
        $data->params = $this->params;

        if ($exists)
        {
            return $db->updateObject($table, $data, 'name');
        }

        return $db->insertObject($table, $data);
    }
}