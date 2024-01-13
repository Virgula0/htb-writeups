<?php
/**
* @version		$Id: mod_jDMTree1.5.5
* @package		DOCMan jDMTree Module for Joomla 1.5
* @copyright	Copyright (C) 2008-2010 youthpole.com. All rights reserved.
* @author     Josh Prakash
* @license		GNU/GPL, see LICENSE.php
* This module is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*
* Dec-20-2010 Adapted and modified for jDownloads by Arno Betz
* Aug-20-2011 Adapted and modified for jDownloads 1.9 by Arno Betz
* Jun-16-2015 Adapted and modified for jDownloads 3.2 by Arno Betz
* Sep-15-20 Adapted and modified for jDownloads 4.0 by Arno Betz, ColinM
* Version 4.0
*
*/

defined('_JEXEC') or die;
	
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

use JDownloads\Module\JDownloadsTree\Site\Helper\JDownloadsTreeHelper;
use JDownloads\Component\JDownloads\Site\Model\DownloadsModel;

require_once(JPATH_SITE.'/modules/mod_jdownloads_tree/jdtree/jdownloadstree.php');    
    
    $app = Factory::getApplication();
    $user = Factory::getUser();
	$db	  = Factory::getDBO();
    
    $lang = Factory::getLanguage();
    $lang->load('com_jdownloads');      
    
    $input = $app->input;
	$Itemid = $input->get('Itemid');
	
    // Add css
    $document = Factory::getDocument();
	$document->addStyleSheet( Uri::base()."components/com_jdownloads/assets/css/jdownloads_modules.css");    
    
    // Get published root menu link
    $db->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=categories' and published = 1 AND client_id = 0");
    $root_itemid = $db->loadResult();
    if ($root_itemid){
        $Itemid = $root_itemid;
    }

    $home_url = Route::_('index.php?option=com_jdownloads&amp;view=categories&amp;Itemid='.$Itemid);
    $home_link = '<a href="'.$home_url.'">'.Text::_('COM_JDOWNLOADS_HOME_LINKTEXT').'</a>';

    $lengthc    = intval( $params->get( 'lengthc', 20 ) );	 // Max length of category before truncation
    $baseGif    = 'modules/mod_jdownloads_tree/jdtree/images/base.gif';
    $nodeId     = 0;
    $counter    = 0;
    $catlink    = '';
    $curcat     = 0;
    $precat     = -1;
	
    // NOTE have to check if tree module has alternate layout
	$layout = $params->get('layout', 'default');	
	
    // See if the selected layout contains 'alternate' from jD3.2 series, if yes switch to default
	if (strpos($layout, 'alternate') !== false){
		$layout = '_:default'; // For some reason the layouts from "$params->get('layout', 'default')" are preceded by "_:"
	}

    $rows = JDownloadsTreeHelper::getList($params);

    if (!$rows){
        return;
    }

	$moduleclass_sfx = '';  //assume empty
	$sfx = $params->get('moduleclass_sfx');
	if ($sfx){
		$moduleclass_sfx = htmlspecialchars($sfx);
	}

    require ModuleHelper::getLayoutPath('mod_jdownloads_tree', $layout);    
?>