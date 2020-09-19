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

defined('_JEXEC') or die('Restricted Access');

?>

<div class="nr-box nr-box-hr">
    <div class="nr-box-title col-md-4">
        <?php echo JText::_('GSD_SDTT'); ?>
        <div><?php echo JText::_('GSD_SDTT_DESC'); ?></div>
    </div>
    <div class="nr-box-content">
        <form class="gsdtt">
            <input id="url" required="true" type="text" placeholder="http://" value="<?php echo JURI::root(); ?>"/>
            <button class="btn btn-primary" type="submit"><?php echo JText::_('GSD_TEST'); ?></button>
        </form>
    </div>
</div>

<?php 
    JFactory::getDocument()->addScriptDeclaration('
        jQuery(function($) {
            $(".gsdtt").submit(function(event) {
                event.preventDefault();
                var base = "https://search.google.com/structured-data/testing-tool/u/0/#url=";
                var URL  = $(this).find("#url").val();
                window.open(base + URL);
            })
        })
    ');
?>