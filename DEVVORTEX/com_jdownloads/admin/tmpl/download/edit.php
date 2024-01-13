<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2021 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Input\Input;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Component\JDownloads\Administrator\Service\HTML;

    // Include the component HTML helpers.
    //HtmlHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
    
    // Load required asset files
    $wa = $this->document->getWebAssetManager();
    $wa->useScript('keepalive')
        ->useScript('form.validate');
    
    $this->configFieldsets  = array('editorConfig');
    $this->hiddenFieldsets  = array('basic-limited');
    $this->ignore_fieldsets = array_merge(array('jmetadata', 'item_associations'));
    $this->useCoreUI = true;

    // Create shortcut to parameters.
    $params = clone $this->state->get('params');
    
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
    $assoc = Associations::isEnabled();
    
    if (!$assoc){
        $this->ignore_fieldsets[] = 'frontendassociations';
    }

    $options['ini_upload_max_filesize'] = JDownloadsHelper::return_bytes(ini_get('upload_max_filesize'));
    $options['admin_images_folder'] = URI::root().'administrator/components/com_jdownloads/assets/images/';
    $options['assigned_file'] = $this->item->url_download;
    $options['assigned_preview_file'] = $this->item->preview_filename;
    $options['file_pic_folder'] = $file_pic_folder;
    $options['file_pic'] = $this->item->file_pic;
    $options['file_pic_size'] = $params->get('file_pic_size');
    $options['files_editor'] = 1;
    $options['images'] = $this->item->images;
    $options['be_upload_amount_of_pictures'] = $params->get('be_upload_amount_of_pictures');

    $images = explode("|", $options['images'] ?? '');
    $amount_images = count($images);

    // Path to the backend jD images folder 
    $admin_images_folder = URI::root().'administrator/components/com_jdownloads/assets/images/';
    // Path to the layouts folder 
    $basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';

    $app = Factory::getApplication();
    $input = $app->input;
    
    // In case of modal
    $isModal = $input->get('layout') == 'modal' ? true : false;
    $layout  = $isModal ? 'modal' : 'edit';
    $tmpl    = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
    


?>

<script type="text/javascript">
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

