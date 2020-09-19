<?php 

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace NRFramework;

defined('_JEXEC') or die;

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

use Joomla\Registry\Registry;

class Functions
{
    /**
     * Return the real site base URL by ignoring the live_site configuration option.
     *
     * @param  bool $ignore_admin   If enabled and we are browsing administrator, we will get the front-end site root URL.
     *
     * @return string
     */
    public static function getRootURL($ignore_admin = true) 
    {
        $factory = \JFactory::getConfig();

        // Store the original live_site value
        $live_site_original = $factory->get('live_site', '');

        // If we live_site is not set, do not proceed further. Return the default website base URL.
        if (empty($live_site_original))
        {
            return $ignore_admin ? \JURI::root() : \JURI::base();
        }

        // Remove the live site
        $factory->set('live_site', '');

        // Remove all cached JURI instances
        \JURI::reset();

        // Get a new URL. The live_site option should be ignored.
        $base_url = $ignore_admin ? \JURI::root() : \JURI::base();

        // Set back the original live_site
        $factory->set('live_site', $live_site_original);
        \JURI::reset();

        return $base_url;
    }

    /**
     * Insert an associative array into a specific position in an array
     *
     * @param $original array 	The original array to add to
     * @param $new array 		The new array of values to insert into the original
     * @param $offset int 		The position in the array ( 0 index ) where the new array should go
     *
     * @return array 		The new combined array
     */
    public static function array_splice_assoc($original,$new,$offset)
    {
        return array_slice($original, 0, $offset, true) + $new + array_slice($original, $offset, NULL, true);  
    }

