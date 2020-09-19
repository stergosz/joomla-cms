<?php
/**
 * @author          Tassos.gr <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('list');

class JFormFieldComparator extends JFormFieldList
{
    /**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
        $this->class = 'input-small';
        $this->required = true;

        return parent::getInput();
    }

    protected function getLabel()
    {
        return 'Match';
    }

    protected function getOptions()
    {
        return [
            1 => 'Is',
            0 => 'Is Not',
        ];
    }
}
