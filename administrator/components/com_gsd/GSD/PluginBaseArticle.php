<?php

/**
 * @package         Google Structured Data
 * @version         4.8.0-RC1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2019 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

namespace GSD;

defined('_JEXEC') or die('Restricted access');

/**
 *  Google Structured Data Product Plugin Base
 */
class PluginBaseArticle extends \GSD\PluginBase
{
	
	/**
	 * Listening to the onAfterRender Joomla event
	 *
	 * @return void
	 */
	public function onAfterRender()
	{
        // Make sure we are on the right context
        if ($this->app->isClient('administrator') || !$this->passContext() || !$this->params->get('remove_default_schema', true))
		{
            return;
		}
		
		// Remove the most common article-based schemas
		$schemas = [
			'BlogPosting',
			'Article',
			'NewsArticle',
			'Blog',
			'AggregateRating',
			'Person'
		];

		\GSD\SchemaCleaner::remove($schemas, false);
	}
	
}

?>