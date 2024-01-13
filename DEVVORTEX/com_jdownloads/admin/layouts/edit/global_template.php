<?php
defined('JPATH_BASE') or die;

$options   = $this->options;
$form      = $displayData->getForm();
$type      = $form->getValue('template_typ');

switch ($type) {
   case 1:
   case 8:
        $fields = $displayData->get('fields') ?: array(
            'locked',
            'template_active',
            'cols',
			'uses_bootstrap',
			'uses_w3css',
            'note',
            'id',
        );
         break;

   case 2:
        $fields = $displayData->get('fields') ?: array(
            'locked',
            'template_active',
            'cols',
            'uses_bootstrap',
            'uses_w3css',
            'checkbox_off',
            'symbol_off',
            'note',
            'id',
        );
         break;

   case 4:         
   case 5:
   case 3:
   case 7:
        $fields = $displayData->get('fields') ?: array(
            'locked',
            'template_active',
            'uses_bootstrap',
            'uses_w3css',
            'note',
            'id',
        );
         break;         
         
}


$html   = array();
$html[] = '<fieldset class="form-vertical">';

foreach ($fields as $field)
{
	$field = is_array($field) ? $field : array($field);

	foreach ($field as $f)
	{
        if ($form->getField($f))
		{
            $html[] = $form->renderField($f);
			break;
		}
	}
}

$html[] = '</fieldset>';

echo implode('', $html);
