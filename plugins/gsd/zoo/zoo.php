<?php

/**
 * @package         Google Structured Data
 * @version         4.8.0-RC1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2019 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

use NRFramework\Cache;

/**
 *  Zoo Google Structured Data Plugin
 */
class plgGSDZoo extends GSD\PluginBase
{
    /**
     *  Current manipulated item
     *
     *  @var  object
     */
    private $item;

    /**
     *  Discover Zoo view name
     *
     *  The 'view' parameter is used only if the Zoom item is assosiated to a menu item.
     *  Otherwise it uses the 'task' parameter. That's why we override this method.
     *
     *  @return  string  The view name
     */
    protected function getView()
    {
    	$input = $this->app->input;

    	if ($input->get('view') == 'item' || $input->get('task') == 'item')
    	{
    		return 'item';
    	}

    	return $input->get('view');
    }

    /**
     *  Discover Zoo Item ID
     *  
     *  Normally the item's id can be read by the request parameters BUT if the item
     *  is assosiated to a menu item the item_id parameter is not yet available and 
     *  we can only find it out through the menu's parameters.
     *  
     *  @return  integer   The item's ID
     */
    protected function getThingID()
    {
    	$requestID = $this->app->input->getInt('item_id', null);

    	if (!is_null($requestID))
    	{
    		return $requestID;
	   	}

	   	// Try to discover the item id from the menu parameters
        return (int) $this->app->getMenu()->getActive()->params->get('item_id');
    }

	/**
	 *  Get the post's data
	 *
	 *  @return  array
	 */
	public function viewItem()
	{
		// Make sure Zoo App is available
		if (!class_exists('App'))
		{
			return;
		}

		$zoo = App::getInstance('zoo');

		if (!$this->item = $zoo->table->item->get($this->getThingID()))
		{
			return;
		}

		$elements = $this->getItemElements();
		$rating   = $this->getItemRating();
		$payload  = [
			'id'           => $this->item->id,
			'alias'        => $this->item->alias,
			'headline'     => $this->item->name,
			'description'  => $this->getItemDescription(),
			'image'        => $this->getImage(),
            'imagetext'	   => \GSD\Helper::getFirstImageFromString($this->getItemDescription()),
			'created_by'   => $this->item->created_by,
			'created'      => $this->item->created,
			'modified'     => $this->item->modified,
			'publish_up'   => $this->item->publish_up,
			'publish_down' => $this->item->publish_down
		];

		return array_merge($payload, $rating, $elements);
	}

	/**
	 * Return a list of all current item elements
	 *
	 * @return array
	 */
	private function getItemElements()
	{
		if (!$elements = $this->item->getElements())
		{
			return;
		}

		$result = [];

		foreach ($elements as $key => $element)
		{
			if (!$data = $element->data())
			{
				continue;
			}

			switch ($element->getElementType())
			{
				// Text: Display multiple values separated by comma.
				case 'text':
					$values = array_map(function($item) {
						return $item['value'];
					}, $data);

					$value = implode(', ', $values);
					break;
				// Select: Display multiple values separated by comma.
				case 'select':
					$value = implode(', ', $data['option']);
					break;

				// The Date and Date Pro Zoo elements save date value in UTC in the database, so we need to modify the timezone offset.
				case 'date':
				case 'datepro':
					if (isset($data[0]) && isset($data[0]['value']))
					{
						$value = GSD\Helper::date($data[0]['value'], true);
					}

					break;

				default:
					if (isset($data[0]) && is_array($data[0]))
					{
						$value = reset($data[0]);
					} else 
					{
						$value = reset($data);
					}
					break;
			}

			if ($value == '')
			{
				continue;
			}

			// Construct the key to be used in Smart Tag replacements
			$type = $element->getType();
			$app  = $type->getApplication()->application_group;
			$payload_key = $app . '.' . $type->id . '.' . $key;

			$result[$payload_key] = $value;
		}

		return $result;
	}

