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

use GSD\Helper;
use GSD\MappingOptions;

/**
 *  DJ Events Google Structured Data Plugin
 */
class plgGSDDJEvents extends \GSD\PluginBaseEvent
{
	/**
	 * Model
	 * 
	 * @var  Class
	 */
	private $model;

	/**
	 * Item tags
	 * 
	 * @var  array
	 */
	private $tags;
	
	/**
	 *  Get article's data
	 *
	 *  @return  array
	 */
	public function viewEvent()
	{	
		// Make sure we have a valid ID
		if (!$id = $this->getThingID())
		{
			return;
		}

		// Load current item via model
		$this->model = JModelLegacy::getInstance('Event', 'DJEventsModel');

		$this->item = $this->model->getItem();
		$this->tags = $this->model->getTags();
		
		if (!is_object($this->item))
		{
			return;
		}

		// Array data
		$payload = [
			'id'   		  		  => $this->item->id,
			'alias'       		  => $this->item->alias,
			'headline'    		  => $this->item->title,
			'description' 		  => empty($this->item->intro) ? $this->item->description : $this->item->intro,
			'introtext'   		  => $this->item->intro,
			'fulltext'   	      => $this->item->description,
			'image'       		  => $this->item->image,
            'imagetext'	   		  => Helper::getFirstImageFromString($this->item->intro . $this->item->description),
			'startDate'	  		  => $this->item->start,
			'endDate'	  		  => $this->item->end, 
			'created_by'  		  => $this->item->created_by,
			'publish_up'  		  => $this->item->created,
			'publish_down'		  => $this->item->end,
			'offerStartDate' 	  => $this->item->start,
			'offerPrice'    	  => $this->item->price,
			'locationName' 		  => $this->item->location,
			'locationAddress'	  => $this->item->address
		];

		// Add Item Data Values
		$this->attachItemData($payload);

		return $payload;
	}

	/**
	 * Adds all item data values to the payload
	 * 
	 * @param   array   $payload
	 * 
	 * @return  void
	 */
	private function attachItemData(&$payload)
	{
		$custom_fields = $this->getItemData();

		foreach ($custom_fields as $key => $field)
		{
			$field_id = strtolower($field['name']);

			// get value
			$value = isset($this->item->{$field['name']}) ? $this->item->{$field['name']} : '';

			if ($field['name'] == 'latlong')
			{
				$value = $this->item->latitude . ',' . $this->item->longitude;
			}
			else if ($field['name'] == 'tags')
			{
				$value = implode(', ', array_map(function($tag) { return $tag->name; }, (array) $this->tags));
			}
			else if ($field['name'] == 'video' && empty($this->item->video) && $media = $this->model->getMedia())
			{
				// video is empty, try to find first video from the "Event Media" section
				foreach ($media as $key => $value)
				{
					$video = $value->video;
					if (empty($video))
					{
						continue;
					}

					// found first valid video, set it as value and stop
					$value = $video;
					break;
				}
				
			}

			// set value
			$payload[$field_id] = $value;
		}
	}

	/**
	 * Retrieves useful item data
	 * 
	 * @return  array
	 */
	private function getItemData()
	{
		$data = [
			'offerprice' => [
				'name' => 'price',
				'title' => 'PLG_GSD_DJEVENTS_PRICE',
			],
			'external_url' => [
				'name' => 'external_url',
				'title' => 'PLG_GSD_DJEVENTS_EXTERNAL_URL',
			],
			'locationname' => [
				'name' => 'location',
				'title' => 'PLG_GSD_DJEVENTS_LOCATION',
			],
			'locationaddress' => [
				'name' => 'address',
				'title' => 'PLG_GSD_DJEVENTS_ADDRESS',
			],
			'post_code' => [
				'name' => 'post_code',
				'title' => 'PLG_GSD_DJEVENTS_POST_CODE',
			],
			'latlong' => [
				'name' => 'latlong',
				'title' => 'PLG_GSD_DJEVENTS_MAP_COORDS',
			],
			'city_name' => [
				'name' => 'city_name',
				'title' => 'NR_CITY',
			],
			'video' => [
				'name' => 'video',
				'title' => 'PLG_GSD_DJEVENTS_VIDEO',
			],
			'tags' => [
				'name' => 'tags',
				'title' => 'PLG_GSD_DJEVENTS_TAGS',
			]
		];

		return $data;
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
		parent::onMapOptions($plugin, $options);

		if ($plugin != $this->_name)
        {
			return;
		}

		// Remove undeeded default mapping options values
		$remove_options = [
			'metakey',
			'metadesc',
			'performerName',
			'offercurrency',
			'offerinventorylevel'
		];

		// Remove unsupported mapping options
		foreach ($remove_options as $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}

		// Add Custom Fields
		if (!$custom_fields = $this->getItemData())
		{
			return;
		}
		
		$custom_fields_options = [];
	
		foreach ($custom_fields as $key => $value)
		{
			$custom_fields_options[strtolower($value['name'])] = $value['title'];
		}

		MappingOptions::add($options, $custom_fields_options, 'GSD_INTEGRATION', 'gsd.item.');
	}
}
