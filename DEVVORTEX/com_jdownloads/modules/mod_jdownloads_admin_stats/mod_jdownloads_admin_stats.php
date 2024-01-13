<?php
/**
* @version $Id: mod_jdownloads_admin_stats.php v4.0
* @package mod_jdownloads_admin_stats
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
* 
* jDownloads admin stats module for use in the jDownloads or the Joomla Control Panel.
* 
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;
use JDownloads\Module\JDownloadsAdminStats\Administrator\Helper\JDownloadsAdminStatsHelper;

    HTMLHelper::_('bootstrap.framework');
    
    $document  = Factory::getDocument();
    
    $db      = Factory::getDBO();
    $user    = Factory::getApplication()->getIdentity();
    $session = Factory::getSession();

	if (!$user->authorise('core.manage', 'com_jdownloads')){
		return;
	}
    
	$language = Factory::getLanguage();
	$language->load('mod_jdownloads_admin_stats.ini', JPATH_ADMINISTRATOR);

    $latesttab     = $params->get('view_latest', 1);
    $populartab    = $params->get('view_popular', 1);
    $featuredtab   = $params->get('view_featured', 1);
    $mostratedtab  = $params->get('view_most_rated', 1);
    $topratedtab   = $params->get('view_top_rated', 1);
    $statisticstab = $params->get('view_statistics', 1);
    $monitoringtab = $params->get('view_monitoring_log', 1);
    $restoretab    = $params->get('view_restore_log', 0);
    $installtab    = $params->get('view_install_log', 1);
    $serverinfotab = $params->get('view_server_info', 1);
    $check_system  = $params->get('view_check_results', 1);

    // jD System plugin check
    require_once __DIR__ . '/src/Helper/JDownloadsAdminStatsHelper.php';
    
    $sys_plugin = JDownloadsAdminStatsHelper::checkSystemPlugin();

    if ($check_system){
        
        // Let's display all other checks only one time in a session
        $check_viewed  = (int) $session->get( 'jd_check_viewed', 0 );  
        if (!$check_viewed){
            $session->set( 'jd_check_viewed', 1 );  
            $first_time = true;
        } else {
            $first_time = false;
        }
        
        $override_folder_found = 0;
        $override_folder_modules_found = 0;
        $menu_item = 0;
    
        // We should display a hint when the override functionality is use in any template - so we will check every template 'html' folder
        $templates = JDownloadsAdminStatsHelper::getTemplates();
        if (count($templates)){
            foreach ($templates as $template){
                // Frontend output part
                $path = JPATH_SITE.'/templates/'.$template.'/html/jdownloads/';
                if (Folder::exists($path)){
                    $files = Folder::files($path, $filter = '.', true, true , array());
                    if (count($files)){
                        $override_folder_found ++;
                    }
                } else {
                    $path = JPATH_SITE.'/templates/'.$template.'/html/com_jdownloads/';
                    if (Folder::exists($path)){
                        $files = Folder::files($path, $filter = '.', true, true , array());
                        if (count($files)){
                            $override_folder_found ++;
                        }
                    }
                }
                
                // Modules part
                $path = JPATH_SITE.'/templates/'.$template.'/html/';
                if (Folder::exists($path)){
                    $files = Folder::files($path, $filter = '.', true, true , array (1 => 'com_content', 2 => 'com_contact', 3 => 'com_newsfeeds', 4 => 'com_weblinks', ));
                    if (count($files)){
                        $result = JDownloadsAdminStatsHelper::findTextInArray($files, 'mod_jdownloads');
                        if (count($result)){
                            $override_folder_modules_found = count($result);
                        }
                    }
                }
            }
        }
        
        // Check if a (published) main menu entry exists 
        $menu_item = JDownloadsAdminStatsHelper::getMainMenuItem();
    }
    
    if ($latesttab){
	    $latest_items = JDownloadsAdminStatsHelper::getLatestItems($params);
    }
    
    if ($populartab){
	    $popular_items = JDownloadsAdminStatsHelper::getPopularItems($params);
    }
    
    if ($featuredtab){
        $featured_items = JDownloadsAdminStatsHelper::getFeaturedItems($params);
    }
    
    if ($mostratedtab){
	    $most_rated_items = JDownloadsAdminStatsHelper::getMostRatedItems($params);
    }
    
    if ($topratedtab){
	    $top_rated_items = JDownloadsAdminStatsHelper::getTopRatedItems($params);
    }
    
    if ($statisticstab){
	    $statistics = JDownloadsAdminStatsHelper::getStatistics();
    }
    
    if ($monitoringtab){
        $monitoring_log = JDownloadsAdminStatsHelper::getMonitoringLog();
    }
    
    if ($restoretab){
        $restore_log = JDownloadsAdminStatsHelper::getRestoreLog();
    }

    if ($installtab){
        $install_log = JDownloadsAdminStatsHelper::getInstallLog();
    }
    
    if (!$latesttab && !$populartab && !$featuredtab && !$mostratedtab && !$topratedtab && !$statisticstab && !$monitoringtab && !$restoretab && !$installtab && !$serverinfotab){
        return;
    }
    
require ModuleHelper::getLayoutPath('mod_jdownloads_admin_stats', $params->get('layout', 'default'));
