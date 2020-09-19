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

if (!$field->value)
{
	return;
}

// Support old value
if (is_numeric($field->value))
{
	$value = (object) [
		'id' => $field->value,
		'playlist' => false
	];
} else 
{
	$value = json_decode($field->value);
}

if (empty($value->id))
{
	return;
}

// Setup Variables
$width        = $fieldParams->get('width', '100%');
$height       = $fieldParams->get('height', '166');
$mode         = (bool) $value->playlist ? 'playlists' : 'tracks';
$query		  = $value->id;


$autoplay     = ($fieldParams->get('autoplay', 0)) ? 'true' : 'false';
$hideRelated  = ($fieldParams->get('hideRelated', 0)) ? 'true' : 'false';
$showComments = ($fieldParams->get('showComments', 1)) ? 'true' : 'false';
$showUser     = ($fieldParams->get('showUser', 1)) ? 'true' : 'false';
$showReposts  = ($fieldParams->get('showReposts', 0)) ? 'true' : 'false';
$visual       = ($fieldParams->get('visual', 0)) ? 'true' : 'false';
$color        = substr($fieldParams->get('color', '#00cc11'), 1);
$query        = $value->id . '&auto_play=' . $autoplay . '&hide_related=' . $hideRelated . '&show_comments=' . $showComments . '&show_user=' . $showUser . '&show_reposts=' . $showReposts . '&visual=' . $visual . '&color=' . $color;


// Output
$buffer = '
	<iframe
		src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/' . $mode . '/' . $query . '"
		width="' . $width . '"
		height="' . $height . '"
		scrolling="no"
		frameborder="0">
	</iframe>
';

echo $buffer;