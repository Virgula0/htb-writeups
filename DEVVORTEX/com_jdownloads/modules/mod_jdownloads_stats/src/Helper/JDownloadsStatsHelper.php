<?php
/**
* @version $Id: mod_jdownloads_stats.php v3.8
* @package mod_jdownloads_stats
* @copyright (C) 2018 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

namespace JDownloads\Module\JDownloadsStats\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use JDownloads\Component\JDownloads\Site\Helper\JDRouteHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;

 abstract class JdownloadsStatsHelper
{
	static function getData($params)
	{
		$app = Factory::getApplication();
        $db = Factory::getDbo();
        $query    = $db->getQuery(true);

        // Get the current user for authorisation checks
        $user    = Factory::getUser();
        $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;
        
        $result = array();
        $use_all_items = $params->get('use_all_items', 0); 
        
        $app = Factory::getApplication();
        $language = $app->getLanguageFilter();       
        
        // Access 
        $access = true;
        $authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
        $groups = implode(',', $authorised);        
        
        // get at first the categories
        $query->select('id')->from('#__jdownloads_categories');
        $query->where('published = 1');
        if (!$use_all_items){
            $query->where('access IN ('.$groups.')');        
        }    
        $query->where("id > '1'"); // remove 'root' cat
        $db->setQuery($query);
        $cats = $db->loadColumn();
        $result['cats'] = (int)count($cats);
        
        $cats_id_list = implode(',', $cats);        
        
        // get the Downloads
        $query->clear();
        $query->select('COUNT(id)')->from('#__jdownloads_files AS a');
        $query->where('a.published = 1');
        if (!$use_all_items){
            if ($user->id > 0){
                // User is not a guest so we can generally use the user-id to find also the Downloads with single user access
                if ($is_admin){
                    // User is admin so we should display all possible Downloads - included the Downloads with single user access 
                    $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access != 0))');
                } else {
                    $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access = '.$db->quote($user->id). '))');
                }
            } else {    
                $query->where('a.access IN ('.$groups.')');
            }
        }    
        $query->where('a.catid IN ('.$cats_id_list.')');        
        $db->setQuery($query);
        $result['files'] = (int)$db->loadResult();
        
        // get the Downloads
        $query->clear();
        $query->select('SUM(downloads)')->from('#__jdownloads_files AS a');
        $query->where('a.published = 1');
        if (!$use_all_items){
            if ($user->id > 0){
                // User is not a guest so we can generally use the user-id to find also the Downloads with single user access
                if ($is_admin){
                    // User is admin so we should display all possible Downloads - included the Downloads with single user access 
                    $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access != 0))');
                } else {
                    $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access = '.$db->quote($user->id). '))');
                }
            } else {    
                $query->where('a.access IN ('.$groups.')');
            }
        }    
        $query->where('a.catid IN ('.$cats_id_list.')');        
        $db->setQuery($query);
        $result['hits'] = (int)$db->loadResult(); 
        
        $query->clear();
        $query->select('SUM(views)')->from('#__jdownloads_files AS a');
        $query->where('a.published = 1');
        if (!$use_all_items){
            if ($user->id > 0){
                // User is not a guest so we can generally use the user-id to find also the Downloads with single user access
                if ($is_admin){
                    // User is admin so we should display all possible Downloads - included the Downloads with single user access 
                    $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access != 0))');
                } else {
                    $query->where('((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access = '.$db->quote($user->id). '))');
                }
            } else {    
                $query->where('a.access IN ('.$groups.')');
            }
        }    
        $query->where('a.catid IN ('.$cats_id_list.')');        
        $db->setQuery($query);
        $result['views'] = (int)$db->loadResult();                

        return $result;        
	}
    
    /**
    * remove the language tag from a given text and return only the text
    *    
    * @param string     $msg
    */
    public static function getOnlyLanguageSubstring($msg)
    {
        // Get the current locale language tag
        $lang       =  Factory::getLanguage();
        $lang_key   = $lang->getTag();        
		if ($msg == '' ) {		
			return $msg;
		}
        
        // remove the language tag from the text
        $startpos = strpos($msg, '{'.$lang_key.'}') +  strlen( $lang_key) + 2 ;
        $endpos   = strpos($msg, '{/'.$lang_key.'}') ;
        
        if ($startpos !== false && $endpos !== false){
            return substr($msg, $startpos, ($endpos - $startpos ));
        } else {    
            return $msg;
        }    
    }
    
    /**
    * Converts a string into Float while taking the given or locale number format into account
    * Used as default the defined separator characters from the Joomla main language ini file (as example: en-GB.ini)  
    * 
    * @param mixed $str
    * @param mixed $dec_point
    * @param mixed $thousands_sep
    * @param mixed $decimals
    * @return mixed
    */
    public static function strToNumber( $str, $dec_point=null, $thousands_sep=null, $decimals = 0 )
    {
        if( is_null($dec_point) || is_null($thousands_sep) ) {
            if( is_null($dec_point) ) {
                $dec_point = Text::_('DECIMALS_SEPARATOR');
            }
            if( is_null($thousands_sep) ) {
                $thousands_sep = Text::_('THOUSANDS_SEPARATOR');
            }
        }
        // in this case use we as default the en-GB format
        if (!$dec_point) $dec_point = '.'; 
        if (!$thousands_sep) $thousands_sep = ','; 

        $number = number_format($str, $decimals, $dec_point, $thousands_sep);
        return $number;
    }            
}	
?>