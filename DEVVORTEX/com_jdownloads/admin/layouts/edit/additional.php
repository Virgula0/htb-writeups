<?php

defined('JPATH_BASE') or die;

    $options = $this->options;
    $form = $displayData->getForm();
    
    $id = $displayData->getForm()->getValue('id');
    $selected_filename = $displayData->getForm()->getValue('selected_filename');
    
    echo $form->renderField('price');     
    echo $form->renderField('password');    
    echo $form->renderField('url_home');    
    echo $form->renderField('author');
    echo $form->renderField('url_author');
    
?>