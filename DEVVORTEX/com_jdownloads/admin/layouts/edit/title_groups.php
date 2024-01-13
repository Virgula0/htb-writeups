<?php

defined('JPATH_BASE') or die;

    $options = $this->options;
    $form = $displayData->getForm();
    
    $form->setValue( 'title', $group=null, $form->title );
    echo $form->renderField('title');     
    echo $form->renderField('importance');    
    
?>