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

class NR_Mautic extends NR_Wrapper
{
	/**
	 * Create a new instance
	 * @param string $key Your Mautic Access Token
	 * @param string $url The URL in which Mautic is installed
	 * @throws \Exception
	 */
	public function __construct($key, $url)
	{
		parent::__construct();
		$this->setKey($key);
		$this->setEndpoint($url);
		$this->options->set('headers.Authorization', 'Bearer ' . $this->key);
	}
}
