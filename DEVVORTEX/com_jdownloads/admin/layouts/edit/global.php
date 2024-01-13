<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;

$app       = Factory::getApplication();
$form      = $displayData->getForm();
$input     = $app->input;

$fields = $displayData->get('fields') ?: array(
    array('parent', 'parent_id'),
	array('published', 'state', 'enabled'),
	array('category', 'catid'),
	'featured',
	'access',
    'user_access',
	'language',
	'tags',
    'license',
    'license_agree',
    'file_language',
    'system',
    'update_active',
	'notes',
    'url',
);

$hiddenFields   = $displayData->get('hidden_fields') ?: array();

if (!Multilanguage::isEnabled())
{
    $hiddenFields[] = 'language';
    $form->setFieldAttribute('language', 'default', '*');
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
            if (in_array($f, $hiddenFields))
            {
                $form->setFieldAttribute($f, 'type', 'hidden');
            }
            
			$html[] = $form->renderField($f);
			break;
		}
	}
}

$html[] = '</fieldset>';

echo implode('', $html);
