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
use Joomla\CMS\Filter\InputFilter;

/**
 *  K2 Google Structured Data Plugin
 */
class plgGSDK2 extends GSD\PluginBase
{
	/**
	 *  Get items's data
	 *
	 *  @return  array
	 */
	public function viewItem()
	{
		// Skip front-end editing page
		if (in_array($this->app->input->get('task'), array('edit', 'add')))
		{
			return;
		}

		// Load current item via model
		$model = JModelLegacy::getInstance('Item', 'K2Model');
		$item  = $model->getData();

		if (!is_object($item))
		{
			return;
		}

		// Prepare the item in order to get the item's images and custom fields.
		$item = $model->prepareItem($item, 'item', '');

		// Image Size. Defaults to large.
		$size_ = $this->params->get('imagesize', 'large');
		$size_ = (substr($size_, 0, 1) == 'x') ? strtoupper(substr($size_, 0, 2)) . substr($size_, 2) : ucfirst($size_);
		$image = $item->{'image' . $size_};

        // Calculate rating
        $ratingValue = isset($item->votingPercentage) ? number_format($item->votingPercentage * 5 / 100, 1) : 0;
		$reviewCount = isset($item->numOfvotes) ? preg_replace('/[^0-9]/', '', $item->numOfvotes) : 0;

        $payload = [
			'id'           => $item->id,
			'alias'        => $item->alias,
			'headline'     => $item->title,
			'description'  => !empty($item->introtext) ? $item->introtext : $item->fulltext,
			'introtext'    => $item->introtext,
			'fulltext'     => $item->fulltext,
			'image'        => $image,
			'imagetext'	   => \GSD\Helper::getFirstImageFromString($item->introtext . $item->fulltext),
			'created_by'   => $item->created_by,
			'created'      => $item->created,
			'modified'     => $item->modified,
			'publish_up'   => $item->publish_up,
			'publish_down' => $item->publish_down,
			'ratingValue'  => $ratingValue,
        	'reviewCount'  => $reviewCount,
        	'metakey'      => $item->metakey,
			'metadesc'     => $item->metadesc,
			
            // Category Info
            'category.id'    => $item->category->id,
            'category.title' => $item->category->name,
            'category.alias' => $item->category->alias,
            'category.desc'  => $item->category->description
		];

		
        // Add K2 Custom Fields to payload
        if ((bool) $this->params->get('load_custom_fields', true) && isset($item->extra_fields) && is_array($item->extra_fields))
        {
	        foreach ($item->extra_fields as $key => $field)
	        {
				$field_path = 'cf.' . $field->alias;
				
	        	switch ($field->type)
	        	{
	        		// In case of a Link field we need the actual URL.
	        		case 'link':
	        			$value = $field->url;
	        			break;
	        		// In case of an Image field we need the actual image path.
	        		case 'image':
		        		preg_match('/src="([^"]*)"/i', $field->value, $image);
		        		if (isset($image[1]))
		        		{
							$value = $image[1];
		        		}
	        			break;
	        		default:
						$value = (isset($field->rawValue) && !empty($field->rawValue)) ? $field->rawValue : $field->value;
	        			break;
	        	}

	        	$payload[$field_path] = $value;
	        }
		}

        return $payload;
	}

	/**
	 *  Prepare form to be added to the K2 item editing page.
	 *
	 *  @param   JForm  $form  The K2 Table Item
	 *
	 *  @return  object
	 */
	public function onGSDPluginForm($data)
	{
		// Only if fast edit is enabled
		if (!(bool) $this->params->get('fastedit', false))
		{
			return;
		}

		// Make sure the user can access com_gsd
		if (!JFactory::getUser()->authorise('core.manage', 'com_gsd'))
		{
			return;
		}

		// Make sure we are manipulating a K2 table object
		if (!($data instanceof TableK2Item))
		{
			return;
		}

		// Prepare our form
        $form = new JForm('form');
		$form->loadFile(__DIR__ . '/form/form.xml', false);
		
		$form->setFieldAttribute('snippet', 'thing', $data->id, 'plugins.gsd');
		$form->setFieldAttribute('snippet', 'thing_title', $data->title, 'plugins.gsd');
		$form->setFieldAttribute('snippet', 'plugin', $this->_name, 'plugins.gsd');
		$form->setFieldAttribute('snippet', 'plugin_assignment_name', 'k2_items',  'plugins.gsd');

		// Oohh boy. Modals look messy due to K2 overrides. Let's fix it.
        JFactory::getDocument()->addStyleDeclaration('
			.itemPlugins {
			    font-family: Arial;
			    margin-top: 0;
			}
			.itemPlugins fieldset {
			    padding-top: 0 !important;
			}
			#gsdModal .modal-header h3 {
			    font-size: 18px !important;
			    padding: 0 !important;
			    color: inherit !important;
			    border:none !important;
			}
        ');

        // Prepare required K2 object
        $plugin = new \stdClass();
        $plugin->name   = JText::_('GSD');
        $plugin->fields = $form->renderFieldset('gsd');

        return $plugin;
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

		if ((bool) !$this->params->get('load_custom_fields', true))
		{
			return;
		}

		$custom_fields_options = $this->getCustomFields();

		MappingOptions::add($options, $custom_fields_options);

        // Add Category Options
        $cat_options = [
            'category.id'    => 'GSD_MAPPING_OPTION_CAT_ID',
            'category.alias' => 'GSD_MAPPING_OPTION_CAT_ALIAS',
            'category.title' => 'GSD_MAPPING_OPTION_CAT_TITLE',
            'category.desc'  => 'GSD_MAPPING_OPTION_CAT_DESC'
        ];

		MappingOptions::add($options, $cat_options, 'GSD_INTEGRATION', 'gsd.item.');
	}

	/**
	 * Get a list of all K2 Custom Fields
	 *
	 * @return array
	 */
	private function getCustomFields()
	{
		$hash = md5($this->_name . 'cf');

		if (Cache::has($hash))
		{
			return Cache::get($hash);
		}

		$db = $this->db;
		
		$query = $db->getQuery(true)
			->select($db->quoteName(['id', 'name', 'value']))
			->from($db->quoteName('#__k2_extra_fields'))
			->where($db->quoteName('published') .  ' = 1');

		$db->setQuery($query);

		$rows = $db->loadObjectList();

		$filter = InputFilter::getInstance();

		$result = [];

		foreach ($rows as $key => $row)
		{
			$params = json_decode($row->value);

			if (isset($params[0]) && isset($params[0]->alias) && !empty($params[0]->alias))
			{
				$alias = $params[0]->alias;
			} else 
			{
				// Fallback to item ID
				if (!$alias = $filter->clean($row->name, 'WORD'))
				{
					$alias = $rows->id;
				}
			}

			$result[$alias] = $row->name;
		}

		return Cache::set($hash, $result);
	}
}
