<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_search
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
/**
 * @package jDownloads
 * @version 4.0  
 * Some parts from the search component 3.x (and search content plugin) adapted and modified to can use it in jDownloads 4.x as an internal search function. 
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;

    $app    = Factory::getApplication();
    $params = $app->getParams();

	// Get the current user for authorisation checks
    $user = Factory::getUser();
    $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;    
        
    ?>

    <div class="search<?php echo $this->pageclass_sfx; ?>">

    <?php if ($this->params->get('show_page_heading')) : ?>
	<h1 class="page-title">
	    <?php if ($this->escape($this->params->get('page_heading'))) :?>
		    <?php echo $this->escape($this->params->get('page_heading')); ?>
	    <?php else : ?>
		    <?php echo $this->escape($this->params->get('page_title')); ?>
	    <?php endif; ?>
    </h1>
    <?php endif; ?>

    <?php
    
    // view offline message - but admins can see it always    
    if ($params->get('offline') && !$is_admin){
        if ($params->get('offline_text') != '') {
            echo JDHelper::getOnlyLanguageSubstring($params->get('offline_text'));
        }
    } else { 

        echo $this->loadTemplate('form');
        if ($this->error == null && count($this->results) > 0){
	        echo $this->loadTemplate('results');
        } else {
	        echo $this->loadTemplate('error');
        }
    }
    
    // ==========================================
    // FOOTER SECTION  
    // ==========================================

    $footer = '';    
    
    $layout = JDHelper::getLayout(7);
    if ($layout){
        $footer = $layout->template_footer_text;
    }
    
    // components footer text
    if ($params->get('downloads_footer_text') != '') {
        $footer_text = stripslashes(JDHelper::getOnlyLanguageSubstring($params->get('downloads_footer_text')));
 
        // replace both Google adsense placeholder with script
        $footer_text = JDHelper::insertGoogleAdsenseCode($footer_text);                 
        $footer .= $footer_text;    
    }
    
    // we need here not a back button
    $footer = str_replace('{back_link}', '', $footer);
    $footer .= JDHelper::checkCom();
    
    // remove empty html tags
    if ($params->get('remove_empty_tags')){
        $footer = JDHelper::removeEmptyTags($footer);
    }
            
    echo $footer.'</div>'; 

?>