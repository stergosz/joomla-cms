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

defined('_JEXEC') or die('Restricted access');
extract($displayData);

?>

<div class="cfup-tmpl" style="display:none;">
	<div class="cfup-file">
		<div class="cfup-status"></div>
		<div class="cfup-thumb">
			<img data-dz-thumbnail />
		</div>
		<div class="cfup-details">
			<div class="cfup-name" data-dz-name></div>
			<div class="cfup-error"><div data-dz-errormessage></div></div>
			<div class="cfup-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
		</div>
		<div class="cfup-right">
			<span class="cfup-size" data-dz-size></span>
			<span class="cfup-controls">
				<?php if ($show_download_links) { ?>
					<a href="#" title="<?php echo JText::_('ACF_UPLOAD_VIEW_FILE') ?>" class="icon-eye upload-link" target="_blank"></a>
					<a href="#" title="<?php echo JText::_('ACF_UPLOAD_DOWNLOAD_FILE') ?>" class="icon-download upload-link" download></a>
				<?php } ?>
				<a href="#" title="<?php echo JText::_('ACF_UPLOAD_DELETE_FILE') ?>" class="icon-delete" data-dz-remove></a>
			</span>
		</div>
	</div>
</div>

<div data-id="<?php echo $field_id ?>"
	data-inputname="<?php echo $input_name ?>"
	data-maxfilesize="<?php echo $max_file_size ?>"
	data-maxfiles="<?php echo $limit_files ?>"
	data-acceptedfiles="<?php echo $upload_types ?>"
	data-value='<?php echo ($value) ? json_encode($value) : '' ?>'
	data-baseurl='<?php echo $base_url ?>'
	class="acfupload">
	<div class="dz-message">
		<span><?php echo JText::_('ACF_UPLOAD_DRAG_AND_DROP_FILES') ?></span>
		<span class="acfupload-browse"><?php echo JText::_('ACF_UPLOAD_BROWSE') ?></span>
	</div>
</div>