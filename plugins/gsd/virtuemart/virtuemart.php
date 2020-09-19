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

defined('_JEXEC') or die('Restricted access');

use GSD\Helper;
use GSD\MappingOptions;
use GSD\PluginBaseProduct;
use NRFramework\Cache;

/**
 *  VirtueMart Google Structured Data Plugin
 */
class plgGSDVirtueMart extends PluginBaseProduct
{
    /**
     *  Indicates the query string parameter name that is used by the front-end component
     *
     *  @var  string
     */
    protected $thingRequestIDName = 'virtuemart_product_id';

	/**
     *  Product View
     *  
     *  @return  object
     */
	protected function viewProductDetails()
	{
        $id       = $this->getThingID();
        $product  = VmModel::getModel('Product')->getProduct($id);
        $currency = VmModel::getModel('currency')->getCurrency($this->app->getUserState('virtuemart_currency_id', null));
        $rating   = $this->getRating($id);

        if (!is_object($product) || !isset($product->product_name))
        {
            return;
        }

        // Prepare Data
        $data = [
            'id'          => $product->virtuemart_product_id,
            'alias'       => $product->slug,
            'headline'    => $product->product_name,
            'description' => empty($product->product_s_desc) ? $product->product_desc : $product->product_s_desc,
			'introtext'   => $product->product_s_desc,
            'fulltext'    => $product->product_desc,
            
            // B/C: The description_short property is deprecated. Use introtext instead.
            'description_short' => $product->product_s_desc, 

            'offerPrice'  => $this->getPrice($product->prices['salesPrice'], $currency->virtuemart_currency_id),
            'currency'    => $currency->currency_code_3,
            'image'       => $this->getImage($product->images),
            'imagetext'	  => Helper::getFirstImageFromString($product->product_s_desc . $product->product_desc),
            'brand'       => $product->mf_name,
            'ratingValue' => $rating['value'],
            'reviewCount' => $rating['count'],
            'sku'         => $product->product_sku,
            'mpn'         => $product->product_mpn,
            'metakey'     => $product->metakey,
            'metadesc'    => $product->metadesc,
            'reviews'     => $this->getReviews($id),
            'bestRating'  => 5,
            'worstRating' => 0
        ];

        // Add Custom Fields to payload
        if (isset($product->customfieldsSorted) && is_array($product->customfieldsSorted))
        {
            foreach ($product->customfieldsSorted as $custom_field_group)
            {
                if (!is_array($custom_field_group))
                {
                    continue;
                }

                foreach ($custom_field_group as $custom_field)
                {
                    if (!isset($custom_field->virtuemart_custom_id) || 
                        !isset($custom_field->customfield_value) || 
                        !is_string($custom_field->customfield_value))
                    {
                        continue;
                    }

                    // If a product uses a custom field more than once, the last value will be used only.
                    $key = 'cf.' . $custom_field->virtuemart_custom_id;
                    $data[$key] = $custom_field->customfield_value;
                }
            }
        }

        $this->product = $product;

        return $data;
    }

    /**
     * Indicates whether the product is available based on the amount of product in stock.
     *
     * @return bool
     */
    protected function productIsAvailable()
    {
        if (!isset($this->product->product_in_stock))
        {
            return;
        }

        return (int) $this->product->product_in_stock > 0;
    }

    /**
     *  Re-calculates and formats given price with a currency
     *
     *  @param   String     The product price
     *  @param   Integer    The currency id
     *
     *  @return  void
     */
    private function getPrice($price, $currency_id)
    {
        @include_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/currencydisplay.php';

        // Try to re-calculate the price using currency
        if (!class_exists('CurrencyDisplay'))
        {
            return 0;
        }

        $currencyHelper = CurrencyDisplay::getInstance($currency_id);
        return $currencyHelper->roundForDisplay($price);
    }

    /**
     *  Gets Virtuemart product rating
     *
     *  @param   Integer  $id  Product ID
     *
     *  @return  Array
     */
    private function getRating($id)
    {
        $ratingModel = VmModel::getModel('ratings');
        $rating = $ratingModel->getRatingByProduct($id);

        if (!is_object($rating) || !isset($rating->rating))
        {
            return [
                'value' => 0,
                'count' => 0
            ];
        }

        return [
            'value' => $rating->rating,
            'count' => $this->getCountReviews($id)
        ];
    }

    /**
     * Get the reviews of the given product ID
     * 
     * @return  array
     */
    protected function getReviews($id)
    {
        $reviews = $this->getReviewsByProduct($id);

        $data = [];

        foreach ($reviews as $review)
        {
            $data[] = [
                'author' => $review->name,
                'datePublished' => Helper::date($review->created_on, true),
                'description' => $review->comment,
                'rating' => $review->review_rating
            ];
        }
        
        return $data;
    }

    /**
     * Gets reviews by a product ID
     *
     * @param   int  $product_id
     * 
     * @return  object
     */
    private function getReviewsByProduct($product_id)
    {
        // Return cached result if it does exist.
        $hash = 'gsdVMProductReviews' . $product_id;

        if (Cache::has($hash))
        {
            return Cache::get($hash);
        }

        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select(array('a.created_on', 'a.comment', 'a.review_rating', 'b.name'))
            ->from($db->quoteName('#__virtuemart_rating_reviews', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'b') . ' ON ' . $db->quoteName('b.id') . ' = ' . $db->quoteName('a.created_by'))
            ->where($db->quoteName('a.created_by') . '=' . $db->quoteName('b.id'))
            ->where($db->quoteName('a.virtuemart_product_id') . '=' . $product_id)
            ->where($db->quoteName('a.published') . '= 1');

        $db->setQuery($query);

        // Cache result
        return Cache::set($hash, $db->loadObjectList());
    }
    
    /**
     * Get the total number of product reviews
     *
     * @param  integer $id
     *
     * @return void
     */
    private function getCountReviews($id)
    {
        return count($this->getReviewsByProduct($id));
    }

     /**
      * Return an array of Virtuemart Custom Fields
      *
      * @param  integer $product_id
      *
      * @return mixed   Array on success, Null on failure
      */
    private function getCustomFields($product_id)
    {
        $fields_model = VmModel::getModel('customfields');

        $custom_fields = $fields_model->getCustomEmbeddedProductCustomFields($product_id);

        return $custom_fields;
    }

    /**
     *  Returns VirtueMart product image
     *
     *  @param   array  $images   Product Images
     *
     *  @return  string
     */
    private function getImage($images)
    {
        if (!is_array($images) || count($images) == 0 || !isset($images[0]) || !isset($images[0]->file_url))
        {
            return;
        }

        return Helper::absURL($images[0]->file_url);
    }

	/**
	 * Listening to the onAfterRender Joomla event
	 *
	 * @return void
	 */
	public function onAfterRender()
	{
        // Make sure we are on the right context
        if ($this->app->isClient('administrator') || 
            !$this->passContext() || 
            $this->getView() != 'productdetails' ||
            !$this->params->get('remove_virtuemart_product_schema', true))
		{
            return;
        }

        // Remove the Product Structured Data item added by Virtuemart
        \GSD\SchemaCleaner::remove('Product');
    }
}
