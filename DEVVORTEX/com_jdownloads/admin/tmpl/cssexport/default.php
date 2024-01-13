<?php
/*
 * @package Joomla
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 * @component jDownloads
 * @version 2.0  
 * @copyright (C) 2007 - 2011 - Arno Betz - www.jdownloads.com
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

HTMLHelper::_('behavior.formvalidator'); 
HtmlHelper::_('bootstrap.tooltip');

?>
<script type="text/javascript">
    Joomla.submitbutton = function(pressbutton) {
        var form = document.getElementById('adminForm');

        // do field validation
                    form.submit();
        
    }
</script>  

<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads');?>" method="post" name="adminForm" id="adminForm">
   
    <?php if (!empty( $this->sidebar)) : ?>
        <div id="j-sidebar-container" class="col-2">
            <?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="col-10">
    <?php else : ?>
        <div id="j-main-container">
    <?php endif;?>   
    
    <div class="row">
        <div class="col-md-12">
            <div class="h2"><?php echo Text::_('COM_JDOWNLOADS_CSS_EXPORT_LABEL'); ?></div>
        </div>
    
        <div class=""><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_TITLE').' '; ?><?php echo Text::_('COM_JDOWNLOADS_CSS_EXPORT_DESC'); ?></div>                              
        <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_1'); ?></div>
		<div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_4'); ?></div>
        <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_2'); ?></div>
        <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_3'); ?></div>
        <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_5'); ?></div>
        <div class="row md-6">
        
        <div class="" style="max-width:800px; margin-top:20px">
            <label id="filename-lbl" for="filename" class="control-group">
                <?php echo Text::_('COM_JDOWNLOADS_CSS_EXPORT_SELECT_LABEL'); ?>
            </label>
        
            <select class="form-select inputbox valid form-control-success" name="filename">
                 <option value="jdownloads_fe.css">jdownloads_fe.css</option>
				 <option value="jdownloads_fe_rtl.css">jdownloads_fe_rtl.css</option>
                 <option value="jdownloads_custom.css">jdownloads_custom.css</option>
                 <option value="jdownloads_buttons.css">jdownloads_buttons.css</option>
                 <option value="jdownloads_buttons.css">jdownloads_modules.css</option>
            </select> 
            <input class="btn btn-primary" type="button" value="<?php echo Text::_('COM_JDOWNLOADS_CSS_EXPORT_LABEL').'&nbsp; '; ?>" onclick="Joomla.submitbutton()" />
        </div>
    </div>
  
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="cssexport.export" />
    <input type="hidden" name="view" value="cssexport" />
    <input type="hidden" name="hidemainmenu" value="0" />
    
    <?php echo HtmlHelper::_('form.token'); ?>
   </form>
