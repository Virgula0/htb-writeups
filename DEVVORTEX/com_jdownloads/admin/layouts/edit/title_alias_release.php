<?php
defined('JPATH_BASE') or die;

$form = $displayData->getForm();

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
        <?php echo $form->renderField('release'); ?>
	</div>
</div>
