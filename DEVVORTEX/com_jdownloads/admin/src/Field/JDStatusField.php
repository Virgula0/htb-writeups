<?php

namespace JDownloads\Component\JDownloads\Administrator\Field;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\Field\PredefinedlistField;

/**
 * Form Field to load a list of states
 *
 */
class JDStatusField extends PredefinedlistField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	public $type = 'JDStatus';

	/**
	 * Available statuses
	 *
	 * @var  array
	 */
	protected $predefinedOptions = array(
		'0'  => 'JUNPUBLISHED',
		'1'  => 'JPUBLISHED',
		'*'  => 'JALL',
	);
}
