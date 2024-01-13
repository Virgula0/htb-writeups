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
 
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text; 
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.tooltip');

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
            <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_OPTIONS_EXPORT_INFO_DESC'); ?></div>
    </div>
    
    <input type="hidden" name="controller" value="optionsexport" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="option" value="com_jdownloads" />

    <?php echo HTMLHelper::_('form.token'); ?>
</form>
