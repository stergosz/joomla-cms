<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace NRFramework;

use NRFramework\Cache;
use Joomla\Registry\Registry;

defined( '_JEXEC' ) or die( 'Restricted access' );

class Extension
{
	/**
	 * Indicates the base url of Tassos.gr Joomla Extensions
	 *
	 * @var string
	 */
	public static $product_base_url = 'https://www.tassos.gr/joomla-extensions';

	/**
	 * Array including already loaded extensions
	 *
	 * @var array
	 */
	public static $cache = [];

	/**
	 * Get extension ID
	 *
	 * @param	string	$element	The extension element name
	 * @param	string	$type		The extension type: component, plugin, library e.t.c
	 * @param	mixed	$folder		The plugin folder: system, content e.t.c
	 *
	 * @return	mixed 	False on failure, Integer on success
	 */
	public static function getID($element, $type = 'component', $folder = null)
	{
		if (!$extension = self::get($element, $type, $folder))
		{
			return false;
		}

		return (int) $extension['extension_id'];
	}

	/**
	 * Get extension data by ID
	 *
	 * @param	string	$extension_id		The extension primary key
	 * 
	 * @return	void
	 */
	public static function getByID($extension_id)
	{
		// Check if element is already cached
		if (isset(self::$cache[$extension_id]))
		{
			return self::$cache[$extension_id];
		}

		// Let's call the database
		$db = \JFactory::getDBO();	

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__extensions'))
			->where($db->quoteName('extension_id') . ' = ' . $extension_id);
			
		$db->setQuery($query);

		return self::$cache[$extension_id] = $db->loadAssoc();
	}

