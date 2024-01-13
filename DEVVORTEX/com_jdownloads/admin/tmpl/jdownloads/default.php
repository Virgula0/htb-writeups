<?php

/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2022 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

\defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;


    HTMLHelper::_('bootstrap.tooltip');
    
    HTMLHelper::_('behavior.multiselect');
    HTMLHelper::_('behavior.keepalive');
    HTMLHelper::_('behavior.formvalidator');  
    HTMLHelper::_('formbehavior.chosen', 'select', null, array('disable_search_threshold' => 0 ));

    // Load JavaScript message titles
    Text::script('ERROR');
    Text::script('WARNING');
    Text::script('NOTICE');
    Text::script('MESSAGE');

    Text::script('COM_CPANEL_UNPUBLISH_MODULE_SUCCESS');
    Text::script('COM_CPANEL_UNPUBLISH_MODULE_ERROR');

    $app  = Factory::getApplication();
    $user = $app->getIdentity();
                                                                    
    // Check whether the warning about general permission to download
    // should be deactivated. 
    $input = $app->input;
    $hide_rules = (int)$input->get('hide_rules');
    if ($hide_rules == 1){
        // Change param setting and reload CP again
        $result = JDownloadsHelper::changeParamSetting('hide_rules', 1);
        $app->redirect(Route::_('index.php?option=com_jdownloads'));
    } 

    $params = ComponentHelper::getParams('com_jdownloads');
    $hide_rules = (int)$params->get('hide_rules');
    
    $rules_info = '';
    $update_info = '';
    
    // Let's display the permission hint only one time in a session
    $session = $app->getSession();
    $permission_hint_viewed  = (int) $session->get( 'jd_permission_hint_viewed', 0 );  
    if (!$permission_hint_viewed){
        $session->set( 'jd_permission_hint_viewed', 1 );  
        $first_time = true;
    } else {
        $first_time = false;
    }

    // Check whether the warning about the general permission to download should be displayed
    if ($first_time && !$hide_rules){
        $db = Factory::getDBO();
        $db->setQuery("SELECT `rules` FROM #__assets WHERE `name` = 'com_jdownloads' AND `title` = 'com_jdownloads' AND `level` = '1'");
        $component_rules = $db->loadResult();
        if (strpos($component_rules, '"download":{"1":1}') !== false){
            $url = Route::_('index.php?option=com_config&view=component&component=com_jdownloads#permissions');
            $hide_url = Route::_('index.php?option=com_jdownloads&hide_rules=1');    
            $rules_info = '<div class="alert alert-warning alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                   <strong>'.Text::_('COM_JDOWNLOADS_DEFAULT_PERMISSION_HINT_TITLE').'</strong><br>'.Text::_('COM_JDOWNLOADS_DEFAULT_PERMISSION_HINT_DESC').'<br>';
            if ($user->authorise('core.admin', 'com_jdownloads')){
                $rules_info .=  '<a href="'.$url.'" class="btn btn-info btn-sm">'
                        .Text::_('COM_JDOWNLOADS_DEFAULT_PERMISSION_HINT_BUTTON').'</a> '
                        .'<a href="'.$hide_url.'" class="btn btn-danger btn-sm">'.Text::_('COM_JDOWNLOADS_DEFAULT_PERMISSION_HIDE_BUTTON').'</a></div>';
            } 
        }
    }

    // Check if the latest version is installed. If not, we display a corresponding message in the header. 
    $exist_newer_release = JDownloadsHelper::existNewerJDVersion();
    if ($exist_newer_release){                                                                                                                                                                                                                                                                                           
        $url = 'https://www.jdownloads.com/index.php/downloads.html';
        $update_info = '<div class="alert alert-danger alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
               <strong>'.Text::_('COM_JDOWNLOADS_CPANEL_NEW_RELEASE_HINT').' </strong>'.Text::sprintf('COM_JDOWNLOADS_CPANEL_NEW_RELEASE_LINK', $url).'</div>';
    }
    
    // Get download stats
    $stats_data = JDownloadsHelper::getDownloadStatsData();
    $stats_data['downloaded'] = sprintf(Text::_('COM_JDOWNLOADS_CP_TOTAL_SUM_DOWNLOADED_FILES'), '<span class="badge badge-warning">'.$stats_data['downloaded'].'</span>');
    
    $user_rules     = JDownloadsHelper::getUserRules();

    // Check that we have valid user rules - when not, create it from joomla users
    if (!$user_rules){
        $user_result = JDownloadsHelper::setUserRules();
    }
    
    $canDo = JDownloadsHelper::getActions();
    $option = 'com_jdownloads';
    
    $position = 'jdcpanel';
    
?>
    <form action="index.php" method="post" name="adminForm" id="adminForm">
    
    <div class="row">
        <div id="cpanel">
            <?php
                // Exist the defined upload root folder?
                if (!is_dir($params->get('files_uploaddir')) &&  $params->get('files_uploaddir') != ''){ ?>
                    <div class="alert alert-error" style="margin-top:10px;"><b><?php echo Text::sprintf('COM_JDOWNLOADS_AUTOCHECK_DIR_NOT_EXIST', $params->get('files_uploaddir')).'</b><br /><br />'.Text::_('COM_JDOWNLOADS_AUTOCHECK_DIR_NOT_EXIST_2'); ?></div> 
            <?php }  ?>
            
            <?php 
            if ($params->get('offline')) {
                echo '<div class="alert alert-error">';                     
                echo Text::_('COM_JDOWNLOADS_BACKEND_PANEL_STATUS_TITEL').' ';
                echo Text::_('COM_JDOWNLOADS_BACKEND_PANEL_STATUS_OFFLINE').'<br /><br />';
                echo Text::_('COM_JDOWNLOADS_BACKEND_PANEL_STATUS_DESC_OFFLINE').'<br /></div>';
            } else { 
                echo '<div class="alert alert-success">';
                echo Text::_('COM_JDOWNLOADS_BACKEND_PANEL_STATUS_TITEL').' ';
                echo Text::_('COM_JDOWNLOADS_BACKEND_PANEL_STATUS_ONLINE').'</div>';
            }
            
            // Display the information about the default permission settings, if required.
            if ($rules_info){
                echo $rules_info;
            }

            // Display the information about the new version
            if ($update_info){
                echo $update_info;
            }
     
            ?>
        </div>   
    </div>    
    
    <div id="cpanel-modules">
        <div class="cpanel-modules <?php echo $position; ?>">
            <div class="card-columns">
                <?php 
                foreach ($this->modules as $module)
                {
                    echo ModuleHelper::renderModule($module, array('style' => 'well'));
                }
                ?>
            </div>
        </div>
    </div>
    
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="controller" value="jdownloads" />
    </form>
    
    <?php
    // View the user information about the current jDownloads release and useful links
    if ($footer = JDownloadsHelper::buildBackendFooterText('text-center')){
        echo $footer;    
    }
    ?>
    
       
     
