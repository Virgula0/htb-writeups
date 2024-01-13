<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2021 - Arno Betz - www.jdownloads.com
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
use Joomla\CMS\Layout\LayoutHelper;

    HtmlHelper::_('bootstrap.tooltip');
    
    HTMLHelper::_('behavior.keepalive');
    HTMLHelper::_('behavior.formvalidator');    
    
    HTMLHelper::_('formbehavior.chosen', 'select', null, array('disable_search_threshold' => 2 ));

?>
<script type="text/javascript">
    Joomla.submitbutton = function(task) {
        var form = document.getElementById('adminForm');
        var images = form.jform_images.value;
        var files  = form.jform_files.value;
        var tables = form.jform_tables.value;
        
        if (task == 'uninstall.cancel'){
            Joomla.submitform(task);
        } else {
            if (images == '0' || files == '0' || tables == '0'){
                var answer = confirm("<?php echo Text::_('COM_JDOWNLOADS_RESTORE_RUN_FINAL'); ?>")
                if (answer){
                    Joomla.submitform(task);
                }    
            } else {
                Joomla.submitform(task);
            }
        }
    }
</script>  

<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads');?>" method="post" name="adminForm" id="adminForm">
   
    <div>
        <fieldset class="adminform">
            <legend><h3><?php echo Text::_('COM_JDOWNLOADS_UNINSTALL_OPTIONS_LABEL'); ?></h3></legend>
  
            <div class="alert alert-warning"><?php echo Text::_('COM_JDOWNLOADS_UNINSTALL_WARNING'); ?>
            </div>                
            
            <div class="row">
                <div class="col-sm-12">
                        <?php echo $this->form->renderField('images'); ?>    
                        <?php echo $this->form->renderField('files'); ?>    
                        <?php echo $this->form->renderField('tables'); ?>    
                </div>
            </div>
            
            <input style="" class="btn btn-danger" type="button" value="<?php echo Text::_('COM_JDOWNLOADS_UNINSTALL_RUN').'&nbsp; '; ?>" onclick="Joomla.submitbutton('uninstall.rununinstall')" />
            <input style="margin:10px;" class="btn btn-success" type="button" value="<?php echo Text::_('COM_JDOWNLOADS_UNINSTALL_CANCEL').'&nbsp; '; ?>" onclick="Joomla.submitbutton('uninstall.canceluninstall')" />
              
        </fieldset>
    </div>
  
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="uninstall.rununinstall" />
    <input type="hidden" name="view" value="uninstall" />
    <input type="hidden" name="hidemainmenu" value="0" />
    
    <?php echo HtmlHelper::_('form.token'); ?>
   </form>