	/**
	 * Get extension information from database
	 *
	 * @param	string	$element	The extension element name
	 * @param	string	$type		The extension type: component, plugin, library e.t.c
	 * @param	mixed	$folder		The plugin folder: system, content e.t.c
	 *
	 * @return	array
	 */
    public static function get($element, $type = 'component', $folder = null)
    {
		// Check if element is already cached
		$hash = md5($element . '_' . $type . '_' . $folder);
		if (isset(self::$cache[$hash]))
		{
			return self::$cache[$hash];
		}

		// Let's call the database
		$db = \JFactory::getDBO();

		switch ($type)
		{
			case 'component':
				$element = 'com_' . str_replace('com_', '', $element);
				break;
			case 'module':
				$element = 'mod_' . str_replace('mod_', '', $element);
				break;
		}
		
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element))
            ->where($db->quoteName('type') . ' = ' . $db->quote($type));

        if (!is_null($folder))
        {
            $query->where($db->quoteName('folder') . ' = ' . $db->quote($folder));
        }

		$db->setQuery($query);

		return self::$cache[$hash] = $db->loadAssoc();
	}

	/**
	 * Helper method to check if a plugin is enabled
	 *
	 * @param	string	$element	The extension element name
	 * @param	string	$type		The extension type: component, plugin, library e.t.c
	 *
	 * @return  boolean
	 */
	public static function pluginIsEnabled($element, $folder = 'system') 
	{
		return self::isEnabled($element, 'plugin', $folder);
	}

	/**
	 * Helper method to check if a component is enabled
	 *
	 * @param	string	$element	The component element name
	 *
	 * @return	boolean
	 */
	public static function componentIsEnabled($element) 
	{
		return self::isEnabled($element);
	}

	/**
	 * Checks if an extension is enabled
	 *
	 * @param	string	$element	The extension element name
	 * @param	string	$type		The extension type: component, plugin, library e.t.c
	 * @param	mixed	$folder		The plugin folder: system, content e.t.c
	 *
	 * @return	boolean
	 */
	public static function isEnabled($element, $type = 'component', $folder = 'system')
	{
		switch ($type)
		{
			case 'component':
				if (!$extension = self::get($element))
				{
					return false;
				}

				return (bool) $extension['enabled'];
				break;

			case 'plugin':
				if (!$extension = self::get($element, $type = 'plugin', $folder))
				{
					return false;
				}
		
				return (bool) $extension['enabled'];
				break;
		}
	}

	/**
     *  Checks if an extension is installed
     *
     *  @param   string  $extension  The extension element name
     *  @param   string  $type       The extension's type 
     *  @param   string  $folder     Plugin folder
     *
     *  @return  boolean             Returns true if extension is installed
     */
    public static function isInstalled($extension, $type = 'component', $folder = 'system')
    {
        $db = \JFactory::getDbo();

        switch ($type)
        {
			case 'component':
				$extension_data = self::get('com_' . str_replace('com_', '', $extension));
				return isset($extension_data['extension_id']);
                break;

            case 'plugin':
                return \JFile::exists(JPATH_PLUGINS . '/' . $folder . '/' . $extension . '/' . $extension . '.php');

            case 'module':
                return (\JFile::exists(JPATH_ADMINISTRATOR . '/modules/mod_' . $extension . '/' . $extension . '.php')
                    || \JFile::exists(JPATH_ADMINISTRATOR . '/modules/mod_' . $extension . '/mod_' . $extension . '.php')
                    || \JFile::exists(JPATH_SITE . '/modules/mod_' . $extension . '/' . $extension . '.php')
                    || \JFile::exists(JPATH_SITE . '/modules/mod_' . $extension . '/mod_' . $extension . '.php')
                );

            case 'library':
                return \JFolder::exists(JPATH_LIBRARIES . '/' . $extension);
        }

        return false;
	}
	
	/**
	 * Discover extension's name based on the query string
	 *
	 * @param	boolean	$translate	If set to yes, the name will be returned translated
	 * 
	 * @return	string
	 */
	public static function getExtensionNameByRequest($translate = false)
	{
		$input  = \JFactory::getApplication()->input;
		$option = $input->get('option');

		switch ($option)
		{
			case 'com_fields':
				$name = 'plg_system_acf';
				break;
			case 'com_plugins':
				$plugin = self::getByID($input->get('extension_id'));

				if (is_array($plugin))
				{
					$name = $plugin['name'];
				}
				break;
			default:
				$name = $option;
				break;
		}

		if ($translate)
		{
			$name = explode(' - ', \JText::_($name));
			return end($name);
		}

		return $name;
	}

	/**
	 * Returns Tassos.gr extension checkout URL
	 *
	 * @param	string	$name		The extension's element name
	 *
	 * @return	string
	 */
	public static function getTassosExtensionUpgradeURL($name = null)
	{
		$name = is_null($name) ? self::getExtensionNameByRequest() : $name;

		switch ($name)
		{
			case 'com_gsd':
				$path = 'google-structured-data-markup/subscribe/new/google-structured-data-markup-year-plan';
				break;
			case 'com_rstbox':
				$path = 'engagebox/subscribe/new/engage-box-1-year-plan';
				break;
			case 'com_convertforms':
				$path = 'convert-forms/subscribe/new/convert-forms-year-plan';
				break;
			case 'plg_system_tweetme':
				$path = 'tweetme/subscribe/new/tweetme-year-plan';
				break;
			case 'plg_system_acf':
				$path = 'advanced-custom-fields/subscribe/new/advanced-custom-fields-1-year-plan';
				break;
			case 'plg_system_restrictcontent':
			case 'com_restrictcontent':
				$path = 'restrict-content/subscribe/new/restrict-content-1-year-plan';
				break;
			default:
				$path = '';
		}

		// Google Analytics UTM Parameters
        $utm = 'utm_source=Joomla&utm_medium=upgradebutton&utm_campaign=freeversion';

		return self::$product_base_url . '/' . $path . '?coupon=FREE2PRO&' . $utm;
	}

	public static function getProductAlias($extension)
	{
		$extension = is_null($extension) ? self::getExtensionNameByRequest() : $extension;

		switch ($extension)
		{
			case 'com_gsd': case 'plg_system_gsd': return 'google-structured-data-markup';
			case 'com_rstbox': return 'engagebox';
			case 'com_convertforms': return 'convert-forms';
			case 'plg_system_tweetme': return 'tweetme';
			case 'plg_system_acf': return 'advanced-custom-fields';
			case 'plg_system_restrictcontent':
			case 'com_restrictcontent': return 'restrict-conten';
		}
	}

	public static function getProductURL($extension) 
	{
		return self::$product_base_url . '/' . self::getProductAlias($extension);
	}

	public static function getPath($element)
	{
		$parts = explode('_', $element);

		switch ($parts[0])
		{
			case 'com':
				return JPATH_ADMINISTRATOR . '/components/' . $element;
			case 'plg':
				return JPATH_SITE . '/plugins/' . $parts[1] . '/' . $parts[2];
		}
	}

	public static function getVersion($extension, $include_type = false)
	{
		$xml = self::getXML($extension);

		if (!$xml || !isset($xml->version))
		{
			return;
		}

		$version = (string) $xml->version;

		// If enabled, it returns EngageBox Pro
		if ($include_type)
		{
			$isPro = self::isPro($extension);
			$version_type = $isPro ? 'Pro' : 'Free';
			$version .= ' ' . $version_type;
		}

		return $version;
	}

	public static function elementToAlias($element)
	{
		$parts = explode('_', $element);
		return end($parts);
	}

	public static function getXML($element)
	{
		if (!$path = self::getPath($element))
		{
			return;
		}

		$extension_alias = self::elementToAlias($element);
		$xml = $path . '/' . $extension_alias . '.xml';

		return \JFactory::getXML($xml);
	}

	/**
	 * Returns a URL where we can check for extension updates.
	 *
	 * @param  strong $extension
	 *
	 * @return mixed  Null of fail, String on success
	 */
	public static function getUpdateServer($extension)
	{
		$xml = self::getXML($extension);

		if (!$xml || !isset($xml->updateservers))
		{
			return;
		}

		$updateserver = trim($xml->updateservers->server);

		// Remove unwanted string added by Free / Pro versions
		$pp = strpos($updateserver, '@');
		if ($pp !== false)
		{
			$updateserver = substr($updateserver, 0, $pp);
		}

		return $updateserver;
	}

	/**
	 * Get the latest extension version from the remote update server
	 *
	 * @param  string $extension
	 *
	 * @return mixed	Null on failure, String on success
	 */
	public static function getLatestVersion($extension)
	{
		// Get the extension's update server URL
		if (!$updateserver = self::getUpdateServer($extension))
		{
			return;
		}

		// Call the Update Server and make sure the response is valid
		$response = \JHttpFactory::getHttp()->get($updateserver);

		if ($response->code != 200 || strpos($response->body, '<updates>') === false)
		{
			return;
		}

		$body = new \SimpleXMLElement($response->body);
		$version = (string) $body->update[0]->version;

		return $version;
	}

	/**
	 * Check if we have the Pro version of the extension
	 *
	 * @param  string $element
	 *
	 * @return bool
	 */
	public static function isPro($element)
	{
		if (!$path = self::getPath($element))
		{
			return false;
		}

		$versionFile = $path . '/version.php';

		// If version file does not exist we assume a PRO version
		if (!\JFile::exists($versionFile))
		{
			return true;
		}

		// Silently load the version file
		@include_once $versionFile;

		// If the NR_PRO variable is not set we're probably under development mode. Assume a Pro version.
		if (!isset($NR_PRO))
		{
			return true;
		}

		return (bool) $NR_PRO;
	}
}