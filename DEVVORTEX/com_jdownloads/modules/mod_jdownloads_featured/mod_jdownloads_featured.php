<?php
/**
* @version $Id: mod_jdownloads_featured.php v4.0
* @package mod_jdownloads_featured
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

/** This Module shows the newest added 'featured' downloads from the component jDownloads. 
*/

defined( '_JEXEC' ) or die;
    
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use JDownloads\Module\JDownloadsFeatured\Site\Helper\JDownloadsFeaturedHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;
use Joomla\CMS\Language\Text;


    $app = Factory::getApplication();
    $db = Factory::getDBO();

    $input = $app->input;
    $Itemid  = $input->get('Itemid');
	
    $model = $app->bootComponent('com_jdownloads')->getMVCFactory()->createModel('Downloads', 'Site', ['ignore_request' => true]);    

	//add style sheet
    $document = Factory::getDocument();
	$document->addStyleSheet( URI::base()."components/com_jdownloads/assets/css/jdownloads_modules.css");
	// get published root menu link
    $db->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=categories' and published = 1 AND client_id = 0");
    $root_itemid = $db->loadResult();
    
    if ($root_itemid){
        $Itemid = $root_itemid;
    }
    
    // get this option from configuration to see whether the links shall run the download without summary page
	$jdparams = $app->getParams('com_jdownloads');
	$direct_download_config = $jdparams->get('direct_download');
	$detail_view_config = $jdparams->get('view_detailsite');   

    $text_before           = '';  // assume no text before
	$before = $params->get( 'text_before' );
	if($before != '') {
		$before        = trim($before);	//only here if not empty
		$text_before   = JDownloadsFeaturedHelper::getOnlyLanguageSubstring($before);
	}
    $text_after           = '';  // assume no text after
	$after = $params->get( 'text_after' );
	if($after != '') {
		$after        = trim($after);	//only here if not empty
		$text_after   = JDownloadsFeaturedHelper::getOnlyLanguageSubstring($after);
	}		

    $catid                 = $params->get('catid', array()); 
    $sum_view              = intval($params->get( 'sum_view' ));
    $sum_char              = intval($params->get( 'sum_char' ));
    $short_char            = $params->get( 'short_char' ) ; 
    $short_version         = $params->get( 'short_version' );
    $detail_view           = $params->get( 'detail_view' ) ; 
    $view_date             = $params->get( 'view_date' ) ;
    $view_date_same_line   = $params->get( 'view_date_same_line' );
	$view_date_text        = $params->get( 'view_date_text', '' );
    $view_date_text        = JdownloadsFeaturedHelper::getOnlyLanguageSubstring($view_date_text);	
    // We use the standard short date format from the activated language when here is not a format defined 
    $date_format           = $params->get( 'date_format', Text::_('DATE_FORMAT_LC4') );
	$date_alignment        = $params->get( 'date_alignment' );
	$view_hits             = $params->get( 'view_hits' ) ;
    $view_hits_same_line   = $params->get( 'view_hits_same_line' );
    $hits_label            = $params->get( 'hits_label' );
	$hits_label        	   = JdownloadsFeaturedHelper::getOnlyLanguageSubstring($hits_label);
    $hits_alignment        = $params->get( 'hits_alignment' );
    $view_pics             = $params->get( 'view_pics' ) ;
    $view_pics_size        = $params->get( 'view_pics_size' ) ;
	$view_pics_link        = $params->get( 'view_pics_link' ) ;
    $view_numerical_list   = $params->get( 'view_numerical_list' );
    $view_thumbnails       = $params->get( 'view_thumbnails' );
	$view_thumbnails_link  = $params->get( 'view_thumbnails_link' ) ;
    $view_thumbnails_size  = $params->get( 'view_thumbnails_size' );
    $view_thumbnails_dummy = $params->get( 'view_thumbnails_dummy' );
    $date_alignment        = $params->get( 'date_alignment' ); 
    $cat_show              = $params->get( 'cat_show' );
    $cat_show_type         = $params->get( 'cat_show_type' );	
    $cat_show_text         = $params->get( 'cat_show_text' );	
    $cat_show_text         = JDownloadsFeaturedHelper::getOnlyLanguageSubstring($cat_show_text);	
    $cat_show_text_color   = $params->get( 'cat_show_text_color' );
    $cat_show_text_size    = $params->get( 'cat_show_text_size' );
    $cat_show_as_link      = $params->get( 'cat_show_as_link' ); 
    $view_tooltip          = $params->get( 'view_tooltip' ); 
    $view_tooltip_length   = intval($params->get( 'view_tooltip_length' ) ); 
    $alignment             = $params->get( 'alignment' );
    $featured_filename	   = $params->get( 'featured_pic_filename' );  // this is NOT in the module parameters!! 
	$featured_folder       = Uri::base().'images/jdownloads/featuredimages/';
    $thumbfolder           = Uri::base().'images/jdownloads/screenshots/thumbnails/';
    $thumbnail = '';
    $border = '';
      
    //remove white space then add space only applies if item not empty		
	if($cat_show_text != '') $cat_show_text = trim($cat_show_text).' ';
	if($view_date_text != '') $view_date_text = trim($view_date_text).' ';
	if($hits_label != '') $hits_label = trim($hits_label).' ';
	
    if ($sum_view == 0) $sum_view = 5;
    $option = 'com_jdownloads';
	
    $layout = $params->get('layout', 'default');
	
	// see if the selected layout contains 'alternate' from jD3.2 series, if yes switch to default (needed if update from earlier versions)
	if (strpos($layout, 'alternate') !== false) {
		$layout = '_:default'; //for some reason the layouts from "$params->get('layout', 'default')" are preceded by "_:"
	}    
        
    $files = JDownloadsFeaturedHelper::getList($params, $model);

    if (!count($files)) {
	    return;
    }

	$moduleclass_sfx = $params->get('moduleclass_sfx');
	if($moduleclass_sfx != '') $moduleclass_sfx = htmlspecialchars($moduleclass_sfx);	//only here if not empty

    require ModuleHelper::getLayoutPath('mod_jdownloads_featured', $layout);
?>