<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;

$app = Factory::getApplication();
$form = $displayData->getForm();

$fields = $displayData->get('fields') ?: array(
	'publish_up',
	'publish_down',
    'created',
	'created_by',
	'modified',
    'modified_by',
	'views',
    'downloads',
	'id',
);

$hiddenFields = $displayData->get('hidden_fields') ?: array();

foreach ($fields as $field)
{
	$field = is_array($field) ? $field : array($field);
	foreach ($field as $f)
	{
		if ($form->getField($f))
		{
			if (in_array($f, $hiddenFields))
			{
				$form->setFieldAttribute($f, 'type', 'hidden');
			}

			echo $form->renderField($f);
			break;
		}
	}
}
