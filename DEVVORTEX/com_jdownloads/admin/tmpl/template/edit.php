<?php 

defined('_JEXEC') or die('Restricted access'); 

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

    HTMLHelper::_('behavior.multiselect');

    // Load required asset files
    $wa = $this->document->getWebAssetManager();
    $wa->useScript('keepalive')
       ->useScript('form.validate');

    $app       = Factory::getApplication();
    $jinput    = $app->input;
    $type      = $jinput->get('type'); 

    if ($this->item->template_typ == NULL ){
        // add a new layout - so we need the layout type number 
        $session = Factory::getSession();
        $this->item->template_typ = (int) $session->get( 'jd_tmpl_type', '' );
    }

    // Path to the layouts folder 
    $basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';

    $this->hiddenFieldsets  = array();

?>

<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&view=template&layout=edit&id='.(int) $this->item->id.'&type='.(int)$this->item->template_typ); ?>" method="post" name="adminForm" id="template-form" accept-charset="utf-8" class="form-validate">
    
    <?php echo LayoutHelper::render('edit.title_type', $this, $basePath); ?>
    
    <div>
        <?php echo HtmlHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>
        <?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_TABTEXT_EDIT_MAIN')); ?>
            <div class="row">
                <!-- View first the button to give the user help information when requested -->
                <div class="container-fluid" style="margin-bottom: 15px;">
                    <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#hint"><?php echo Text::_('COM_JDOWNLOADS_HELP_INFORMATIONS'); ?></button>
                    <div id="hint" class="collapse hide">
                        <fieldset class="alert alert-info">
                            <ul class="adminformlist">
                                <li>
                                <?php if($this->item->template_typ == 1) {      // Categories
                                        ?>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CATS_DESC'); ?></p>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CATS_DESC3'); ?></p>
                                <?php } ?>    

                                <?php if($this->item->template_typ == 2) {      // Files/Downloads
                                        ?>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_DESC'); ?></p>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FILES_DESC2'); ?></p>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_INFO_LIGHTBOX'); ?></p>
                                <?php } ?>                 

                                <?php if($this->item->template_typ == 3) {    // Summary
                                        ?>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_FINAL_DESC'); ?></p>
                                <?php } ?>                 
                                
                                <?php if($this->item->template_typ == 4) {      // Category
                                        ?>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CAT_DESC'); ?></p>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CATS_DESC2'); ?></p> 
                                <?php } ?>                 
                                
                                <?php if($this->item->template_typ == 5) {      // Details View
                                        ?>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_DETAILS_DESC'); ?></p>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_DETAILS_DESC_FOR_TABS'); ?></p>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_INFO_LIGHTBOX'); ?></p> 
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_INFO_LIGHTBOX2'); ?></p>                    
                                <?php } ?>                 
                                
                                <?php if($this->item->template_typ == 6) {      // Upload Form
                                        ?>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_UPLOADS_DESC'); ?></p>
                                <?php } ?>                 
                                
                                <?php if($this->item->template_typ == 7) {      // Search Result
                                        ?>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_SEARCH_DESC'); ?></p>
                                <?php } ?>
                                
                                <?php if($this->item->template_typ == 8) {      // SubCategories
                                        ?>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_SUB_CATS_DESC'); ?></p>
                                        <p><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_CATS_DESC3'); ?></p>
                                <?php } ?>                 
                                
                                <?php echo '<p>'.Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_TAG_TIP').'</p>'; ?>
                                
                                </li>   
                            </ul>
                        </fieldset>    
                    </div>
                </div>
                
                <div class="col-lg-9">
                        <?php if($this->item->template_typ == 1 || $this->item->template_typ == 2 || $this->item->template_typ == 4 || $this->item->template_typ == 8) { ?> 
                            <div style="margin-bottom: 10px;">
                                <?php echo $this->form->getLabel('template_before_text'); ?>
                                <div class="clr"></div> 
                                <?php echo $this->form->getInput('template_before_text'); ?>       
                            </div>
                            
                        <?php } ?>
                        
                        <div style="margin-bottom: 10px;">
                            <?php
                                if ($this->item->template_typ == 7){
                                    $this->form->setFieldAttribute( 'template_text', 'description', '' ); 
                                } 
                                echo $this->form->getLabel('template_text'); 
                            ?>
                            <div class="clr"></div> 
                            <?php echo $this->form->getInput('template_text'); ?>       
                        </div>                

                        <?php if($this->item->template_typ == 1 || $this->item->template_typ == 2 || $this->item->template_typ == 4 || $this->item->template_typ == 8) { ?> 
                            <div style="margin-bottom: 10px;">
                                <?php echo $this->form->getLabel('template_after_text'); ?>
                                <div class="clr"></div>
                                <?php echo $this->form->getInput('template_after_text'); ?>       
                            </div>            
                        <?php } ?>
                </div>
                
                <div class="col-lg-3">
                
                    <!-- Add the right panel with basicly data: ID, locked, template_active, cols, note, symbol_off, checkbox_off  -->
                
                    <div class="bg-white px-3">
                        <?php echo LayoutHelper::render('edit.global_template', $this, $basePath); ?>
                    </div>
                </div>
                
            </div>
        <?php echo HtmlHelper::_('uitab.endTab'); ?>
        
        <?php if ($this->item->template_typ != 8){ ?>
            <?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'header', Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_TABTEXT_EDIT_HEADER')); ?>
                <div class="row">
                    <!-- View first the button to give the user help information when requested -->
                    <div class="container-fluid" style="margin-bottom: 15px;">
                        <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#hint2"><?php echo Text::_('COM_JDOWNLOADS_HELP_INFORMATIONS'); ?></button>
                        <div id="hint2" class="collapse hide">
                            <fieldset class="alert alert-info">
                                <ul class="adminformlist">
                                    <li>
                                        <?php echo '<p><b>'.Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_HEADER_TEXT').'</b>:<br />';
                                            echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_HEADER_DESC').'</p>'; ?>
                                                 
                                        <?php echo '<p><b>'.Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_SUBHEADER_TEXT').'</b>:<br />';
                                            switch ($this->item->template_typ){
                                                case 1:  //cats
                                                case 2:  //files
                                                case 4:  //cat
                                                    echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_SUBHEADER_DESC').'</p>';
                                                    break;
                                                case 5:  //details                                   
                                                    echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_SUBHEADER_DETAIL_DESC').'</p>';
                                                    break;                                     
                                                case 3:  //summary                                   
                                                    echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_SUBHEADER_SUMMARY_DESC').'</p>';
                                                    break;
                                                case 6:  //upload form                                   
                                                    echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_SUBHEADER_DESC').'</p>';
                                                    break;                                    
                                                case 7:  //search results                                   
                                                    echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_SUBHEADER_SEARCH_DESC').'</p>';
                                                    break;                                    
                                            } ?>                       
                                        <?php echo '<p><b>'.Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_FOOTER_TEXT').'</b>:<br />';
                                            switch ($this->item->template_typ) {
                                                case 1:  //cats
                                                case 2:  //files
                                                case 4:  //cat
                                                    echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_FOOTER_FILES_CATS_DESC').'</p>';
                                                    break;
                                                default:  //other types                                   
                                                    echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_FOOTER_OTHER_DESC').'</p>';
                                                    break;
                                            } ?>
                                    </li>   
                                </ul>
                            </fieldset>    
                        </div>
                    </div>    

                    <!-- View the header text fields -->                    
                    <div class="col-lg-12">
                        <div style="margin-bottom: 10px;">
                            <?php echo $this->form->getLabel('template_header_text'); ?>
                            <div class="clr"></div> 
                            <?php echo $this->form->getInput('template_header_text'); ?>       
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <?php 
                                if ($this->item->template_typ == 7){
                                    $this->form->setFieldAttribute( 'template_subheader_text', 'description', Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_SUBHEADER_SEARCH_DESC') ); 
                                }
                            
                                echo $this->form->getLabel('template_subheader_text'); 
                            ?>
                            
                            <div class="clr"></div>
                            <?php echo $this->form->getInput('template_subheader_text'); ?>       
                        </div>                         
                        
                        <div style="margin-bottom: 10px;">
                            <?php 
                                if ($this->item->template_typ == 7){
                                    $this->form->setFieldAttribute( 'template_footer_text', 'description', Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_FOOTER_OTHER_DESC') );
                                }

                                echo $this->form->getLabel('template_footer_text'); 
                            ?>
                                
                            <div class="clr"></div>
                            <?php echo $this->form->getInput('template_footer_text'); ?>       
                        </div>
                    </div>
                </div>
            <?php echo HtmlHelper::_('uitab.endTab'); ?>                        
            
        <?php } ?>
            
            <?php echo HtmlHelper::_('uitab.endTabSet'); ?>                        
            
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="hidemainmenu" value="0">        
        <input type="hidden" name="templocked" value="<?php echo $this->item->locked; ?>">
        <input type="hidden" name="tempname" value="<?php echo $this->item->template_name; ?>">
        <input type="hidden" name="type" value="<?php echo $this->item->template_typ; ?>">
        <input type="hidden" name="view" value="" />
        <?php echo HtmlHelper::_('form.token'); ?>
    
    </div>
    
</form>
    
