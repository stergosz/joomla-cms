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

if (!$files = $field->value)
{
	return;
}

require_once JPATH_SITE . '/plugins/fields/acfupload/fields/uploadhelper.php';

$files         = (array) $files;
$upload_folder = $fieldParams->get('upload_folder', 'media/acfupload');
$layout        = $fieldParams->get('layout', 'link');
$buffer        = [];

foreach ($files as $file)
{
	// Make sure we have a value
	if (!$file)
	{
		continue;
	}

	$file_path = JPath::clean($upload_folder . '/' . $file);
	$file_url  = JURI::root() . $file_path;

	switch ($layout)
	{
		// Image
		case 'img':
			$item = '<img src="' .  $file_url . '"/>';
			break;

		// Custom Layout
		case 'custom':
			if (!$subject = $fieldParams->get('custom_layout'))
			{
				return;
			}

			$file_full_path = JPATH_SITE . '/' . $file_path;
			$exists = JFile::exists($file_full_path);

			$vars = [
				'{site.url}'  => JURI::root(),
				'{file.name}' => $file,
				'{file.path}' => $file_path,
				'{file.url}'  => $file_url,
				'{file.size}' => $exists ? ACFUploadHelper::humanFilesize(filesize($file_full_path)) : 0,
				'{file.ext}'  => $exists ? JFile::getExt($file_full_path) : '',
			];

			$item = str_ireplace(array_keys($vars), array_values($vars), $subject);
			break;

		// Link
		default:
			$item = '<a href="' . $file_url . '"';

			if ($fieldParams->get('force_download', true))
			{
				$item .= ' download';
			}

			$item .= '>' . $fieldParams->get('link_text', $file) . '</a>';
			break;
	}

	$buffer[] = '<span class="acfup-item">' . $item . '</span>';
}

echo implode('', $buffer);
