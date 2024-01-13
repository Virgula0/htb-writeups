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

// Get the system plugin ID
$db = Factory::getDBO();
$db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "jdownloads" AND `folder` = "system" AND `type` = "plugin"');
$id = $db->loadResult();
$extension_id = (int) $id;

$displayData = [
	'textPrefix' => 'COM_JDOWNLOADS_LOGS',
	'formURL'    => 'index.php?option=com_jdownloads&view=logs',
	'helpURL'    => 'https://www.jdownloads.net/documentation',
	'icon'       => 'icon-list',
];

$user = Factory::getApplication()->getIdentity();


if ($user->authorise('core.create', 'com_jdownloads') || count($user->getAuthorisedCategories('com_jdownloads', 'core.create')) > 0)
{
	$displayData['createURL'] = 'index.php?option=com_config&view=component&component=com_jdownloads#global';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
