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

use NRFramework\Cache;
use GSD\MappingOptions;
use GSD\PluginBaseArticle;

/**
 *  SobiPro Google Structured Data Plugin
 */
class plgGSDSobiPro extends PluginBaseArticle
{
    /**
     *  Discover SobiPro view name
	 * 
	 *	There is no view added automatically so we fetch it from the task.
     *
     *  @return  string  The view name
     */
    protected function getView()
    {
    	$input = $this->app->input;

    	if ($input->get('task') == 'entry.details')
    	{
    		return 'entry';
    	}

	}
	
    /**
     *  Discover SobiPro Item ID
     *  
     *  @return  integer   The item's ID
     */
    protected function getThingID()
    {
		return \SPRequest::sid();
    }
	
	/**
	 *  Get entry's data
	 *
	 *  @return  array
	 */
	public function viewEntry()
	{
		// Make sure we have a valid ID
		if (!$id = $this->getThingID())
		{
			return;
		}

		// entry data
		$entry = \SPFactory::Entry($id);

		$introtext = $entry->get('field_short_description');
		$fulltext = $entry->get('field_full_description');

		// review data
		$reviewData = $this->getRatingReviewStats($id);

		$payload = [
			'id'           => $entry->get('id'),
			'alias'        => $entry->get('nid'),
			'headline'     => $entry->get('name'),
			'description'  => empty($introtext) ? $fulltext : $introtext,
			'introtext'    => $introtext,
			'fulltext'     => $fulltext,
			'imagetext'	   => \GSD\Helper::getFirstImageFromString($introtext . $fulltext),
			'created_by'   => $entry->get('cout'),
			'created'      => $entry->get('createdTime'),
			'modified'     => $entry->get('updatedTime'),
			'publish_up'   => $entry->get('validSince'),
			'publish_down' => $entry->get('validUntil'),
        	'metakey'	   => $entry->get('metaKeys'),
            'metadesc'	   => $entry->get('metaDesc'),
			'ratingValue'  => isset($reviewData[0]->average) ? $reviewData[0]->average : '',
			'reviewCount'  => isset($reviewData[0]->count) ? $reviewData[0]->count : '',
			'bestRating'   => 10
		];

		// Load custom fields
		$this->attachCustomFields($entry, $payload);

		return $payload;

	}

	/**
	 * Append Custom Fields to payload
	 *
	 * @param	object	$entry
	 * @param	array	$payload
	 * @param   string 	$prefix
	 *
	 * @return	void
	 */
	private function attachCustomFields($entry, &$payload, $prefix = 'cf.')
	{
		$fields = $entry->getFields();
		
		if (!is_array($fields) || count($fields) == 0)
		{
			return;
		}
		
		foreach ($fields as $key => $field)
		{
			$field_id = strtolower($field->get('nid'));

			$field_path = $prefix . $field_id;
			$value = $field->get('_rawData');

			if ($field_id == 'field_company_logo')
			{
				$data = $this->unserializeValue($value);
				$value = isset($data['image']) ? $data['image'] : '';
			}
			else if ($field_id == 'field_website')
			{
				$data = $this->unserializeValue($value);
				$protocol = isset($data['protocol']) ? $data['protocol'] : '';
				$url = isset($data['url']) ? $data['url'] : '';
				$value = $protocol . '://' . $url;
			}
			else if ($field_id == 'field_category')
			{
				$data = $this->unserializeValue($value);
				$ids = $this->getCategoriesFromIds($data);
				$value = implode(', ', array_map(function($x) { return $x->title; }, $ids));
			}

			$payload[$field_path] = is_array($value) ? implode(', ', $value) : $value;
		}
	}

	/**
	 * Unserialize value by base64_decode() and then unserialize()
	 * 
	 * @return  array
	 */
	private function unserializeValue($value)
	{
		return unserialize(base64_decode($value));
	}

	/**
	 * Fetch categories based on ids
	 * 
	 * @return  array
	 */
	private function getCategoriesFromIds($ids)
	{
		// Get a database object.
        $db = $this->db;
        
		$query = $db->getQuery(true)
			->select('sValue as title')
			->from('#__sobipro_language')
			->where($db->quoteName('id') . ' IN (' . implode(",",$ids) . ')')
			->where($db->quoteName('sKey') . ' = '. $db->quote('name'))
			->where($db->quoteName('oType') . ' = '. $db->quote('category'));
			
		$db->setQuery($query);

		return $db->loadObjectList();
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
		
		// Add Custom Fields
		if (!$custom_fields = $this->getCustomFields())
		{
			return;
		}

		$custom_fields_options = [];
	
		foreach ($custom_fields as $key => $field)
		{
			$custom_fields_options[$field->name] = $field->title;
		}

		MappingOptions::add($options, $custom_fields_options);

		$remove_options = [
			'image'
		];
		
		// Remove unsupported mapping options
		foreach ($remove_options as $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}
	}

	/**
	 * Load SobiPro Entry Fields
	 *
	 * @return void
	 */
	private function getCustomFields()
	{
		$hash = md5($this->_name . 'cf');

		if (Cache::has($hash))
		{
			return Cache::get($hash);
		}

		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		// Get all SobiPro Fields
		$query
			->select($db->quoteName(array('f.nid', 'l.sValue'), array('name', 'title')))
			->from($db->quoteName('#__sobipro_field', 'f'))
			->join('LEFT', $db->quoteName('#__sobipro_language', 'l') . ' ON (' . $db->quoteName('l.fid') . ' = ' . $db->quoteName('f.fid') . ')')
			->where($db->quoteName('l.sKey') . " = 'name'");

		$db->setQuery($query);

		$fields = $db->loadObjectList();

		return Cache::set($hash, $fields);
	}

	/**
	 * Get the average of the ratings and the total number of reviews
	 * 
	 * @return  array
	 */
	private function getRatingReviewStats($id)
	{
		// Check if the review table exists, otherwise exit
		if (!$this->reviewTableExists())
		{
			return;
		}

		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		$query
            ->select(['AVG(' . $db->quoteName('r.oar') . ') as average', 'COUNT(' . $db->quoteName('r.rid') . ') as count'])
			->from($db->quoteName('#__sobipro_sprr_review', 'r'))
			->where($db->quoteName('r.sid') . ' = '. $db->quote($id))
			->where($db->quoteName('r.state') . ' = 1')
			->where($db->quoteName('r.oar') . ' > 0');

		$db->setQuery($query);

		$result = $db->loadObjectList();
		
		return $result;

	}

	/**
	 * Checks whether the SobiPro Review table exists
	 * 
	 * @return  boolean
	 */
	private function reviewTableExists() {
		$db = JFactory::getDbo();

		$query = "SHOW TABLES LIKE '%" . $db->getPrefix() . "sobipro_sprr_review%'";

		$db->setQuery($query);

		$result = $db->loadColumn();

		return (count($result)) ? true : false;
	}
}
