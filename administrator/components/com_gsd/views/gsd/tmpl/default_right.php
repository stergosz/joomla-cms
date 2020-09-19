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

$installedVersion = \NRFramework\Functions::getExtensionVersion("plg_system_gsd", false);
$isPro = \NRFramework\Functions::extensionHasProInstalled("plg_system_gsd");
$FreePro = $isPro ? "Pro" : "Free";
$downloadKey = \NRFramework\Functions::getDownloadKey();

?>

<div class="mod mod-version-check">
    <div class="mod-head"><?php echo JText::_('GSD') . " " . $FreePro ?></div>
    <div class="mod-content">
        <p>
            <?php echo JText::_('GSD_INSTALLED_VERSION') ?>: <?php echo $installedVersion; ?>
        </p>
        
    </div>
</div>

<?php if (!$downloadKey) { ?>
<div class="mod mod-version-check">
    <div class="mod-head">
        <span class="icon-key"></span>
        <?php echo JText::_('NR_DOWNLOAD_KEY_MISSING'); ?>
    </div>
    <div class="mod-content">
        <p><?php echo JText::sprintf("NR_DOWNLOAD_KEY_HOW", JText::_("GSD")); ?></p>
        <a class="btn btn-danger" href="<?php echo JURI::base() ?>index.php?option=com_plugins&view=plugins&filter_search=novarain">
            <?php echo JText::_("NR_DOWNLOAD_KEY_UPDATE")?>
        </a>
        <a class="btn btn-secondary" target="_blank" href="http://www.tassos.gr/downloads">
            <?php echo JText::_("NR_DOWNLOAD_KEY_FIND")?>
        </a>
    </div>
</div>
<?php } ?>

<div class="mod">
    <div class="mod-head">
        <span class="icon-star"></span>
        <?php echo JText::_("NR_LIKE_THIS_EXTENSION") ?>
    </div>
    <div class="mod-content">
        <p>
            <?php echo JText::_("GSD_WRITE_REVIEW_ON_JED") ?>
            <a href="https://extensions.joomla.org/extensions/extension/search-a-indexing/web-search/google-structured-data/" target="_blank"><?php echo JText::_("NR_LEAVE_A_REVIEW") ?></a>
        </p>
    </div>
</div>

<div class="mod">
    <div class="mod-head">
        <span class="icon-heart"></span>
        <?php echo JText::_("GSD_FOLLOW_US") ?>
    </div>
    <div class="mod-content">
        <ul class="socialNav">
            <li><a target="_blank" href="https://www.facebook.com/wwwtassosgr/"><?php echo JText::_("GSD_LIKE_FACEBOOK") ?></a></li>
            <li><a target="_blank" href="https://twitter.com/tassosm"><?php echo JText::_("GSD_FOLLOW_TWITTER") ?></a></li>
            <li><a target="_blank" href="https://plus.google.com/u/0/+TassosMarinos85"><?php echo JText::_("GSD_FOLLOW_GOOGLE_PLUS") ?></a></li>
        </ul>
    </div>
</div>

<div class="mod copy">
    &copy; <?php echo JText::sprintf('NR_COPYRIGHT', date("Y")) ?></p>
</div>