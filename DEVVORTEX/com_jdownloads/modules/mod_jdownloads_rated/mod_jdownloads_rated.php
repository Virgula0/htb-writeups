<?php
/**
* @version $Id: mod_jdownloads_rated.php v4.0
* @package mod_jdownloads_rated
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
* This module shows you the most-rated or top-rated downloads from the jDownloads component. It is only for jDownloads 4.0 and later
*/

defined( '_JEXEC' ) or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;

use JDownloads\Module\JDownloadsRated\Site\Helper\JDownloadsRatedHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;

	$app = Factory::getApplication();
	$db  = Factory::getDBO();
	
    $input  = $app->input;
    $Itemid = (int)$input->get('Itemid');
	
	// Add style sheet
    $document = Factory::getDocument();
	$document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_modules.css"); 

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
	
	$image_dir = Uri::root(true).'/modules/mod_jdownloads_rated/mod_jdownloads_images/';
    
    $top_view              = $params->get( 'top_view' );
	$text_before           = '';  // Assume no text before
	$before = $params->get( 'text_before' );
	if($before != '') {
		$before        = trim($before);	// Only here if not empty
		$text_before   = JDownloadsRatedHelper::getOnlyLanguageSubstring($before);
	}
    $text_after           = '';  // Assume no text after
	$after = $params->get( 'text_after' );
	if($after != '') {
		$after        = trim($after);	// Only here if not empty
		$text_after   = JDownloadsRatedHelper::getOnlyLanguageSubstring($after);
	}

    $catid                 = (int)$params->get('catid', array()); 
    $sum_view              = intval($params->get( 'sum_view' ));
    $sum_char              = intval($params->get( 'sum_char' ));
    $short_char            = $params->get( 'short_char', '' ) ; 
    $short_version         = $params->get( 'short_version', '' );
    $detail_view           = $params->get( 'detail_view' ) ;
    
    $view_date             = intval($params->get( 'view_date') ) ;
    $view_date_same_line   = intval($params->get( 'view_date_same_line') );
	$view_date_label        = $params->get( 'view_date_label' );
    $view_date_label        = JDownloadsRatedHelper::getOnlyLanguageSubstring($view_date_label);	
    // We use the standard short date format from the activated language when here is not a format defined 
    $date_format           = $params->get( 'date_format', Text::_('DATE_FORMAT_LC4') );
	
    $view_hits             = intval($params->get( 'view_hits' ) );
    $view_hits_same_line   = intval($params->get( 'view_hits_same_line' ));
    $view_hits_label       = $params->get( 'view_hits_label' );
	$view_hits_label       = JDownloadsRatedHelper::getOnlyLanguageSubstring($view_hits_label);	
    
    $view_pics             = intval($params->get( 'view_pics' ));
	$view_pics_link        = intval($params->get( 'view_pics_link' ));
    $view_pics_size        = intval($params->get( 'view_pics_size' )) ;
    
    $view_numerical_list   = intval($params->get( 'view_numerical_list' ));
    $view_stars            = intval($params->get( 'view_stars' ) );
    $view_stars_same_line  = intval($params->get( 'view_stars_same_line' ) );
	$view_votes            = intval($params->get( 'view_votes' ) );
    $view_stars_votes_same_line  = intval($params->get( 'view_stars_votes_same_line' ) );
    $alignment             = $params->get( 'alignment' );
    $moduleclass_sfx       = htmlspecialchars($params->get('moduleclass_sfx'));

	if($view_date_label != '') $view_date_label = trim($view_date_label).' ';	// Only here if not empty
	if($view_hits_label != '') $view_hits_label = trim($view_hits_label).' ';
	
    if ($sum_view == 0) $sum_view = 5;
    $option = 'com_jdownloads';

	$layout = $params->get('layout', 'default');
    
	// See if the selected layout contains 'alternate' from jD3.2 series, if yes switch to default (needed if update from earlier versions)
	if(strpos($layout, 'alternate') !== false) {
		$layout = '_:default'; // For some reason the layouts from "$params->get('layout', 'default')" are preceded by "_:"
	}        

	$files = JDownloadsRatedHelper::getList($params);
    if (!count($files)) {
        return;
    }
    
    require ModuleHelper::getLayoutPath('mod_jdownloads_rated',$layout);    
?>