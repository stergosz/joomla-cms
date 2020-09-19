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

if (!$map_value = $field->value)
{
	return;
}

// Setup Variables
$mapID  = 'acf_osm_map_' . $item->id . '_' . $field->id;
$coords = $map_value;

if ($map_value = json_decode($map_value, true))
{
	$coords = $map_value['coordinates'];
	
	$marker_tooltip_label = isset($map_value['tooltip']) ? $map_value['tooltip'] : '';
	
}
$coords = explode(',', $coords);

if (!isset($coords[1]))
{
	return;
}

\JHtml::_('behavior.core');

$width = $fieldParams->get('width', '400px');
$height = $fieldParams->get('height', '350px');
$zoom = $fieldParams->get('zoom', 4);
$extra_atts[] = 'data-marker-image="media/plg_fields_acfosm/img/marker.png"';


$marker_image = $fieldParams->get('marker_image', 'media/plg_fields_acfosm/img/marker.png');
$scale = $fieldParams->get('scale', '0');
$extra_atts[0] = 'data-marker-image="' . $marker_image . '"';
$extra_atts[] = 'data-scale="' . $scale . '"';
JHtml::stylesheet('plg_fields_acfosm/acf_osm_map.css', ['relative' => true, 'version' => 'auto']);


// Add Media Files
JHtml::stylesheet('https://cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@master/en/v6.0.1/css/ol.css');
JHtml::script('https://cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@master/en/v6.0.1/build/ol.js');
JHtml::script('plg_fields_acfosm/acf_osm_map.js', ['relative' => true, 'version' => 'auto']);
JHtml::script('plg_fields_acfosm/script.js', ['relative' => true, 'version' => 'auto']);

$buffer = '<div clas="osm_map_item_wrapper"><div class="osm_map_item" id="' . $mapID . '" data-zoom="' . $zoom . '" data-lat="' . trim($coords[0]) . '" data-long="' . trim($coords[1]) . '" ' . implode(' ', $extra_atts) . ' style="width:' . $width . ';height:' . $height . ';max-width:100%;"></div>';


$tooltipEnabled = (bool) $fieldParams->get('show_tooltip', '0');
if ($tooltipEnabled && !empty($marker_tooltip_label))
{
	$text = !empty($marker_tooltip_label) ? '<div class="tooltip-body">' . $marker_tooltip_label . '</div>' : '';
	$buffer .= '<div class="marker-tooltip" style="display:none;">' . $text . '<div class="arrow"></div></div>';
}


$buffer .= '</div>';

echo $buffer;