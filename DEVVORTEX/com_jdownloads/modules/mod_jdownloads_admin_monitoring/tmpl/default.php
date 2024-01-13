<?php
/**
* @version $Id: mod_jdownloads_admin_monitoring.php v4.0
* @package mod_jdownloads_admin_monitoring
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

HTMLHelper::_('bootstrap.tooltip');
    
    $canDo  = JDownloadsHelper::getActions();
    $app    = Factory::getApplication();
    $user   = $app->getIdentity();
    
    // Get the secret key then we need it as link param
    // So nobody else outside can run the script (or he know the key value - e.g. to start it via a cronjob)
    $config = Factory::getConfig();
    $key    = $config->get( 'secret' );                         
    $test   = (int)$params->get('use_first_testrun');

    $url = 'index.php?option=com_config&view=component&component=com_jdownloads#monitoring';
    ?>
    <div class="accordion" id="accordionPanels">
        <div class="accordion-item">
            <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="false" aria-controls="panelsStayOpen-collapseOne">
                    <?php echo Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_DESC_LONG_LABEL'); ?>
                </button>
            </h2>
        </div>
        <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingOne">
            <div class="accordion-body">
                <div>
                    <?php echo Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_DESC_LONG'); ?>
                </div>    
            </div>
        </div>
    </div>
        
    <div class="alert alert-info">    
        <div style="margin-top:15px;">
            <div class="dropdown clearfix">
                <button id="dropdownMenu1" class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?php echo Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_BUTTON_TEXT').'&nbsp;'; ?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                    <li><a class="dropdown-item" href="<?php echo Uri::base();?>components/com_jdownloads/helpers/scan.php?key=<?php echo $key; ?>&mode=0&test=<?php echo (int)$test; ?>" target="_blank" onclick="openWindow(this.href, 850, 400); return false"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_SELECT_OPTION_ALL'); ?></a></li>
                    <li role="separator" class="divider"></li>
                    <li><a class="dropdown-item" href="<?php echo Uri::base();?>components/com_jdownloads/helpers/scan.php?key=<?php echo $key; ?>&mode=1&test=<?php echo (int)$test; ?>" target="_blank" onclick="openWindow(this.href, 650, 400); return false"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_SELECT_OPTION_SEARCH_DIRS'); ?></a></li>
                    <li><a class="dropdown-item" href="<?php echo Uri::base();?>components/com_jdownloads/helpers/scan.php?key=<?php echo $key; ?>&mode=2&test=<?php echo (int)$test; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_SELECT_OPTION_FILES_HINT'); ?>" target="_blank" onclick="openWindow(this.href, 650, 400); return false"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_SELECT_OPTION_SEARCH_FILES'); ?></a></li>
                    <li><a class="dropdown-item" href="<?php echo Uri::base();?>components/com_jdownloads/helpers/scan.php?key=<?php echo $key; ?>&mode=3&test=<?php echo (int)$test; ?>" target="_blank" onclick="openWindow(this.href, 650, 400); return false"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_SELECT_OPTION_CHECK_CATS'); ?></a></li>
                    <li><a class="dropdown-item" href="<?php echo Uri::base();?>components/com_jdownloads/helpers/scan.php?key=<?php echo $key; ?>&mode=4&test=<?php echo (int)$test; ?>" target="_blank" onclick="openWindow(this.href, 650, 400); return false"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_SELECT_OPTION_CHECK_DOWNLOADS'); ?></a></li>
                </ul>
            </div>
        </div>
        <div style="margin-top:11px;">                                        
            <?php echo '<small>'.Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_RUN_MONITORING_INFO').'</small>'; ?>
            <?php echo '<ul>';
                  
                if ($test) {
                    echo '<li><small>'.Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_TEST_RUN_ACTIVE_HINT').'</small></li>'; 
                }
                  
                if (!$params->get('all_folders_autodetect')) {
                    echo '<li><small>'.Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_EXCLUDE_INCLUDE_OPTION_IS_ACTIVE_HINT').'</small></li>'; 
                }
                  
                if ($params->get('autopublish_founded_files')) {
                    echo '<li><small>'.Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_AUTO_PUBLISH_NEW_FOUND_ITEMS_HINT').'</small></li>'; 
                }
                  
                if ($params->get('autopublish_use_cat_default_values')) {
                    echo '<li><small>'.Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_AUTO_PUBLISH_USE_DEFAULT_CAT').'</small></li>'; 
                }
                
                if ($params->get('autopublish_use_default_values')) {
                    echo '<li><small>'.Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_AUTO_PUBLISH_USE_DEFAULT_FILE').'</small></li>'; 
                }
                  
                echo '</ul>';
                
                if ($user->authorise('core.admin', 'com_jdownloads') || $user->authorise('core.options', 'com_jdownloads')) {
                    echo '<a href="' . $url . '" class="badge bg-info">' . Text::_('MOD_JDOWNLOADS_ADMIN_MONITORING_CHANGE_OPTIONS') . '</a>';
                }  
            ?>
        </div>
    </div>
