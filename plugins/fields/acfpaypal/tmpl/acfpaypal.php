<?php

/**
 * @package         Advanced Custom Fields
 * @version         1.2.0-RC2 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2019 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die;

// Setup variables
if (!$field->value)
{
	return;
}

$type = $fieldParams->get('type', 'checkout');
$paypal_account = $fieldParams->get('paypal_account', '');
$sandbox_mode = (bool) $fieldParams->get('sandbox_mode', '');
$sandbox_account = $fieldParams->get('sandbox_account', '');
$paypal_account = (!$sandbox_mode) ? $paypal_account : $sandbox_account;

// get value data
$data = isset($field->value) ? $field->value : [];
$data = json_decode($data);

// Setup variables
$item_name = (isset($data->item_name) && !empty($data->item_name)) ? $data->item_name : '';
$price = (isset($data->price) && !empty($data->price)) ? $data->price : '';

// do not render if name and price are empty except if we are rendering a donation button
// which may not have a fixed amount to donate
if ((empty($item_name) || empty($price)) && $type != 'donation')
{
	return;
}

// Setup variables
$currency = $fieldParams->get('currency', 'USD');
$billing_interval = $fieldParams->get('billing_interval', '');
$language = $fieldParams->get('language', 'auto');
$language_locale = $fieldParams->get('language_locale', '');
$return_url = $fieldParams->get('return_url', '');
$cancel_url = $fieldParams->get('cancel_url', '');
$button_style = $fieldParams->get('button_style', 'style');
$button_style_selector = $fieldParams->get('button_style_selector', '');
$button_style_image = $fieldParams->get('button_style_image', '');
$new_tab = (bool) $fieldParams->get('new_tab', '');
$new_tab = ($new_tab) ? ' target="_blank"' : '';

// base url
$base_url = (!$sandbox_mode) ? 'https://www.paypal.com/cgi-bin/webscr' : 'https://www.sandbox.paypal.com/cgi-bin/webscr';

$image_name = ($button_style == 'style') ? $button_style_selector : $button_style_image;
$image_url = ($button_style == 'style') ? JURI::root() . $image_name : $button_style_image;

// command
$command = '_xclick';
switch ($type) {
	case 'donation':
		$command = '_donations';
		break;
	case 'subscription':
		$command = '_xclick-subscriptions';
		break;
}
?>
<form method="post" action="<?php echo $base_url; ?>"<?php echo $new_tab; ?>>
	<input type="hidden" name="cmd" value="<?php echo $command; ?>">
	<input type="hidden" name="business" value="<?php echo $paypal_account; ?>">
	<input type="hidden" name="currency_code" value="<?php echo $currency; ?>">
	<?php
	if (!empty($item_name))
	{
		?><input type="hidden" name="item_name" value="<?php echo $item_name; ?>"><?php
	}
	if (!empty($price))
	{
		if ($type == 'subscription')
		{
			?>
			<input type="hidden" name="p3" value="<?php echo $billing_interval; ?>">
			<input type="hidden" name="t3" value="D">
			<?php
		}
		?><input type="hidden" name="<?php echo ($type == 'subscription') ? 'a3' : 'amount'; ?>" value="<?php echo $price; ?>"><?php
	}
	if (!empty($return_url))
	{
		?><input type="hidden" name="return" value="<?php echo $return_url; ?>"><?php
	}
	
	if (!empty($cancel_url))
	{
		?><input type="hidden" name="cancel_return" value="<?php echo $cancel_url; ?>"><?php
	}
	
	if ($language == 'fixed' && !empty($language_locale))
	{
		?><input type="hidden" name="lc" value="<?php echo $language_locale; ?>"><?php
	}
	?>
	<input type="image" src="<?php echo $image_url; ?>" style="border:none;max-width: 100%;" />
</form>