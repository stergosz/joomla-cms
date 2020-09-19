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

use GSD\Helper;
use GSD\PluginBaseProduct;
use NRFramework\Cache;

/**
 *  JShopping Google Structured Data Plugin
 */
class plgGSDJShopping extends PluginBaseProduct
{
    /**
     *  Indicates the query string parameter name that is used by the front-end component
     *
     *  @var  string
     */
    protected $thingRequestIDName = 'product_id';

    /**
     *  Active Product Object
     *
     *  @var  object
     */
    private $product;

    /**
     *  JoomShopping Configuration Class
     *
     *  @var  class
     */
    private $config;

    /**
     *  Get View Name
     *
     *  @return  string  Return the current executed view in the front-end
     */
    protected function getView()
    {
        $input = $this->app->input;

        if ($input->get('controller') == 'product')
        {
            if ($input->get('view') == 'product' || $input->get('task') == 'view')
            {
                return 'product';
            }
        }

        return $this->app->input->get('view');
    }

    /**
     *  Product View
     *  
     *  @return  object
     */
    protected function viewProduct()
    {
        // Load JShopping class and current product data
        if (!$this->loadFactory() || !$this->loadProduct())
        {
            return;
        }

        // Product Brand
        $brand = $this->product->getManufacturerInfo();
        $brand = isset($brand->name) ? $brand->name : '';

        // Prepare Data
        return [
            'id'          => $this->product->product_id,
            'headline'    => $this->product->getName(),
            'description' => empty($this->product->short_description) ? $this->product->description : $this->product->short_description,
            'introtext'   => $this->product->short_description,
			'fulltext'    => $this->product->description,
            'offerPrice'  => $this->product->getPrice(),
            'currency'    => $this->getProductCurrencyCode(),
            'image'       => $this->getProductImage(),
            'imagetext'	  => \GSD\Helper::getFirstImageFromString($this->product->short_description . $this->product->description),
            'brand'       => $brand,
            'ratingValue' => $this->product->average_rating,
            'reviewCount' => $this->product->reviews_count,
            'bestRating'  => isset($this->config->max_mark) ? $this->config->max_mark : 10,
            'sku'         => $this->product->product_ean,
            'reviews'     => $this->getReviews()
        ];
    }

    /**
     * Get the reviews of the product
     * 
     * @return  array
     */
    protected function getReviews()
    {
        // Return cached result if it does exist.
        $hash = 'gsdJSProductReviews' . $this->product->product_id;

        if (Cache::has($hash))
        {
            return Cache::get($hash);
        }

        $reviews = $this->product->getReviews();

        $data = [];

        foreach ($reviews as $review)
        {
            $data[] = [
                'author' => $review->user_name,
                'datePublished' => Helper::date($review->time),
                'description' => $review->review,
                'rating' => $review->mark
            ];
        }

        // Cache result
        return Cache::set($hash, $data);
    }

    /**
     * Indicates whether the product is available based on the amount of product in stock.
     *
     * @return bool
     */
    protected function productIsAvailable()
    {
        if ($this->product->unlimited == '1')
        {
            return true;
        }

        return ((int) $this->product->product_quantity > 0);
    }

    /**
     *  Initialize JShopping Classes
     *
     *  @return  bool
     */
    private function loadFactory()
    {
        require_once JPATH_SITE . '/components/com_jshopping/lib/factory.php';

        if (!class_exists('JSFactory'))
        {
            return;
        }

        $this->config = JSFactory::getConfig();
        return true;
    }

    /**
     *  Load JShopping current product data
     *
     *  @return  bool  
     */
    private function loadProduct()
    {
        $product = JSFactory::getTable('product', 'jshop');
        $product->load($this->getThingID());

        if (!$product instanceof jshopProduct)
        {
            return;
        }

        // This method helps us initialize language-based texts
        $product->getDescription();

        // Load rating information
        $product->loadReviewsCount();
        $product->loadAverageRating();

        $this->product = $product;

        return true;
    }

    /**
     *  Get Product Main Image
     *
     *  @return  string
     */
    private function getProductImage()
    {
        $images = $this->product->getImages();

        if (!is_array($images) || count($images) == 0)
        {
            return;
        }

        return $this->config->image_product_live_path . '/' . $images[0]->image_full;
    }

    /**
     *  Get Product currency code (EUR, USD)
     *
     *  @return  string       
     */
    private function getProductCurrencyCode()
    {
        $table = JSFactory::getTable('currency', 'jshop');
        $table->load($this->product->currency_id);

        return isset($table->currency_code_iso) ? $table->currency_code_iso : '';
    }
}