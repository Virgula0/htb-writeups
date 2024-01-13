<?php
/**
* @version $Id: mod_jdownloads_most_recently_downloaded.php v.4.0
* @package mod_jdownloads_most_recently_downloaded
* @copyright (C) 2008/2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
* 
* This Module shows the Most Recently Downloaded from the component jDownloads.
*/

defined( '_JEXEC' ) or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;

use JDownloads\Module\JDownloadsMostRecentlyDownloaded\Site\Helper\JDownloadsMostRecentlyDownloadedHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;

    $app	 = Factory::getApplication();
	$db      = Factory::getDBO(); 
	$user    = Factory::getUser(); 
	$Itemid  = $input->get('Itemid');
    $option  = 'com_jdownloads';
	
    // Add css
    $document = Factory::getDocument();
    $document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_modules.css");
    
    // Get published root menu link
    $db->setQuery('SELECT id from #__menu WHERE link = ' . $db->quote('index.php?option=com_jdownloads&view=categories') . 'and published = 1 AND client_id = 0');
    $root_itemid = $db->loadResult();
    
    if ($root_itemid){
        $Itemid = $root_itemid;
    }
	
    // Get this option from configuration to see whether the links shall run the download without summary page
    
	$jdparams = $app->getParams('com_jdownloads');
	$direct_download_config = $jdparams->get('direct_download');
	$detail_view_config = $jdparams->get('view_detailsite');    

    $text_before      = '';  // Assume no text before
	$before = $params->get( 'text_before' );
	
    if($before != '') {
		$before       = trim($before);	// Only here if not empty
		$text_before  = JDownloadsMostRecentlyDownloadedHelper::getOnlyLanguageSubstring($before);
	}
    
    $text_after       = '';  // Assume no text after
	$after = $params->get( 'text_after' );
	
    if($after != '') {
		$after        = trim($after);	// Only here if not empty
		$text_after   = JDownloadsMostRecentlyDownloadedHelper::getOnlyLanguageSubstring($after);
	}
    	    
	$catid           = $params->get( 'catid', array() );
	$sum_view        = intval(($params->get( 'sum_view', 5 ) ));
	$sum_char        = intval(($params->get( 'sum_char' ) ));
	$short_char      = ($params->get( 'short_char', '' ) ); 
	$short_version   = ($params->get( 'short_version', '' ) );
	$detail_view     = ($params->get( 'detail_view' ) ); 

	$view_date       = ($params->get( 'view_date' ) );
	$view_date_same_line = ($params->get( 'view_date_same_line' ) );
	$view_date_text  = ($params->get( 'view_date_text', '' ) );
    $view_date_text  = JDownloadsMostRecentlyDownloadedHelper::getOnlyLanguageSubstring($view_date_text);        
	$date_format     = ($params->get( 'date_format' ) );
	$date_alignment  = ($params->get( 'date_alignment' ) );

	$view_user       = ($params->get( 'view_user' ) ); 
	$view_user_by    = ($params->get( 'view_user_by' ) );
	$view_user_name    = ($params->get( 'view_user_name' ) );
	$view_user_name  = JDownloadsMostRecentlyDownloadedHelper::getOnlyLanguageSubstring($view_user_name);

	$view_pics       = ($params->get( 'view_pics' ) );
	$view_pics_link  = $params->get( 'view_pics_link' );
	$view_pics_size  = ($params->get( 'view_pics_size' ) );
	$view_numerical_list = ($params->get( 'view_numerical_list' ) );

	$cat_show    	 = ($params->get( 'cat_show' ) );
	$cat_show_type	 = ($params->get( 'cat_show_type' ) );
	$cat_show_text   =  ($params->get( 'cat_show_text' ) );
    $cat_show_text         = JDownloadsMostRecentlyDownloadedHelper::getOnlyLanguageSubstring($cat_show_text);
	$cat_show_text_color   = ($params->get( 'cat_show_text_color' ) );
	$cat_show_text_size    = ($params->get( 'cat_show_text_size' ) );
	$cat_show_as_link      = ($params->get( 'cat_show_as_link' ) ); 

	$view_tooltip          = ($params->get( 'view_tooltip' ) ); 
	$view_tooltip_length   = intval(($params->get( 'view_tooltip_length' ) ));
	$alignment       = ($params->get( 'alignment' ) ); 

	if($cat_show_text != '') $cat_show_text = trim($cat_show_text).' ';
	if($view_date_text != '') $view_date_text = trim($view_date_text).' ';	// Only here if not empty

    if ($sum_view == 0) $sum_view = 5;
    
    // NOTE have to check if module has alternate layout
	$layout = $params->get('layout', 'default');
    
	// See if the selected layout contains 'alternate' from jD3.2 series, if yes switch to default (needed if update from earlier versions)
	if(strpos($layout, 'alternate') !== false) {
		$layout = '_:default'; // For some reason the layouts from "$params->get('layout', 'default')" are preceded by "_:"
	}

    $files = JDownloadsMostRecentlyDownloadedHelper::getList($params);
	if (empty($files)){
		return;  // Case where there are no recently downloaded Downloads
	}
    if (!count($files)) {
	    return;
    }
	$moduleclass_sfx = $params->get('moduleclass_sfx');
	if($moduleclass_sfx != '') $moduleclass_sfx = htmlspecialchars($moduleclass_sfx);	// Only here if not empty

    require ModuleHelper::getLayoutPath('mod_jdownloads_most_recently_downloaded', $layout);

?>