	/**
	 *  Get Zoo Item's Description
	 *
	 *  @return  mixed  Null on failure, String on success
	 */
	private function getItemDescription()
	{
		$text = $this->getElement('textarea', $this->params->get('text_element'));

		if (!is_object($text))
		{
			return;
		}

		return $text->data()[0]['value'];
	}

	/**
	 *  Get Zoo Item's image
	 *
	 *  @return  string  The image filename path
	 */
	private function getImage()
	{
		$teaser = $this->getElement('image', $this->params->get('image_element'));

		if (!is_object($teaser))
		{
			return;
		}

		return $teaser->get('file');
	}

	/**
	 *  Get Zoo Item's Rating value and review's counter
	 *
	 *  @return  mixed  Null on failure, Array on success
	 */
	private function getItemRating()
	{
		$rating = $this->getElement('rating');

		if (!$rating || is_null($rating))
		{
			return array();
		}

		return array(
			'ratingValue' => $rating->getRating(),
			'reviewCount' => (int) $rating->get('votes', 0),
			'bestRating'  => (int) $rating->config->get('stars', 5)
		);
	}

	/**
	 *  Finds any Item's element by type or name
	 *
	 *  @param   string  $type  Element type name
	 *  @param   string  $name  Element name
	 *
	 *  @return  object         The found element object
	 */
	private function getElement($type = 'image', $name = null)
	{
		if (!is_array($this->item->getElements()))
		{
			return;
		}

		foreach ($this->item->getElements() as $element)
		{
			if ($element->getElementType() != $type)
			{
				continue;
			}

			if (!is_null($name) && $element->config->get('name') != $name)
			{
				continue;
			}

			return $element;
		}
	}

	/**
	 * The MapOptions Backend Event. Triggered by the mappingoptions fields to help each integration add its own map options.
	 *  
	 * @param	string	$plugin
	 * @param	array	$options
	 *
	 * @return	void
	 */
    public function onMapOptions($plugin, &$options)
    {
		if ($plugin != $this->_name)
        {
			return;
		}

		if (!$applications = $this->getApplicationElementsFromZooConfig())
		{
			return;
		}

		foreach ($applications as $app_key => $application)
		{
			foreach ($application as $type_key => $type)
			{
				$options_group_name = JText::_('PLG_GSD_ZOO_ALIAS') . ': ' . ucfirst($app_key) . ' - ' . ucfirst($type_key);
				GSD\MappingOptions::add($options, $type, $options_group_name, 'gsd.item.' . $app_key . '.' . $type_key . '.');
			}
		}	

		// Remove unsupported mapping options
		$unsupported_options = [
			'gsd.item.introtext',
			'gsd.item.fulltext'
		];

		foreach ($unsupported_options as $option)
		{
			unset($options['GSD_INTEGRATION'][$option]);
		}
	}

	/**
	 * Get all Application Elements as saved in the Zoo component config files.
	 *
	 * @return array
	 */
	private function getApplicationElementsFromZooConfig()
	{
		// Check if we have already the elements in cache
		$hash = 'zooelements';

		if (Cache::read($hash))
		{
			return Cache::get($hash);
		}

		$path = JPATH_SITE . '/media/zoo/applications/';

		if (!$applications = JFolder::folders($path))
		{
			return;
		}

		$elements = [];

		foreach ($applications as $application)
		{
			$application_path = $path . '/' . $application . '/types/';

			if (!$types = JFolder::files($application_path, '.config'))
			{
				continue;
			}

			foreach ($types as $type)
			{
				if (!$config = JFile::read($application_path . $type))
				{
					continue;
				}

				$config = json_decode($config, true);

				if (!isset($config['name']))
				{
					continue;
				}

				foreach ($config['elements'] as $element_key => $element)
				{
					$elements[$application][$config['name']][$element_key] = $element['name'];
				}
			}
		}

		return Cache::set($hash, $elements);
	}
}
