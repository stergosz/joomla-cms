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

if (!@include_once(JPATH_PLUGINS . '/system/nrframework/autoload.php'))
{
	throw new RuntimeException('Novarain Framework is not installed', 500);
}

JHTML::_('behavior.modal'); 

/**
 *  Get list of all available fields
 *
 *  @return  array
 */
function getFieldsCollection()
{
    // Load XML file
    $xmlfile = __DIR__ . '/fieldscollection.xml';

    if (!JFile::exists($xmlfile))
    {
        return;
    }

    if (!$xmlItems = simplexml_load_file($xmlfile))
    {
        return;
    }

    $fields = array();

    foreach ($xmlItems as $key => $item)
    {
        $item = (array) $item;
        $item = new JRegistry($item["@attributes"]);

        $extensionName = 'acf' . $item->get("name");
        $extensionID   = NRFramework\Functions::getExtensionID($extensionName, 'fields');
        $backEndURL    = "index.php?option=com_plugins&task=plugin.edit&extension_id=" . $extensionID;

        $url = $item->get("proonly", null) ? NRFramework\Functions::getUTMURL($item->get("url", "https://www.tassos.gr/joomla-extensions/advanced-custom-fields")) : JURI::base() . $backEndURL;

        $path = JPATH_PLUGINS . '/fields/acf' . $item->get("name");
        NRFramework\Functions::loadLanguage('plg_fields_acf' . $item->get("name"), $path);

        $obj = array(
            "label"        => isset($item['label']) ? $item['label'] : str_replace('ACF - ', '', JText::_('PLG_FIELDS_ACF' . strtoupper($item->get("name")) . '_LABEL')),
            "description"  => isset($item['description']) ? $item['description'] : JText::_('ACF_' . strtoupper($item->get("name")) . '_DESC'),
            "backendurl"   => JURI::base() . $backEndURL,
            "extensionid"  => $extensionID,
            "proonly"      => $item->get("proonly", null),
            "comingsoon"   => $item->get("comingsoon", false),
            'docurl'       => 'https://www.tassos.gr/joomla-extensions/advanced-custom-fields/docs/' . $item->get('doc')
        );

        $fields[] = $obj;
    }

    asort($fields);

    $layout = new JLayoutFile('fieldscollection', __DIR__);
	return $layout->render($fields);
}

?>

<div class="nr">
	<div class="nr-well well">
		<h4><?php echo JText::_("NR_INFORMATION") ?></h4>
		<p>
			<a target="_blank" href="https://www.tassos.gr/joomla-extensions/advanced-custom-fields">
				<?php echo JText::_("ACF") ?>
				<?php echo NRFramework\Functions::getExtensionVersion("plg_system_acf", true) ?>
			</a>
		</p>
		<p class="bg-primary"><?php echo JText::_("NR_LIKE_THIS_EXTENSION") ?> <a target="_blank" href="https://extensions.joomla.org/extensions/extension/authoring-a-content/content-construction/advanced-custom-fields/"><?php echo JText::_("NR_LEAVE_A_REVIEW") ?></a> 
			<a class="stars" target="_blank" href="https://extensions.joomla.org/extensions/extension/authoring-a-content/content-construction/advanced-custom-fields/">
				<span class="icon-star"></span>
				<span class="icon-star"></span>
				<span class="icon-star"></span>
				<span class="icon-star"></span>
				<span class="icon-star"></span>
			</a>
		</p>
		<?php echo JText::_("NR_NEED_SUPPORT") ?> 
		<a target="_blank" href="http://www.tassos.gr/contact?extension=Advanced Custom Fields"><?php echo JText::_("NR_DROP_EMAIL") ?></a>
	</div>

	<!-- Fields Collection -->
	<?php echo getFieldsCollection(); ?>

	<hr>
	<p><?php echo JText::sprintf('NR_COPYRIGHT', '&copy; ' . Date("Y")) ?></p>
</div>

<style>
	.stars:hover {
		text-decoration: none;
	}
	.icon-star {
	    color: #fcac0a;
	    width: 7px;
	}
</style>