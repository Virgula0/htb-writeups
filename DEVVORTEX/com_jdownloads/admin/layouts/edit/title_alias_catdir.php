<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

$options = $this->options;
$form = $displayData->getForm();
$id = $displayData->getForm()->getValue('id');

$title = $form->getField('title') ? 'title' : ($form->getField('name') ? 'name' : '');

?>
<div class="row title-alias form-vertical mb-3">
	<div class="col-12 col-md-4">
        <?php echo $title ? $form->renderField($title) : ''; ?>
    </div>
    <div class="col-12 col-md-4">
        <?php echo $form->renderField('alias'); ?>
    </div>
    <div class="col-12 col-md-4">
    
    <?php
    if ($options['create_auto_cat_dir']){
        if ($id){ 
           // Change field attribs first
           $form->setFieldAttribute( 'cat_dir',  'readonly', 'true' );
           $form->setFieldAttribute( 'cat_dir', 'required', 'false' );
           $form->setFieldAttribute( 'cat_dir', 'class', 'readonly' );
           $form->setFieldAttribute( 'cat_dir', 'description', Text::_('COM_JDOWNLOADS_EDIT_CAT_DIR_TITLE_MSG') );
           echo $form->renderField('cat_dir');
        } else { 
           // View field for new category
           echo $form->renderField('cat_dir_parent');
        }    
    } else {
         // Auto creation is switch off
         echo $form->renderField('cat_dir');
    }
    ?>
    
    </div>
</div>
