<?php
defined('JPATH_BASE') or die;

$options = $this->options;
$form = $displayData->getForm();

?>
<div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-6">
        <?php
            echo $form->renderField('template_name');
        ?>
    </div>
    <div class="col-12 col-md-6">
        <?php
            echo $form->renderField('template_typ');
        ?>
    </div>
</div>
