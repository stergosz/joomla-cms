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

class JFormRuleRecommended extends JFormRule
{
	/**
	 *  Method to test if the value of the field is recommended and not empty
	 *
	 *  @param   SimpleXMLElement              $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 *  @param   mixed                         $value    The form field value to validate.
	 *  @param   string                        $group    The field name group control value. This acts as as an array container for the field.
	 *                                                   For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                                   full field name would end up being "bar[foo]".
	 *  @param   Joomla\Registry\Registry|null  $input   An optional Registry object with the entire data set to validate against the entire form.
	 *  @param   JForm                     		$form    The form object for which the field is being tested.
	 *
	 *  @return  boolean                                 True if the value is valid, false otherwise.
	 */
	public function test(SimpleXMLElement $element, $value, $group = null, Joomla\Registry\Registry $input = null, JForm $form = null)
	{
		if (empty($input) || !$this->checkRequisites($element, $input))
		{
			return true;
		}

		if (empty($value))
		{
			$isRecommended  = $element->attributes()->recommended;
			$elementMessage = $element->attributes()->message;
			$elementLabel   = $element->attributes()->label;
			$message        = JText::_("GSD") . ": " . JText::sprintf($elementMessage, JText::_($elementLabel));

			// A value for the field is recommended. Save the form but throw a notice as well.
			if ($isRecommended)
			{
				JFactory::getApplication()->enqueueMessage($message, "notice");
				return true;
			} 
			
			// A value for the field is required. Don't save the form.
			$element->attributes()->message = $message;
			return false;
		}
	}

	/**
	 *  Checks if the requisites for the field to be validated are met.
	 *
	 *  @param   SimpleXMLElement  			$element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 *  @param   Joomla\Registry\Registry	$input    An optional Registry object with the entire data set to validate against the entire form.
	 *
	 *  @return  boolean							  True if the requisites are met, false otherwise
	 */
	private function checkRequisites($element, $input)
	{
		$requisites = explode('|', (string) $element->attributes()->requisites);

		if (empty($requisites))
		{
			return true;
		}

		foreach ($requisites as $requisite)
		{
			$requisite = explode(':', $requisite);

			if (empty($requisite) || count($requisite) < 2)
			{
				continue;
			}

			if ($input->get($requisite[0]) != $requisite[1])
			{
				return false;
			}
		}
		
		return true;
	}
}
