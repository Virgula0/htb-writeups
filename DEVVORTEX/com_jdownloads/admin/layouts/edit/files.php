<?php

defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

    $options = $this->options;
    $form = $displayData->getForm();
    
    $id = $displayData->getForm()->getValue('id');
    if (isset($displayData->selected_filename)){
        $selected_filename = $displayData->selected_filename;
    } else {
        $selected_filename = null; 
    }
    
    if ($options['assigned_file'] != "") { ?>
        <?php echo $form->renderField('url_download'); ?>
        <p>
        <?php echo '<a href="index.php?option=com_jdownloads&amp;task=download.download&amp;id='.$id.'" target="_blank"><img src="'.$options['admin_images_folder'].'download.png'.'" width="18px" height="18px" border="0" style="vertical-align:middle;" alt="'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_DOWNLOAD').'" title="'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_DOWNLOAD').'" /></a>&nbsp;'; ?>
              <input type="button" value="" class="button_rename" title="<?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_RENAME'); ?>" name="activateFileNameField" onClick="editFilename();" >
        <?php echo '<a href="index.php?option=com_jdownloads&amp;task=download.delete&amp;id='.$id.'"><img src="'.$options['admin_images_folder'].'delete.png'.'" width="18px" height="18px" border="0" style="vertical-align:middle;" alt="'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_REMOVE').'" title="'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FILE_REMOVE').'" /></a>'; ?>    
        </p>
        <?php // url_download is not empty, so the external link field must be set to readonly
              $form->setFieldAttribute( 'extern_file', 'readonly', 'true' );
              $form->setFieldAttribute( 'extern_file', 'class="inputbox"', 'class="readonly"' );
    } ?>
    
    
    <?php
    echo $form->renderField('file_upload');
    //echo '<small>'.Text::_('COM_JDOWNLOADS_UPLOAD_MAX_FILESIZE_INFO_TITLE').' '.($options['ini_upload_max_filesize'] / 1024).' KB</small>'; 
	
    echo $form->renderField('spacer'); 
    echo $form->renderField('other_file_id'); ?>
    
    <?php 
    if (isset($selected_filename) && $selected_filename != ''){
        $form->setFieldAttribute( 'update_file', 'default', $selected_filename );
    } ?>    
    
    <?php
    
    echo $form->renderField('update_file');
    echo $form->renderField('use_xml');
    echo $form->renderField('spacer'); 
    
    echo $form->renderField('extern_file');
    echo $form->renderField('extern_site');
    echo $form->renderField('spacer'); 
    echo $form->renderField('mirror_1');
    echo $form->renderField('extern_site_mirror_1');
    echo $form->renderField('mirror_2');
    echo $form->renderField('extern_site_mirror_2');
    echo $form->renderField('spacer'); 
    echo $form->renderField('size');
    echo $form->renderField('file_date');
    if ($options['assigned_file'] != "") { 
        echo $form->renderField('md5_value');
        echo $form->renderField('sha1_value');
    } 
    // echo $form->renderField('spacer');
    
?>