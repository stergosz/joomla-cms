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

namespace GSD;

defined('_JEXEC') or die('Restricted Access');

use GSD\Json;
use NRFramework\Cache;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * 
 *  Google Structured Data Helper Class
 */
class Helper
{
	/**
	 *  Plugin Params
	 *
	 *  @var  JRegistry
	 */
	public static $params;

	/**
	 *  Log Messages
	 *
	 *  @var  array
	 */
	public static $log;

	/**
	 *  Get all available Content Types
	 *
	 *  @return  array
	 */
	public static function getContentTypes()
	{
        $json = new Json();
        return $json->getContentTypes();
	}

	/**
	 *  Returns an array with crumbs
	 *
	 *  @return  array
	 */
	public static function getCrumbs($hometext, $addhome = true)
	{
		$pathway = \JFactory::getApplication()->getPathway();
		$items   = $pathway->getPathWay();
		$menu    = \JFactory::getApplication()->getMenu();
		$lang    = \JFactory::getLanguage();
		$count   = count($items);

		if (!$count)
		{
			return false;
		}

		// We don't use $items here as it references JPathway properties directly
		$crumbs = [];

		for ($i = 0; $i < $count; $i++)
		{
			// Note: In some cases, the link in the last crumb (current page) is returned empty by Joomla. 
			// We don't want to skip over this crumb as it represents the crumb of the current page which needs to be included in the schema. 
			// Thus, we skip only null link properties, so the empty crumb is included in the list.
			if (is_null($items[$i]->link) || !$items[$i]->name)
			{
				continue;
			}

			$crumbName = stripslashes(htmlspecialchars(strip_tags($items[$i]->name), ENT_COMPAT, 'UTF-8'));

			// Remove [icon] shortcodes added by 3rd party plugins
			$crumbName = preg_replace('#\[icon\].*?\[\/icon\]#', '', $crumbName);

			$crumbs[$i] = (object) [
				'name' => trim($crumbName),
				'link' => self::route($items[$i]->link)
 			];
		}

		// Add Home item
		if ($addhome)
		{
			// Look for the home menu
			$home = \JLanguageMultilang::isEnabled() ? $menu->getDefault($lang->getTag()) : $menu->getDefault();

			$item       = new \stdClass;
			$item->name = htmlspecialchars($hometext);
			$item->link = self::route('index.php?Itemid=' . $home->id);

			array_unshift($crumbs, $item);
		}

		// Fix last item's missing URL to make Google Markup Tool happy
		end($crumbs);
		if (empty($crumbs->link))
		{
			$crumbs[key($crumbs)]->link = \JURI::current();
		}

		// Convert relative URLs to absolute URLs
		foreach ($crumbs as $key => &$crumb)
		{
			$crumb->link = self::absURL($crumb->link);
		}

		return $crumbs;
	}

	/**
	 *  Makes text safe for JSON outpout
	 *
	 *  @param   string   $text   The text 
	 *  @param   integer  $limit  Limit characters
	 *
	 *  @return  string
	 */
	public static function makeTextSafe($text, $allowed_tags = null, $limit = 0)
	{
		if (empty($text))
		{
			return;
		}

		// Strip HTML tags/comments and minify
		$text = strip_tags($text, $allowed_tags);

		// There are some plugins that parse their shortcodes while Joomla! is booting like on the onAfterRender event instead of the onContentPrepare event
		// which GSD can control. So, if one of these shortcodes is parsed in the generated structured data, the page is very likely to broke and the only
		// way to prevent that from happening is to remove these shortcodes using regex.
		$text = preg_replace([
			// System - Zen Shortcodes
			'/{(\/?)zen-(.*?)}/m', 									   
			// System - wbAMP 
			'/{wbamp-(show|hide) start}(.*?){wbamp-(hide|show) end}/s' 
		], '', $text);
		
		// Minify Text
		$text = self::minify($text);

		// Limit characters length
		if ($limit > 0)
		{
			$text = StringHelper::substr($text, 0, $limit);
		}

        return trim($text);
	}

	/**
	 *  Minify String
	 *
	 *  @param   string  $string  The string to be minified
	 *
	 *  @return  string           The minified string
	 */
	public static function minify($string)
	{
    	return preg_replace('/(\s)+/s', ' ', $string);
	}

	/**
	 *  Returns absolute URL
	 *
	 *  @param   string  $url  The URL
	 *
	 *  @return  string
	 */
	public static function absURL($url)
	{
		$url = \JURI::getInstance($url);

		// Return the original URL if we're manipulating an external URL
		if (in_array($url->getScheme(), ['https', 'http']))
		{
			return $url->toString();
		}

		$url = str_replace([\JURI::root(), \JURI::root(true)], '', $url->toString());
		$url = ltrim($url, '/');
		
		return \JURI::root() . $url;
	}

