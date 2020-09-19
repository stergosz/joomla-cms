<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

// No direct access
defined('_JEXEC') or die;

require_once __DIR__ . '/wrapper.php';

class NR_OntraPort extends NR_Wrapper
{
	protected $appID;

	/**
	 * Create a new instance
	 * @param string $key Your API Key
	 * @param string $appID The App ID
	 * @throws \Exception
	 */
	public function __construct($key, $appID)
	{
		parent::__construct();
		$this->setKey($key, $appID);
		$this->setEndpoint('http://api.ontraport.com/1');
		$this->options->set('headers.Api-Appid', $this->appID);
		$this->options->set('headers.Api-Key', $this->key);
	}
}
