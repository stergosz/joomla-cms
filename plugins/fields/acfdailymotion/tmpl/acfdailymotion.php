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

if (preg_match('!^.+dailymotion\.com/(video|hub)/([^_]+)[^#]*(#video=([^_&]+))?|(dai\.ly/([^_]+))!', $field->value, $match)) 
{
        if (isset($match[6])) 
        {
            $videoID = $match[6];
            unset($match);
        }

        if (isset($match[4])) 
        {
            $videoID = $match[4];
            unset($match);
        }

        $videoID = $match[2];
}

// Setup Variables
$size    		   = $fieldParams->get('size', 'fixed');
$width             = $fieldParams->get('width', '480');
$height            = $fieldParams->get('height', '270');
$width_height_atts = ($size == 'fixed') ? 'width="' . $width . '" height="' . $height . '"' : '';
$query             = $videoID;


$autoplay          = $fieldParams->get('autoplay', 0);
$controls          = $fieldParams->get('controls', 1);
$endscreen         = $fieldParams->get('endscreen', 1);
$mute              = $fieldParams->get('mute', 0);
$quality           = $fieldParams->get('quality', 'auto');
$sharing           = $fieldParams->get('sharing', 1);
$uiHighlight       = substr($fieldParams->get('uiHighlight', '#ffcc33'), 1);
$uiLogo            = $fieldParams->get('uiLogo', 1);
$uiStartScreenInfo = $fieldParams->get('uiStartScreenInfo', 1);
$uiTheme           = $fieldParams->get('uiTheme', 'dark');
$query             = $videoID . '?autoplay=' . $autoplay . '&controls=' . $controls . '&endscreen-enable=' . $endscreen . '&mute=' . $mute . '&quality=' . $quality . '&sharing-enable=' . $sharing . '&ui-highlight=' . $uiHighlight . '&ui-logo=' . $uiLogo .'&ui-start-screen-info=' . $uiStartScreenInfo . '&ui-theme=' . $uiTheme;


// Output
$buffer = '
	<iframe
		src="//www.dailymotion.com/embed/video/' . $query . '"
		' . $width_height_atts . '
		frameborder="0"
		allowfullscreen>
	</iframe>
';

if ($size == 'responsive')
{
    JHtml::stylesheet('plg_system_acf/responsive_embed.css', ['relative' => true, 'version' => 'auto']);

	$buffer = '<div class="acf-responsive-embed">' . $buffer . '</div>';
}

echo $buffer;