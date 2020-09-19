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
extract($displayData);

?>

<div class="nr-app-sidebar">
	<div class="nav">
		<ul>
			<?php foreach ($items as $key => $item) { 

				$class = '';

				if (isset($item['view']))
				{
					if (in_array($view, explode(",", $item['view'])))
					{
						$class = 'active';
					}
				}

				$target  = isset($item['target']) ? '_' . $item['target'] : "_self";
				$isModal = $item['url'] == '#proOnly';

				?>
			<li class="<?php echo $class; ?>">
				<a href="<?php echo $item['url']; ?>" target="<?php echo $target; ?>" <?php echo $isModal ? 'data-pro-only="' . JText::_($item['label']) . '"' : '' ?>>
					<span class="icon icon-<?php echo $item['icon'] ?>"></span>
					<span class="nav-label"><?php echo JText::_($item['label']); ?></span>
				</a>
			</li>
			<?php } ?>
		</ul>
	</div>
</div>
