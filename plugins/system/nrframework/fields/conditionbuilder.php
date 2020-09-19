<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

// No direct access to this file
defined('_JEXEC') or die;

use NRFramework\ConditionBuilder;

class JFormFieldConditionBuilder extends JFormField
{
    /**
     *  Method to render the input field
     *
     *  @return  string
     */
    protected function getInput()
    {   
        return ConditionBuilder::render($this->name, $this->value, $this->getConditionsList());
    }

    protected function getConditionsList()
    {
        return $this->element['conditions'];
    }
}