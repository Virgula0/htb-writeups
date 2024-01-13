<?php
defined('JPATH_BASE') or die;

$options = $this->options;
$form = $displayData->getForm();
$id = $displayData->getForm()->getValue('id');

$title = $form->getField('title') ? 'title' : ($form->getField('name') ? 'name' : '');

?>
<div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-6">
        <?php echo $title ? $form->renderField($title) : ''; ?>
    </div>
    <div class="col-12 col-md-6">
        <?php echo $form->renderField('alias'); ?>
    </div>
</div>
