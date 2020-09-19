<?php

/**
 * @package         Google Structured Data
 * @version         4.8.0-RC1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2020 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('text');

class JFormFieldNRImagesSelector extends JFormFieldText
{
    /**
	 * Renders the Images Selector
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$field_attributes = (array) $this->element->attributes();
		$attributes = isset($field_attributes["@attributes"]) ? $field_attributes["@attributes"] : null;
		$field_attributes = new JRegistry($attributes);

		$columns = $field_attributes->get('columns', 6);
		$width = $field_attributes->get('width', '100%');
		$height = $field_attributes->get('height', '');
		
		if (!$images = $field_attributes->get('images', ''))
		{
			return;
		}

		$paths = explode(',', $images);

		$images = [];
		foreach ($paths as $key => $path)
		{
			// skip empty paths
			if (empty(rtrim(ltrim($path, ' '), ' ')))
			{
				continue;
			}

			if ($imgs = $this->getImagesFromPath($path))
			{
				// add new images to array of images
				$images = array_merge($images, $imgs);
			}
			else
			{
				// check if image exist
				if (file_exists(JPATH_ROOT . '/' . ltrim($path, ' /')))
				{
					// add new image to array of images
					$images[] = ltrim($path, ' /');
				}
			}
		}
		
		// load CSS
		JHtml::stylesheet('plg_system_nrframework/images-selector-field.css', ['relative' => true, 'version' => true]);
		
		$layout = new \JLayoutFile('imagesselector', JPATH_PLUGINS . '/system/nrframework/layouts');
		
		$data = [
			'value'   => $this->value,
			'name' 	  => $this->name,
			'images'  => $images,
			'columns' => $columns,
			'width'   => $width,
			'height'   => $height
		];
		
        return $layout->render($data);
	}

    /**
     * Returns all images in path
     * 
     * @return  mixed
     */
	private function getImagesFromPath($path)
	{
		$folder = JPATH_ROOT . '/' . ltrim($path, ' /');

		if (!is_dir($folder) || !$folder_files = scandir($folder))
		{
			return false;
		}
		
		$images = array_diff($folder_files, array('.', '..', '.DS_Store'));
		$images = array_values($images);

		// prepend path to image file names
		array_walk($images, function(&$value, $key) use ($path) { $value = ltrim($path, ' /') . '/' . $value; } );
		
		return $images;
	}
}
