<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

    $options = $this->options;
    $form = $displayData->getForm();
    
    $id = $displayData->getForm()->getValue('id');
    $selected_filename = $displayData->getForm()->getValue('selected_filename');
    $form = $displayData->getForm();

    if ($options['assigned_preview_file'] != "") { 
        echo $form->renderField('preview_filename'); ?>
        <p>
        <?php echo '<a href="index.php?option=com_jdownloads&amp;task=download.download&amp;id='.$id.'" target="_blank"><img src="'.$options['admin_images_folder'].'download.png'.'" width="18px" height="18px" border="0" style="vertical-align:middle;" alt="'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_DOWNLOAD').'" title="'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_DOWNLOAD').'" /></a>&nbsp;'; ?>
              <input type="button" value="" class="button_rename" title="<?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_RENAME'); ?>" name="activateFilePrevNameField" onClick="editFilenamePreview();" >
        <?php echo '<a href="index.php?option=com_jdownloads&amp;task=download.delete&amp;id='.$id.'&amp;type=prev"><img src="'.$options['admin_images_folder'].'delete.png'.'" width="18px" height="18px" border="0" style="vertical-align:middle;" alt="'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_REMOVE').'" title="'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_REMOVE').'" /></a>'; ?>    
        </p>
        <?php
    } 
	echo $form->renderField('preview_file_upload');
	//echo '<small>'.Text::_('COM_JDOWNLOADS_UPLOAD_MAX_FILESIZE_INFO_TITLE').' '.($options['ini_upload_max_filesize'] / 1024).' KB</small>';
    //echo $form->renderField('spacer'); 

?>