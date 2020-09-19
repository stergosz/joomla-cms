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

defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('text');

class JFormFieldACFUpload extends JFormFieldText
{
    /**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
        JHtml::script('plg_fields_acfupload/dropzone.min.js', ['relative' => true, 'version' => 'auto']);
        JHtml::script('plg_fields_acfupload/acfupload.js', ['relative' => true, 'version' => 'auto']);
        JHtml::stylesheet('plg_fields_acfupload/acfupload.css', ['relative' => true, 'version' => 'auto']);

        // Add language strings used by script
        JText::script('ACF_UPLOAD_FILETOOBIG');
        JText::script('ACF_UPLOAD_INVALID_FILE');
        JText::script('ACF_UPLOAD_FALLBACK_MESSAGE');
        JText::script('ACF_UPLOAD_RESPONSE_ERROR');
        JText::script('ACF_UPLOAD_CANCEL_UPLOAD');
        JText::script('ACF_UPLOAD_CANCEL_UPLOAD_CONFIRMATION');
        JText::script('ACF_UPLOAD_REMOVE_FILE');
        JText::script('ACF_UPLOAD_MAX_FILES_EXCEEDED');
        JText::script('ACF_UPLOAD_FILE_MISSING');

        // Render File Upload Field
        $data = [
            'value'               => $this->prepareValue(),
            'input_name'          => $this->element['max_file_size'] == 1 ? $this->name : $this->name . '[]',
            'field_id'            => $this->element['field_id'],
            'max_file_size'       => $this->element['max_file_size'],
            'limit_files'         => $this->element['limit_files'],
            'upload_types'        => $this->element['upload_types'],
            'show_download_links' => isset($this->element['show_download_links']) && $this->element['show_download_links'] == 1 ? true : false,
            'base_url'            => JURI::base() // AJAX endpoint works on both site and backend.
        ];

		$layout = new \JLayoutFile('acfuploadlayout', __DIR__);
        return $layout->render($data);
    }

    private function prepareValue()
    {
        if (empty($this->value))
        {
            return;
        }

        require_once __DIR__ . '/uploadhelper.php';

        $upload_folder = $this->element['upload_folder'];
        $files = (array) $this->value;
        $value = [];

        foreach ($files as $file)
        {
            if (!$file)
            {
                continue;
            }

            $file_path = JPath::clean(JPATH_ROOT . '/' . $upload_folder . '/' . $file);
            $exists    = JFile::exists($file_path);
            $file_size = $exists ? filesize($file_path) : 0;

            $value[] = [
                'name'   => $file,
                'url'    => ACFUploadHelper::absURL($file_path),
                'size'   => $file_size,
                'exists' => $exists
            ];
        }

        return $value;
    }
}