	/**
	 *  Returns URLs based on the Force SSL global configuration
	 *
	 *  @param   string   $route  The route for which we want a URL
	 *  @param   boolean  $xhtml  If we want the output to be in XHTML
	 *
	 *  @return  string           The absolute url
	 */
	public static function route($route, $xhtml = true)
	{
		$siteSSL = \JFactory::getConfig()->get('force_ssl');
		$sslFlag = 2;

		// the force_ssl value in the global configuration needs
		// to be 2 for the frontend to also be under HTTPS
		if (($siteSSL == 2) || (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'))
		{
			$sslFlag = 1;
		}
		
		return \JRoute::_($route, $xhtml, $sslFlag);
	}

	/**
	 *  Transform a UTC date to ISO8601 format. 
	 *  
	 *  The timezone part describes the hours that already have been counted towards the time.
	 * 
	 *  Let's say we have an article published on 2012-04-10 19:31:00 local date Athens +02:00.
	 * 	The date should be displayed in the structured data as 2012-04-10T19:31:00+02:00
	 * 
	 *  References:
	 *  https://developers.google.com/search/docs/data-types/event#time-date-incorrect
	 *  https://github.com/Yoast/wordpress-seo/issues/12765
	 *  https://wordpress.org/support/topic/structured-data-wrong-time
	 *
	 *  @param   JDate  $date  
	 *
	 *  @return  JDate
	 */
	public static function date($date, $modify_offset = false)
	{
		$date = is_string($date) ? trim($date) : $date;

		if (empty($date) || is_null($date) || $date == '0000-00-00 00:00:00')
		{
			return $date;
		}

		// Skip if date is already in ISO8601 format
		if (strpos($date, 'T') !== false)
		{
			return $date;
		}

		try {
			$tz = new \DateTimeZone(\JFactory::getApplication()->getCfg('offset', 'UTC'));

			if ($modify_offset)
			{
				$date_ = new \JDate($date);
				$date_->setTimezone($tz);
			} else 
			{
				$date_ = new \JDate($date, $tz);
			}
	
			return $date_->toISO8601(true);

		} catch (\Exception $e) {
			return $date;
		}
	}

	/**
	 * Formats price according to https://schema.org/price
	 * 
	 * @param  mixed $price    Use '.' rather than ',' to indicate a decimal point.
	 *
	 * @return float 2 Decimal point float price
	 */
	public static function formatPrice($price)
	{
		// Prevent "A non well formed numeric value encountered" error.
		$price = str_replace(',', '.', $price);

		return number_format((float) $price, 2, '.', '');
	}

	/**
	 *  Determine if the user is viewing the front page
	 *
	 *  @return  boolean
	 */
	public static function isFrontPage()
	{
		$menu = \JFactory::getApplication()->getMenu();
		$lang = \JFactory::getLanguage()->getTag();
		return ($menu->getActive() == $menu->getDefault($lang));
	}

    /**
     *  Logs messages to log file
     *
     *  @param   object  $type  The log type
     *
     *  @return  void
     */
    public static function log($msg)
    {
		self::$log[] = $msg;
    }

    /**
     *  Renders Backend's Sidebar
     *
     *  @return  string  The HTML output
     */
    public static function renderSideBar()
    {
		

		$data = array(
			'view'  => \JFactory::getApplication()->input->get('view', 'gsd'),
			'items' => array(
				array(
					'label' => 'NR_DASHBOARD',
					'url'   => 'index.php?option=com_gsd',
					'icon'  => 'dashboard',
					'view'  => 'gsd'
				),
				array(
				 	'label' => 'GSD_ITEMS',
					'url'   => 'index.php?option=com_gsd&view=items',
					'icon'  => 'list',
					'view'  => 'items,item'
				),
				array(
				 	'label' => 'GSD_CONFIG',
					'url'   => 'index.php?option=com_gsd&view=config&layout=edit',
					'icon'  => 'options',
					'view'  => 'config'
				),
				array(
				 	'label'  => 'NR_DOCUMENTATION',
					'url'    => 'https://www.tassos.gr/joomla-extensions/google-structured-data-markup/docs/',
					'icon'   => 'file-2',
					'target' => 'blank'
				),
				array(
				 	'label'  => 'NR_SUPPORT',
					'url'    => 'http://www.tassos.gr/contact',
					'icon'   => 'help',
					'target' => 'blank'
				)
			)
		);

        return \JLayoutHelper::render('sidebar', $data);
    }

    /**
     *  Get website name
     *
     *  @return  string  Site URL
     */
    public static function getSiteName()
    {
        return self::getParams()->get('sitename_name', \JFactory::getConfig()->get('sitename'));
    }
    
    /**
     *  Returns the Site Logo URL
     *
     *  @return  string
     */
    public static function getSiteLogo()
    {
        if (!$logo = self::getParams()->get('logo_file', null))
        {
            return;
        }

        return \JURI::root() . $logo;
    }

	/**
	 *  Get website URL
	 *
	 *  @return  string  Site URL
	 */
	public static function getSiteURL()
	{
		return self::getParams()->get('sitename_url', \JURI::root());
	}

    /**
     *  Get Plugin Parameters
     *
     *  @return  JRegistry
     */
    public static function getParams()
    {
    	if (self::$params)
		{
			return self::$params;
		}

		\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_gsd/tables');
		
		$table = \JTable::getInstance('Config', 'GSDTable');
		$table->load('config');

		return (self::$params = new Registry($table->params));
    }

    /**
     *  Returns permissions
     *
     *  @return  object
     */
    public static function getActions()
    {
        $user = \JFactory::getUser();
        $result = new \JObject;
        $assetName = 'com_gsd';

        $actions = array(
            'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.state', 'core.delete'
        );

        foreach ($actions as $action)
        {
            $result->set($action, $user->authorise($action, $assetName));
        }

        return $result;
    }

    /**
     *  Loads all GSD plugins and triggers an event
     *
     *  @return  mixed
     */
    public static function event($name, $arguments = array())
    {
		\JPluginHelper::importPlugin('gsd');

    	return \JFactory::getApplication()->triggerEvent($name, $arguments);
    }

    /**
     *  Get list with all available plugins
     *
     *  @return  array
     */
    public static function getPlugins()
    {
		return array_filter(self::event('onGSDGetType'));
    }

    /**
     *  Get the 1st found plugin's alias
     *
     *  @return  string  The plugin's alias
     */
    public static function getDefaultPlugin()
    {
		$plugins = self::getPlugins();
        return $plugins[0]['alias'];
    }

	/**
     *  Returns active component alias
     *
     *  @return  mixed String on success, false on failure
     */
    public static function getComponentAlias()
    {
        if (!$option = \JFactory::getApplication()->input->get('option'))
        {
            return;
        }

        $optionParts = explode('_', $option);
        
        return isset($optionParts[1]) ? $optionParts[1] : false;
    }

	/**
     *  Checks whether the plugin is a Pro version
     *
     *  @return  boolean  
     */
    public static function isPro()
    {
    	return \NRFramework\Functions::extensionHasProInstalled('plg_system_gsd');
	}
	
    /**
     *  Split string into array on each new line character
     *
     *  @param   string  $str  The string to split
     *
     *  @return  array
     */
    public static function makeArrayFromNewLine($str)
    {
        $array = preg_split("/\\r\\n|\\r|\\n/", $str);

        if (!$array)
        {
            return $str;
        }

        return array_values(array_filter($array));
	}
	
	/**
	 * Convert an array to UTF8 encoding
	 *
	 * @param  array	The array to convert
	 *
	 * @return array
	 */
	public static function arrayToUTF8($array)
	{
		if (!is_array($array) || !function_exists('mb_convert_encoding'))
		{
			return $array;
		}

		array_walk_recursive($array, function(&$value, $key)
		{
			if (is_string($value))
			{
				$value = mb_convert_encoding($value, 'utf8');
			}
		});

		return $array;
	}

	/**
	 * Convert a string to UTF8 encoding
	 * 
	 * @param  string
	 * 
	 * @return string
	 */
	public static function stringToUTF8($string)
	{
		if (!function_exists('mb_convert_encoding'))
		{
			return $string;
		}

		return mb_convert_encoding($string, 'html-entities', 'utf8');
	}

	/**
	 * Get first image src attribute from a string
	 *
	 * @param  string $text  The text to search for an image
	 *
	 * @return mixed  string on success, null on failure
	 */
	public static function getFirstImageFromString($text)
	{
		if (empty($text))
		{
			return;
		}

		preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $text, $image);
		return !empty($image) && isset($image['src']) ? $image['src'] : '';
	}

	/**
	 * Find the FAQ content using XPath based on the provided selector
	 * 
	 * @param  string  $content   The content to search
	 * @param  string  $selector  The selector used for the search
	 * 
	 * @return array
	 */
	public static function findFAQContent($content, $selector)
	{
		// If empty selector, return
		if (empty($selector))
		{
			return;
		}

		// If empty content, return
		if (empty($content))
		{
			return;
		}

		$data = [];

		if (!class_exists('DOMDocument') || !class_exists('DOMXPath'))
		{
			return;
		}

		libxml_use_internal_errors(true);
		$dom = new \DOMDocument;
		$dom->loadHTML(self::stringToUTF8($content));
		$finder = new \DOMXPath($dom);
		
		// Check if we are writing our own XPath query
		// example: =//h1[contains(@class, "faq-question")]
		if (substr($selector, 0, 1) == '=')
		{
			$xpath = substr($selector, 1);
		}
		else
		{
			// Create the XPath via the provided selector
			$xpath = self::QuerySelectorToXPath($selector);
		}
		
		$nodes = $finder->query($xpath);
		if ($nodes && $nodes->length)
		{
			foreach ($nodes as $node)
			{
				// Remove attributes from node
				$html = self::removeAttributesFromNode($node);

				$data[] = array(
					'html' => $html,
					'value' => $node->nodeValue
				);
			}
		}
		return $data;
	}

	/**
	 * Loop through all elements in the node that have an attribute
	 * and remove them
	 * 
	 * @param   DOMElement  $domNode
	 * 
	 * @return  string
	 */
	public static function removeAttributesFromNode($domNode)
	{
		// Loop through all elements that have an attribute and remove it
		$dom = new \DOMDocument;
		$content = $domNode->ownerDocument->saveHTML($domNode);
		$dom->loadHTML(self::stringToUTF8($content));
		$xpath = new \DOMXPath($dom);
		$nodes = $xpath->query('//@*');
		foreach ($nodes as $node)
		{
			// Skip the href attribute which is allowed
			if ($node->nodeName == 'href')
			{
				// Fix relative URLs.
				$url = Helper::absURL($node->nodeValue);
				$node->parentNode->setAttribute('href', $url);
				continue;
			}

			$node->parentNode->removeAttribute($node->nodeName);
		}
		
		// return inner child only
		$html = '';
		foreach($dom->getElementsByTagName('body')->item(0)->firstChild->childNodes as $node) {
			$html .= $dom->saveHTML($node);
		}

		return $html;
	}

	/**
	 * Transforms the selector to a valid XPath query
	 * 
	 * @param   string  $selector
	 * 
	 * @return  string
	 */
	private static function QuerySelectorToXPath($selector)
	{
		// explode() the given selectors and create a XPath syntax
		$selectors = explode(" ", $selector);

		$xpath = '';

		foreach ($selectors as $selector)
		{
			// Check if the selector contains a class or ID
			$explode_class = explode(".", $selector);
			$explode_id = explode("#", $selector);

			// Selector contains a class
			if (count($explode_class) > 1)
			{
				$prefix = (isset($explode_class[0]) && !empty($explode_class[0])) ? $explode_class[0] : '*';
				$xpath .= '//' . $prefix . '[';

				// When we use a selector such as div.class1.class2 or .class1.class2
				// we need to use all classes in the xpath and no the first one only
				unset($explode_class[0]);
				$total = count($explode_class);
				$counter = 1;
				$xpath_and_prefix = 'and';
				foreach ($explode_class as $class)
				{
					$xpath .= ($counter != 1) ? $xpath_and_prefix : '';
					$xpath .=  ' contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ") ';
					$counter++;
				}
				$xpath .=  ']';
			}
			else if (count($explode_id) > 1) // Selector contains an ID
			{
				$prefix = (isset($explode_id[0]) && !empty($explode_id[0])) ? $explode_id[0] : '*';
				$xpath .= '//' . $prefix . '[@id="' . $explode_id[1] . '"]';
			}
			else // No class or ID given
			{
				$xpath .= '//' . $selector;
			}
		}

		return $xpath;
	}

	/**
	 * Returns the weekdays
	 * 
	 * @param  boolean  $capitalize	 Capitalizes the first letter of each day
	 * 
	 * @return array
	 */
	public static function getWeekdays($capitalize = false)
	{
		$days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

		if ($capitalize)
		{
			$days = array_map('ucfirst', $days);
		}
		
		return $days;
	}

	/**
	 * Return the current page's buffer including modules output.
	 * 
	 * We do not use shorter $app->getBody() method because since GSD is booting up during onBeforeCompileHead the getBody() is empty at that time.
	 *
	 * @return html
	 */
	public static function getBuffer()
	{
		$doc = \JFactory::getDocument();

		$output = $doc->getBuffer('component');

		// Note: The modules buffer is available on the onBeforeCompileHead event and later.
		if (!isset($doc->getBuffer()['modules']))
		{
			return $output;
		}

		foreach ($doc->getBuffer()['modules'] as $key => $value)
		{
			$output .= $doc->getBuffer('modules', $key);
		}

		return $output;
	}
}

?>