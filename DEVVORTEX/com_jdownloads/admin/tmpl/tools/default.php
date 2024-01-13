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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');

$params = ComponentHelper::getParams('com_jdownloads');
$canDo  = JDownloadsHelper::getActions();

?>

<script type="text/javascript">
    function confirmAction(task)
    {
        if (task == 'resetDownloadCounter' ){
            var x = confirm("<?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_RESET_COUNTER_CONFIRM'); ?>")
            if (x == true){     
                window.location="index.php?option=com_jdownloads&task=tools.resetDownloadCounter"
            }
        }
        
        if (task == 'cleanImageFolders' ){
            var x = confirm("<?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_RESET_COUNTER_CONFIRM'); ?>")
            if (x == true){     
                window.location="index.php?option=com_jdownloads&task=tools.cleanImageFolders"
            }
        }
        
        if (task == 'cleanPreviewFolder' ){
            var x = confirm("<?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_RESET_COUNTER_CONFIRM'); ?>")
            if (x == true){     
                window.location="index.php?option=com_jdownloads&task=tools.cleanPreviewFolder"
            }
        }                 
        
        if (task == 'resetCom' ){
            var x = confirm("<?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_RESET_COUNTER_CONFIRM'); ?>")
            if (x == true){     
                window.location="index.php?option=com_jdownloads&task=tools.resetCom"
            }
        }
        
        if (task == 'resetCategoriesRules' ){
            var x = confirm("<?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_RESET_COUNTER_CONFIRM'); ?>")
            if (x == true){     
                window.location="index.php?option=com_jdownloads&task=tools.resetCategoriesRules"
            }
        }
        
        if (task == 'resetDownloadsRules' ){
            var x = confirm("<?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_RESET_COUNTER_CONFIRM'); ?>")
            if (x == true){     
                window.location="index.php?option=com_jdownloads&task=tools.resetDownloadsRules"
            }
        }         
        
        if (task == 'resetBatchSwitch' ){
                window.location="index.php?option=com_jdownloads&task=tools.resetBatchSwitch"
        }
        
        if (task == 'runBackup' ){
                window.location="index.php?option=com_jdownloads&view=backup"
        }        
        
        if (task == 'runRestore' ){
                window.location="index.php?option=com_jdownloads&view=restore"
        } 
        
        if (task == 'runOptionsExport' ){
                window.location="index.php?option=com_jdownloads&view=optionsexport"
    }
        
        if (task == 'runOptionsImport' ){
                window.location="index.php?option=com_jdownloads&view=optionsimport"
        }
        
        if (task == 'runOptionsDefault' ){
                window.location="index.php?option=com_jdownloads&view=optionsdefault"
        } 
        
        
    }
</script>
<?php

