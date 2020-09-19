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
 *  RSBlog Google Structured Data Plugin
 *  
 *  Note: RSBlog component produces its own microdata.
 */
class plgGSDRSBlog extends GSD\PluginBase
{
	/**
	 *  Get post's data
	 *
	 *  @return  array
	 */
	public function viewPost()
	{
		// Load current item via model
		$model = JModelLegacy::getInstance('Post', 'RSBlogModel');
		$item  = $model->getItem();

        // Rating
		$rating = $this->getRating($item->id);

		// Array data
		return [
			'id'           => $item->id,
			'alias'        => $item->alias,
			'headline'     => $item->title,
			'description'  => empty($item->introtext) ? $item->fulltext : $item->introtext,
            'introtext'    => $item->introtext,
			'fulltext'     => $item->fulltext,
			'image'        => RSBlogHelper::postimage($item->image),
			'imagetext'	   => \GSD\Helper::getFirstImageFromString($item->introtext . $item->fulltext),
			'publish_up'   => $item->publish_up,
			'publish_down' => $item->publish_down,
			'created'      => $item->created_date  != '0000-00-00 00:00:00' ? $item->created_date : $item->publish_up,
			'modified'     => $item->modified_date != '0000-00-00 00:00:00' ? $item->modified_date : $item->publish_up,
			'created_by'   => $item->created_by,
			'ratingValue'  => $rating['ratingValue'],
        	'reviewCount'  => $rating['reviewCount']
		];
	}

	/**
	 *  Get RSBlog Post Rating Data
	 *
	 *  @param   integer  $id  The post id
	 *
	 *  @return  array
	 */
	private function getRating($id)
	{
		$db = $this->db;

		$query = $db->getQuery(true)
			->select('count(*) as reviewCount')
			->select('TRUNCATE(IFNULL(SUM(rating)/COUNT(id),0), 1) as ratingValue')
			->from('#__rsblog_rating')
			->where('post_id = '. (int) $id);

		$db->setQuery($query);

		return $db->loadAssoc();
	}
}
