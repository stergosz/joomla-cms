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

?> 

<div class="cb-item" data-key="<?php echo $conditionKey ?>">
    <div class="cb-item-toolbar">
        <div class="cb-dropdown">
            <?php echo $toolbar->renderFieldset('base'); ?>
        </div>
        <div class="cb-item-buttons">
            <a class="btn addCondition" href="#"><span class="icon-plus"></span></a>
            <a class="btn removeCondition" href="#"><span class="icon-minus"></span></a>
        </div>
    </div>
    <div class="cb-item-content">
        <?php echo $options; ?>
    </div>
</div>