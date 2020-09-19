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

if (!defined('nrJ4'))
{
    JHtml::_('behavior.modal'); 
}

extract($displayData);

$isPro = GSD\Helper::isPro();

?>

<div class="nr-app-addons" data-base="<?php echo JURI::base() ?>">
    <table class="table nrTable">
    	<?php foreach ($items as $key => $item) { 
            $docsURL  = 'http://www.tassos.gr/joomla-extensions/google-structured-data-markup/docs/' . $item['docalias'];
            $btnClass = defined('nrJ4') ? 'btn-outline-secondary btn-sm' : 'btn-secondary';
    	?>
        <tr data-id="<?php echo $item['id']; ?>">
            <td class="addonImg">
                <img alt="<?php echo $item["label"]; ?>" src="//static.tassos.gr/images/integrations/gsd/<?php echo $item["name"]; ?>.png"/>
            </td>
            <td>
                <div class="addonTitle"><?php echo JText::_($item["label"]); ?></div>
                <div class="addonDesc"><?php echo JText::_($item["description"]); ?></div>
            </td>
            <td class="addonButtons">
                <?php if ($item['comingsoon']) { ?><?php echo JText::_('NR_ROADMAP'); ?><?php } ?>
                
                <?php if (!$item['comingsoon']) { ?>
                    <?php if ($item['id']) { ?>
        				<a class="btn <?php echo $btnClass ?> pluginState" href="#" title="<?php echo JText::_('GSD_INTEGRATION_TOGGLE') ?>">
        					<span class="icon-<?php echo $item['isEnabled'] ? "publish" : "unpublish" ?>"></span>
        				</a>

                        <?php 
                            $optionsURL = JURI::base(true) . '/index.php?option=com_plugins&view=plugin&tmpl=component&layout=modal&extension_id=' . $item['id'];
                            $modalName = 'gsdPluginModal-' . $item['id'];
                        ?>

              			<a class="btn <?php echo $btnClass ?>"
                            data-toggle="modal"
                            href="#<?php echo $modalName ?>"
                            role="button"
                            title="<?php echo JText::_("JOPTIONS") ?>">
                        	<span class="icon-options"></span>
                        </a>

                        <?php
                            $options = [
                                'title'       => JText::_('GSD_INTEGRATION_EDIT'),
                                'url'         => $optionsURL,
                                'height'      => '400px',
                                'width'       => '800px',
                                'backdrop'    => 'static',
                                'bodyHeight'  => '70',
                                'modalWidth'  => '70',
                                'footer'      => '<button type="button" class="btn btn-secondary" data-dismiss="modal" aria-hidden="true">'
                                        . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>                                      
                                        <button type="button" class="btn btn-primary" aria-hidden="true"
                                         onclick="jQuery(\'#' . $modalName . ' iframe\').contents().find(\'#saveBtn\').click();">'
                                        . JText::_('JSAVE') . '</button>
                                        <button type="button" class="btn btn-success" aria-hidden="true"
                                        onclick="jQuery(\'#' . $modalName . ' iframe\').contents().find(\'#applyBtn\').click();">'
                                        . JText::_('JAPPLY') . '</button>',
                            ];

                            echo JHtml::_('bootstrap.renderModal', $modalName, $options);
                        ?>

                    <?php } ?>
                    
                    <a class="btn <?php echo $btnClass ?>" href="<?php echo $docsURL; ?>" target="_blank" title="<?php echo JText::_("NR_DOCUMENTATION") ?>">
                        <span class="icon-info"></span>
                    </a>
                    <?php if (!$isPro && isset($item['image'])) { ?>
                        <a class="btn <?php echo $btnClass ?>" target="_blank" href="<?php echo $item['image']; ?>" title="<?php echo JText::_('NR_SAMPLE') ?>">
                            <span class="icon-image"></span>
                        </a>
                    <?php } ?>
                <?php } ?>
            </td>
        </tr>
    	<?php } ?>
		<tr>
			<td class="addonImg">
                <a target="_blank" target="_blank" href="https://www.tassos.gr/contact">
                    <img width="60px" alt="<?php echo $item["description"]; ?>" src="//static.tassos.gr/images/integrations/addon.png"/>
                </a>
            </td>
            <td>
                <div class="addonTitle"><?php echo JText::_("GSD_INTEGRATIONS_MISSING") ?></div>
                <?php echo JText::_("GSD_INTEGRATIONS_MISSING_DESC") ?>
            </div>
            <td class="addonButtons" colspan="2">
                <a class="btn btn-secondary btn-sm" target="_blank" href="https://www.tassos.gr/contact">
                    <span class="icon-mail"></span>
                	<?php echo JText::_("NR_CONTACT_US")?>
                </a>
            </td>
		</tr>
	</table>
</div>