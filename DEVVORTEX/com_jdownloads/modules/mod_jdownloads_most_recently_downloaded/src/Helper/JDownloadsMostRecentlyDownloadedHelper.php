<?php
/**
* @version $Id: mod_jdownloads_most_recently_downloaded.php
* @package mod_jdownloads_most_recently_downloaded
* @copyright (C) 2018 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

/** This Module shows the Most Recently Downloaded from the component jDownloads. 
*/

namespace JDownloads\Module\JDownloadsMostRecentlyDownloaded\Site\Helper;

\defined( '_JEXEC' ) or die;

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

abstract class JDownloadsMostRecentlyDownloadedHelper
{
	static function getList(&$params)
	{
        $db = Factory::getDbo();
        $user = Factory::getUser();
        
        $app = Factory::getApplication();
        $appParams = $app->getParams('com_jdownloads');

        $sum_view = (int) $params->get('sum_view');
        $sum_view_total = $sum_view + 50;
        
        // Get Logs model
        $logs_model      = $app->bootComponent('com_jdownloads')->getMVCFactory()->createModel('Logs', 'Administrator', ['ignore_request' => true]);
        // Get Downloads model
        $downloads_model = $app->bootComponent('com_jdownloads')->getMVCFactory()->createModel('Downloads', 'Site', ['ignore_request' => true]);
        
        // Set application parameters in model
        $logs_model->setState('params', $appParams);         
        
        // Set the filters based on the module params
        $logs_model->setState('list.start', 0);
        $logs_model->setState('list.limit', $sum_view_total);
        
        $logs_model->setState('filter.type', 1);  // 1=download 2=upload
        
        $logs_model->setState('list.ordering', 'a.log_datetime');
        $logs_model->setState('list.direction', 'DESC');        
        
        $logs = $logs_model->getItems();
        
        if (!$logs){
            return;  // Return if logs table is empty
        }
        
        // Build a comma seperated (Downloads) ID list
        $logs_ids = '';
        foreach ($logs as $log) {
            $logs_ids .= $log->log_file_id . ",";
        }
        $logs_ids = trim( $logs_ids, ',' );  
        
        // Set application parameters in model
        $downloads_model->setState('params', $appParams); 

        // Set the filters based on the module params
        $downloads_model->setState('list.start', 0);
        $downloads_model->setState('list.limit', $sum_view_total);
        $downloads_model->setState('filter.published', 1);
        
        // Access filter
        $downloads_model->setState('filter.access', true);
        $downloads_model->setState('filter.user_access', true);
        
        $access = true;
        $authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
        
        // Category filter
        $catid = $params->get('catid', array()); 
        if (empty($catid)){
            $downloads_model->setState('filter.category_id', '');
        } else {
            $downloads_model->setState('filter.category_id', $catid);
        }
        
        // Logs filter
        $downloads_model->setState('filter.log_id', $logs_ids);
            
        // Filter by language
        $downloads_model->setState('filter.language', $app->getLanguageFilter());

        // Set sort ordering
        $ordering = 'id';
        $dir = 'ASC';

        $downloads_model->setState('list.ordering', $ordering);
        $downloads_model->setState('list.direction', $dir);

        $items = $downloads_model->getItems();

        foreach ($items as $item)
        {
            $item->slug = $item->id . ':' . $item->alias;
            $item->catslug = $item->catid . ':' . $item->category_alias;

            if ($access || in_array($item->access, $authorised))
            {
                // We know that user has the privilege to view the download
                $item->link = '-'; 
            }
            else
            {
                $item->link = Route::_('index.php?option=com_users&view=login');
            }
        }
        
	    if ($items){
            $count = count($logs);
            for ($x=0; $x < count($logs); $x++) {
                for ($i=0; $i < count($items); $i++) {
                    if ($items[$i]->id == $logs[$x]->log_file_id){
                        $logs[$x]->title    = $items[$i]->title;
                        $logs[$x]->catid        = $items[$i]->catid;
                        $logs[$x]->fileid       = $items[$i]->id;
                        $logs[$x]->file_pic      = $items[$i]->file_pic;
                        $logs[$x]->release       = $items[$i]->release;
                        $logs[$x]->description   = $items[$i]->description;
                        $logs[$x]->menu_itemid     = $items[$i]->menuf_itemid;
                        $logs[$x]->menu_cat_itemid = $items[$i]->menuc_cat_itemid;
                        $logs[$x]->cat_title     = $items[$i]->category_title;
                        $logs[$x]->cat_dir       = $items[$i]->category_cat_dir;
                        $logs[$x]->cat_dir_parent = $items[$i]->category_cat_dir_parent;
                        $logs[$x]->catslug       = $items[$i]->catslug;
                        $logs[$x]->slug          = $items[$i]->slug;
                        $logs[$x]->link          = $items[$i]->link;
                        $logs[$x]->url_download  = $items[$i]->url_download;
                        $logs[$x]->other_file_id = $items[$i]->other_file_id;
                        $logs[$x]->extern_file   = $items[$i]->extern_file;
                        continue 2;
                    } 
                }  
                
                if (!isset($logs[$x]->fileid)){
                    // Download not found or user is not allowed to see it
                    $unset_logs[] = $logs[$x]->id;
                }
            } 
            if (isset($unset_logs)){
                $newlogs = array();
                $sum = 0;
                foreach ($logs as $newlog){
                    if (!in_array($newlog->id, $unset_logs)){
                        $newlogs[] = $newlog;
                        $sum ++;
                        if ($sum == $sum_view) continue;
                    }
                }
                return $newlogs;
            }                       
            return $logs;  
        }		
		return;    
	}
    
    /**
    * remove the language tag from a given text and return only the text
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
        // In this case use we as default the en-GB format
        if (!$dec_point) $dec_point = '.'; 
        if (!$thousands_sep) $thousands_sep = ','; 

        $number = number_format($str, $decimals, $dec_point, $thousands_sep);
        return $number;
    }            
}
?>