<?php
/**
* @version $Id: mod_jdownloads_rated.php v4.0
* @package mod_jdownloads_rated
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

namespace JDownloads\Module\JDownloadsRated\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;
use JDownloads\Component\JDownloads\Site\Model\LogsModel;

abstract class JDownloadsRatedHelper
{
	public static function getList(&$params)
	{
        $db = Factory::getDbo();
        $app = Factory::getApplication();
        $appParams = $app->getParams('com_jdownloads');

        $access = true;    // Access filter
        $authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
        $groups = implode(',', $authorised);
        
        // Get the current user for authorisation checks
        $user    = Factory::getUser();
        $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;
        
        $type = $params->get('top_view');
        if (!$type){
            $order = 'rating_count DESC, ratenum DESC';    // Most rated
        } else {
            $order = 'ratenum DESC , rating_count DESC';    // Top rated
        }
        
        $catid = $params->get('catid', array());
        $catid = implode(',', $catid);
        
        if ($user->id > 0){
            // User is not a guest so we can generally use the user-id to find also the Downloads with single user access
            if ($is_admin){
                // User is admin so we should display all possible Downloads - included the Downloads with single user access 
                $where  = ' WHERE ((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access != 0))';
                $where .= ' AND c.access IN ('.$groups.')';
            } else {
                $where  = ' WHERE ((a.access IN ('.$groups.') AND a.user_access = 0) OR (a.access != 0 AND a.user_access = '.$db->quote($user->id). '))';
                $where .= ' AND c.access IN ('.$groups.')';
            }
        } else {    
            $where = ' WHERE a.access IN ('.$groups.')';
            $where .= ' AND c.access IN ('.$groups.')';
        }
        
        if (!$catid){
            $where .= ' AND a.published = 1 AND a.language in (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')';
        } else {
            $where .= ' AND a.published = 1 AND a.language in (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') .
            ') AND a.catid IN (' . $catid . ')';
        }

        // Create the query  - CAM added a.downloads and a.created
        $query = 'SELECT a.id, 
                         a.title,
                         a.alias,
						 a.created,
                         a.description,
						 a.downloads,
                         a.file_pic,
                         a.url_download,
                         a.other_file_id,
                         a.extern_file,
                         a.catid,
                         a.release,
                         a.access,
                         a.user_access,
                         c.title as category_title,
                         c.access as category_access,
                         c.alias as category_alias,
                         c.cat_dir as category_cat_dir,
                         c.cat_dir_parent as category_cat_dir_parent,
                         mf.id as menu_itemid,
                         mf.link as menu_link,
                         mf.access as menu_access,
                         mf.published as menu_published,
                         mc.id as menu_cat_itemid,
                         mc.link as menu_cat_link,
                         mc.access as menu_cat_access,
                         mc.published as menu_cat_published,                         
                         r.file_id,
                         r.rating_count ,
                       round( r.rating_sum / r.rating_count ) * 20 as ratenum
                       
                    FROM #__jdownloads_files AS a
                    LEFT JOIN #__jdownloads_categories AS c
                          ON c.id = a.catid
                    LEFT JOIN #__menu AS mf
                          ON mf.link LIKE CONCAT(\'index.php?option=com_jdownloads&view=download&id=\',a.id)
                    LEFT JOIN #__menu AS mc
                          ON mc.link LIKE CONCAT(\'index.php?option=com_jdownloads&view=category&catid=\',a.catid)                                                    
                    INNER JOIN #__jdownloads_ratings AS r
                          ON a.id = r.file_id ' .
                          $where .
                    ' ORDER BY '.$order;

        $db->setQuery($query, 0, (int) $params->get('sum_view'));
        $items = $db->loadObjectList();
   
        foreach ($items as &$item){
            $item->slug = $item->id . ':' . $item->alias;
            $item->catslug = $item->catid . ':' . $item->category_alias;

            if ($access || in_array($item->access, $authorised)){
                // We know that user has the privilege to view the download
                $item->link = '-';
            } else {
                $item->link = Route::_('index.php?option=com_users&view=login');
            }
        }
        return $items;        
	}
    
    /**
    * Remove the language tag from a given text and return only the text
    *    
    * @param string     $msg
    */
    public static function getOnlyLanguageSubstring($msg)
    {
        // Get the current locale language tag
        $lang       = Factory::getLanguage();
        $lang_key   = $lang->getTag();        
        if ($msg == '' ) {		
			return $msg;
		}
        // Remove the language tag from the text
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
        
        // In this case use as default the en-GB format
        if (!$dec_point) $dec_point = '.'; 
        if (!$thousands_sep) $thousands_sep = ','; 

        $number = number_format($str, $decimals, $dec_point, $thousands_sep);
        return $number;
    }    
}			
?>