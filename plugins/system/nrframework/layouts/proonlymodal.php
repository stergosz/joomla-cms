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

JFactory::getDocument()->addScriptDeclaration('
    jQuery(function($) {
        var $proOnlyModal = $("#proOnlyModal");

        // Move to body so it can be accessible by all buttons
        $proOnlyModal.appendTo("body");

        $(document).on("click", "*[data-pro-only]", function() {
            event.preventDefault();

            var $el = $(this)
                feature_name = $el.data("pro-only");

            if (feature_name) {
                $proOnlyModal.find("em").html(feature_name);
                $proOnlyModal.find(".po-upgrade").hide().end().find(".po-feature").show();
            } else {
                $proOnlyModal.find(".po-feature").hide().end().find(".po-upgrade").show();
            }

            $proOnlyModal.modal("show");
        });
    });
');

JHtml::stylesheet('plg_system_nrframework/proonlymodal.css', ['relative' => true, 'version' => 'auto']);

?>

<div class="pro-only-body text-center">
    <span class="icon-lock"></span>

    <!-- This is shown when we click on a Pro only feature button -->
    <div class="po-feature">
        <h2><?php echo \JText::sprintf('NR_PROFEATURE_HEADER', '') ?></h2>
        <p><?php echo JText::sprintf('NR_PROFEATURE_DESC', '') ?></p>
    </div>

    <!-- This is shown when click on Upgrade to Pro button -->
    <div class="po-upgrade">
        <h2><?php echo \JText::_($extension_name) ?> Pro</h2>
        <p><?php echo JText::sprintf('NR_UPGRADE_TO_PRO_VERSION', \JText::_($extension_name)); ?></p>
    </div>

    <p><a class="btn btn-danger btn-large" href="<?php echo $upgrade_url ?>" target="_blank">
        <?php echo JText::_('NR_UPGRADE_TO_PRO') ?>
    </a></p>
    <div class="pro-only-bonus"><?php echo JText::sprintf('NR_PROFEATURE_DISCOUNT', $extension_name) ?></div>

    <div class="pro-only-footer">
        <div>Pre-Sales questions? <a target="_blank" href="http://www.tassos.gr/contact?topic=Pre-sale Question&extension=<?php echo $extension_name ?>">Ask here</a></div>
        <div>Already purchased Pro? Learn how to <a target="_blank" href="https://www.tassos.gr/kb/general/how-to-upgrade-an-extension-from-free-to-pro">Unlock Pro Features</a></div>
    </div>
</div>