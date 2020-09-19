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

use GSD\PluginBaseProduct;

/**
 *   Google Structured Data Plugin
 */
class plgGSDDJCatalog2 extends PluginBaseProduct
{
    /**
     * The product data
     *
     * @var array
     */
    private $product;

    /**
     *  Product View
     *  
     *  @return  object
     */
    protected function viewItem()
    {
        $this->loadProduct($this->getThingID());

        if (!$product = $this->product)
        {
            return;
        }

        // Prepare Data
        return [
			'id'          => $product->id,
            'alias'       => $product->alias,
            'headline'    => $product->name,
            'description' => empty($product->intro_desc) ? $product->description : $product->intro_desc,
			'introtext'   => $product->intro_desc,
			'fulltext'    => $product->description,
            'image'       => $product->image,
            'imagetext'	   => \GSD\Helper::getFirstImageFromString($product->intro_desc . $product->description),
            'offerPrice'  => $product->price,
            'sku'         => $product->sku,
            'brand'       => $product->brand,
            'currency'    => $product->currency,
            'ratingValue' => ($product->rating && isset($product->rating->value)) ? $product->rating->value : 0,
            'reviewCount' => ($product->rating && isset($product->rating->count)) ? $product->rating->count : 0,
            'offerAvailability' => ($product->onstock == '0' || $product->available == '0') ? 'http://schema.org/OutOfStock' : 'http://schema.org/InStock',
			'created'      => $product->created,
            'created_by'   => $product->created_by,
			'modified'     => $product->modified,
            'publish_up'   => $product->publish_up,
            'publish_down' => $product->publish_down
        ];
    }

    /**
     * Load DJCatalog2 product including rating, image and pricing info.
     *
     * @param  integer $id
     *
     * @return void
     */
    private function loadProduct($id)
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select([
                'a.*',
                'CASE WHEN (a.special_price > 0.0 AND a.special_price < a.price) THEN a.special_price ELSE a.price END as final_price',
            ])

            // Get first ordered image
            ->select('(SELECT fullpath from #__djc2_images WHERE item_id = ' . $id . ' order by ordering LIMIT 1) as image')

            // Include Brand
            ->select($db->quoteName('p.name', 'brand'))
            ->join('left', '#__djc2_producers AS p ON p.id = a.producer_id')

            ->from($db->quoteName('#__djc2_items', 'a'))
            ->where($db->quoteName('a.id') . '=' . $id);

        $db->setQuery($query);

        $this->product = $db->loadObject();
        $this->product->rating = $this->getProductRating();
        $this->product->image = $this->getProductImage();
        $this->product->price = $this->getProductPrice();
        $this->product->currency = $this->getCurrencyISOCode();
    }

    /**
     * Get product's final price
     *
     * @return mixed
     */
    private function getProductPrice()
    {
        $prices = Djcatalog2HelperPrice::getPrices($this->product->final_price, $this->product->price, $this->product->tax_rule_id, false, JComponentHelper::getParams('com_djcatalog2'));
        return isset($prices['display']) ? $prices['display'] : 0;
    }

    /**
     * Get product's image. Since DJ-Catalog2 uses multiple images per product, we only use the 1st ordered image.
     *
     * @return mixed String on sucess, null on failure
     */
    private function getProductImage()
    {
        $images = DJCatalog2ImageHelper::getImages('item', $this->getThingID());

        if (is_array($images) && isset($images[0]))
        {
            return $images[0]->large;
        }
    }

    /**
     * Get the product's average rating value and reviews count
     *
     * @return mixed Null on failure, object on success
     */
    private function getProductRating()
    {
        // The com_djreviews component must be enabled
        if (!\NRFramework\Extension::componentIsEnabled('com_djreviews'))
        {
            return;
        }

        // The DJ-Catalog2 - DJ-Reviews plugin must be enabled
        if (!\NRFramework\Extension::pluginIsEnabled('djreviews', 'djcatalog2'))
        {
            return;
        }

        // Get the product's average rating value and reviews count
        $db = JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select(['AVG(' . $db->quoteName('a.rating') . ') as value', 'COUNT(' . $db->quoteName('a.rating') . ') as count'])
            ->from($db->quoteName('#__djrevs_reviews_items', 'a'))
            ->join('left', '#__djrevs_reviews AS b ON a.review_id = b.id')
            ->where($db->quoteName('b.published') . ' = 1')
            ->where($db->quoteName('b.item_id') . ' = ' . $this->getThingID())
            ->where($db->quoteName('b.item_type') . ' = ' . $db->quote('com_djcatalog2.item'))
            ->group($db->quoteName('b.item_type'));

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Unfortunately, DJ-Catalog2 uses symbols instead of the 3-letter ISO code for the currency making the currency code detection impossible.
     * We are only adding check for the EUR and USD currencies. If the site is using a different currency, the administrator should override
     * the currency schema property.
     *
     * @return string
     */
    private function getCurrencyISOCode()
    {
        $params = JComponentHelper::getParams('com_djcatalog2');
        $currency_symbol = $params->get('price_unit', '$');
        return ($currency_symbol == '€') ? 'EUR' : 'USD';
    }
}
