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

/**
 *  Add Google Structured Data to Menu Items
 */
class plgGSDMenus extends GSD\PluginBase
{
	/**
	 *  Active Menu Item
	 *
	 *  @var  object
	 */
	protected $menu;

    /**
     *  Validate context to decide whether the plugin should run or not.
     *
     *  @return   bool
     */
    protected function passContext()
    {
    	$this->menu = $this->app->getMenu()->getActive();

		if (is_object($this->menu) && isset($this->menu->id))
		{
			return true;
		}

		return false;
    }

    /**
     *  Get Item's ID
     *
     *  @return  string
     */
    protected function getThingID()
    {
        return $this->menu->id;
    }

    /**
     *  Discover view name
     *
     *  @return  string  The view name
     */
    protected function getView()
    {
    	return $this->_name;
    }

    /**
     *  Get Menu Item Payload
     *
     *  @return  array   The Menu Item Payload array
     */
	public function viewMenus()
	{
		$params = $this->menu->getParams();

		return array(
			'id'    	  => $this->menu->id,
			'alias'       => $this->menu->alias,
			'headline'    => $params->get('page_title', $this->menu->title),
			'description' => $params->get('menu-meta_description'),
			'image'       => $params->get('menu_image')
		);
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

		// Remove unsupported mapping options
		$unsupported_options = [
			'user.id',
			'user.name',
			'user.email',
			'user.firstname',
			'user.lastname',
			'user.login',
			'gsd.item.created',
			'gsd.item.publish_up',
			'gsd.item.publish_down',
			'gsd.item.modified',
			'gsd.item.ratingValue',
			'gsd.item.reviewCount',
			'gsd.item.metakey',
			'gsd.item.metadesc',
			'gsd.item.introtext',
			'gsd.item.fulltext',
			'gsd.item.imagetext'
		];

		foreach ($unsupported_options as $option)
		{
			unset($options['GSD_INTEGRATION'][$option]);
		}
	}

	/**
	 *  Route default form's prepare event to onGSDPluginForm to help our plugins manipulate the form
	 *
	 *  @param   JForm  $form  The form to be altered.
	 *  @param   mixed  $data  The associated data for the form.
	 *
	 *  @return  boolean
	 */
	public function onGSDPluginForm($form, $data)
	{
		// Run only on backend
		if (!$this->app->isClient('administrator') || !$form instanceof JForm)
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

		// Add a new tab called 'Google Structured Data' in the Menu Manager Item editing page
		// only on component-based menu items. System links such as as URLs or Menu Item Aliases are not supported.
		if ($form->getName() == 'com_menus.item' && $data['component_id'] > 0 && $this->params->get("fastedit", false))
		{
			$form->loadFile(__DIR__ . '/form/form.xml', false);

			// Here we need to set the id to the publishing rules
			$form->setFieldAttribute('snippet', 'thing', $data['id'], 'params.gsd');
			$form->setFieldAttribute('snippet', 'thing_title', $data['title'], 'params.gsd');
			$form->setFieldAttribute('snippet', 'plugin', $this->_name, 'params.gsd');
			$form->setFieldAttribute('snippet', 'plugin_assignment_name', 'menu',  'params.gsd');

			return;
		}
	}
}
