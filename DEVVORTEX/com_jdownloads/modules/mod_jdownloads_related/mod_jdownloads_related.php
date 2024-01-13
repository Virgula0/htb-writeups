<?php
/**
* @version $Id: mod_jdownloads_related.php
* @package mod_jdownloads_related
* @copyright (C) 2018 - 2020 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
* This modul shows you some related downloads from the jDownloads component. 
* It is only for jDownloads 4.0 and later (Support: www.jDownloads.com)
*/

defined( '_JEXEC' ) or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Registry\Registry;

use JDownloads\Module\JDownloadsRelated\Site\Helper\JDownloadsRelatedHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;

    $classname = 'JDownloads\Module\JDownloadsRelated\Site\Helper\JDownloadsRelatedHelper'; //<--- namespace 
    if (!class_exists($classname)) {
        $path = JPATH_SITE . '/modules/mod_jdownloads_related/src/Helper/JDownloadsRelatedHelper.php'; 
        if (is_file($path)) {
            include_once $path;
            \JLoader::register($classname, $path); 
        } else {
            return false;
        }
    }
    
    $app = Factory::getApplication();
    $db = Factory::getDBO();
    
	$input  = $app->input;
    
    $Itemid = (int)$input->get('Itemid');
    $option = $input->get('option');
    $id     = (int)$input->get('id');
    $catid  = (int)$input->get('catid');
	
    // Add css
    $document = Factory::getDocument();
    $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_modules.css");
    
    if ($option != 'com_jdownloads'){
        return;
    }
    
    if (!$catid || !$id){
        return;
    }
    
    $catids = array( $catid ); 
    
    // Get published root menu link
    $db->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=categories' and published = 1 AND client_id = 0");
    $root_itemid = $db->loadResult();
    
    if ($root_itemid){
        $Itemid = $root_itemid;
    }
    
    // Get this option from configuration to see whether the links shall run the download without summary page
	$jdparams = $app->getParams('com_jdownloads');
	$direct_download_config = $jdparams->get('direct_download');
	$detail_view_config = $jdparams->get('view_detailsite');    

    $text_before        = '';  // Assume no text before
	$before = $params->get( 'text_before' );
	if ($before != '') {
		$before        = trim($before);	//only here if not empty
		$text_before   = JDownloadsRelatedHelper::getOnlyLanguageSubstring($before);
	}
	
    $text_after       = '';  // Assume no text after
	$after = $params->get( 'text_after' );
	if ($after != '') {
		$after        = trim($after);	// Only here if not empty
		$text_after   = JDownloadsRelatedHelper::getOnlyLanguageSubstring($after);
	}
	
	$title_text       = '';  // Assume no title text
	$title_text = $params->get( 'title' );
	if ($title_text != ''){
		$title_text   = trim($title_text);	// Only here if not empty
		$title_text   = JDownloadsRelatedHelper::getOnlyLanguageSubstring($title_text);
	}    

    $view_not_found        = intval(($params->get( 'view_not_found' ) ));
    $sum_view              = intval(($params->get( 'sum_view' ) ));
    $sum_view++;
    
    $sum_char              = intval(($params->get( 'sum_char' ) ));
    $short_char            = $params->get( 'short_char' ) ; 
    $short_version         = $params->get( 'short_version' );
    $detail_view           = $params->get( 'detail_view' ) ; 
    $view_hits             = $params->get( 'view_hits' ) ;
    $view_hits_same_line   = $params->get( 'view_hits_same_line' );

    $hits_label        = '';  // Assume hits label is blank
	$hits = $params->get( 'hits_label' );
	if ($hits != '') {
		$hits         = trim($hits);	// Only here if not empty
		$hits_label   = JDownloadsRelatedHelper::getOnlyLanguageSubstring($hits);
	}

    $hits_alignment        = $params->get( 'hits_alignment' );
    $view_date             = $params->get( 'view_date' ) ;
    $view_date_same_line   = $params->get( 'view_date_same_line' );

	$view_date_text        = '';  // Assume $view_date_text is blank
	$date_text			   = $params->get( 'view_date_text', '' );
	if ($date_text != '') {
		$date_text         = trim($date_text);	// Only here if $date_text not empty
		$view_date_text    = JDownloadsRelatedHelper::getOnlyLanguageSubstring($date_text);
		$view_date_text    = $view_date_text.' ';
	}
    
    // We use the standard short date format from the activated language when here is not a format defined 
    $date_format           = $params->get( 'date_format', Text::_('DATE_FORMAT_LC4') );
    $date_alignment        = $params->get( 'date_alignment' ); 
    $view_pics             = $params->get( 'view_pics' ) ;
    $view_pics_size        = $params->get( 'view_pics_size' ) ;
	$view_pics_link        = $params->get( 'view_pics_link' ) ;
    $view_numerical_list   = $params->get( 'view_numerical_list' );
    $view_thumbnails       = $params->get( 'view_thumbnails' );
    $view_thumbnails_size  = $params->get( 'view_thumbnails_size' );
    $view_thumbnails_dummy = $params->get( 'view_thumbnails_dummy' );
	$view_thumbnails_link  = $params->get( 'view_thumbnails_link' ) ;
    $hits_alignment        = $params->get( 'hits_alignment' ); 
    $cat_show              = $params->get( 'cat_show' );
    $cat_show_type         = $params->get( 'cat_show_type' );
	
    $cat_show_text       = '';  // Assume no cat show text
	$cat_text = $params->get( 'cat_show_text' );
	if ($cat_text != '') {
		$cat_text        = trim($cat_text);	// Only here if not empty
		$cat_show_text   = JDownloadsRelatedHelper::getOnlyLanguageSubstring($cat_text);
		$cat_show_text = ' '.$cat_show_text.' ';
	}	
	
    $cat_show_text_color   = $params->get( 'cat_show_text_color' );
    $cat_show_text_size    = $params->get( 'cat_show_text_size' );
    $cat_show_as_link      = $params->get( 'cat_show_as_link' ); 
    $view_tooltip          = $params->get( 'view_tooltip' ); 
    $view_tooltip_length   = intval($params->get( 'view_tooltip_length' ) ); 
    $alignment             = $params->get( 'alignment' );
    
    $moduleclass_sfx = $params->get('moduleclass_sfx');
	if ($moduleclass_sfx != '') {
		$moduleclass_sfx = htmlspecialchars($moduleclass_sfx);	// Only here if not empty
	}

    $thumbfolder = Uri::base().'images/jdownloads/screenshots/thumbnails/';
    $thumbnail = '';
    $border = ''; 

    // NOTE have to check if related module has alternate layout
	$layout = $params->get('layout', 'default');
    
	// See if the selected layout contains 'alternate' from jD3.2 series, if yes switch to default
	if(strpos($layout, 'alternate') !== false) {
		$layout = '_:default'; // For some reason the layouts from "$params->get('layout', 'default')" are preceded by "_:"
	}

    if ($sum_view == 0) $sum_view = 5;
    
    $files = JDownloadsRelatedHelper::getList($params, $catids, $id);

    if (count($files) < 2 && !$view_not_found){
        return;
    }
    
    require ModuleHelper::getLayoutPath('mod_jdownloads_related', $layout);
?>