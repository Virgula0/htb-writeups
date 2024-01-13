<?php

defined('JPATH_BASE') or die;

    $form = $displayData->getForm();

    echo $form->renderField('metadesc');
	echo $form->renderField('metakey');
	echo $form->renderField('robots');
    
?>