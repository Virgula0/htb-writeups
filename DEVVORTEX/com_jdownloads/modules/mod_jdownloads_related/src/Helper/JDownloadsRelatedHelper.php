<?php
/**
* @version $Id: mod_jdownloads_related.php
* @package mod_jdownloads_related
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
* This module shows you some related downloads from the jDownloads component. 
* It is only for jDownloads 4.0 and later (Support: www.jDownloads.com)
*/

namespace JDownloads\Module\JDownloadsRelated\Site\Helper;

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

abstract class JDownloadsRelatedHelper
{
	static function getList(&$params, $catids, $id)
	{
        $db = Factory::getDbo();
		$app = Factory::getApplication();
        
        // Get an instance of the dite Downloads model
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jdownloads/src/Model');
        $downloads = BaseDatabaseModel::getInstance ('Downloads', 'jdownloads', array('ignore_request' => true));

        // Set application parameters in model
        $appParams = $app->getParams('com_jdownloads');
        $downloads->setState('params', $appParams);
       
        // Set the filters based on the module params
        $downloads->setState('list.start', 0);
        $downloads->setState('list.limit', (int) $params->get('sum_view', 5) + 1);
        $downloads->setState('filter.published', 1);

        // Access filter
        $access = true;
        $authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
        $downloads->setState('filter.access', true);
        $downloads->setState('filter.user_access', true);

		if ($params->get('category_filter', 0) == 1) {

	        // Category filter
	        if ($params->get('show_child_category_downloads', 0) && (int) $params->get('levels', 0) > 0){
	            // Get an instance of the generic categories model
				$categories = BaseDatabaseModel::getInstance('Categories', 'jdownloads', array('ignore_request' => true));
	            $categories->setState('params', $appParams);
	            $levels = $params->get('levels', 1) ? $params->get('levels', 1) : 9999;
	            $categories->setState('filter.get_children', $levels);
	            $categories->setState('filter.published', 1);
	            $categories->setState('filter.access', $access);
	            $additional_catids = array();            
	        
	            $categories->setState('filter.parentId', $catids[0]);
	            $recursive = true;
	            $cats = $categories->getItems($recursive);
	    
	            if ($cats){
	                foreach($cats as $category){
	                    $condition = (($category->level - $categories->getParent()->level) <= $levels);
	                    if ($condition){
	                        $additional_catids[] = $category->id;
	                    }
	                }
	            }
	            $catids = array_unique(array_merge($catids, $additional_catids));
	        }
	        $downloads->setState('filter.category_id', $catids);
		}	
        
        // User filter
        $userId = Factory::getUser()->get('id');

        // Filter by language
        $downloads->setState('filter.language', $app->getLanguageFilter());

        // Set sort ordering
        $downloads->setState('list.ordering', $params->get('download_ordering', 'a.ordering'));
        $downloads->setState('list.direction', $params->get('download_ordering_direction', 'ASC'));        


		if ($params->get('daterange_filter', 0) == 1) {
			$daterange_filter_field = $params->get('daterange_filter_field', 'a.created');
		
			$query = $db->setQuery($db->getQuery(true)
					->select('date(created)')
					->from('#__jdownloads_files')
					->where('id='. (int)$id)
			);
			$date_filtervalue = $db->loadResult();
		
			// CAM  $date_filtervalue is a string object containing creation date of the download
			// Make to start and end of day for range filter. 
			// DB query just returns the date part. Note date is composed of digits and - chars so no UTF8 challenge

			$startdatetime = $date_filtervalue.' 00:00:00';
			$enddatetime = $date_filtervalue.' 23:59:59';
            
			$downloads->setState('filter.date_filtering', 'range');	
			$downloads->setState('filter.start_date_range', $startdatetime);
			$downloads->setState('filter.end_date_range', $enddatetime);
		}

        $items = $downloads->getItems();

        foreach ($items as &$item)
        {
            $item->slug = $item->id . ':' . $item->alias;
            $item->catslug = $item->catid . ':' . $item->category_alias;

            if ($access || in_array($item->access, $authorised))
            {
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
        // In this case use we as default the en-GB format
        if (!$dec_point) $dec_point = '.'; 
        if (!$thousands_sep) $thousands_sep = ','; 

        $number = number_format($str, $decimals, $dec_point, $thousands_sep);
        return $number;
    }    
}	
?>