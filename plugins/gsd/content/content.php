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
use NRFramework\Functions;
use GSD\MappingOptions;
use Joomla\Registry\Registry;

/**
 *  Joomla! Content Google Structured Data Plugin
 */
class plgGSDContent extends GSD\PluginBaseArticle
{
    /**
     *  Validate context to decide whether the plugin should run or not.
     *
     *  @return   bool
     */
    protected function passContext()
    {
		// YooTheme Builder is previewing an article
        return parent::passContext() && !$this->app->input->get('customizer');
    }

	/**
	 *  Get article's data
	 *
	 *  @return  array
	 */
	public function viewArticle()
	{
		// Load current item via model
		if (defined('nrJ4'))
		{	
			$model = new Joomla\Component\Content\Site\Model\ArticleModel(['ignore_request' => true]);
			$model->setState('article.id', $this->getThingID());
			$model->setState('params', $this->app->getParams());
		} else 
		{
			$model = JModelLegacy::getInstance('Article', 'ContentModel');
		}

		// Make sure we have a valid item data
		if (!is_object($model) || !$item = $model->getItem())
		{
			return;
		}

		// Image
		$image = new Registry($item->images);

		// // Set text property required by the Content Prepare Event
		$item->text = isset($item->introtext) && !empty($item->introtext) ? $item->introtext : $item->fulltext;

		// Prepare Article with Content Plugins
		if ($this->params->get('preparecontent', false))
		{
			$this->prepareItem($item);
		}

		// Array data
		$payload = [
			'id'           => $item->id,
			'alias'        => $item->alias,
			'headline'     => $item->title,
			'description'  => $item->text,
			'introtext'    => $item->introtext,
			'fulltext'     => $item->fulltext,
			'image_intro'  => $image->get('image_intro'),
			'image_full'   => $image->get('image_fulltext'),
			'image'        => $image->get('image_intro') ?: $image->get('image_fulltext'),
			'imagetext'	   => \GSD\Helper::getFirstImageFromString($item->introtext . $item->fulltext),
			'created_by'   => $item->created_by,
			'created_by_alias' => $item->created_by_alias,
			'created'      => $item->created,
			'modified'     => $item->modified,
			'publish_up'   => $item->publish_up,
			'publish_down' => $item->publish_down,
			'ratingValue'  => $item->rating,
        	'reviewCount'  => $item->rating_count,
        	'metakey'	   => $item->metakey,
            'metadesc'	   => $item->metadesc,
            
            // Category Info
            'category.id'     => $item->catid,
            'category.title'  => $item->category_title,
            'category.alias'  => $item->category_alias
		];
		
		if ((bool) $this->params->get('load_custom_fields', true))
		{
			$this->attachCustomFields($item, $payload);
		}

		return $payload;
	}
	
	/**
	 * Append Custom Fields to payload
	 *
	 * @param	object	$article
	 * @param	array	$payload
	 * @param   string 	$prefix
	 *
	 * @return	void
	 */
	private function attachCustomFields($article, &$payload, $prefix = 'cf.')
	{
		$fields = $this->getCustomFields($article);
		
		if (!is_array($fields) || count($fields) == 0)
		{
			return;
		}

		foreach ($fields as $key => $field)
		{
			$field_path = $prefix . strtolower($field->name);
			$value = $field->value;

			if ($field->rawvalue && $field->value != $field->rawvalue)
			{
				$value = $field->rawvalue;
			}

			$payload[$field_path] = is_array($value) ? implode(', ', $value) : $value;
		}
	}
	
	/**
	 *  Add a new tab called Google Structured Data in the article editing page
	 *
	 *  @param   JForm  $form  The form to be altered.
	 *  @param   mixed  $data  The associated data for the form.
	 *
	 *  @return  boolean
	 */
	public function onGSDPluginForm($form, $data)
	{
		// Only if fast edit is enabled
		if (!(bool) $this->params->get('fastedit', true))
		{
			return;
		}
		
		// Make sure the user can access com_gsd
		if (!JFactory::getUser()->authorise('core.manage', 'com_gsd'))
		{
			return;
		}
		
		// Make sure we are manipulating a JForm
		if (!($form instanceof JForm))
		{
			return;
		}
		
		if ($form->getName() != 'com_content.article')
		{
			return;
		}

		if (empty($data))
		{
			return;
		}
		
		// Ohh boy.. another B/C break introduced in Joomla! 3.8.10
		// Issue:   https://github.com/joomla/joomla-cms/issues/20879
		// Culprit: https://github.com/joomla/joomla-cms/pull/20313
		if (is_object($data))
		{
			$data = (array) $data;
		}

		$form->loadFile(__DIR__ . '/form/form.xml', false);
		
		$form->setFieldAttribute('snippet', 'thing', $data['id'], 'attribs.gsd');
		$form->setFieldAttribute('snippet', 'thing_title', $data['title'], 'attribs.gsd');
		$form->setFieldAttribute('snippet', 'plugin_assignment_name', 'article',  'attribs.gsd');
		$form->setFieldAttribute('snippet', 'plugin', $this->_name, 'attribs.gsd');
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

		// intro and full images
		$images_options = [
			'image_intro' => 'NR_INTRO_IMAGE',
			'image_full'  => 'NR_FULL_IMAGE',
		];
		MappingOptions::add($options, $images_options, 'GSD_INTEGRATION', 'gsd.item.');

		if ((bool) !$this->params->get('load_custom_fields', true))
		{
			return;
		}
		
		// Add Author Alias option
		$offset = array_search('user.name', array_keys($options['GSD_INTEGRATION']));
		$options['GSD_INTEGRATION'] = Functions::array_splice_assoc($options['GSD_INTEGRATION'], ['gsd.item.created_by_alias' => 'Author Alias'], $offset);
		
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

        // Add Category Options
        $cat_options = [
            'category.id'     => 'GSD_MAPPING_OPTION_CAT_ID',
            'category.alias'  => 'GSD_MAPPING_OPTION_CAT_ALIAS',
            'category.title'  => 'GSD_MAPPING_OPTION_CAT_TITLE'
        ];

		MappingOptions::add($options, $cat_options, 'GSD_INTEGRATION', 'gsd.item.');
    }
	
	/**
	 * Load Joomla Articles Custom Fields
	 *
	 * @param  mixed $article
	 *
	 * @return void
	 */
	private function getCustomFields($article = null)
	{
		$hash = md5($this->_name . 'cf');

		if (Cache::has($hash))
		{
			return Cache::get($hash);
		}

		JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

		if (!class_exists('FieldsHelper'))
		{
			return;
		}

		$fields = FieldsHelper::getFields('com_content.article', $article, true);

		return Cache::set($hash, $fields);
	}

	/**
	 * Prepare Article with Content Plugins.
	 *
	 * @param	object	$item 	The article object
	 *
	 * @return	void
	 */
	private function prepareItem($item)
	{
		// add more to parameters if needed
		$params = new JObject;
		\JPluginHelper::importPlugin('content');
		$this->app->triggerEvent('onContentPrepare', ['com_content.article', &$item, &$params, 0]);
	}
}
