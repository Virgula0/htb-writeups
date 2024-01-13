<?php
/**
* @version $Id: mod_jdownloads_stats.php
* @package mod_jdownloads_stats
* @copyright (C) 2018 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jdownloads.com
*
* This module shows the statistic values from the jDownloads component.
*/

defined( '_JEXEC' ) or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use JDownloads\Module\JDownloadsStats\Site\Helper\JDownloadsStatsHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;

	$app = Factory::getApplication();
	$db  = Factory::getDBO(); 
	$user= Factory::getUser(); 

    $text            = $params->get( 'text' );
    $text            = JDownloadsStatsHelper::getOnlyLanguageSubstring($text);	

    $text_admin      = $params->get( 'text_admin' );
    $text_admin      = JDownloadsStatsHelper::getOnlyLanguageSubstring($text_admin);    

	$color           = trim($params->get( 'value_color' ) );
	$alignment       = ($params->get( 'alignment' ) ); 
	    // NOTE have to check if module has alternate layout
	$layout = $params->get('layout', 'default');
    
	// See if the selected layout contains 'alternate' from jD3.2 series, if yes switch to default
	if(strpos($layout, 'alternate') !== false) {
		$layout = '_:default'; // For some reason the layouts from "$params->get('layout', 'default')" are preceded by "_:"
	}
    $result = JDownloadsStatsHelper::getData($params);    

    $sumcats        = JDownloadsStatsHelper::strToNumber($result['cats']);
    $sumfiles       = JDownloadsStatsHelper::strToNumber($result['files']);
    $sumdownloads   = JDownloadsStatsHelper::strToNumber($result['hits']);
    $sumviews       = JDownloadsStatsHelper::strToNumber($result['views']);

	$moduleclass_sfx = $params->get('moduleclass_sfx');
	if($moduleclass_sfx != '') {
		$moduleclass_sfx = htmlspecialchars($moduleclass_sfx);	//only here if not empty
	}
	
    require ModuleHelper::getLayoutPath('mod_jdownloads_stats',$layout);    
?>