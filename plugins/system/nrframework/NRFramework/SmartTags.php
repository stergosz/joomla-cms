<?php 

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace NRFramework;

defined('_JEXEC') or die('Restricted access');

use \NRFramework\WebClient;
use Joomla\Registry\Registry;

/**
 *   SmartTags replaces placeholder variables in a string
 */
class SmartTags
{
	/**
	 * Factory Class
	 *
	 * @var object
	 */
	protected $factory;

	/**
	 * Joomla Application object
	 *
	 * @var object
	 */
	protected $app;

	/**
	 *  Tags Array
	 *
	 *  @var  array
	 */
	protected $tags = [];

	/**
	 * Class options
	 *
	 * @var array
	 */
	protected $options;

	/**
	 *  Tag placeholder
	 *
	 *  @var  string
	 */
	protected $placeholder = '{}';

	/**
	 * List of all tag prefixes
	 *
	 * @var array
	 */
	private $prefixes = [];

	/**
	 *  Class constructor
	 */
	public function __construct($options = array(), $factory = null)
	{
		$this->options = new Registry($options);

		// Set Factory
        if (!$factory)
        {
            $factory = new \NRFramework\Factory();
        }

		$this->factory = $factory;
		$this->app = $factory->getApplication();
	}

	public function addDefaultTags()
	{
		if ($this->options->get('site_tags', true))
		{
			$this->addSiteTags();
		}

		if ($this->options->get('page_tags', true))
		{
			$this->addPageTags();
		}

		if ($this->options->get('date_tags', true))
		{
			$this->addDateTags();
		}

		if ($this->options->get('querystring_tags', true))
		{
			$this->addQueryStringTags();
		}

		if ($this->options->get('user_tags', true))
		{
			$this->addUserTags();
		}

		if ($this->options->get('technology_tags', true))
		{
			$this->addTechnologyTags();
		}

		if ($this->options->get('other_tags', true))
		{
			$this->addOtherTags();
		}
	}

	/**
	 * Add site-based Tags
	 *
	 * @return void
	 */
	protected function addSiteTags()
	{
		$url = $this->factory->getURI();

		$tags = [
			'name'  => $this->app->get('sitename'),
			'email' => $this->app->get('mailfrom'),
			'url'   => $url::root(),
		];

		$this->add($tags, 'site.');
	}

	/**
	 * Add technology-based tags
	 *
	 * @return void
	 */
	protected function addTechnologyTags()
	{
		$tags = [
			'device'    => WebClient::getDeviceType(),
			'os'		=> WebClient::getOS(),
			'browser'   => WebClient::getBrowser()['name'],
			'useragent' => WebClient::getClient()->userAgent
		];

		$this->add($tags, 'client.');
	}

	/**
	 * Add user-based tags
	 *
	 * @return void
	 */
	protected function addUserTags()
	{
		if (!$user = $this->factory->getUser($this->options->get('user', null)))
		{
			return;
		}

		// Proper capitalize name
		$name = ucwords(strtolower($user->name));
		
		// Set First and Last name
    	$nameParts = explode(' ', $name, 2);
    	$firstname = trim($nameParts[0]);
    	$lastname  = isset($nameParts[1]) ? trim($nameParts[1]) : $nameParts[0];

		$tags = [
			'id'        => $user->id,
			'name'      => $name,
			'firstname' => $firstname,
			'lastname'  => $lastname,
			'login'     => $user->username,
			'email'     => $user->email,
			'registerdate' => \JHtml::_('date', $user->registerDate, \JText::_('DATE_FORMAT_LC5')),
			'groups'    => implode(',', $user->groups),
		];

		$this->add($tags, 'user.');
	}

	/**
	 * Add Query String Tags to the collection
	 *
	 * @return void
	 */
	protected function addQueryStringTags()
	{
		$query = $this->factory->getURI()->getQuery(true);
		
		// Sanitize all query parameters by removing HTML.
		if (!empty($query))
		{
			$filter = \JFilterInput::getInstance();

			foreach ($query as $key => &$param)
			{
				$param = $filter->clean($param);
			}
		}
		
		// Add an empty query parameter in order to force the cleaning of unreplaced tags.
		if (empty($query))
		{
			$query = ['' => ''];
		}

		$this->add($query, 'querystring.');
	}

	/**
	 * Add Date-based Tags to the collection
	 *
	 * @return void
	 */
	protected function addDateTags()
	{
		$tz   = new \DateTimeZone($this->factory->getApplication()->getCfg('offset', 'GMT'));
		$date = $this->factory->getDate()->setTimezone($tz);

		$tags = [
			'time' => $date->format('H:i', true),
			'date' => $date->format('Y-m-d H:i:s', true),
			'day' => $date->format('j'),
			'month' => $date->format('n'),
			'year' => $date->format('Y')
		];

		$this->add($tags);
	}

