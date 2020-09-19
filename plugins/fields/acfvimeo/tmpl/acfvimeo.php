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

if (!$videoID = $field->value)
{
	return;
}

if (preg_match("/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/", $field->value, $match)) 
{
    $videoID = $match[5];
}

// Setup Variables
$size    		   = $fieldParams->get('size', 'fixed');
$width   		   = $fieldParams->get('width', '640');
$height   		   = $fieldParams->get('height', '360');
$width_height_atts = ($size == 'fixed') ? 'width="' . $width . '" height="' . $height . '"' : '';
$query   		   = $videoID;


$autoplay = $fieldParams->get('autoplay', 0);
$loop     = $fieldParams->get('loop', 0);
$color    = substr($fieldParams->get('color', '#00adef'), 1);
$title    = $fieldParams->get('title', 1);
$byline   = $fieldParams->get('byline', 1);
$portrait = $fieldParams->get('portrait', 0);
$query    = $videoID . '?autoplay=' . $autoplay . '&loop=' . $loop . '&color=' . $color . '&title=' . $title . '&byline=' . $byline . '&portrait=' . $portrait;


// Output
$buffer = '
	<iframe
		src="//player.vimeo.com/video/' . $query . '"
		frameborder="0"
		' . $width_height_atts . '
		webkitallowfullscreen
		mozallowfullscreen
		allowfullscreen>
	</iframe>
';

if ($size == 'responsive')
{
    JHtml::stylesheet('plg_system_acf/responsive_embed.css', ['relative' => true, 'version' => 'auto']);
	$buffer = '<div class="acf-responsive-embed">' . $buffer . '</div>';
}

echo $buffer;