    public static function renderField($fieldname)
    {
        $fieldname = strtolower($fieldname);

		require_once JPATH_PLUGINS . '/system/nrframework/fields/' . $fieldname . '.php';

        $classname = '\JFormField' . $fieldname;

		$field = new $classname();

        $element = new \SimpleXMLElement('
            <field name="' . $classname . '" type="' . $classname . '"

			/>');
			
        $field->setup($element, null);
        
        return $field->__get('input');
    }

	/**
	 *  Checks if an array of values (needle) exists in a text (haystack)
	 *
	 *  @param   array   $needle            The searched array of values.
	 *  @param   string  $haystack          The text
	 *  @param   bool    $case_insensitive  Indicates whether the letter case plays any role
	 *
	 *  @return  bool
	 */
	public static function strpos_arr($needles, $haystack, $case_insensitive = false)
	{
        $needles = !is_array($needles) ? (array) $needles : $needles;
        $haystack = $case_insensitive ? strtolower($haystack) : $haystack;

		foreach ($needles as $needle)
		{
            $needle = $case_insensitive ? strtolower($needle) : $needle;

			if (strpos($haystack, $needle) !== false) 
			{
				// stop on first true result
				return true; 
			}
		}

		return false;
	}

    /**
     *  Log message to framework's log file
     *
     *  @param   mixed  $data    Log message
     *
     *  @return  void
     */
    public static function log($data)
    {
        $data = (is_object($data) || is_array($data)) ? print_r($data, true) : $data;

        try {
            \JLog::add($data, \JLog::DEBUG, 'nrframework');
        } catch (\Throwable $th) {
        }
    }

    /**
     *  Return's a URL with the Google Analytics Campaign Parameters appended to the end
     *
     *  @param   string  $url       The URL
     *  @param   string  $medium    Campaign Medium
     *  @param   string  $campaign  Campaign Name
     *
     *  @return  string
     */
    public static function getUTMURL($url, $medium = "upgradebutton", $campaign = "freeversion")
    {
        if (!$url)
        {
            return;
        }

        $utm  = 'utm_source=Joomla&utm_medium=' . $medium . '&utm_campaign=' . $campaign;
        $char = strpos($url, "?") === false ? "?" : "&";

        return $url . $char . $utm;
    }

    /**
     *  Returns user's Download Key
     *
     *  @return  string
     */
    public static function getDownloadKey()
    {
        $class = new Updatesites();
        return $class->getDownloadKey();
    }

    /**
     *  Adds a script or a stylesheet to the document
     *
     *  @param  Mixed    $files           The files to be to added to the document
     *  @param  boolean  $appendVersion   Adds file versioning based on extension's version
     *
     *  @return void
     */
    public static function addMedia($files, $extension = "plg_system_nrframework", $appendVersion = true)
    {
        $doc       = \JFactory::getDocument();
        $version   = self::getExtensionVersion($extension);
        $mediaPath = \JURI::root(true) . "/media/" . $extension;

        if (!is_array($files))
        {
            $files = array($files);
        }

        foreach ($files as $key => $file)
        {
            $fileExt  = \JFile::getExt($file);
            $filename = $mediaPath . "/" . $fileExt . "/" . $file;
            $filename = ($appendVersion) ? $filename . "?v=" . $version : $filename;

            if ($fileExt == "js")
            {
                $doc->addScript($filename);
            }

            if ($fileExt == "css")
            {
                $doc->addStylesheet($filename);
            }
        }
    }

    /**
     *  Get the Framework version
     *
     *  @return  string  The framework version
     */
    public static function getVersion()
    {
        return self::getExtensionVersion("plg_system_nrframework");
    }

    /**
     *  Checks if document is a feed document (xml, rss, atom)
     *
     *  @return  boolean
     */
    public static function isFeed()
    {
        return (
            \JFactory::getDocument()->getType() == 'feed'
            || \JFactory::getDocument()->getType() == 'xml'
            || \JFactory::getApplication()->input->getWord('format') == 'feed'
            || \JFactory::getApplication()->input->getWord('type') == 'rss'
            || \JFactory::getApplication()->input->getWord('type') == 'atom'
        );
    }

    public static function loadLanguage($extension = 'plg_system_nrframework', $basePath = '')
    {
        if ($basePath && \JFactory::getLanguage()->load($extension, $basePath))
        {
            return true;
        }

        $basePath = self::getExtensionPath($extension, $basePath, 'language');

        return \JFactory::getLanguage()->load($extension, $basePath);
    }

    /**
     *  Returns extension ID
     *
     *  @param   string  $extension  Extension name
     *
     *  @return  integer
     * 
     *  @deprecated Use \NRFramework\Extension::getID instead
     */
    public static function getExtensionID($extension, $folder = null)
    {
        $type = is_null($folder) ? 'component' : 'plugin';
        return \NRFramework\Extension::getID($extension, $type, $folder);
    }

    /**
     *  Checks if extension is installed
     *
     *  @param   string  $extension  The extension element name
     *  @param   string  $type       The extension's type 
     *  @param   string  $folder     Plugin folder     * 
     *
     *  @return  boolean             Returns true if extension is installed
     * 
     *  @deprecated Use \NRFramework\Extension::isInstalled instead
     */
    public static function extensionInstalled($extension, $type = 'component', $folder = 'system')
    {
        return \NRFramework\Extension::isInstalled($extension, $type, $folder);
    }

    /**
     *  Returns the version number from the extension's xml file
     *
     *  @param   string  $extension  The extension element name
     *
     *  @return  string              Extension's version number
     */
    public static function getExtensionVersion($extension, $type = false)
    {
        $hash  = MD5($extension . "_" . ($type ? "1" : "0"));
        $cache = Cache::read($hash);

        if ($cache)
        {
            return $cache;
        }

        $xml = self::getExtensionXMLFile($extension);

        if (!$xml)
        {
            return false;
        }

        $xml = \JInstaller::parseXMLInstallFile($xml);

        if (!$xml || !isset($xml['version']))
        {
            return '';
        }

        $version = $xml['version'];

        if ($type)
        {
            $extType = (self::extensionHasProInstalled($extension)) ? "Pro" : "Free";
            $version = $xml["version"] . " " . $extType;
        }

        return Cache::set($hash, $version);
    }

    public static function getExtensionXMLFile($extension, $basePath = JPATH_ADMINISTRATOR)
    {
        $alias = explode("_", $extension);
        $alias = end($alias);

        $filename = (strpos($extension, 'mod_') === 0) ? "mod_" . $alias : $alias;
        $file = self::getExtensionPath($extension, $basePath) . "/" . $filename . ".xml";

        if (\JFile::exists($file))
        {
            return $file;
        }
        
        return false;
    }

    public static function extensionHasProInstalled($extension)
    {
        static $result;

        if ($result)
        {
            return $result;
        }

        // Path to extension's version file
        $versionFile = self::getExtensionPath($extension) . "/version.php";
        $NR_PRO = true;

        // If version file does not exist we assume we have a PRO version installed
        if (file_exists($versionFile))
        {
            require_once($versionFile);
        }

        return ($result = (bool) $NR_PRO);
    }

    public static function getExtensionPath($extension = 'plg_system_nrframework', $basePath = JPATH_ADMINISTRATOR, $check_folder = '')
    {
        if (!in_array($basePath, array('', JPATH_ADMINISTRATOR, JPATH_SITE)))
        {
            return $basePath;
        }

        switch (true)
        {
            case (strpos($extension, 'com_') === 0):
                $path = 'components/' . $extension;
                break;

            case (strpos($extension, 'mod_') === 0):
                $path = 'modules/' . $extension;
                break;

            case (strpos($extension, 'plg_system_') === 0):
                $path = 'plugins/system/' . substr($extension, strlen('plg_system_'));
                break;

            case (strpos($extension, 'plg_editors-xtd_') === 0):
                $path = 'plugins/editors-xtd/' . substr($extension, strlen('plg_editors-xtd_'));
                break;
        }

        $check_folder = $check_folder ? '/' . $check_folder : '';

        if (is_dir($basePath . '/' . $path . $check_folder))
        {
            return $basePath . '/' . $path;
        }

        if (is_dir(JPATH_ADMINISTRATOR . '/' . $path . $check_folder))
        {
            return JPATH_ADMINISTRATOR . '/' . $path;
        }

        if (is_dir(JPATH_SITE . '/' . $path . $check_folder))
        {
            return JPATH_SITE . '/' . $path;
        }

        return $basePath;
    }

    public static function loadModule($id, $moduleStyle = null)
    {  
        // Return if no module id passed
        if (!$id) 
        {
            return;
        }

        // Fetch module from db
        $db = \JFactory::getDBO();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__modules')
            ->where('id='.$db->q($id));

        $db->setQuery($query);

        // Return if no modules found
        if (!$module = $db->loadObject()) 
        {
            return;
        }

        // Success! Return module's html
        return \JModuleHelper::renderModule($module, $moduleStyle);
    }

    public static function fixDate(&$date)
    {
        if (!$date)
        {
            $date = null;

            return;
        }

        $date = trim($date);
        
        // Check if date has correct syntax: 00-00-00 00:00:00
        if (preg_match('#^[0-9]+-[0-9]+-[0-9]+( [0-9][0-9]:[0-9][0-9]:[0-9][0-9])$#', $date))
        {
            return;
        }
        
        // Check if date has syntax: 00-00-00 00:00
        // If so, add :00 (seconds)
        if (preg_match('#^[0-9]+-[0-9]+-[0-9]+ [0-9][0-9]:[0-9][0-9]$#', $date))
        {
            $date .= ':00';

            return;
        }

        // Check if date has a prepending date syntax: 00-00-00 ...
        // If so, add 00:00:00 (hours:mins;secs)
        if (preg_match('#^([0-9]+-[0-9]+-[0-9]+)#', $date, $match))
        {
            $date = $match[1] . ' 00:00:00';
            
            return;
        }

        // Date format is not correct, so return null
        $date = null;
    }

    public static function fixDateOffset(&$date)
    {
        if ($date <= 0)
        {
            $date = 0;

            return;
        }

        $date = \JFactory::getDate($date, \JFactory::getUser()->getParam('timezone', \JFactory::getConfig()->get('offset')));
        $date->setTimezone(new \DateTimeZone('UTC'));

        $date = $date->format('Y-m-d H:i:s', true, false);
    }

    // Text
    public static function clean($string) 
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

    public static function dateTimeNow() 
    {
        return \JFactory::getDate()->format("Y-m-d H:i:s");
    }

    /**
     *  Get framework plugin's parameters
     *
     *  @return  JRegistry   The plugin parameters
     */
    public static function params()
    {
        $hash = md5('frameworkParams');

        if (Cache::has($hash))
        {
            return Cache::read($hash);
        }

        $db = \JFactory::getDBO();

        $result = $db->setQuery(
            $db->getQuery(true)
            ->select('params')
            ->from('#__extensions')
            ->where('element = ' . $db->quote('nrframework'))
        )->loadResult();

        return Cache::set($hash, new Registry($result));
    }
}

?>