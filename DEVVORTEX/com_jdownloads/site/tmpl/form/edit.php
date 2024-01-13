<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2022 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
 
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
    
    
    $app      = Factory::getApplication();
    $params   = $app->getParams();

	// Check if it is still a Joomla 4 version.
    // Required for reCaptcha processing, as we no longer have reCaptcha v2 available as of Joomla 5.
	$cms_version = new Version();
	$cms_version_5 = $cms_version->isCompatible(5);
    
    /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
    $wa = $this->document->getWebAssetManager();
    $wa->useScript('keepalive')
        ->useScript('form.validate')
        ->useScript('com_jdownloads.form-edit');

    $this->tab_name = 'com-jdownloads-form';
    $this->ignore_fieldsets = array('image-intro', 'image-full', 'jmetadata', 'item_associations');
    $this->useCoreUI = true;
    
    // Path to the mime type image folder (for file symbols) 
    switch ($params->get('selected_file_type_icon_set'))
    {
        case 1:
            $file_pic_folder = 'images/jdownloads/fileimages/';
            break;
        case 2:
            $file_pic_folder = 'images/jdownloads/fileimages/flat_1/';
            break;
        case 3:
            $file_pic_folder = 'images/jdownloads/fileimages/flat_2/';
            break;
    }
 
    // Added to support the Joomla Language Associations
    //  $assoc = Associations::isEnabled();

    $options['ini_upload_max_filesize'] = JDownloadsHelper::return_bytes(ini_get('upload_max_filesize'));
    $options['admin_images_folder'] = URI::root().'administrator/components/com_jdownloads/assets/images/';
    $options['assigned_file'] = $this->item->url_download;
    $options['assigned_preview_file'] = $this->item->preview_filename;
    $options['file_pic_folder'] = $file_pic_folder;
    $options['file_pic_size'] = $params->get('file_pic_size');
    $options['files_editor'] = 1;
    $options['images'] = $this->item->images;
    $options['be_upload_amount_of_pictures'] = $params->get('be_upload_amount_of_pictures');

    $amount_images = 0;
    if (!is_null($options['images'])) {
    	$images = explode("|", $options['images']);
    	$amount_images = count($images);
    }

    // Path to the backend jD images folder 
    $admin_images_folder = URI::root().'administrator/components/com_jdownloads/assets/images/';
    // Path to the layouts folder 
    $basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';

    $this->hiddenFieldsets  = array();

    // Create shortcuts
    $menu_params = $this->state->get('params');
    $rules       = $this->get('user_rules');
    $limits      = $this->get('user_limits');
    
    $this->tab_name = 'com-jdownloads-form';

    if (!is_null($this->item->id)){
        $new = false;  
    } else {
        $new = true;  
    }
    $allowed = $this->item->params->get('access-change') || $this->item->params->get('access-create') || $this->item->params->get('access-edit');
    
    // This checks if the editor config options have ever been saved. If they haven't they will fall back to the original settings.
    $editoroptions = isset($menu_params->show_publishing_options);
    if (!$editoroptions){
	    $menu_params->show_urls_images_frontend = '0';
    }
    ?>

    <script type="text/javascript">
	    Joomla.submitbutton = function(task) {
		    if (task == 'download.cancel' || document.formvalidator.isValid(document.getElementById('adminForm'))) {
			    Joomla.submitform(task);
		    } else {
			    alert('<?php echo $this->escape(htmlspecialchars(Text::_('COM_JDOWNLOADS_VALIDATION_FORM_FAILED'), ENT_QUOTES, 'UTF-8'));?>');
		    }
	    }
        
        // get the selected file name to view the file type pic new
        function getSelectedText( frmName, srcListName ) 
        {
            var form = eval( 'document.' + frmName );
            var srcList = eval( 'form.' + srcListName );

            i = srcList.selectedIndex;
            if (i != null && i > -1) {
                return srcList.options[i].text;
            } else {
                return null;
            }
        }
        
        function editFilename(){
             document.getElementById('jform_url_download').readOnly = false;
             document.getElementById('jform_url_download').focus();
        }

        function editFilenamePreview(){
             document.getElementById('jform_preview_filename').readOnly = false;
             document.getElementById('jform_preview_filename').focus();
        }                   
    </script>
    
