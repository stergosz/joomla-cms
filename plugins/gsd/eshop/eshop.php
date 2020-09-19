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

/**
 *   Google Structured Data Plugin
 */
class plgGSDEshop extends PluginBaseProduct
{
    /**
     *  Active Product Object
     *
     *  @var  object
     */
    private $product;

    /**
     *  Product View
     *  
     *  @return  object
     */
    protected function viewProduct()
    {
        // Make sure we have a valid ID
		if (!$id = $this->getThingID())
		{
			return;
        }

        // Load EShop current product data
        if (!$this->loadProduct())
        {
            return;
        }
        
        if (!is_object($this->product))
		{
			return;
        }

        $price = $this->getProductPrice($id);
        $product_image = !empty($this->product->product_image) ? Helper::absURL('/media/com_eshop/products/' . $this->product->product_image) : "";

        // Prepare Data
        return [
			'id'   		  => $this->product->id,
			'alias'       => $this->product->product_alias,
            'headline'    => $this->product->product_name,
            'description' => empty($this->product->product_short_desc) ? $this->product->product_desc : $this->product->product_short_desc,
			'introtext'   => $this->product->product_short_desc,
			'fulltext'    => $this->product->product_desc,
            'offerPrice'  => $price,
            'currency'    => $this->getProductCurrencyCode(),
            'image'       => $product_image,
            'imagetext'	   => \GSD\Helper::getFirstImageFromString($this->product->product_short_desc . $this->product->product_desc),
            'brand'       => EshopHelper::getManufacturer($this->product->manufacturer_id)->manufacturer_name,
            'ratingValue' => EshopHelper::getProductRating($id),
            'reviewCount' => count(EshopHelper::getProductReviews($id)),
            'sku'         => $this->product->product_sku
        ];
    }

    /**
     * Indicates whether the product is available based on the amount of product in stock.
     *
     * @return bool
     */
    protected function productIsAvailable()
    {
        return ($this->product->product_quantity > 0);
    }

    /**
     * Get product price. Return a special price first, otherwise use the baseprice. 
     *
     * @param   integer $product_id  The product ID
     *
     * @return  mixed   Null on error, Number on success
     */
    private function getProductPrice($product_id)
    {
        if (!$prices = EshopHelper::getProductPriceArray($product_id, $this->product->product_price))
        {
            return;
        }

        if (isset($prices['salePrice']) && (float) $prices['salePrice'] > 0)
        {   
            return $prices['salePrice'];    
        }

        if (isset($prices['basePrice']))
        {   
            return $prices['basePrice'];            
        }
    }

    /**
     *  Load EShop current product data
     *
     *  @return  bool  
     */
    private function loadProduct()
    {
        $product = EShopHelper::getProduct($this->getThingID());

        $this->product = $product;
        return true;
    }

    /**
     *  Get global currency code (EUR, USD, etc)
     *
     *  @return  string       
     */
    private function getProductCurrencyCode()
    {
		// Load current item via model
        $config_data = EshopHelper::getConfig();
        return $config_data->default_currency_code;
    }
}
