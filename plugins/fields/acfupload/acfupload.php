<?php

/**
 * @package         Advanced Custom Fields
 * @version         1.2.0-RC2 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2020 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die;

JLoader::register('ACF_Field', JPATH_PLUGINS . '/system/acf/helper/plugin.php');

if (!class_exists('ACF_Field'))
{
	JFactory::getApplication()->enqueueMessage('Advanced Custom Fields System Plugin is missing', 'error');
	return;
}

class PlgFieldsACFUpload extends ACF_Field
{
	/**
	 *  The validation rule will be used to validate the field on saving
	 *
	 *  @var  string
	 */
	protected $validate = 'acfrequired';

	/**
	 * The default upload folder
	 *
	 * @var string
	 */
	protected $default_upload_folder = '/media/acfupload/';

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
			return $fieldNode;
        }
        
		$fieldNode->setAttribute('field_id', $field->id);

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

		$result = parent::onContentPrepareForm($form, $data);

		// Display the server's maximum upload size in the field's description
		$max_upload_size_str = \JHtml::_('number.bytes', \JUtility::getMaxUploadSize());
		$field_desc = $form->getFieldAttribute('max_file_size', 'description', null, 'fieldparams');
		$form->setFieldAttribute('max_file_size', 'description', JText::sprintf($field_desc, $max_upload_size_str), 'fieldparams');

		return $result;
	}

    /**
     * Handle AJAX endpoint
     *
     * @return void
     */
    public function onAjaxACFUpload()
    {
		if (!JSession::checkToken('request'))
        {
        	$this->uploadDie(JText::_('JINVALID_TOKEN'));
        }

		$task = JFactory::getApplication()->input->get('task', 'upload');
		$taskMethod = 'task' . ucfirst($task);

		if (!method_exists($this, $taskMethod))
		{
			$this->uploadDie('Invalid endpoint');
		}

		$this->$taskMethod();
	}
	
	/**
	 * The Upload task called by the AJAX hanler
	 *
	 * @return void
	 */
	public function taskUpload()
	{
		$input = JFactory::getApplication()->input;

		// Make sure we have a valid form and a field key
		if (!$field_id = $input->getInt('id'))
		{
			$this->uploadDie('ACF_UPLOAD_ERROR');
		}

		// Get Upload Settings
		if (!$upload_field_settings = $this->getCustomFieldData($field_id))
		{
			$this->uploadDie('ACF_UPLOAD_ERROR_INVALID_FIELD');
		}

		$allow_unsafe = $upload_field_settings->get('allow_unsafe', false);

		// Make sure we have a valid file passed
		if (!$file = $input->files->get('file', null, ($allow_unsafe ? 'raw' : null)))
		{
			$this->uploadDie('ACF_UPLOAD_ERROR_INVALID_FILE');
		}

		// In case we allow multiple uploads the file parameter is a 2 levels array.
		$first_property = array_pop($file);
		if (is_array($first_property))
		{
			$file = $first_property;
		}

		$upload_folder = $upload_field_settings->get('upload_folder', $this->default_upload_folder);
		$randomize_filename = $upload_field_settings->get('randomize_filename', false);

		JLoader::register('ACFUploadHelper', __DIR__ . '/fields/uploadhelper.php');

		// Upload the file but before add a random prefix and .tmp suffix.
		if (!$uploaded = ACFUploadHelper::upload($file, $upload_folder, $randomize_filename, $allow_unsafe))
		{
			$this->uploadDie('ACF_UPLOAD_ERROR_CANNOT_UPLOAD_FILE');
		}

		echo json_encode($uploaded);
	}

	/**
	 * The delete task called by the AJAX hanlder
	 *
	 * @return void
	 */
	private function taskDelete()
	{
		$input = JFactory::getApplication()->input;

		// Make sure we have a valid form and a field key
		if (!$field_id = $input->getInt('field_id'))
		{
			$this->uploadDie('ACF_UPLOAD_ERROR');
		}

		// Make sure we have a valid file passed
		if (!$filename = $input->get('filename'))
		{
			$this->uploadDie('ACF_UPLOAD_ERROR_INVALID_FILE');
		}

		// Get File Upload Field Settings
		if (!$upload_field_settings = $this->getCustomFieldData($field_id))
		{
			$this->uploadDie('ACF_UPLOAD_ERROR_INVALID_FIELD');
		}

		$upload_folder = $upload_field_settings->get('upload_folder', $this->default_upload_folder);

		JLoader::register('ACFUploadHelper', __DIR__ . '/fields/uploadhelper.php');

		// Delete the uploaded file
		$deleted = ACFUploadHelper::deleteFile($filename, $upload_folder);
		echo json_encode(['success' => $deleted]);
	}

	/**
	 * Pull Custom Field Data
	 *
	 * @param  integer $id The Custom Field primary key
	 *
	 * @return object
	 */
    private function getCustomFieldData($id)
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true);

        $query
            ->select($db->quoteName(['fieldparams']))
            ->from($db->quoteName('#__fields'))
            ->where($db->quoteName('id') . ' = ' . $id)
            ->where($db->quoteName('type') . ' = ' . $db->quote('acfupload'))
            ->where($db->quoteName('state') . ' = 1');

        $db->setQuery($query);

        if (!$result = $db->loadResult())
        {
            return;
        }

        return new Joomla\Registry\Registry($result);
    }

	/**
	 * DropzoneJS detects errors based on the response error code.
	 *
	 * @param  string $error_message
	 *
	 * @return void
	 */
	private function uploadDie($error_message)
	{
		http_response_code('500');
		die(\JText::_($error_message));
    }
}
