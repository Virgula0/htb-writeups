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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

// Include the component HTML helpers.
HtmlHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Load required asset files
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
   ->useScript('form.validate');

$params = $this->state->params;

$options['ini_upload_max_filesize'] = JDownloadsHelper::return_bytes(ini_get('upload_max_filesize'));
$options['admin_images_folder']     = URI::root().'administrator/components/com_jdownloads/assets/images/';
$options['cat_pic_size']            = $params->get('cat_pic_size');
$options['categories_editor']       = 1;
$options['create_auto_cat_dir']     = $params->get('create_auto_cat_dir');

$app = Factory::getApplication();
$input = $app->input;

$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';

$assoc = Associations::isEnabled();
// Are associations implemented for this extension?
$extensionassoc = array_key_exists('item_associations', $this->form->getFieldsets());

// Fieldsets to not automatically render by /layouts/joomla/edit/params.php
$this->ignore_fieldsets = array('jmetadata', 'item_associations');

$this->useCoreUI = true;

// In case of modal
$isModal = $input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

?>

<script type="text/javascript">
    
    // get the selected file name to view the cat pic new 
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
</script>

<form action="<?php echo Route::_('index.php?option=com_jdownloads&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" accept-charset="utf-8" enctype="multipart/form-data" class="form-validate">

    <div class="row title-alias form-vertical mb-3">
        <div class="col-12 col-md-12">
        <?php echo LayoutHelper::render('edit.title_alias_catdir', $this, $basePath, $options); ?>
        </div>
    </div>
    
    <div>    
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('JCATEGORY')); ?>
        <div class="row">
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-body">
                        <?php echo $this->form->getLabel('description'); ?>
                        <?php echo $this->form->getInput('description'); ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card card-block">
                    <div class="card-body">
                    <?php echo LayoutHelper::render('edit.global', $this, $basePath); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'additional', Text::_('COM_JDOWNLOADS_ADDITIONAL_DATA')); ?>
        <div class="row">
            <div class="col-12 col-lg-6">
                <fieldset id="fieldset-additional" class="options-form">
                    <legend><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_FRONTEND_PIC_TEXT'); ?></legend>
                    <div>
                        <?php echo LayoutHelper::render('edit.images_cat', $this, $basePath, $options); ?>
                    </div>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_JDOWNLOADS_PUBLISHING_DETAILS')); ?>
        <div class="row">
            <div class="col-12 col-lg-6">
                <fieldset id="fieldset-publishingdata" class="options-form">
                    <legend><?php echo Text::_('COM_JDOWNLOADS_PUBLISHING_DETAILS'); ?></legend>
                    <div>
                    <?php echo LayoutHelper::render('edit.publishingdata_cat', $this, $basePath, $options); ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-12 col-lg-6">
                <fieldset id="fieldset-metadata" class="options-form">
                    <legend><?php echo Text::_('COM_JDOWNLOADS_METADATA_OPTIONS'); ?></legend>
                    <div>
                    <?php echo LayoutHelper::render('edit.metadata', $this, $basePath, $options); ?>
                    </div>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        
        <?php if ( ! $isModal && $assoc && $extensionassoc) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'associations', Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS')); ?>
                <fieldset id="fieldset-associations" class="options-form">
                <legend><?php echo Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS'); ?></legend>
            <div>
                <?php echo LayoutHelper::render('joomla.edit.associations', $this); ?>
            </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php elseif ($isModal && $assoc && $extensionassoc) : ?>
            <div class="hidden"><?php echo LayoutHelper::render('joomla.edit.associations', $this); ?>
        <?php endif; ?>
        
        <?php 
        if ($this->canDo->get('core.admin')) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'rules', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL')); ?>
            <fieldset id="fieldset-rules" class="options-form">
                <legend><?php echo Text::_('COM_JDOWNLOADS_CATEGORY_RULES'); ?></legend>
                <div>
                <?php echo $this->form->getInput('rules'); ?>
                </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

        <?php
            if ($params->get('create_auto_cat_dir')){
                if (!$this->item->id){            
                    // cat_dir is defined as required, so we need a default value here
                    echo '<input type="hidden" name="jform[cat_dir]" value="DUMMY" />';
                }         
            }        
        ?>
        <?php echo $this->form->getInput('extension'); ?>
        <input type="hidden" name="task" value="">
        <input type="hidden" name="return" value="<?php echo $input->getBase64('return'); ?>">
        <input type="hidden" name="forcedLanguage" value="<?php echo $input->get('forcedLanguage', '', 'cmd'); ?>">
        <input type="hidden" name="view" value="category">         
        <input type="hidden" name="cat_dir_org" value="<?php echo $this->item->cat_dir; ?>">
        <input type="hidden" name="cat_dir_parent_org" value="<?php echo $this->item->cat_dir_parent; ?>">
        <input type="hidden" name="cat_title_org" value="<?php echo $this->item->title; ?>">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>    
    