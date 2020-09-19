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

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use GSD\Helper;
use GSD\MappingOptions;

/**
 *  JCal Pro Google Structured Data Plugin
 */
class plgGSDJCalPro extends \GSD\PluginBaseEvent
{
	/**
	 * Item
	 * 
	 * @var  object
	 */
	private $item;
	
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

		$model = JModelLegacy::getInstance('Event', 'JcalproModel');
		$this->item = $model->getItem();

		// ensure event item is a valid object
		if (!is_object($this->item))
		{
			return;
		}

		// Array data
		$payload = [
			'id'   		  	 => $this->item->id,
			'alias'       	 => $this->item->alias,
			'headline'    	 => $this->item->title,
			'description' 	 => $this->item->description,
            'imagetext'	   	 => Helper::getFirstImageFromString($this->item->description),
			'startDate'	  	 => $this->item->start_date,
			'endDate'	  	 => $this->item->duration_type == '0' ? null : $this->item->end_date, 
			'created_by'  	 => $this->item->created_by,
			'publish_up'  	 => $this->item->created,
			'publish_down'	 => $this->item->end_date,
		];

		// Add event properties values to payload
		$this->attachEventPropertiesValues($payload);

		// Add custom fields values to payload
		$this->attachCustomFieldsValues($payload);

		return $payload;
	}

	/**
	 * Adds all event properties values to the payload
	 * 
	 * @param   array   $payload
	 * 
	 * @return  void
	 */
	private function attachEventPropertiesValues(&$payload)
	{
		$item_data = $this->getEventProperties();

		foreach ($item_data as $key => $field)
		{
			$field_id = $field['name'];

			// value
			$value = '';

			// check if its a location item
			if(strpos($key, 'location_') === 0 && isset($this->item->location_data))
			{
				$location = isset($this->item->location_data) ? $this->item->location_data : [];
				$location = new Registry($location);

				$value = $location->get($field_id);

				// manually handle location name, address and latlong
				if ($field_id == 'locationName')
				{
					$value = $location->get('title');
				}
				else if ($field_id == 'locationAddress')
				{
					$value = $location->get('address');
				}
				else if ($field_id == 'latlong')
				{
					$value = $location->get('latitude') . ',' . $location->get('longitude');
				}
			}

			// process tags
			if ($key == 'tags')
			{
				$tagsHelper = new JHelperTags();
				$tags_names = $tagsHelper->getTagNames($this->getTags());

				$value = implode(', ', $tags_names);
			}

			// set value
			$payload[$field_id] = $value;
		}
	}

	/**
	 * Attach custom field values to payload
	 * 
	 * @param   array   $payload
	 * @param   string  $prefix
	 * 
	 * @return  void
	 */
	private function attachCustomFieldsValues(&$payload, $prefix = 'cf.')
	{
		// get the custom field values from the event item
		$custom_fields_values = $this->item->params;
		if (!$custom_fields_values)
		{
			return;
		}

		$custom_fields = $this->getCustomFieldsData();

		foreach ($custom_fields_values as $key => $value)
		{
			/**
			 * Determine the field type and customize further the value.
			 */
			$field_type = isset($custom_fields[$key]) ? $custom_fields[$key]['type'] : null;

			/**
			 * If the value comes from a media field, then it does not provide a full URL,
			 * instead it returns a path relative to the joomla installation folder.
			 * We return a full URL to the selected media file.
			 */
			if ($field_type == 'media')
			{
				$value = Helper::absURL($value);
			}
			
			$payload[$prefix . $key] = $value;
		}
	}

	/**
	 * Returns all custom fields available
	 * 
	 * @return  array
	 */
	private function getCustomFields()
	{
		// also add the custom fields
		$custom_fields = $this->getCustomFieldsData();
		
		if (!count($custom_fields))
		{
			return [];
		}
		
		$data = [];
		foreach ($custom_fields as $key => $value)
		{
			$data[$key] = [
				'name' => $key,
				'title' => $value['label']
			];
		}

		return $data;
	}

	/**
	 * Returns the event tags
	 * 
	 * @return  array
	 */
	private function getTags()
	{
		$tags = $this->item->tags->tags;
		return explode(',', $tags);
	}

	/**
	 * Retrieves useful item data
	 * 
	 * @return  array
	 */
	private function getEventProperties()
	{
		$data = [
			'location_title' => [
				'name' => 'locationName',
				'title' => 'PLG_GSD_JCALPRO_LOC_TITLE',
			],
			'location_address' => [
				'name' => 'locationAddress',
				'title' => 'PLG_GSD_JCALPRO_LOC_ADDRESS',
			],
			'location_city' => [
				'name' => 'city',
				'title' => 'PLG_GSD_JCALPRO_LOC_CITY',
			],
			'location_state' => [
				'name' => 'state',
				'title' => 'PLG_GSD_JCALPRO_LOC_STATE',
			],
			'location_country' => [
				'name' => 'country',
				'title' => 'PLG_GSD_JCALPRO_LOC_COUNTRY',
			],
			'location_postal_code' => [
				'name' => 'postal_code',
				'title' => 'PLG_GSD_JCALPRO_LOC_POSTAL_CODE',
			],
			'location_latitude' => [
				'name' => 'latitude',
				'title' => 'PLG_GSD_JCALPRO_LOC_LATITUDE',
			],
			'location_longitude' => [
				'name' => 'longitude',
				'title' => 'PLG_GSD_JCALPRO_LOC_LONGITUDE',
			],
			'location_latlong' => [
				'name' => 'latlong',
				'title' => 'PLG_GSD_JCALPRO_LOC_LATLNG',
			],
			'tags' => [
				'name' => 'tags',
				'title' => 'PLG_GSD_JCALPRO_TAGS',
			]
		];

		return $data;
	}

	/**
	 * Get all custom fields data to be parsed
	 * 
	 * @return  array
	 */
	private function getCustomFieldsData()
	{
		$data = [];

		$db     = Factory::getDbo();
		$fields = $db->setQuery(
			$db->getQuery(true)
				->select('Field.*')
				->from('#__jcalpro_fields AS Field')
				->where('Field.formtype = 0')
				->where('Field.published = 1')
				->group('Field.id')
		)->loadObjectList();

		foreach ($fields as $field)
		{
			$data[$field->name] = [
				'type' => $field->type,
				'label' => $field->title
			];
		}

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
			'image',
			'introtext',
			'fulltext',
			'offerprice',
			'metakey',
			'metadesc',
			'performername',
			'offercurrency',
			'offerinventorylevel',
			'offerprice',
			'offercurrency',
			'offerinventorylevel',
			'offerstartdate',
			'locationname',
			'locationaddress',
		];

		// Remove unsupported mapping options
		foreach ($remove_options as $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}

		// Add Event Properties
		$event_properties = $this->getEventProperties();
		if ($event_properties)
		{
			$event_properties_options = [];
		
			foreach ($event_properties as $key => $value)
			{
				$event_properties_options[$value['name']] = $value['title'];
			}

			MappingOptions::add($options, $event_properties_options, 'GSD_INTEGRATION', 'gsd.item.');
		}

		// Add Custom Fields
		$custom_fields = $this->getCustomFields();
		if ($custom_fields)
		{
			$custom_fields_options = [];
		
			foreach ($custom_fields as $key => $value)
			{
				$custom_fields_options[strtolower($value['name'])] = $value['title'];
			}

			MappingOptions::add($options, $custom_fields_options);
		}
	}
}
