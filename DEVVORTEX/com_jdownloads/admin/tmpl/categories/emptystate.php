<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_categories
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$title = Text::_('COM_JDOWNLOADS_EMPTYSTATE_TITLE_CAT');

$displayData = [
	'textPrefix' => 'COM_JDOWNLOADS_CATEGORIES',
	'formURL'    => 'index.php?option=com_jdownloads',
	'helpURL'    => 'https://www.jdownloads.net/documentation/getting-started-v3-9/create-a-category-in-backend',
	'title'      => $title,
	'icon'       => 'icon-folder',
];

if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_jdownloads'))
{
	$displayData['createURL'] = 'index.php?option=com_jdownloads&task=category.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
