<?php 
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2013 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
 
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text; 
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Form;

HtmlHelper::_('bootstrap.tooltip');

?>

<form action="index.php" method="post" name="adminForm" id="adminForm">

<?php if (!empty( $this->sidebar)) : ?>
    <div id="j-sidebar-container" class="col-2">
        <?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="col-10">
<?php else : ?>
    <div id="j-main-container">
<?php endif;?>

    <div>
            <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKUP_INFO_DESC'); ?></div>
            <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKUP_INFO_DESC_DATA'); ?></div>
            <div class="alert alert-error"><?php echo Text::_('COM_JDOWNLOADS_BACKUP_TAGS_WARNING'); ?></div>
            
            <div class="alert"><p><b><?php echo Text::_('COM_JDOWNLOADS_BACKUP_LOGS_OPTION_DESC');?></b></p>
	            <div class="">
	                    <input type="checkbox" class="checkbox" name="logs" id="logs" value="1" /> <?php echo '&nbsp;&nbsp;'.Text::_('COM_JDOWNLOADS_BACKUP_LOGS_OPTION_DESC_2');?>
	            </div>
            </div>
    </div>
    
<input type="hidden" name="controller" value="backup" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="option" value="com_jdownloads" />
</form>
