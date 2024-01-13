<?php

defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

    $options = $this->options;
    $form = $displayData->getForm();

    // We will always display the tab identifier
    $use_tabs = true;
    
    echo '<div class="alert alert-info">'.Text::_('COM_JDOWNLOADS_USERGROUPS_GROUP_CREATION_SETTINGS_NOTE').'</div>';
    
    echo '<div class="row">';
        echo '<div class="col-xl-6">';
    
            echo $form->renderField('uploads_view_upload_icon');
            
            echo $form->renderField('uploads_can_change_category');
            
            echo $form->renderField('uploads_allow_custom_tags');
            
            echo $form->renderField('uploads_auto_publish');
            
            echo $form->renderField('uploads_use_editor');
            
            echo $form->renderField('uploads_use_tabs');
    
        echo '</div>';
        echo '<div class="col-xl-6">';
            
            echo $form->renderField('uploads_default_access_level');    
            
            echo $form->renderField('uploads_allowed_types');
            
            echo $form->renderField('uploads_allowed_preview_types');
            
            echo $form->renderField('uploads_maxfilesize_kb');    

            echo $form->renderField('uploads_max_amount_images'); 
            
            echo $form->renderField('uploads_form_text'); 
            
            echo $form->renderField('spacer1');
             
        echo '</div>';
    echo '</div>';
    
    echo '<div class="alert alert-info" style="margin-top:15px;">'.Text::_('COM_JDOWNLOADS_USERGROUPS_GROUP_CREATION_SETTINGS_DESC').'</div>';    
    
    
    
    /* 
       To get the forms fields data also when we use a unmarked checkbox, we must use the trick with the hidden input field (value="0")
       See also here: http://docs.joomla.org/Talk:Checkbox_form_field_type
       To use a checkbox field only as 'readonly', we must use the hidden value="1" for it
    */
