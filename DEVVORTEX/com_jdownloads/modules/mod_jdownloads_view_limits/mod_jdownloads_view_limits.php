<?php
/**
* @version $Id: mod_jdownloads_related.php
* @package mod_jdownloads_related
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
* This module shows you some the User Group limits for the logged in user from the jDownloads component. 
* It is only for jDownloads 4.0 and later (Support: www.jDownloads.com)
*/

defined( '_JEXEC' ) or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;

use JDownloads\Module\JDownloadsViewLimits\Site\Helper\JDownloadsViewLimitsHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;

    $app = Factory::getApplication(); 
    $db  = Factory::getDBO();
    
    $user = Factory::getUser();
    if (!$user->id){
        // User is guest
        return;
    }
    
    $types = $params->get('limit_types');
     
    if (!isset($types) && !$params->get('display_no_limits_found_msg')){
        // No limit type selected
        return;
    } 
    
    $access_groups = implode(',', $user->getAuthorisedGroups()); 
    $access_levels = implode(',', $user->getAuthorisedViewLevels());    
    
    $document   = Factory::getDocument();
    $active_language = $document->language;
   
    $view_link = (int)$params->get('display_link_to_history'); 
    if ($view_link){
        $history_link_id = JDownloadsViewLimitsHelper::getHistoryLink($params, $active_language, $access_levels);
    } else {
        $history_link_id = 0;
    }
    
	// NOTE have to check if module has alternate layout
	$layout = $params->get('layout', 'default');
    
	// See if the selected layout contains 'alternate' from jD3.2 series, if yes switch to default
	if(strpos($layout, 'alternate') !== false) {
		$layout = '_:default'; // For some reason the layouts from "$params->get('layout', 'default')" are preceded by "_:"
	}
    $alignment             = $params->get( 'alignment' );

    $moduleclass_sfx = $params->get('moduleclass_sfx');
	if($moduleclass_sfx != '') {
		$moduleclass_sfx = htmlspecialchars($moduleclass_sfx, ENT_COMPAT, 'UTF-8');	//only here if not empty
	}    
    
    $total_consumed = JDownloadsViewLimitsHelper::getLimits($params);

    if ($total_consumed['no_limits_defined']){
        // 'No Limits' message not activated
        if (!$params->get('display_no_limits_found_msg')){
            return;
        }
    }
    
    require ModuleHelper::getLayoutPath('mod_jdownloads_view_limits', $layout);
?>