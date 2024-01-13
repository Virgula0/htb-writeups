<?php
/**
* @version $Id: mod_jdownloads_view_limits.php
* @package mod_jdownloads_view_limits
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
*/

namespace JDownloads\Module\JDownloadsViewLimits\Site\Helper;

\defined( '_JEXEC' ) or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

abstract class JDownloadsViewLimitsHelper
{
	public static function getLimits($params)
	{
        $db = Factory::getDbo();
		$app = Factory::getApplication();		
        $user_rules = JDHelper::getUserRules();
        $total_consumed = JDHelper::getUserLimits($user_rules, '');
        
        if (!$user_rules->download_limit_daily && !$user_rules->download_limit_weekly && !$user_rules->download_limit_monthly && !$user_rules->download_volume_limit_daily && !$user_rules->download_volume_limit_weekly && !$user_rules->download_volume_limit_monthly){
            $total_consumed['no_limits_defined'] = true;
        } else {
            $total_consumed['no_limits_defined'] = false;
        }
        
        $sql = 'SELECT title FROM #__usergroups WHERE id = '.$db->Quote($user_rules->group_id);
        $db->setQuery($sql);
        $usergroup = $db->loadResult();
        
        if ($usergroup){
            $total_consumed['group_name'] = $usergroup;
        } else {
            $total_consumed['group_name'] = '';
        }
        
        return $total_consumed;
	}
    
    
    public static function getHistoryLink($params, $active_language, $access_levels)
    {        
        $db = Factory::getDbo();
        $app = Factory::getApplication();            
                
        $sql = 'SELECT id FROM #__menu WHERE link = ' . $db->Quote('index.php?option=com_jdownloads&view=myhistory'). ' AND published = 1 AND language = '.$db->Quote($active_language).' AND access IN ('.$access_levels.')' ;
        $db->setQuery($sql);
        
        $history_link_id = $db->loadResult();
        
        if (!$history_link_id){
            $sql = 'SELECT id FROM #__menu WHERE link = ' . $db->Quote('index.php?option=com_jdownloads&view=myhistory'). ' AND published = 1 AND language = '.$db->Quote('*').' AND access IN ('.$access_levels.')' ;
            $db->setQuery($sql);
            $history_link_id = $db->loadResult();
        }
	    
        return $history_link_id;
	}
}	
?>