/* Main  
	  title  */     
    
    echo 
    '<div class="row">';
        echo 
        '<div class="col-lg-4">';   
    
            echo '<input type="hidden" name="jform[form_title]" value="1">';
            echo $form->renderField('form_title'); ?> 
	        <!-- alias -->	
            <input type="hidden" name="jform[form_alias]" value="0">                        
            <input type="hidden" name="jform[form_alias_x]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
                <div class="control-label">
                    <?php echo $form->getLabel('form_alias'); ?>
                </div>
                <div class="controls">                 
                    <?php echo $form->getInput('form_alias'); ?>
                    <?php echo $form->getInput('form_alias_x'); ?>
                </div>
            </div>
	        <!-- version -->
            <input type="hidden" name="jform[form_version]" value="0">
            <input type="hidden" name="jform[form_version_x]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
                <div class="control-label">
                    <?php echo $form->getLabel('form_version'); ?>
                </div>
                <div class="controls">                 
                    <?php echo $form->getInput('form_version'); ?>
                    <?php echo $form->getInput('form_version_x'); ?>
                </div>
            </div>
           
	        <!-- Download Language -->
            <input type="hidden" name="jform[form_language]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
                <div class="control-label">
                    <?php echo $form->getLabel('form_language'); ?>
                </div>
                <div class="controls">                 
                    <?php echo $form->getInput('form_language'); ?>
                </div>
            </div>

            <!-- PUBLISHING tab -->
	        
	        <?php if ($use_tabs) { echo '<p class="bg-primary text-white">&nbsp;'.Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_UPLOAD_PUBLISH_TAB').'</p>'; } ?>
	        
	        <!--P1 category -->
            <input type="hidden" name="jform[form_category]" value="1">
            <div class="control-group" style="margin-bottom: 0px;">
                <div class="control-label">
                    <?php echo $form->getLabel('form_category'); ?>
                </div>
                <div class="controls">                 
                    <?php echo $form->getInput('form_category'); ?>
                </div>
            </div> 
   	        <!--P2 access -->   
            <input type="hidden" name="jform[form_access]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_access'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_access'); ?>
               </div>
            </div>
	        <!--P3 Single user access -->   
            <input type="hidden" name="jform[form_user_access]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_user_access'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_user_access'); ?>
               </div>
            </div>
	        <!--P4 Tags -->   
            <input type="hidden" name="jform[form_tags]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_tags'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_tags'); ?>
               </div>
            </div>
		        <!--P5 published -->
            <input type="hidden" name="jform[form_published]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_published'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_published'); ?>
               </div>
           </div>    
	        <!--P6 featured -->   
           <input type="hidden" name="jform[form_featured]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_featured'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_featured'); ?>
               </div>
           </div>
	        <!--P7 created by -->   
           <input type="hidden" name="jform[form_created_id]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_created_id'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_created_id'); ?>
               </div>
           </div>      
	        <!--P8 created date -->   
           <input type="hidden" name="jform[form_creation_date]" value="0">
           <input type="hidden" name="jform[form_creation_date_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_creation_date'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_creation_date'); ?>
                    <?php echo $form->getInput('form_creation_date_x'); ?>
               </div>
           </div>
	        <!--P9 modified date -->   
           <input type="hidden" name="jform[form_modified_date]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_modified_date'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_modified_date'); ?>
               </div>
           </div>
	        <!--P10 updated -->
           <input type="hidden" name="jform[form_update_active]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php 
                        echo $form->getLabel('form_update_active'); 
                    ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_update_active'); ?>
               </div>
           </div>
	        <!--P11 timeframe -->   
           <input type="hidden" name="jform[form_timeframe]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_timeframe'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_timeframe'); ?>
               </div>
           </div>  

	        <!--P12 ordering -->   
           <input type="hidden" name="jform[form_ordering]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_ordering'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_ordering'); ?>
               </div>
           </div>

        </div>        
        <div class="col-lg-4">                       

            <!-- DESCRIPTIONS tab --> 	
	        
	        <?php if ($use_tabs) { echo '<p class="bg-primary text-white">&nbsp;'.Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_UPLOAD_DESCRIPTIONS_TAB').'</p>'; } ?>
            
		    <!--D1 short desc -->
            <input type="hidden" name="jform[form_short_desc]" value="0">
            <input type="hidden" name="jform[form_short_desc_x]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
                <div class="control-label">
                    <?php echo $form->getLabel('form_short_desc'); ?>
                </div>
                <div class="controls">                 
                    <?php echo $form->getInput('form_short_desc'); ?>
                    <?php echo $form->getInput('form_short_desc_x'); ?>
                </div>
            </div> 
	        <!--D2 long desc -->   
            <input type="hidden" name="jform[form_long_desc]" value="0">
            <input type="hidden" name="jform[form_long_desc_x]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
                <div class="control-label">
                    <?php echo $form->getLabel('form_long_desc'); ?>
                </div>
                <div class="controls">                 
                    <?php echo $form->getInput('form_long_desc'); ?>
                    <?php echo $form->getInput('form_long_desc_x'); ?>
                </div>
            </div>       

            <!-- FILES tab -->
	        
	        <?php if ($use_tabs) { echo '<p class="bg-primary text-white">&nbsp;'.Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_UPLOAD_FILES_TAB').'</p>'; }  ?>
            
		    <!-- main file -->
            <input type="hidden" name="jform[form_select_main_file]" value="0">
            <input type="hidden" name="jform[form_select_main_file_x]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
                <div class="control-label">
                    <?php echo $form->getLabel('form_select_main_file'); ?>
                </div>
                <div class="controls">                 
                    <?php echo $form->getInput('form_select_main_file'); ?>
                    <?php echo $form->getInput('form_select_main_file_x'); ?>
                </div>
            </div>    

	        <!-- get file from other Download -->
            <input type="hidden" name="jform[form_select_from_other]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
                <div class="control-label">
                    <?php echo $form->getLabel('form_select_from_other'); ?>
                </div>
                <div class="controls">                 
                    <?php echo $form->getInput('form_select_from_other'); ?>
                </div>
            </div>	
	        <!-- file size -->   
           <input type="hidden" name="jform[form_file_size]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_file_size'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_file_size'); ?>
               </div>
           </div>  
	        <!-- file date -->
           <input type="hidden" name="jform[form_file_date]" value="0">
           <input type="hidden" name="jform[form_file_date_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_file_date'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_file_date'); ?>
                    <?php echo $form->getInput('form_file_date_x'); ?>
               </div>
           </div>  
	        <!-- preview -->
           <input type="hidden" name="jform[form_select_preview_file]" value="0">
           <input type="hidden" name="jform[form_select_preview_file_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_select_preview_file'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_select_preview_file'); ?>
                    <?php echo $form->getInput('form_select_preview_file_x'); ?>
               </div>
           </div> 
	        <!-- external link -->   
           <input type="hidden" name="jform[form_external_file]" value="0">
           <input type="hidden" name="jform[form_external_file_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_external_file'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_external_file'); ?>
                    <?php echo $form->getInput('form_external_file_x'); ?>
               </div>
           </div> 
	        <!-- mirror 1 -->   
           <input type="hidden" name="jform[form_mirror_1]" value="0">
           <input type="hidden" name="jform[form_mirror_1_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_mirror_1'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_mirror_1'); ?>
                    <?php echo $form->getInput('form_mirror_1_x'); ?>
               </div>
           </div> 
	        <!-- mirror 2 -->   
           <input type="hidden" name="jform[form_mirror_2]" value="0">
           <input type="hidden" name="jform[form_mirror_2_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_mirror_2');  ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_mirror_2'); ?>
                    <?php echo $form->getInput('form_mirror_2_x'); ?>
               </div>
            </div>

            <!-- IMAGES tab -->   
	        
	        <?php if ($use_tabs) { echo '<p class="bg-primary text-white">&nbsp;'.Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_UPLOAD_IMAGES_TAB').'</p>'; }  ?>
            
            <input type="hidden" name="jform[form_images]" value="0">
            <input type="hidden" name="jform[form_images_x]" value="0">
            <div class="control-group" style="margin-bottom: 0px;">
                <div class="control-label">
                    <?php echo $form->getLabel('form_images'); ?>
                </div>
                <div class="controls">                 
                    <?php echo $form->getInput('form_images'); ?>
                    <?php echo $form->getInput('form_images_x'); ?>
                </div>
            </div>

        </div>           
        <div class="col-lg-4">                   

         
            <!-- ADDITIONAL tab -->
	        <?php if ($use_tabs) { echo '<p class="bg-primary text-white">&nbsp;'.Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_UPLOAD_ADDITIONAL_TAB').'</p>'; }  ?>
	        
            <!--A1 icon file pic -->
           <input type="hidden" name="jform[form_file_pic]" value="0">
           <input type="hidden" name="jform[form_file_pic_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_file_pic'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_file_pic'); ?>
			        <?php echo $form->getInput('form_file_pic_x'); ?>
               </div>
           </div>
	        <!--A2 password -->   
           <input type="hidden" name="jform[form_password]" value="0">
           <input type="hidden" name="jform[form_password_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_password'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_password'); ?>
                    <?php echo $form->getInput('form_password_x'); ?>
               </div>
           </div>     
	        <!--A3 price -->
           <input type="hidden" name="jform[form_price]" value="0">
           <input type="hidden" name="jform[form_price_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_price'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_price'); ?>
                    <?php echo $form->getInput('form_price_x'); ?>
               </div>
           </div>
	        <!--4A downloadable file language -->
           <input type="hidden" name="jform[form_file_language]" value="0">
           <input type="hidden" name="jform[form_file_language_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_file_language'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_file_language'); ?>
                    <?php echo $form->getInput('form_file_language_x'); ?>
               </div>
           </div>
	        <!--5A operating system -->
           <input type="hidden" name="jform[form_file_system]" value="0">
           <input type="hidden" name="jform[form_file_system_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_file_system'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_file_system'); ?>
                    <?php echo $form->getInput('form_file_system_x'); ?>
               </div>
           </div>   
	        <!--6A licence -->
           <input type="hidden" name="jform[form_license]" value="0">
           <input type="hidden" name="jform[form_license_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_license'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_license'); ?>
                    <?php echo $form->getInput('form_license_x'); ?>
               </div>
           </div> 
	        <!--7A confirm licence -->    
           <input type="hidden" name="jform[form_confirm_license]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_confirm_license'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_confirm_license'); ?>
               </div>
           </div>
	        <!--8A website -->   
           <input type="hidden" name="jform[form_website]" value="0">
           <input type="hidden" name="jform[form_website_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_website'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_website'); ?>
                    <?php echo $form->getInput('form_website_x'); ?>
               </div>
           </div>     
	        <!--9A author -->   
           <input type="hidden" name="jform[form_author_name]" value="0">
           <input type="hidden" name="jform[form_author_name_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_author_name'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_author_name'); ?>
                    <?php echo $form->getInput('form_author_name_x'); ?>
               </div>
           </div>
	        <!--10A author email -->   
           <input type="hidden" name="jform[form_author_mail]" value="0">
           <input type="hidden" name="jform[form_author_mail_x]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_author_mail'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_author_mail'); ?>
                    <?php echo $form->getInput('form_author_mail_x'); ?>
               </div>
           </div>               

	        <!--11A viewed -->
           <input type="hidden" name="jform[form_views]" value="0">

           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_views'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_views'); ?>
               </div>
           </div>   
                       
	        <!--12A downloaded -->   
           <input type="hidden" name="jform[form_downloaded]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_downloaded'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_downloaded'); ?>
               </div>
           </div>    
           
	        <!--13A change log -->
           <input type="hidden" name="jform[form_changelog]" value="0">
           <input type="hidden" name="jform[form_changelog_x]" value="0">

           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_changelog'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_changelog'); ?>
                    <?php echo $form->getInput('form_changelog_x'); ?>
               </div>
           </div>
        
        <!-- META tab  -->   
	        
            <?php if ($use_tabs) { echo '<p class="bg-primary text-white">&nbsp;'.Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_UPLOAD_METADATA_TAB').'</p>'; }  ?>
           
           <input type="hidden" name="jform[form_meta_desc]" value="0">
	        <!-- meta desc -->
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_meta_desc'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_meta_desc'); ?>
               </div>
           </div>  
	        <!-- meta key -->   
           <input type="hidden" name="jform[form_meta_key]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_meta_key'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_meta_key'); ?>
               </div>
           </div>  
	        <!-- robots -->   
           <input type="hidden" name="jform[form_robots]" value="0">
           <div class="control-group" style="margin-bottom: 0px;">
               <div class="control-label">
                    <?php echo $form->getLabel('form_robots'); ?>
               </div>
               <div class="controls">                 
                    <?php echo $form->getInput('form_robots'); ?>
               </div>
           </div>  
   
        </div>           
    </div>           
        
   
       
