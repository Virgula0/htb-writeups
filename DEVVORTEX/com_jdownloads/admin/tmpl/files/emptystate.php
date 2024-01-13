<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contact
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

$displayData = [
	'textPrefix' => 'COM_JDOWNLOADS_FILES',
	'formURL'    => 'index.php?option=com_jdownloads&view=files',
	'helpURL'    => 'https://www.jdownloads.net/documentation/general-items-v3-9/creating-downloads-with-the-files-facility',
	'icon'       => 'icon-copy',
];

$user = Factory::getApplication()->getIdentity();

if ($user->authorise('core.create', 'com_jdownloads') || count($user->getAuthorisedCategories('com_jdownloads', 'core.create')) > 0)
{
	$displayData['createURL'] = 'index.php?option=com_jdownloads&view=uploads';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