<form accept-charset="utf-8" action="<?php echo Route::_('index.php?option=com_jdownloads&layout=' . $layout . $tmpl . '&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="download-form" accept-charset="utf-8" enctype="multipart/form-data" class="form-validate">
    
    <input id="jform_form_title" type="hidden" name="download-form-title"/>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $options['ini_upload_max_filesize']; ?>" />
    
    <!-- Has the user selected before a file from the 'files list' then we will give him a hint -->
    <?php 
    if (isset($this->selected_filename) && $this->selected_filename != ''){ ?>
        <div class="alert alert-warning"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_NOTE_FILE_SELECTED_IN_LIST'); ?> </div>
        <div class="clr"> </div> 
    <?php } ?> 
    
    <!-- View the title and alias --> 
    <?php echo LayoutHelper::render('edit.title_alias_release', $this, $basePath); ?>

    <!-- ========== GENERAL ============ -->            
    <div>
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_JDOWNLOADS_GENERAL')); ?>
        <div class="row">
            <div class="col-lg-9">
                <div class="accordion" id="accordionPanelsStayOpenExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                                <?php echo $this->form->getLabel('description'); ?>
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show" aria-labelledby="panelsStayOpen-headingOne">
                            <div class="accordion-body">
                                <div>
                                    <?php echo $this->form->getInput('description'); ?>
                                </div>    
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="panelsStayOpen-headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                                <?php echo $this->form->getLabel('description_long'); ?>    
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingTwo">
                            <div class="accordion-body">
                                <div>
                                    <?php echo $this->form->getInput('description_long'); ?>       
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="panelsStayOpen-headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                                <?php echo $this->form->getLabel('changelog'); ?>
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingThree">
                            <div class="accordion-body">
                                <div>
                                    <?php echo $this->form->getInput('changelog'); ?>           
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="bg-white px-3">
                <?php echo LayoutHelper::render('edit.global', $this, $basePath); ?>
                </div>
            </div>
        </div>

        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    
        <!-- ========== FILES DATA ============ -->        

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'filesdata', Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_TABTITLE_3')); ?>
            <div class="row">
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-files" class="options-form">
                        <legend><?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_SELECT_MAIN_FILE'); ?></legend>
                            <?php echo LayoutHelper::render('edit.files', $this, $basePath, $options); ?>
                    </fieldset>
                </div>
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-preview" class="options-form">
                        <legend><?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESLIST_PREVIEW_FILE'); ?></legend>
                            <?php echo LayoutHelper::render('edit.preview', $this, $basePath, $options); ?>
                    </fieldset>
                </div>
            </div>        
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        
        <!-- ========== ADDITIONAL ============ -->
        
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'additional', Text::_('COM_JDOWNLOADS_ADDITIONAL_DATA')); ?>
            <div class="row">
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-additional" class="options-form">
                        <legend><?php echo Text::_('COM_JDOWNLOADS_ADDITIONAL_DATA'); ?></legend>
                            <?php echo LayoutHelper::render('edit.additional', $this, $basePath, $options); ?>
                            <?php echo LayoutHelper::render('edit.custom_fields', $this, $basePath, $options); ?>
                    </fieldset>
                </div>
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-images" class="options-form">
                        <legend><?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_TABTITLE_4'); ?></legend>
                            <?php echo LayoutHelper::render('edit.images', $this, $basePath, $options); ?>
                    </fieldset>
                </div>
            </div>        
        <?php echo HTMLHelper::_('uitab.endTab'); ?>        
        
        <!-- ========== ADD THE CUSTOM_FIELDS ============ -->
        
        <?php 
            if (ComponentHelper::isEnabled('com_fields') && $params->get('custom_fields_enable') == 1){
                $this->ignore_fieldsets = array_merge($this->ignore_fieldsets, ['general', 'info', 'detail', 'jmetadata', 'item_associations']);
                echo LayoutHelper::render('joomla.edit.params', $this);
            }
        ?>

        <!-- ========== PUBLISHING ============ -->
                
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_JDOWNLOADS_FIELDSET_PUBLISHING')); ?>
            <div class="row">
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-publishingdata" class="options-form">
                        <legend><?php echo Text::_('COM_JDOWNLOADS_PUBLISHING_DETAILS'); ?></legend>
                        <?php echo LayoutHelper::render('edit.publishingdata', $this, $basePath); ?>
                    </fieldset>
                </div>
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-metadata" class="options-form">
                        <legend><?php echo Text::_('COM_JDOWNLOADS_METADATA_OPTIONS'); ?></legend>
                        <?php echo LayoutHelper::render('edit.metadata', $this, $basePath); ?>
                    </fieldset>
                </div>
            </div>
            <?php echo HTMLHelper::_('uitab.endTab'); ?> 
        
        <!-- ========== ASSOCIATIONS ============ -->
        
        <?php if ( ! $isModal && $assoc) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'associations', Text::_('COM_JDOWNLOADS_HEADING_ASSOCIATION')); ?>
            <fieldset id="fieldset-associations" class="options-form">
            <legend><?php echo Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS'); ?></legend>
            <div>
                <?php echo LayoutHelper::render('joomla.edit.associations', $this); ?>
            </div>
            </fieldset>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php elseif ($isModal && $assoc) : ?>
            <div class="hidden"><?php echo LayoutHelper::render('joomla.edit.associations', $this); ?></div>
        <?php endif; ?>
        
        <!-- ========== PERMISSIONS ============ -->
        
        <?php if ($this->canDo->get('core.admin')) { ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL')); ?>
            <fieldset id="fieldset-rules" class="options-form">
                <legend><?php echo Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL'); ?></legend>
                <div>
                    <?php 
                    if (empty($this->item->id)){ 
                             echo '<div>'.$this->form->getLabel('permissions_warning');
                             echo '</div>';
                    } ?> 
                    <?php echo $this->form->getInput('rules'); ?>
                </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php } ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
        
        <!-- Creating 'id' hiddenField to cope with com_associations sidebyside loop -->
        
        <?php $hidden_fields = $this->form->getInput('id'); ?>
        <div class="hidden"><?php echo $hidden_fields; ?></div>
        
    </div>

    <div>
        <input type="hidden" name="task" value="">
        <input type="hidden" name="return" value="<?php echo $input->getBase64('return'); ?>">
        <input type="hidden" name="forcedLanguage" value="<?php echo $input->get('forcedLanguage', '', 'cmd'); ?>">

        <input type="hidden" name="view" value="download">
        <input type="hidden" name="image_file_count" id="image_file_count" value="0">         
        <input type="hidden" name="cat_dir_org" value="<?php echo $this->item->catid; ?>">
        <input type="hidden" name="sum_listed_images" id="sum_listed_images" value="<?php echo $amount_images; ?>">
        <input type="hidden" name="max_sum_images" id="max_sum_images" value="<?php echo (int)$params->get('be_upload_amount_of_pictures'); ?>"> 
        
        <input type="hidden" name="filename" value="<?php echo $this->item->url_download; ?>">        
        <input type="hidden" name="modified_date_old" value="<?php echo $this->item->modified; ?>">
        <input type="hidden" name="submitted_by" value="<?php echo $this->item->submitted_by; ?>">
        <input type="hidden" name="set_aup_points" value="<?php echo $this->item->set_aup_points; ?>">
        <input type="hidden" name="filename_org" value="<?php echo $this->item->url_download; ?>">          
        <input type="hidden" name="preview_filename_org" value="<?php echo $this->item->preview_filename; ?>"> 
        <input type="hidden" name="file_pic_org" value="<?php echo $this->item->file_pic; ?>">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>    
    