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

class NR_MailChimp extends NR_Wrapper
{
	/**
	 *  MailChimp Endpoint URL
	 *
	 *  @var  string
	 */
	protected $endpoint = 'https://<dc>.api.mailchimp.com/3.0';

	/**
	 * Create a new instance
	 * 
	 * @param array $options The service's required options
	 * @throws \Exception
	 */
	public function __construct($options)
	{
		parent::__construct();

		$this->setKey($options);

		list(, $data_center) = explode('-', $this->key);
		$this->endpoint  = str_replace('<dc>', $data_center, $this->endpoint);

		$this->options->set('headers.Authorization', 'apikey ' . $this->key);
	}

	/**
	 *  Subscribe user to MailChimp
	 *
	 *  API References:
	 *  https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/#edit-put_lists_list_id_members_subscriber_hash
	 *  https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/#create-post_lists_list_id_members
	 *
	 *  @param   string   $email         	  User's email address
	 *  @param   string   $list          	  The MailChimp list unique ID
	 *  @param   Object   $merge_fields  	  Merge Fields
	 *  @param   boolean  $update_existing	  Update existing user
	 *  @param   boolean  $double_optin  	  Send MailChimp confirmation email?
	 *
	 *  @return  void
	 */
	public function subscribe($email, $list, $merge_fields = array(), $update_existing = true, $double_optin = false)
	{
		$data = array(
			'email_address' => $email,
			'status' 		=> $double_optin ? 'pending' : 'subscribed'
		);

		if (is_array($merge_fields) && count($merge_fields))
		{
			foreach ($merge_fields as $merge_field_key => $merge_field_value) 
			{
				$value = is_array($merge_field_value) ? implode(',', $merge_field_value) : (string) $merge_field_value;
				$data['merge_fields'][$merge_field_key] = $value;
			}
		}

		$interests = $this->validateInterestCategories($list, $merge_fields);

		if (!empty($interests)) 
		{
			$data = array_merge($data, array('interests' => $interests));
		}

		if ($update_existing)
		{
			$subscriberHash = md5(strtolower($email));
			$this->put('lists/' . $list . '/members/' . $subscriberHash, $data);
		} else 
		{
			$this->post('lists/' . $list . '/members', $data);
		}

		return true;
	}

	/**
	 *  Returns all available MailChimp lists
	 *
	 *  https://developer.mailchimp.com/documentation/mailchimp/reference/lists/#read-get_lists
	 *
	 *  @return  array
	 */
	public function getLists()
	{
		$data = $this->get('/lists');

		if (!$this->success())
		{
			return;
		}

		if (!isset($data['lists']) || !is_array($data['lists']))
		{
			return;
		}

		$lists = [];

		foreach ($data['lists'] as $key => $list)
		{
			$lists[] = array(
				'id'   => $list['id'],
				'name' => $list['name']
			);
		}

		return $lists;
	}

	/**
	 *  Gets the Interest Categories from MailChimp
	 *
	 *  @param   string  $listID  The List ID
	 *
	 *  @return  array           
	 */
	public function getInterestCategories($listID)
	{
		if (!$listID) 
		{
			return;
		}

		$data = $this->get('/lists/' . $listID . '/interest-categories');

		if (!$this->success())
		{
			return;
		}

		if (isset($data['total_items']) && $data['total_items'] == 0) 
		{
			return;
		}

		return $data['categories'];
	}

	/**
	 *  Gets the values accepted for the particular Interest Category
	 *
	 *  @param   string  $listID              The List ID
	 *  @param   string  $interestCategoryID  The Interest Category ID
	 *
	 *  @return  array                       
	 */
	public function getInterestCategoryValues($listID, $interestCategoryID)
	{
		if (!$interestCategoryID || !$listID) 
		{
			return array();
		}

		$data = $this->get('/lists/' . $listID . '/interest-categories/' . $interestCategoryID . '/interests');

		if (isset($data['total_items']) && $data['total_items'] == 0) 
		{
			return array();
		}

		return $data['interests'];
	}

	/**
	 *  Filters the interests categories through the form fields
	 *  and constructs the interests array for the subscribe method
	 *
	 *  @param   string  $listID  The List ID
	 *  @param   array   $params  The Form fields
	 *
	 *  @return  array            
	 */
	public function validateInterestCategories($listID, $params)
	{
		if (!$params || !$listID) 
		{
			return array();
		}

		$interestCategories = $this->getInterestCategories($listID);

		if (!$interestCategories) 
		{
			return array();
		}

		$categories = array();

		foreach ($interestCategories as $category) 
		{
			if (array_key_exists($category['title'], $params)) 
			{
				$categories[] = array('id' => $category['id'], 'title' => $category['title']);
			}
		}

		if (empty($categories)) 
		{
			return array();
		}

		$interests = array();

		foreach ($categories as $category) 
		{
			$data = $this->getInterestCategoryValues($listID, $category['id']);

			if (isset($data['total_items']) && $data['total_items'] == 0) 
			{
				continue;
			}

			foreach ($data as $interest) 
			{
				if (in_array($interest['name'], (array) $params[$category['title']]))
				{
					$interests[$interest['id']] = true;
				}
				else 
				{
					$interests[$interest['id']] = false;
				}
			}
		}

		return $interests;
	}

	/**
	 *  Get the last error returned by either the network transport, or by the API.
	 *
	 *  @return  string
	 */
	public function getLastError()
	{
		$body = $this->last_response->body;

		if (isset($body['errors']))
		{
			$error = $body['errors'][0];
			return $error['field'] . ': ' . $error['message'];
		}

		if (isset($body['detail']))
		{
			return $body['detail'];
		}
	}

	/**
	 *  The get() method overridden so that it handles
	 *  the default item paging of MailChimp which is 10
	 *
	 *  @param   string          $method URL of the API request method
	 *  @param   array $args     Assoc array of arguments (usually your data)
	 *  @return  array|false     Assoc array of API response, decoded from JSON
	 */
	public function get($method, $args = array())
	{
		$data = $this->makeRequest('get', $method, $args);

		if ($data && isset($data['total_items']) && (int) $data['total_items'] > 10)
		{
			$args['count'] = $data['total_items'];
			return $this->makeRequest('get', $method, $args);
		}

		return $data;
	}
}
