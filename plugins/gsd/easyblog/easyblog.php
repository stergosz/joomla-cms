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

/**
 *  EasyBlog Google Structured Data Plugin

 *  It produces the Articles snippet using microdata
 *
 *  Developer References:
 *  components\com_easyblog\themes\wireframe\blogs\entry\default.php
 */
class plgGSDEasyBlog extends \GSD\PluginBaseArticle
{
    /**
     *  Validate context to decide whether the plugin should run or not.
     *  Disable when the article is on Preview Mode.
     *
     *  @return   bool
     */
    protected function passContext()
    {
    	if ($this->app->input->get('layout') == 'preview')
    	{
    		return;
    	}

    	return parent::passContext();
    }

	/**
	 *  Get the post's data
	 *
	 *  @return  array
	 */
	public function viewEntry()
	{
		// Abort if EasyBlog's main class is missing
		if (!class_exists('EB'))
		{
			return;
		}

		// Load EasyBlog config
		$config = EB::config();

		// Make sure we have a valid ID
		if (!$id = $this->getThingID())
		{
			return;
		}
		
		// Load EasyBlog post
		$post = EB::post($id);

		// Ratings
		$rating = $post->getRatings();

		// For a reason, we need to assign post content to a variable first in order to be able to access multiple times. 
		$text = $post->content;

		// Array data
		return [
			'id'           => $post->id,
			'headline'     => $post->title,
			'description'  => $post->getIntro() ?: $text,
			'introtext'    => $post->getIntro(),
			'fulltext'     => $text,
			'image'        => $post->getImage($config->get('cover_size_entry', 'large'), true, true, false),
			'imagetext'	   => \GSD\Helper::getFirstImageFromString($post->getIntro() . $text),
			'created_by'   => $post->created_by,
			'created'      => $post->created,
			'modified'     => $post->modified,
			'publish_up'   => $post->publish_up,
			'publish_down' => $post->publish_down,
			'ratingValue'  => number_format($rating->ratings / 2, 1), // EasyBlog's best rating value is 10
        	'reviewCount'  => $rating->total
		];
	}
}
