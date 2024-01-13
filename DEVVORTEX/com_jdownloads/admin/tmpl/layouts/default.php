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
use Joomla\CMS\Toolbar\ToolbarHelper;

?>
    
<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&view=layouts');?>" method="POST" name="adminForm" id="adminForm">
    
    <div>
        <nav class="navbar navbar-expand-sm bg-primary">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link text-light" href="index.php?option=com_jdownloads&amp;view=templates&type=1"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP1' ) ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-light" href="index.php?option=com_jdownloads&amp;view=templates&type=8"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP8' ) ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-light" href="index.php?option=com_jdownloads&amp;view=templates&type=4"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP4' ) ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-light" href="index.php?option=com_jdownloads&amp;view=templates&type=2"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP2' ) ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-light" href="index.php?option=com_jdownloads&amp;view=templates&type=5"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP5' ) ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-light" href="index.php?option=com_jdownloads&amp;view=templates&type=3"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP3' ) ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-light" href="index.php?option=com_jdownloads&amp;view=cssedit"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_EDIT_CSS_TITLE' ) ?></a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <div class="col-12">
            <div class="col-12">
                <div class="alert alert-info">
                    <?php echo  Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_HEAD'); ?>
                    <?php echo  Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_HEAD_INFO').Text::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_HEAD_INFO2'); ?>
                </div>
            </div>
        </div>
    </div>    

    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="controller" value="layouts" />
</form>    
