<?php
/**
* @version $Id: mod_jdownloads_admin_monitoring.php v4.0
* @package mod_jdownloads_admin_monitoring
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
* 
* jDownloads admin stats module for use in the jDownloads Control Panel to scan the downloads folder.
* 
*/

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

use JDownloads\Module\JDownloadsAdminMonitoring\Helper;

    
$user = Factory::getApplication()->getIdentity();

if (!$user->authorise('core.manage', 'com_jdownloads')){
	return;
}

$language = Factory::getLanguage();
$language->load('mod_jdownloads_admin_monitoring.ini', JPATH_ADMINISTRATOR);

$params = ComponentHelper::getParams('com_jdownloads');

require ModuleHelper::getLayoutPath('mod_jdownloads_admin_monitoring', $params->get('layout', 'default'));
