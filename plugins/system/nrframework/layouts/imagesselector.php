<?php

/**
 * @package         Google Structured Data
 * @version         4.8.0-RC1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2020 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');
extract($displayData);

if (empty($images))
{
	return;
}

$value = !empty($value) ? $value : $images[0];
$heightAtt = !empty($height) ? ' style="height:' . $height . ';"' : '';
?>
<div class="nr-images-selector cols_<?php echo $columns; ?>" style="width:<?php echo $width;?>;">
	<?php
	foreach ($images as $img)
	{
		$id = "nr-images-selector-" . md5($img);
		$isChecked = ($value == $img) ? ' checked="checked"' : '';
		?>
		<div class="image"<?php echo $isChecked . $heightAtt; ?>>
			<input type="radio" id="<?php echo $id; ?>" value="<?php echo $img; ?>" name="<?php echo $name; ?>"<?php echo $isChecked; ?> />
			<label for="<?php echo $id; ?>"><img src="<?php echo JURI::root() . $img; ?>" alt="<?php echo $img; ?>" /></label>
		</div>
		<?php
	}
	?>
</div>