<?php
/**
* @version $Id: mod_jdownloads_top.php v4.0
* @package mod_jdownloads_top
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
* This module shows you the top (most downloaded) Downloads from the jDownloads component. 
*/

defined( '_JEXEC' ) or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use JDownloads\Module\JDownloadsTop\Site\Helper\JDownloadsTopHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

    $app = Factory::getApplication(); 
    $db  = Factory::getDBO();
	
	$input = $app->input;
    $Itemid  = $input->get('Itemid'); 

    $model = $app->bootComponent('com_jdownloads')->getMVCFactory()->createModel('Downloads', 'Site', ['ignore_request' => true]);
    
	// Add css
    $document = Factory::getDocument();
	$document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_modules.css");
    
    // Get published root menu link
    $db->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=categories' and published = 1 AND client_id = 0");
    $root_itemid = $db->loadResult();
    
    if ($root_itemid){
        $Itemid = $root_itemid;
    }
    
    // Get this option from configuration to see whether the links shall run the download without summary page
	$jdparams               = $app->getParams('com_jdownloads');
	$direct_download_config = $jdparams->get('direct_download');
	$detail_view_config     = $jdparams->get('view_detailsite');
   
    $text_before           = '';  // assume no text before
	$before = $params->get( 'text_before' );
	if($before != '') {
		$before        = trim($before);	//only here if not empty
    $text_before           = JDownloadsTopHelper::getOnlyLanguageSubstring($before);
	}
    $text_after           = '';  // assume no text after
	$after = $params->get( 'text_after' );
	if($after != '') {
		$after        = trim($after);	//only here if not empty
    $text_after            = JDownloadsTopHelper::getOnlyLanguageSubstring($after);
	}   

    $catid                 = $params->get('catid', array());
    $sum_view              = intval(($params->get( 'sum_view' ) ));
    $sum_char              = intval(($params->get( 'sum_char' ) ));
    $short_char            = $params->get( 'short_char' ) ; 
    $short_version         = $params->get( 'short_version' );
    $detail_view           = $params->get( 'detail_view' ) ; 
    $view_date             = $params->get( 'view_date' ) ;
    $view_date_same_line   = $params->get( 'view_date_same_line' );
	$view_date_text        = $params->get( 'view_date_text' );
    $view_date_text        = JDownloadsTopHelper::getOnlyLanguageSubstring($view_date_text);	
    
    // We use the standard short date format from the activated language when a format is not defined 
    $date_format           = $params->get( 'date_format', Text::_('DATE_FORMAT_LC4') );
	$date_alignment        = $params->get( 'date_alignment' );	
    $view_hits             = $params->get( 'view_hits' ) ;
    $view_hits_same_line   = $params->get( 'view_hits_same_line' );
    $hits_label            = $params->get( 'hits_label' );
	$hits_label            = JdownloadsTopHelper::getOnlyLanguageSubstring($hits_label);
    $hits_alignment        = $params->get( 'hits_alignment' );
    $view_pics             = $params->get( 'view_pics' ) ;
    $view_pics_size        = $params->get( 'view_pics_size' ) ;
	$view_pics_link        = $params->get( 'view_pics_link' ) ;
    $view_numerical_list   = $params->get( 'view_numerical_list' );
    $view_thumbnails       = $params->get( 'view_thumbnails' );
    $view_thumbnails_size  = $params->get( 'view_thumbnails_size' );
	$view_thumbnails_link  = $params->get( 'view_thumbnails_link' );
    $view_thumbnails_dummy = $params->get( 'view_thumbnails_dummy' );
    $hits_alignment        = $params->get( 'hits_alignment' ); 
    $cat_show              = $params->get( 'cat_show' );
    $cat_show_type         = $params->get( 'cat_show_type' );
    $cat_show_text         = $params->get( 'cat_show_text' );
    $cat_show_text         = JDownloadsTopHelper::getOnlyLanguageSubstring($cat_show_text);
    $cat_show_text_color   = $params->get( 'cat_show_text_color' );
    $cat_show_text_size    = $params->get( 'cat_show_text_size' );
    $cat_show_as_link      = $params->get( 'cat_show_as_link' ); 
    $view_tooltip          = $params->get( 'view_tooltip' ); 
    $view_tooltip_length   = intval($params->get( 'view_tooltip_length' ) ); 
    $alignment             = $params->get( 'alignment' );
    
    $thumbfolder = Uri::base().'images/jdownloads/screenshots/thumbnails/'; 
    $thumbnail = '';
    $border = ''; 
    
	if($cat_show_text != '') $cat_show_text = trim($cat_show_text).' ';	
    if ($view_date_text != '') $view_date_text = trim($view_date_text).' ';
    
    if ($sum_view == 0) $sum_view = 5;
    
    $option = 'com_jdownloads';
    
    // NOTE have to check if top module has alternate layout
	$layout = $params->get('layout', 'default');
    
	// See if the selected layout contains 'alternate' from jD3.2 series, if yes switch to default
	if(strpos($layout, 'alternate') !== false) {
		$layout = '_:default'; // For some reason the layouts from "$params->get('layout', 'default')" are preceded by "_:"
	}

    $files = JDownloadsTopHelper::getList($params, $model);

    if (!count($files)) {
	    return;
    }

    $moduleclass_sfx = $params->get('moduleclass_sfx');
	if($moduleclass_sfx != '') {
		$moduleclass_sfx = htmlspecialchars($moduleclass_sfx);	//only here if not empty
	}

    require ModuleHelper::getLayoutPath('mod_jdownloads_top', $layout); // 
?>