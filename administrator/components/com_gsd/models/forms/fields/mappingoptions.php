<?php
/**
 * @author          Tassos.gr <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

use GSD\Helper;
use NRFramework\Cache;
use GSD\MappingOptions;

JFormHelper::loadFieldClass('groupedlist');	

class JFormFieldMappingOptions extends JFormFieldGroupedList
{
    /**
	 * Method to get the field option groups.
	 *
	 * @return  array  The field option objects as a nested array in groups.
	 *
	 * @since   1.6
	 */
	protected function getGroups()
	{
        $groups  = array();
		$options = MappingOptions::$options;

        // Add empty option
		$groups[][] = JHtml::_('select.option', '', JText::_('GSD_PLEASE_SELECT'));
		
		// Add disable option
		if (!$this->required)
		{
			$text = '- ' . JText::_('JDISABLED') . ' -';
			$groups[][] = JHtml::_('select.option', '_disabled_', $text);
		}

        // Load plugin-based options
		$plugin = $this->form->getData()->get('plugin');
		Helper::event('onMapOptions', [$plugin, &$options]);

        // Load XML-based options
        if ($xmlGroups = $this->getGroupsFromXML())
        {
            $options = array_merge_recursive($options, $xmlGroups);
        }

		foreach ($options as $name => $group)
		{
			$name = \JText::_($name);

			// Initialize the group if necessary.
			if (!isset($groups[$name]))
			{
				$groups[$name] = array();
			}

			foreach ($group as $key => $option)
			{
				$groups[$name][] = JHtml::_('select.option', strtolower($key), JText::_($option));
			}
        }
        
		return $groups;
    }

    protected function getInput()
    {	
		$this->value = strtolower($this->value);

		if (!is_null($this->element['required']))
		{
			$this->required = $this->element['required'] == 'false' ? false : true;
		}

		$data = $this->form->getData();
		$isUnsaved = is_null($data->get($this->group));

		// Allow user to select null values and especially the '- Please Select -' option for non required fields
		if (!$this->required && !$isUnsaved)
		{
			$real_value = $data->get($this->group . '.option');

			if (is_null($real_value))
			{
				$this->value = '';
			}
		}
		
        return parent::getInput();
    }
    
    /**
     * Method to get the field option groups.
     *
     * @return  array  The field option objects as a nested array in groups.
     *
     * @since   11.1
     */
    private function getGroupsFromXML()
    {
		$groups = array();
		$label = 'Custom Info';

		foreach ($this->element->children() as $element)
		{
			switch ($element->getName())
			{
				// The element is an <option />
                case 'option':
                
					// Initialize the group if necessary.
					if (!isset($groups[$label]))
					{
						$groups[$label] = array();
					}

					// Add the option.
					$groups[$label][(string) $element['value']] = (string) $element;
					break;

				// The element is a <group />
				case 'group':
					// Get the group label.
					if ($groupLabel = (string) $element['label'])
					{
						$label = JText::_($groupLabel);
					}

					// Initialize the group if necessary.
					if (!isset($groups[$label]))
					{
						$groups[$label] = array();
					}

					// Iterate through the children and build an array of options.
					foreach ($element->children() as $option)
					{
						// Only add <option /> elements.
						if ($option->getName() != 'option')
						{
							continue;
                        }

						// Add the option.
						$groups[$label][(string) $option['value']] = (string) $option;
					}

					if ($groupLabel)
					{
						$label = count($groups);
					}
					break;
				default:
			}
		}

		reset($groups);

		return $groups;
    }
}