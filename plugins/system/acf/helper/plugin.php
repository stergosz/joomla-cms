<?php

/**
 * @package         Advanced Custom Fields
 * @version         1.2.0-RC2 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2019 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die;

if (!@include_once(JPATH_PLUGINS . '/system/nrframework/autoload.php'))
{
	throw new RuntimeException('Novarain Framework is not installed', 500);
}

JLoader::import('components.com_fields.libraries.fieldsplugin', JPATH_ADMINISTRATOR);
JLoader::register('ACFHelper', __DIR__ . '/helper.php');

class ACF_Field extends FieldsPlugin
{
	/**
	 *  Override the field type
	 *
	 *  @var  string
	 */
	protected $overrideType;

	/**
	 *  The validation rule will be used to validate the field on saving
	 *
	 *  @var  string
	 */
	protected $validate;

	/**
	 *  Field's Hint Description
	 *
	 *  @var  string
	 */
	protected $hint;

	/**
	 *  Field's Class
	 *
	 *  @var  string
	 */
	protected $class;

	/**
	 * Transforms the field into a DOM XML element and appends it as a child on the given parent.
	 *
	 * @param   stdClass    $field   The field.
	 * @param   DOMElement  $parent  The field node parent.
	 * @param   JForm       $form    The form.
	 *
	 * @return  DOMElement
	 *
	 * @since   3.7.0
	 */
	public function onCustomFieldsPrepareDom($field, DOMElement $parent, JForm $form)
	{
		if (!$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form))
		{
			return;
		}

		// Load framework's fields
		$form->addFieldPath(JPATH_PLUGINS . '/system/nrframework/fields');
		$form->addFieldPath(JPATH_PLUGINS . '/fields/' . $field->type . '/fields');

		// Set Field Class
		if ($this->class)
		{
			$fieldNode->setAttribute('class', $this->class);
		}

		// Set Field Class
		if ($this->hint)
		{
			$fieldNode->setAttribute('hint', $this->hint);
		}

		// Set Field Type
		if ($this->overrideType)
		{
			$fieldNode->setAttribute('type', $this->overrideType);
		}

		// Set validation rule
		if ($this->validate)
		{
			$form->addRulePath(JPATH_PLUGINS . '/system/nrframework/NRFramework/Rules');
			$form->addRulePath(JPATH_PLUGINS . '/system/acf/form/rules');
			$fieldNode->setAttribute('validate', $this->validate);
		}

		// Set Field Description
		$desc_def  = JText::_(str_replace('ACF', 'ACF_', strtoupper($field->type)) . '_VALUE_DESC');
		$desc_user = $fieldNode->getAttribute('description');
		$desc      = !empty($desc_user) ? $desc_user : $desc_def;
	
		$fieldNode->setAttribute('description', $desc);

		return $fieldNode;
	}

	/**
	 * The form event. Load additional parameters when available into the field form.
	 * Only when the type of the form is of interest.
	 *
	 * @param   JForm     $form  The form
	 * @param   stdClass  $data  The data
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	public function onContentPrepareForm(JForm $form, $data)
	{
		// Make sure we are manipulating the right field.
		if (isset($data->type) && ($data->type != $this->_name))
		{
			return;
		}

		// Load framework's fields
		$form->addFieldPath(JPATH_PLUGINS . '/system/nrframework/fields');

		// Include update notification checker on backend only
		if (JFactory::getApplication()->isClient('administrator'))
		{
			// load ACF backend style
			JHtml::stylesheet('plg_system_acf/acf-backend.css', ['relative' => true, 'version' => 'auto']);

			if (defined('nrJ4'))
			{
				JHtml::stylesheet('plg_system_nrframework/joomla4.css', ['relative' => true, 'version' => 'auto']);
				\NRFramework\HTML::fixFieldTooltips();
			}
						
			echo \NRFramework\HTML::checkForUpdates('plg_system_acf');
		}

		return parent::onContentPrepareForm($form, $data);
	}
}