<div class="edit jd-item-page<?php echo $this->pageclass_sfx; ?>">

    <?php if ($menu_params->get('show_page_heading')) { ?>
        <div class="page-header">
            <h1>
	            <?php echo $this->escape($menu_params->get('page_heading')); ?>
            </h1>
        </div>
    <?php } ?>

    <?php if ($rules->uploads_form_text){
        echo JDHelper::getOnlyLanguageSubstring($rules->uploads_form_text);
    } ?> 
    
    <form action="<?php echo Route::_('index.php?option=com_jdownloads&a_id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-vertical" enctype="multipart/form-data" accept-charset="utf-8">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo ($rules->uploads_maxfilesize_kb * 1024); ?>" />
            
        <div class="btn-toolbar">         
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('download.save')">
                    <span class="icon-ok"></span><?php echo ' ' . Text::_('COM_JDOWNLOADS_SAVE') ?>
                </button>
			</div>
			<div class="btn-group">
				<button type="button" class="btn btn-success" onclick="Joomla.submitbutton('download.cancel')">
                    <span class="icon-cancel"></span><?php echo ' ' . Text::_('COM_JDOWNLOADS_CANCEL') ?>
                </button>
			</div>
			<div class="btn-group">            
				<?php if (!$new && ($this->item->params->get('access-delete') == true)){ ?>
                    <button type="button" class="btn btn-danger" onclick="Joomla.submitbutton('download.delete')">
                        <span class="icon-delete"></span><?php echo ' ' . Text::_('COM_JDOWNLOADS_DELETE') ?>
                    </button>
                <?php } ?>
			</div>            
		</div>
        
        <fieldset class=jd_fieldset_outer>        
            <p style="margin-bottom: 20px;" class="jd-upload-form-hint"><small><?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_FIELD_INFO') ?></small></p>
                
	        <?php if (!$menu_params->get('show_page_heading')){ ?>
                <legend>
                    <?php if (!$new){ ?> 
                        <?php echo Text::_('COM_JDOWNLOADS_EDIT_DOWNLOAD'); ?>
                    <?php } else { ?>
                        <?php echo Text::_('COM_JDOWNLOADS_ADD_NEW_DOWNLOAD'); ?>
                    <?php } ?>                
                </legend>
            <?php } ?> 
			
            <!-- title  -->              
            <div class="control-group">
                <div class="control-label">
			        <?php echo $this->form->getLabel('title'); ?>
			    </div>
                <div class="controls">
                    <?php echo $this->form->getInput('title'); ?>
                </div>
			</div>
			
            <!-- alias only in create new  -->
            <?php if ($rules->form_alias):?>
                <?php if ($new):?>
			        <div class="control-group">
			            <div class="control-label"> <?php echo $this->form->getLabel('alias'); ?> </div>
			            <div class="controls"> <?php echo $this->form->getInput('alias'); ?> </div>
			        </div>
		        <?php endif; ?>
            <?php endif; ?>                
			
            <!-- version  -->            
            <?php if ($rules->form_version):?>
                <div class="control-group">
                    <div class="control-label"> <?php echo $this->form->getLabel('release'); ?> </div>
                    <div class="controls"> <?php echo $this->form->getInput('release'); ?> </div>
                    </div>
            <?php endif; ?>
                        
			<!-- download language  -->
            <?php if ($rules->form_language):?>                        
                  <?php echo $this->form->renderField('language'); ?>
            <?php endif; ?>
            
        </fieldset>      
       
        <fieldset>
        <?php if ($rules->uploads_use_tabs) { ?>
            
            <?php echo HtmlHelper::_('uitab.startTabSet', $this->tab_name, array('active' => 'publishing')); ?>
            <!-- Publishing TAB -->
            <?php echo HtmlHelper::_('uitab.addTab', $this->tab_name, 'publishing', Text::_('COM_JDOWNLOADS_FORM_LABEL_TAB_PUBLISHING')); ?>
            
        <?php } ?>
			<!--P1 Category  -->                
            <?php echo $this->form->renderField('catid'); ?>
                
			<!--P2 Access  --> 			
			<?php if ($allowed || $new): ?>
                      <?php if ($rules->form_access):?>
                                <?php echo $this->form->renderField('access'); ?>
                      <?php endif; ?>
		    <!--P3 single user access  -->       
                      <?php if ($rules->form_user_access):?>
                                <?php echo $this->form->renderField('user_access'); ?>
                      <?php endif; ?>
                <?php endif; ?>                
			
            <!--P4 tags  -->                                                                     
            <?php if ($rules->form_tags):?>                        
                      <?php echo $this->form->renderField('tags'); ?>
            <?php endif; ?>                            

			<!--P5 Publish state  -->
            <?php if ($allowed || $new): ?> 
                       <?php if ($rules->form_published):?>
                                 <?php echo $this->form->renderField('published'); ?>
                       <?php endif; ?>            
            <?php endif; ?>
                    
			<!--P6 featured  -->                    
            <?php if ($allowed || $new): ?>
                      <?php if ($rules->form_featured):?>
                                <?php echo $this->form->renderField('featured'); ?>
                      <?php endif; ?>            
            <?php endif; ?>                         
                    
			<!--P7 created by  -->			
            <?php if ($rules->form_created_id):?>                        
                      <?php echo $this->form->renderField('created_by'); ?>
            <?php endif; ?>  
			
            <!--P8 created date -->                             
            <?php if ($rules->form_creation_date):?>
                      <?php echo $this->form->renderField('created'); ?>
            <?php endif; ?>            
			
            <!--P9 modified  only in edit -->
			<?php if (!$new): ?>
                    <?php if ($rules->form_modified_date):?>
                              <?php echo $this->form->renderField('modified'); ?>
                    <?php endif; ?>            
                    
			        <!--P10 set updated flag only in edit -->                        
				    <?php if ($rules->form_update_active):?>
					          <div class="control-group">
						          <div class="control-label"><?php echo $this->form->getLabel('update_active'); ?> </div>
						          <div class="controls"><?php echo $this->form->getInput('update_active'); ?> </div>
					          </div>
				    <?php endif; ?>
			<?php endif; ?>
			
            <!--P11 publishing timeframe  -->			
            <?php if ($allowed || $new): ?>
                      <?php if ($rules->form_timeframe):?>
                                <?php echo $this->form->renderField('publish_up'); ?>
                                <?php echo $this->form->renderField('publish_down'); ?>
                      <?php endif; ?>            
            <?php endif; ?>
			
            <!--P11-2 notes: Cannot currently be configured in the User Group settings and is therefore NOT currently displayed. -->
            <?php if (isset($rules->form_notes)): ?> 
                      <?php if ($rules->form_notes): ?>
                          <?php echo $this->form->renderField('notes'); ?>
                      <?php endif; ?>
            <?php endif; ?>
            
            <!--P12 ordering  -->
            <?php if ($rules->form_ordering):?> 
                <?php if ($new){?>
                    <div class="form-note">
                        <p><?php echo Text::_('COM_JDOWNLOADS_FORM_ORDERING'); ?></p>
                    </div>
                <?php } else { ?>
                          <?php echo $this->form->renderField('ordering'); ?>
                <?php } ?>
            <?php endif; ?>
            
            <!-- Required for reCaptcha processing, as we no longer have reCaptcha v2 available as of Joomla 5. -->
			<?php if ($rules->view_captcha && $cms_version_5) : ?>
                      <?php echo $this->form->renderField('captcha'); ?>
            <?php endif; ?>
        
            <?php if ($rules->uploads_use_tabs) { ?>    
                      <?php echo HtmlHelper::_('uitab.endTab'); ?>
            <?php } ?>

            <!-- Descriptions TAB -->                
            <?php if ($rules->uploads_use_tabs) { ?>    
                      <?php if ($rules->form_short_desc || $rules->form_long_desc){ ?> 
                                <?php echo HtmlHelper::_('uitab.addTab', $this->tab_name, 'descriptions', Text::_('COM_JDOWNLOADS_FORM_LABEL_DESCRIPTIONS')); ?>
                      <?php } ?>
            <?php } ?>
			
            <!--D1 short desc  -->           
            <?php if ($rules->form_short_desc){
                      if ($rules->uploads_use_editor){ ?>
                              <label><?php echo '<b>'.$this->form->getLabel('description').'</b>'; ?></label>
                              <?php echo $this->form->getInput('description'); ?>
                              <div style="clear:both"></div>
                              <br />
                      <?php } else { ?> 
                              <div class="control-group">
                                  <div class="control-label"> <?php echo $this->form->getLabel('description'); ?> </div>
                                  <div class="controls"> <?php echo $this->form->getInput('description'); ?> </div>
                              </div>
                      <?php } ?>                          
            <?php } ?>
                    
			<!--D2 long desc  -->                    
            <?php if ($rules->form_long_desc){ 
                      if ($rules->uploads_use_editor){ ?>
                          <label><?php echo '<b>'.$this->form->getLabel('description_long').'</b>'; ?></label>
                          <?php echo $this->form->getInput('description_long'); ?>
                          <div style="clear:both"></div>
                          <br />
                      <?php } else { ?> 
                          <div class="control-group">
                              <div class="control-label"> <?php echo $this->form->getLabel('description_long'); ?> </div>
                              <div class="controls"> <?php echo $this->form->getInput('description_long'); ?> </div>
                          </div>    
                      <?php } ?>                          
            <?php } ?>
            
            <?php if ($rules->uploads_use_tabs) { ?>    
                  <?php if ($rules->form_short_desc || $rules->form_long_desc){ ?> 
                            <?php  echo HtmlHelper::_('uitab.endTab'); ?>
                  <?php } ?>                
            <?php } ?>
            
            <!-- Files TAB -->            
            <?php if ($rules->uploads_use_tabs) { ?>   
                      <?php echo HtmlHelper::_('uitab.addTab', $this->tab_name, 'files', Text::_('COM_JDOWNLOADS_FORM_LABEL_TAB_FILES')); ?>
            <?php } ?>
            
            <!-- F1A Edit Only when file already assigned -->
            <?php if ($rules->form_select_main_file){
                        if ($this->item->url_download != ''){ ?> 
                            <?php echo $this->form->renderField('url_download'); ?>
                            <span><input type="button" value="" class="button_rename" style="margin-bottom:12px;" title="<?php echo Text::_('COM_JDOWNLOADS_FORM_RENAME_FILE_LABEL'); ?>" name="activateFileNameField" onClick="editFilename();" >
                                <?php echo ' <a href="index.php?option=com_jdownloads&amp;task=download.deletefile&amp;id='.$this->item->id.'"><img src="'.URI::root().'components/com_jdownloads/assets/images/'.'delete.png'.'" width="18px" height="18px" class="jd_edit_button_delete" style="vertical-align:middle;border:0px;margin-bottom:12px;" alt="'.Text::_('COM_JDOWNLOADS_FORM_DELETE_FILE_LABEL').'" title="'.Text::_('COM_JDOWNLOADS_FORM_DELETE_FILE_LABEL').'" /></a>'; ?>
                            </span>
                        <?php }  ?>
            <?php }  ?>
                   
            <!-- F1B Main File only ask if new download or download with no file already present-->                   
            <?php if ($rules->form_select_main_file){
                    if ($new||$this->item->url_download == ''){ ?> 
                       <?php echo $this->form->renderField('file_upload'); ?>
                       <?php echo '<small><b>'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_ALLOWED_FILETYPE').'</b> '.str_replace(',', ', ', $rules->uploads_allowed_types).'</small><br />'; ?>
                       <?php echo '<small><b>'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_ALLOWED_MAX_SIZE').'</b> '.$rules->uploads_maxfilesize_kb.' KB</small>'; ?>
					<?php } ?>
			<?php } ?>
            
			<!--F2 file size  -->
            <?php if ($rules->form_file_size):?>             
                <?php echo $this->form->renderField('size'); ?>
            <?php endif; ?>
			
            <!--F3 file date  -->                                
            <?php if ($rules->form_file_date):?> 
                <?php echo $this->form->renderField('file_date'); ?>
            <?php endif; ?>
			                    
            <hr class="jd_uploadform_line">
			                    
            <!--  F3A Select from other Download. Only ask if new download or download with no file already present  -->
   			<?php if ($rules->form_select_from_other){
                        if ($new||$this->item->url_download == ''){ ?> 
			                <?php echo $this->form->renderField('other_file_id'); ?>
                        <?php } else { ?> 
                            <?php echo $this->form->renderField('other_file_id'); ?>
			            <?php } ?>
                        <hr class="jd_uploadform_line">
			<?php } ?>            
			
            <!--F4A preview file  already selected -->                    
            <?php if ($rules->form_select_preview_file && $this->item->preview_filename != ''):?>
                	<?php echo $this->form->renderField('preview_filename'); ?>
                    <span><input type="button" value="" class="button_rename" title="<?php echo Text::_('COM_JDOWNLOADS_FORM_RENAME_FILE_LABEL'); ?>" name="activateFilePrevNameField" onClick="editFilenamePreview();" >
                        <?php echo ' <a href="index.php?option=com_jdownloads&amp;task=download.deletefile&amp;id='.$this->item->id.'&amp;type=prev"><img src="'.URI::root().'components/com_jdownloads/assets/images/'.'delete.png'.'" width="18px" height="18px" style="vertical-align:middle;border:0px;" alt="'.Text::_('COM_JDOWNLOADS_FORM_DELETE_FILE_LABEL').'" title="'.Text::_('COM_JDOWNLOADS_FORM_DELETE_FILE_LABEL').'" /></a>'; ?>
                    </span>                    
            <?php endif;?>            
                    
			<!--F4A preview file  not yet selected -->                    
            <?php if ($rules->form_select_preview_file && $this->item->preview_filename == ''):?>            
                        <?php echo $this->form->renderField('preview_file_upload'); ?>
                        <?php echo '<small><b>'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_ALLOWED_FILETYPE').'</b> '.str_replace(',', ', ', $rules->uploads_allowed_preview_types).'</small><br />'; ?>
                        <?php echo '<small><b>'.Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_ALLOWED_MAX_SIZE').'</b> '.$rules->uploads_maxfilesize_kb.' KB</small>'; ?>
                        <hr class="jd_uploadform_line">
            <?php endif;?>                                        
            
            <!-- F5 External File -->			
			<?php if ($rules->form_external_file):?>
						<div style = "clear:both; margin-top: 12px;"></div>
			            <?php // echo "<div class=jd_uploadform_text>&nbsp;".Text::_('COM_JDOWNLOADS_UPLOAD_EXTERNAL_FILE_LINE')."</div>" ; ?>
						<div style = "clear:both;"></div>
                        <?php echo $this->form->renderField('extern_file'); ?>
                        <?php echo $this->form->renderField('extern_site'); ?>
                        <hr class="jd_uploadform_line">
            <?php endif; ?>        
            
            <!-- F6 Mirror 1 -->			
            <?php if ($rules->form_mirror_1):?>
						<div style = "clear:both;"></div>
			            <?php // echo "<div class=jd_uploadform_text>&nbsp;".Text::_('COM_JDOWNLOADS_UPLOAD_MIRROR_1_LINE')."</div>" ; ?>
						<div style = "clear:both;"></div>        
                        <?php echo $this->form->renderField('mirror_1'); ?>
                        <?php echo $this->form->renderField('extern_site_mirror_1'); ?>
                        <hr class=jd_uploadform_line>
            <?php endif; ?>                    
            
            <!-- F7 Mirror 2 -->			
			<?php if ($rules->form_mirror_2):?>
						<div style = "clear:both;"></div>
			            <?php // echo "<div class=jd_uploadform_text>&nbsp;".Text::_('COM_JDOWNLOADS_UPLOAD_MIRROR_2_LINE')."</div>" ; ?>
						<div style = "clear:both;"></div> 

                        <?php echo $this->form->renderField('mirror_2'); ?>
                        <?php echo $this->form->renderField('extern_site_mirror_2'); ?>
            <?php endif; ?> 
            
            <?php if ($rules->uploads_use_tabs) { ?>    
                      <?php echo HtmlHelper::_('uitab.endTab'); ?>
            <?php } ?>
            
            <!-- Images TAB -->            
            <?php if ($rules->uploads_use_tabs && $rules->form_images) { ?>    
                      <?php echo HtmlHelper::_('uitab.addTab', $this->tab_name, 'images', Text::_('COM_JDOWNLOADS_FORM_LABEL_TAB_IMAGES')); ?>
            <?php } ?>
            
          <?php if ($rules->form_images){ ?>
        
                <?php $image_id = 0; ?>

                <?php if ($this->item->images){ ?>    
                        <table class="admintable" style="width:100%; border:0px; border-spacing: 10px;">
                        <tr><td><?php if ($this->item->images) echo '<div class="alert alert-info">'.Text::_('COM_JDOWNLOADS_THUMBNAIL_LIST_INFO').'</div>'; ?></td></tr>
                        <tr><td style="vertical-align:top">
                        <?php 
                        // display the selected images
                        if ($this->item->images){
                            $images = array();
                            $images = explode("|", $this->item->images);
                            echo '<ul style="list-style-type: none; margin: 0px 0 0 0; padding: 0; width: 350px; overflow: visible;" id="displayimages">';
                            foreach ($images as $image){
                                 $image_id ++;
                                 echo '<li id="'.$image.'">';
                                 echo '<input style="position:relative;
                                        left: 7px;
                                        top: 15px;
                                        vertical-align: top;
                                        z-index: 1;
                                        margin: 0;
                                        padding: 0;" type="checkbox" name="keep_image['.$image_id.']" value="'.$image.'" checked />';
                                 echo '<a href="'.URI::root().'images/jdownloads/screenshots/'.$image.'" target="_blank">';
                                 
                                 echo '<img style="position:relative;border:1px solid black; max-width:100px; max-height:100px;" align="middle" src="'.URI::root().'images/jdownloads/screenshots/thumbnails/'.$image.'" alt="'.substr($image,0,-4).'" title="'.$image.'"/>';
                                 echo '</a>';
                                 echo '</li>';                         
                            }
                            echo '</ul>'; 
                        }
                        ?>
                        </td></tr>
                        </table>                
                <?php } ?>
                             
                <?php if ($image_id < (int)$rules->uploads_max_amount_images){ ?>
                            <label>
                                <?php  echo HtmlHelper::_('tooltip', Text::_('COM_JDOWNLOADS_FORM_IMAGE_UPLOAD_DESC'), Text::_('COM_JDOWNLOADS_FORM_IMAGE_UPLOAD_LABEL').' '.Text::sprintf('COM_JDOWNLOADS_LIMIT_IMAGES_MSG', $rules->uploads_max_amount_images), '', Text::_('COM_JDOWNLOADS_FORM_IMAGE_UPLOAD_LABEL').' '.Text::sprintf('COM_JDOWNLOADS_LIMIT_IMAGES_MSG', $rules->uploads_max_amount_images) ); ?>
                            </label>
                            <table id="files_table" class="admintable" style="border:0px; border-spacing: 10px;">
                                <tr id="new_file_row">
                                    <td class=""><input type="file" name="file_upload_thumb[0]" id="file_upload_thumb[0]" class="form-control valid" size="50" aria-invalid="false" accept="image/gif,image/jpeg,image/jpg,image/png" onchange="add_new_image_file(this)" />
                                    </td>
                                </tr>
                            </table> 
                        <?php } else { 
                                // limit is reached - display an info message 
                                echo '<p>'.Text::_('COM_JDOWNLOADS_LIMIT_IMAGES_REACHED_MSG').'</p>'; 
                              }?>        
              <?php } ?>                
            
            <?php if ($rules->uploads_use_tabs && $rules->form_images) { ?>   
                      <?php echo HtmlHelper::_('uitab.endTab'); ?>
            <?php } ?>
            
            <!-- Additional TAB --> 
            <?php
            // The tab shall only be displayed if at least one field is activated for this purpose.
            if ($rules->form_file_pic || $rules->form_password || $rules->form_price || $rules->form_file_language || $rules->form_file_system || $rules->form_license || $rules->form_website 
                || $rules->form_author_name || $rules->form_author_mail || $rules->form_views || $rules->form_downloaded || $rules->form_changelog)
            {
                $additional_has_fields = true;
            } else {
                $additional_has_fields = false;
            }    
            ?>
            
            <?php if ($rules->uploads_use_tabs && $additional_has_fields) { ?>   
                      <?php echo HtmlHelper::_('uitab.addTab', $this->tab_name, 'additional', Text::_('COM_JDOWNLOADS_FORM_LABEL_ADDITIONAL')); ?>
            <?php } ?>

            <!--A1 Symbol pic -->
            <?php if ($rules->form_file_pic):?> 
                <?php echo $this->form->renderField('file_pic'); ?>
                        <div class="control-group"> 
                            <!--
                            <?php if ($this->item->file_pic != ''){ ?>
                                <img src="<?php echo URI::root().'images/jdownloads/fileimages/'.$this->item->file_pic; ?>" name="imagelib" alt="<?php echo $this->item->file_pic; ?>" />
                            <?php } else { ?>
                                 <img src="<?php echo URI::root().'images/jdownloads/fileimages/'.$params->get('file_pic_default_filename'); ?>" name="imagelib" alt="<?php echo $params->get('file_pic_default_filename'); ?>" />
                            <?php } ?>
                            -->   
                            <script type="text/javascript">
                                if (document.adminForm.file_pic.options.value != ''){
                                    jsimg = "<?php echo URI::root().$file_pic_folder; ?>" + getSelectedText( 'adminForm', 'file_pic' );
                                } else {
                                    jsimg = '';
                                }
                                document.write('<img src="' + jsimg + '" name="imagelib" width="<?php echo $params->get('file_pic_size'); ?>" height="<?php echo $params->get('file_pic_size'); ?>" border="1" class="" alt="<?php echo Text::_('COM_JDOWNLOADS_FORM_NO_SYMBOL_TEXT'); ?>" />');
                            </script>                        
                        </div>
                        <div style="clear:both"></div>
            <?php endif; ?>
          
            <!--A2 password -->
            <?php if ($rules->form_password):?> 
                 <?php echo $this->form->renderField('password'); ?>
            <?php endif; ?>
          
            <!--A3 price -->
            <?php if ($rules->form_price):?> 
                 <?php echo $this->form->renderField('price'); ?>
            <?php endif; ?>

            <?php if ($rules->form_file_language || $rules->form_file_system):?>
                      <!--A4 Downloadable file language -->
                      <?php if ($rules->form_file_language):?>
                                <div class="control-group">
                                    <div class="control-label"> <?php echo $this->form->getLabel('file_language'); ?> </div>
                                    <div class="controls"> <?php echo $this->form->getInput('file_language'); ?></div>    
                                </div>    
                      <?php endif; ?>
                      
                      <!-- Operating System -->
                      <?php if ($rules->form_file_system):?>            
                                <div class="control-group">
                                    <div class="control-label"> <?php echo $this->form->getLabel('system'); ?> </div>
                                    <div class="controls"> <?php echo $this->form->getInput('system'); ?> </div>
                                </div>
                      <?php endif; ?>
            <?php endif; ?>

            <!-- Licence -->    
            <?php if ($rules->form_license):?>             
                      <div class="control-group">
                          <div class="control-label"><?php echo $this->form->getLabel('license'); ?> </div>
                          <div class="controls"><?php echo $this->form->getInput('license'); ?> </div>
                      </div>                        
            <?php endif; ?>

            <!-- Confirm Licence -->
            <?php if ($rules->form_confirm_license):?>                         
                      <div class="control-group">
                          <div class="control-label"><?php echo $this->form->getLabel('license_agree'); ?> </div>
                          <div class="controls"><?php echo $this->form->getInput('license_agree'); ?> </div>
                      </div>                        
            <?php endif; ?>      

            <?php if ($rules->form_website):?> 
                      <?php echo $this->form->renderField('url_home'); ?>
            <?php endif; ?>

            <?php if ($rules->form_author_name):?> 
                      <?php echo $this->form->renderField('author'); ?>
            <?php endif; ?> 

            <?php if ($rules->form_author_mail):?> 
                      <?php echo $this->form->renderField('url_author'); ?>
            <?php endif; ?>

            <!-- only show number of views and times downloaded if editing-->                    
            <?php if (!$new):?>
                <?php if ($rules->form_views):?>                
                    <?php echo $this->form->renderField('views'); ?>
                <?php endif; ?>            
                <?php if ($rules->form_downloaded):?>            
                    <?php echo $this->form->renderField('downloads'); ?>
                <?php endif; ?>            
            <?php endif; ?>
        
            <?php if ($rules->form_changelog){       
                      if ($rules->uploads_use_editor){ ?>
                          <div class="control-group" style="margin-top: 10px;">
                              <div class="control-label"> <?php echo '<b>'.$this->form->getLabel('changelog').'</b>'; ?> </div>
                              <div class="controls"> <?php echo $this->form->getInput('changelog'); ?> </div>
                          </div>
                          <div style="clear:both"></div>
                      <?php } else { ?>
                               <div class="control-group">
                                   <div class="control-label"> <?php echo $this->form->getLabel('changelog'); ?> </div>
                                   <div class="controls"> <?php echo $this->form->getInput('changelog'); ?> </div>
                               </div>          
                      <?php } ?>
            <?php } ?>
                    
            <?php if ($rules->uploads_use_tabs && $additional_has_fields) { ?>   
                      <?php echo HtmlHelper::_('uitab.endTab'); ?>
            <?php } ?>            
        
            <!-- CustomFields TAB -->              
            <?php if (ComponentHelper::isEnabled('com_fields') && $params->get('custom_fields_enable') == 1 && $this->jd_custom_fields){ ?>
                     <?php // Custom fields are always be displayed in tabs! ?>                 
	                 <?php if ($rules->uploads_use_tabs) { ?>
	                           <?php // echo HtmlHelper::_('uitab.addTab', $this->tab_name, 'fields', Text::_('COM_JDOWNLOADS_TAB_CUSTOM_FIELDS')); ?>
	                 <?php } else { ?>
	                           <?php echo HtmlHelper::_('uitab.startTabSet', $this->tab_name, array('active' => '')); ?>
	                 <?php } ?>
                     
                     <?php $this->ignore_fieldsets = array('general', 'info', 'detail', 'jmetadata', 'item_associations'); ?>
                     <?php echo LayoutHelper::render('joomla.edit.params', $this); ?>
                     
	                 <?php if ($rules->uploads_use_tabs) { ?>
	                           <?php // echo HtmlHelper::_('uitab.endTab'); ?>
	                 <?php } else { ?>
	                           <?php echo HtmlHelper::_('uitab.endTabSet', $this->tab_name); ?>
	                 <?php } ?>
            <?php } ?>
                 
            <!-- Metadata TAB -->      
            <?php if ($rules->form_meta_desc || $rules->form_meta_key || $rules->form_robots){ ?> 
                
                <?php if ($rules->uploads_use_tabs) { ?>   
                          <?php echo HtmlHelper::_('uitab.addTab', $this->tab_name, 'metadata', Text::_('COM_JDOWNLOADS_FORM_LABEL_META_DATA')); ?>   
                <?php } ?>            

                <?php if ($rules->form_meta_desc):?>
                          <?php echo $this->form->renderField('metadesc'); ?>
                <?php endif; ?>
                
                <?php if ($rules->form_meta_key):?>
                          <?php echo $this->form->renderField('metakey'); ?>
                <?php endif; ?>

                <?php if ($rules->form_robots):?>
                          <?php echo $this->form->renderField('robots'); ?>
                <?php endif; ?>            

                <?php if ($rules->uploads_use_tabs) { ?>   
                          <?php echo HtmlHelper::_('uitab.endTab'); ?>
                          <?php echo HtmlHelper::_('uitab.endTabSet', $this->tab_name); ?>
                <?php } ?>
            <?php } ?>
    
        </fieldset>        
        
		<!-- Add the buttons also in the footer if not using TABS -->
        <?php // if (!$rules->uploads_use_tabs) { ?>    
            <div class="btn-toolbar">         
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('download.save')">
                        <span class="icon-ok"></span><?php echo ' ' . Text::_('COM_JDOWNLOADS_SAVE') ?>
                    </button>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-success" onclick="Joomla.submitbutton('download.cancel')">
                        <span class="icon-cancel"></span><?php echo ' ' . Text::_('COM_JDOWNLOADS_CANCEL') ?>
                    </button>
                </div>
                <div class="btn-group">            
                    <?php if (!$new && ($this->item->params->get('access-delete') == true)){ ?>
                        <button type="button" class="btn btn-danger" onclick="Joomla.submitbutton('download.delete')">
                            <span class="icon-delete"></span><?php echo ' ' . Text::_('COM_JDOWNLOADS_DELETE') ?>
                        </button>
                    <?php } ?>
                </div>            
            </div>
        <?php // } ?>        

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="view" value="form" />
        <input type="hidden" name="image_file_count" id="image_file_count" value="0" />         
        <input type="hidden" name="cat_dir_org" value="<?php echo $this->item->catid; ?>" />
        <input type="hidden" name="sum_listed_images" id="sum_listed_images" value="<?php echo (int)$image_id; ?>" />
        <input type="hidden" name="max_sum_images" id="max_sum_images" value="<?php echo (int)$rules->uploads_max_amount_images; ?>" /> 
        <input type="hidden" name="filename" value="<?php echo $this->item->url_download; ?>" />        
        <input type="hidden" name="modified_date_old" value="<?php echo $this->item->modified; ?>" />
        <input type="hidden" name="submitted_by" value="<?php echo $this->item->submitted_by; ?>" />
        <input type="hidden" name="set_aup_points" value="<?php echo $this->item->set_aup_points; ?>" />
        <input type="hidden" name="filename_org" value="<?php echo $this->item->url_download; ?>" />          
        <input type="hidden" name="preview_filename_org" value="<?php echo $this->item->preview_filename; ?>" />
        <input type="hidden" name="return" value="<?php echo $this->return_page;?>" /> 

        <?php echo HtmlHelper::_('form.token'); ?>
    </form>
            
</div>