	/**
	 * Include Page-related Tags to the collection
	 *
	 * @return void
	 */
	protected function addPageTags()
	{
		$doc = $this->factory->getDocument();

		$tags = [
			'title'     => $doc->getTitle(),
			'desc'      => $doc->getMetaData('description'),
			'keywords'  => $doc->getMetaData('keywords'),
			'lang'      => $doc->getLanguage(),
			'langurl'   => explode('-',  $doc->getLanguage())[0],
			'generator' => $doc->getGenerator()
		];

		if ($menu = $this->app->getMenu()->getActive())
		{
			$tags = array_merge($tags, [
				'browsertitle' => $menu->getParams()->get('page_title')
			]);
		}

		$this->add($tags, 'page.');
	}

	/**
	 * Add the rest of the tags
	 *
	 * @return void
	 */
	protected function addOtherTags()
	{
		$url = $this->factory->getURI();

		$tags = [
			'url'			=> $url->toString(),
			'url.encoded'	=> urlencode($url->toString()),
			'url.path'		=> $url::current(),
			'referrer'	    => $this->app->input->server->get('HTTP_REFERER', '', 'STRING'),
			'ip'			=> $this->app->input->server->get('REMOTE_ADDR', '', 'STRING'),
			'randomid'      => bin2hex(\JCrypt::genRandomBytes(8))
		];

		$this->add($tags);
	}

	/**
	 *  Returns list of all tags
	 *
	 *  @return  array
	 */
	public function get($prepare = true)
	{
		if ($prepare)
		{
			$this->prepare();
		}

		return $this->tags;
	}

	/**
	 *  Sets the tag placeholder
	 *  For example: {} or [] or {{}} or {[]}
	 *
	 *  @param  string  $placeholder  
	 */
	public function setPlaceholder($placeholder)
	{
		$this->placeholder = $placeholder;
		return $this;
	}

	/**
	 *  Returns placeholder in 2 pieces
	 *
	 *  @return  array
	 */
	private function getPlaceholder()
	{
		return str_split($this->placeholder, strlen($this->placeholder) / 2);
	}

	/**
	 *  Adds Custom Tags to the list
	 *
	 *  @param  Mixed   $tags    Tags list (Array or Object)
	 *  @param  String  $prefix  A string to prefix all keys
	 */
	public function add($tags, $prefix = null)
	{
		// Convert Object to array
		if (is_object($tags))
		{
			$tags = (array) $tags;
		}

		if (!is_array($tags) || !count($tags))
		{
			return;
		}

		// Add Prefix to keys
		if ($prefix)
		{
			// Add prefix to the collection which is used by the clean method to strip unreplaced tags.
			if (!in_array($prefix, $this->prefixes))
			{
				$this->prefixes[] = $prefix;
			}

			foreach ($tags as $key => $value)
			{
		        $newKey = $prefix . $key;
		        $tags[$newKey] = $value;
		        unset($tags[$key]);
			}
		}

		$this->tags = array_merge($this->tags, $tags);

		return $this;
	}

    /**
     *  Replace tags in object
     *
     *  @param   mixed  $obj  The data object for search for smarttags
     *
     *  @return  mixed
     */
    public function replace($subject)
    {
		$this->prepare();
		$subject = $this->replace_recursive($subject);
        return $subject;
	}
	
    /**
     *  Replace tags in object recursively
     *
     *  @param   mixed  $obj  The data object to search for smarttags
     *
     *  @return  mixed
     */
	private function replace_recursive($subject)
	{
		if (is_string($subject))
		{
			$result = str_ireplace(array_keys($this->tags), array_values($this->tags), $subject);
			return $this->clean($result);
		}

		if (is_array($subject) || is_object($subject))
		{
			foreach ($subject as $key => &$subject_item)
			{
				$subject_item = $this->replace_recursive($subject_item);
			}
		}

		return $subject;	
	}

	/**
	 * Remove all unreplaced tags from string
	 *
	 * @param  string $subject
	 *
	 * @return string The cleaned string
	 */
	private function clean($subject)
	{
		if (!is_string($subject))
		{
			return $subject;
		}

		if (empty($this->prefixes))
		{
			return $subject;
		}

		$prefixes = implode('|', $this->prefixes);

		return preg_replace('#{(' . $prefixes . ')(.*?)}#s', '', $subject);
	}
	
    /**
     *  Prepares tags by adding the placeholder to each key
     *
     *  @return  void
     */
    private function prepare()
    {
		$this->addDefaultTags();

    	$placeholder = $this->getPlaceholder();

    	foreach ($this->tags as $key => $variable)
    	{
    		// Check if tag is already prepared
    		if (substr($key, 0, 1) == $placeholder[0])
			{
				continue;
			}

			if (is_string($variable) || is_numeric($variable))
			{
				$this->tags[$placeholder[0] . $key . $placeholder[1]] = $variable;
			}

			unset($this->tags[$key]);
    	}
    }
}

?>