// Check user access rights
if ($canDo->get('core.admin', 'com_jdownloads') || $canDo->get('core.options', 'com_jdownloads')) {
 

?>

<form action="index.php" method="post" name="adminForm" id="adminForm">

    
    
    <!--<div style="margin-left:15px;">-->
    <div class="container-fluid">
        
        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="runBackup" value="<?php echo Text::_('COM_JDOWNLOADS_BACKUP'); ?>" onclick="confirmAction('runBackup')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_BACKUP_INFO_SHORT_DESC'); ?></span>
            </div>            
        </div>
        
        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="runRestore" value="<?php echo Text::_('COM_JDOWNLOADS_RESTORATION'); ?>" onclick="confirmAction('runRestore')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_RESTORE_FILE_DESC'); ?></span>
            </div>            
        </div>    
                            
        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="runOptionsExport" value="<?php echo Text::_('COM_JDOWNLOADS_OPTIONS_EXPORT'); ?>" onclick="confirmAction('runOptionsExport')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_OPTIONS_EXPORT_DESC'); ?></span>
            </div>            
        </div>
        
        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="runOptionsImport" value="<?php echo Text::_('COM_JDOWNLOADS_OPTIONS_IMPORT'); ?>" onclick="confirmAction('runOptionsImport')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_OPTIONS_IMPORT_DESC'); ?></span>
            </div>            
        </div>   

        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="runOptionsDefault" value="<?php echo Text::_('COM_JDOWNLOADS_OPTIONS_DEFAULT'); ?>" onclick="confirmAction('runOptionsDefault')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_OPTIONS_DEFAULT_DESC'); ?></span>
            </div>            
        </div>
                            
        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="resetcounter" value="<?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_RESET_COUNTER_TITEL'); ?>" onclick="confirmAction('resetDownloadCounter')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_RESET_COUNTER_DESC'); ?></span>
            </div>            
        </div>                
                    
        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="resetCategoriesRules" value="<?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_CAT_RULES_TITLE'); ?>" onclick="confirmAction('resetCategoriesRules')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_CAT_RULES_DESC'); ?></span>
            </div>            
        </div>   

        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="resetDownloadsRules" value="<?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_DOWNLOADS_RULES_TITLE'); ?>" onclick="confirmAction('resetDownloadsRules')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_DOWNLOADS_RULES_DESC'); ?></span>
            </div>            
        </div> 
                    
        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="cleanImageFolder" value="<?php echo Text::_('COM_JDOWNLOADS_TOOLS_DELETE_NOT_USED_PICS'); ?>" onclick="confirmAction('cleanImageFolders')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_TOOLS_DELETE_NOT_USED_PICS_DESC'); ?></span>
            </div>            
        </div> 

        <div class="row">
            <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="cleanPreviewFolder" value="<?php echo Text::_('COM_JDOWNLOADS_TOOLS_DELETE_NOT_USED_PREVIEWS'); ?>" onclick="confirmAction('cleanPreviewFolder')">
            </div>
            <div class="col-9 pt-3">
                <span><?php echo Text::_('COM_JDOWNLOADS_TOOLS_DELETE_NOT_USED_PREVIEWS_DESC'); ?></span>
            </div>            
        </div> 

        <?php 
        if ((int)$params->get('categories_batch_in_progress') == 1 || (int)$params->get('downloads_batch_in_progress') == 1) { ?>
            <div class="row">
                <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                    <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="resetbatch" value="<?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_BATCH'); ?>" onclick="confirmAction('resetBatchSwitch')">
                </div>
                <div class="col-9 pt-3">
                    <span><?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_BATCH_DESC'); ?></span>
                </div>            
            </div> 
        <?php } 
        else { ?>
            <div class="row">
                <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                    <input type="button" class="btn btn-primary" style="min-width: 280px; max-width: 350px;" name="resetbatch" disabled value="<?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_BATCH'); ?>" onclick="">     
                </div>
                <div class="col-9 pt-3">
                    <span><?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_BATCH_DESC'); ?></span>
                </div>            
            </div> 
        <?php } ?>
        
        <?php if ($params->get('com') != ''){ ?>
            <div class="row">
                <div class="col-3 pt-2" style="margin-bottom:5px; width: auto;">
                    <input type="button" class="btn btn-primary" name="resetbatch" value="<?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_COM'); ?>" onclick="confirmAction('resetCom')">
                </div>
                <div class="col-9 pt-3">
                    <span><?php echo Text::_('COM_JDOWNLOADS_TOOLS_RESET_COM_DESC'); ?></span>
                </div>            
            </div>
        <?php } ?>
        
    </div>
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="view" value="tools" />
    <input type="hidden" name="hidemainmenu" value="0" />
   </form>

<?php
   } else {
?>           
    <form action="index.php" method="post" name="adminForm" id="adminForm">
    <div>
        <fieldset style="background-color: #ffffff; margin-top:5px;" class="infotext">
            <div class="alert alert-danger">
                 <?php echo '<b>'.Text::_('COM_JDOWNLOADS_ALERTNOAUTHOR').'</b>'; ?>
            </div>
        </fieldset>
    </div>
    </form>           
<?php
   }    
?>