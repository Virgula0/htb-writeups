<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2018 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
 
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator'); 
    
?>

<script type="text/javascript">
    Joomla.submitbutton = function(pressbutton) {
        var form = document.getElementById('adminForm');

        // do field validation
        if (form.options_import_file.value == ""){
            alert("<?php echo Text::_('COM_JDOWNLOADS_RESTORE_NO_FILE', true); ?>");
        } else {
            var answer = confirm("<?php echo Text::_('COM_JDOWNLOADS_RESTORE_RUN_FINAL'); ?>")
            if (answer){
                form.submit();
            }    
        }
    }
</script>  

<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads');?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
   
    <?php if (!empty( $this->sidebar)) : ?>
        <div id="j-sidebar-container" class="col-2">
            <?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="col-10">
    <?php else : ?>
        <div id="j-main-container">
    <?php endif;?>   
    
    <div class="alert alert-info">
        <?php echo Text::_('COM_JDOWNLOADS_OPTIONS_IMPORT_INFO_DESC'); ?>
        <?php echo '<b>'.Text::_('COM_JDOWNLOADS_OPTIONS_IMPORT_WARNING_DESC').'</b>'; ?>
    </div>                
    
    <div class="well">            
        <div class="col-8" style="margin-bottom: 20px;">
            <input style="margin-left:10px;" class="input_box" id="options_import_file" name="options_import_file" type="file" size="80" />
            <input style="margin-left:10px;" class="btn btn-primary" type="button" value="<?php echo Text::_('COM_JDOWNLOADS_OPTIONS_IMPORT').'&nbsp; '; ?>" onclick="Joomla.submitbutton()" />
        </div>
        
    </div>
  
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="optionsimport.runimport" />
    <input type="hidden" name="view" value="optionsimport" />
    <input type="hidden" name="hidemainmenu" value="0" />
    
    <?php echo HTMLHelper::_('form.token'); ?>
   </form>
