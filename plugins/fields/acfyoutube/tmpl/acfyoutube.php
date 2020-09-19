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

if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $field->value, $match))
{
	$videoID = $match[1];
}

// Setup Variables
$id                = 'acf_yt_' . $item->id . '_' . $field->id;
$size              = $fieldParams->get('size', 'fixed');
$width             = $fieldParams->get('width', '480');
$height            = $fieldParams->get('height', '270');
$width_height_atts = ($size == 'fixed') ? 'width="' . $width . '" height="' . $height . '"' : '';
$autoplay_att 	   = '';
$query             = $videoID;


$autoplay       = $fieldParams->get('autoplay', '0');
$cc_load_policy = $fieldParams->get('cc_load_policy', '0');
$color          = $fieldParams->get('color', 'red');
$controls       = $fieldParams->get('controls', '1');
$disablekb      = $fieldParams->get('disablekb', '0');
$start          = $fieldParams->get('start', '0');
$end            = $fieldParams->get('end', '0');
$fs             = $fieldParams->get('fs', '1');
$hl             = $fieldParams->get('hl', 'en-GB');
$iv_load_policy = ($fieldParams->get('iv_load_policy', '1')) ? '1' : '3';
$loop           = ($fieldParams->get('loop', '0')) ? '1&playlist=' . $videoID : '0';
$modestbranding = $fieldParams->get('modestbranding', '0');
$rel            = $fieldParams->get('rel', '1');
$showinfo       = $fieldParams->get('showinfo', '1');
$query          = $videoID . '?autoplay=' . $autoplay . '&cc_load_policy=' . $cc_load_policy . '&color=' . $color . '&disablekb=' . $disablekb . '&start=' . $start . '&end=' . $end . '&fs=' . $fs . '&hl=' . $hl . '&iv_load_policy=' . $iv_load_policy . '&loop=' . $loop . '&modestbranding=' . $modestbranding . '&rel=' . $rel . '&showinfo=' . $showinfo;
$autoplay_att 	= (isset($autoplay)) ? ' allow="autoplay; encrypted-media"' : '';


// Output
$buffer = '
	<iframe
		id="' . $id . '"
		class="acf_yt"
		' . $width_height_atts . '
		src="https://www.youtube.com/embed/' . $query . '"
		frameborder="0"
		' . $autoplay_att . '
		allowfullscreen>
	</iframe>
';

if ($size == 'responsive')
{
    JHtml::stylesheet('plg_system_acf/responsive_embed.css', ['relative' => true, 'version' => 'auto']);
	$buffer = '<div class="acf-responsive-embed">' . $buffer . '</div>';
}

echo $buffer;
