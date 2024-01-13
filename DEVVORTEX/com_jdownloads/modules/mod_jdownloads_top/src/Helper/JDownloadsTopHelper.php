<?php
/**
* @version $Id: mod_jdownloads_latest.php v4.0
* @package mod_jdownloads_latest
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
*
* This module shows you the most recent downloads from the jDownloads component. 
*/

namespace JDownloads\Module\JDownloadsTop\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;

use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;

abstract class JDownloadsTopHelper
{
	public static function getList(&$params, $model)
	{
        $db = Factory::getDbo();
		$app = Factory::getApplication();

        // Set application parameters in model
        $appParams = $app->getParams('com_jdownloads');
        $model->setState('params', $appParams);
       
        // Set the filters based on the module params
        $model->setState('list.start', 0);
        $model->setState('list.limit', (int) $params->get('sum_view', 5));
        $model->setState('filter.published', 1);

        // Access filter
        $model->setState('filter.access', true);
        $model->setState('filter.user_access', true);
        
        $access = true;
        $authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));

        // Category filter
        $catid = $params->get('catid', array()); 
        if (empty($catid)){
            $model->setState('filter.category_id', '');
        } else {
            $model->setState('filter.category_id', $catid);
        }    

        // User filter
        $userId = Factory::getUser()->get('id');

        // Filter by language
        $model->setState('filter.language', $app->getLanguageFilter());

        // Set sort ordering
        $ordering = 'a.downloads';
        $dir = 'DESC';

        $model->setState('list.ordering', $ordering);
        $model->setState('list.direction', $dir);

        $items = $model->getItems();

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