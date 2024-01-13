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
	'textPrefix' => 'COM_JDOWNLOADS',
	'formURL'    => 'index.php?option=com_jdownloads',
	'helpURL'    => 'https://www.jdownloads.net/documentation/getting-started-v3-9/create-a-download-in-the-backend',
	'icon'       => 'icon-copy',
];

$user = Factory::getApplication()->getIdentity();

if ($user->authorise('core.create', 'com_jdownloads') || count($user->getAuthorisedCategories('com_jdownloads', 'core.create')) > 0)
{
	$displayData['createURL'] = 'index.php?option=com_jdownloads&task=download.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
