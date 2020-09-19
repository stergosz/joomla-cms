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

class NR_InfusionSoft extends NR_Wrapper
{
	/**
	 * Create a new instance
	 * @param string $key Your InfusionSoft API key
	 * @throws \Exception
	 */
	public function __construct($key)
	{
		parent::__construct();
		$this->setKey($key);
		$this->setEndpoint('https://api.infusionsoft.com/crm/rest/v1');
		$this->options->set('headers.Content-Type', 'application/x-www-form-urlencoded');
	}

	/**
	 * Setter method for the endpoint
	 * @param string $url The URL which is set in the account's developer settings
	 * @throws \Exception
	 */
	public function setEndpoint($url)
	{
		if (!empty($url))
		{
			$query          = http_build_query(array('access_token' => $this->key));
			$this->endpoint = $url . '?' . $query;
		}
		else
		{
			throw new \Exception("Invalid InfusionSoft URL `{$url}` supplied.");
		}
	}
	/**
	 * Encode the data and attach it to the request
	 * @param   array $data Assoc array of data to attach
	 */
	protected function attachRequestPayload($data)
	{
		$this->last_request['body'] = http_build_query($data);
	}

}
