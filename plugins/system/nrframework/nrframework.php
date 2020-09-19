<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.filesystem.file');

use Joomla\String\StringHelper;
use NRFramework\HTML;

// Initialize Novarain Library
require_once __DIR__ . '/autoload.php';

class plgSystemNRFramework extends JPlugin
{
	/**
	 *  Auto load plugin language 
	 *
	 *  @var  boolean
	 */
	protected $autoloadLanguage = true;
	
	/**
	 *  The Joomla Application object
	 *
	 *  @var  object
	 */
	protected $app;

 	/**
     *  Plugin constructor
     *
     *  @param  mixed   &$subject
     *  @param  array   $config
     */
    public function __construct(&$subject, $config = array())
    {
        // Declare extension logger
        JLog::addLogger(
            array('text_file' => 'plg_system_nrframework.php'),
            JLog::ALL, 
            array('nrframework')
        );

        // execute parent constructor
        parent::__construct($subject, $config);
    }

    /**
     *  Update UpdateSites after the user has entered a Download Key
     *
     *  @param   string  $context  The component context
     *  @param   string  $table    
     *  @param   boolean $isNew    
     *
     *  @return  void
     */
	public function onExtensionAfterSave($context, $table, $isNew)
	{
		// Run only on Novarain Framework edit form
		if (
			$this->app->isClient('site')
			|| $context != 'com_plugins.plugin'
			|| $table->element != 'nrframework'
			|| !isset($table->params)
		)
		{
			return;
		}

		// Set Download Key & fix Update Sites
		$upds = new NRFramework\Updatesites();
		$upds->update();
	}

	/**
	 *  Handling of PRO for extensions
	 *  Throws a notice message if the Download Key is missing before downloading the package
	 *
	 *  @param   string  &$url      Update Site URL
	 *  @param   array   &$headers  
	 */
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		$uri  = JUri::getInstance($url);
		$host = $uri->getHost();

		// This is not a Tassos.gr extension
		if (strpos($host, 'tassos.gr') === false)
		{
			return true;
		}

		// If it's a Free version. No need to check for the Download Key. 
		if (strpos($url, 'free') !== false)
		{
			return true;
		}

		// This is a Pro version. Let's validate the Download Key.
		$download_id = $this->params->get('key', '');
		
		// Append it to the URL
		if (!empty($download_id))
		{
			$uri->setVar('dlid', $download_id);
			$url = $uri->toString();
			return true;
		} 
	
		$this->app->enqueueMessage('To be able to update the Pro version of this extension via the Joomla updater, you will need enter your Download Key in the settings of the <a href="' . JURI::base() . 'index.php?option=com_plugins&view=plugins&filter_search=novarain">Novarain Framework System Plugin</a>');
		return true;
	}

    /**
     *  Listens to AJAX requests on ?option=com_ajax&format=raw&plugin=nrframework
     *
     *  @return void
     */
    public function onAjaxNRFramework()
    {
		JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));

		// Only in backend
        if (!$this->app->isClient('administrator'))
        {
            return;
        }

        // Check if we have a valid task
		$task = $this->app->input->get('task', null);

		// Check if we have a valid method task
		$taskMethod = 'ajaxTask' . $task;

		if (!method_exists($this, $taskMethod))
		{
			die('Task not found');
		}

		$this->$taskMethod();
	}
	
	private function ajaxTaskInclude()
	{
		$input = $this->app->input;

		$file  = $input->get('file');
		$path  = JPATH_SITE . '/' . $input->get('path', '', 'RAW');
		$class = $input->get('class');

		$file_to_include = $path . $file . '.php';

		if (!JFile::exists($file_to_include))
		{
			die('FILE_ERROR');
		}

		@include_once $file_to_include;

		if (!class_exists($class))
		{
			die('CLASS_ERROR');
		}

		if (!method_exists($class, 'onAJAX'))
		{
			die('METHOD_ERROR');
		}

		(new $class())->onAJAX($input->getArray());
	}

	private function ajaxTaskConditionBuilder()
	{
		$input = $this->app->input;

		$subtask = $input->get('subtask', null);

		switch ($subtask)
		{
			case 'add':
				$controlGroup = $input->get('controlgroup', null, 'RAW');
				$groupKey     = $input->getInt('groupKey');
				$conditionKey = $input->getInt('conditionKey');
				$conditions_list = $input->get('conditionsList', null, 'RAW');

				echo NRFramework\ConditionBuilder::add($controlGroup, $groupKey, $conditionKey, null, $conditions_list);
				break;
			case 'options':
				$controlGroup = $input->get('controlgroup', null, 'RAW');
				$name = $input->get('name');

				echo NRFramework\ConditionBuilder::renderOptions($name, $controlGroup);
				break;
		}
	}

	/**
	 * Check if the extension has an update and display a notification
	 *
	 * @return string
	 */
	private function ajaxTaskUpdateNotification()
	{
		echo HTML::updateNotification($this->app->input->get('element'));
	}
}
