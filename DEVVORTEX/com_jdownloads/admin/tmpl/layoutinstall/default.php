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

HtmlHelper::_('bootstrap.tooltip');

HTMLHelper::_('behavior.formvalidator');

?>
<script type="text/javascript">
    Joomla.submitbutton = function(pressbutton) {
        var form = document.getElementById('adminForm');

        // do field validation
        if (form.install_file.value == ""){
            alert("<?php echo Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_NO_FILE', true); ?>");
        } else {
            form.submit();
        }
    }
</script>  

<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads');?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
   
    <div class="row">
        <div class="col-md-12">
            <div class="h2"><?php echo Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_LABEL'); ?></div> 
            <div class=""><?php echo Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_DESC'); ?></div>                              
            
            <div class="col" style="max-width:800px; margin-top:20px">
                <input class="inputbox" id="install_file" name="install_file" type="file" size="80" /> 
                <input class="btn btn-primary" type="button" value="<?php echo Text::_('COM_JDOWNLOADS_LAYOUTS_IMPORT_LABEL').'&nbsp; '; ?>" onclick="Joomla.submitbutton()" />
            </div>
        </div>
    </div>
  
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="layoutinstall.install" />
    <input type="hidden" name="view" value="layoutinstall" />
    <input type="hidden" name="hidemainmenu" value="0" />
    
    <?php echo HtmlHelper::_('form.token'); ?>
   </form>
