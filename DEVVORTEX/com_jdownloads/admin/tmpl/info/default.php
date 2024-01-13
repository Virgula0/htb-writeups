<?php
/*
 * @package Joomla
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 * @component jDownloads
 * @version 3.8  
 * @copyright (C) 2007 - 2018 - Arno Betz - www.jdownloads.com
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
        <fieldset style="background-color: #ffffff; margin-top:5px;" class="infotext">
            <legend> <?php echo Text::_('COM_JDOWNLOADS_BACKEND_INFO_TEXT_TITLE')." "; ?> </legend>
            <div class="infotext"> <img src="components/com_jdownloads/assets/images/jdownloads.jpg" alt="jDownloads Logo"/><br /><br />
               <big>jDownloads - a Download Management Component for Joomla!</big><br />
                         Copyright 2007/2022 by Arno Betz - <a href="https://www.jdownloads.com" target="_blank">www.jDownloads.com</a> all rights reserved.
                         <br /><br />
                         <b>jDownloads Logo</b> created for jDownloads by 'rkdesign' - all rights reserved.<br /><br />
             </div>
        </fieldset>
    </div>          
    
    <div> 
        <fieldset style="background-color: #ffffff; margin-top:5px;" class="uploadform">
        <legend> <?php echo Text::_('COM_JDOWNLOADS_TERMS_OF_USE')." "; ?> </legend> 
        <div class="infotext">
                 <?php echo Text::_('COM_JDOWNLOADS_BACKEND_INFO_LICENSE_TITLE').'<br />';
                       echo Text::_('COM_JDOWNLOADS_BACKEND_INFO_LICENSE_TEXT'); 
                 ?>
        </div>
        </fieldset>
    </div> 
    <div> 
        <fieldset style="background-color: #ffffff; margin-top:5px;" class="uploadform">
        <legend> <?php echo Text::_('COM_JDOWNLOADS_BACKEND_TESTERS_TEXT_TITLE')." "; ?> </legend> 
        <div class="infotext">
            Colin Mercer and some others.<br />Many thanks at all testing team members and all translators! 
        </div>
        </fieldset>
    </div>    
       
    <div> 
        <fieldset style="background-color: #ffffff; margin-top:5px;" class="uploadform">
        <legend> <?php echo Text::_('COM_JDOWNLOADS_TRANSLATED_TITLE')." "; ?> </legend> 
        <div class="infotext">
        <b><?php echo Text::_('COM_JDOWNLOADS_TRANSLATED_BY_NAME')." "; ?></b><br />
        <?php echo Text::_('COM_JDOWNLOADS_TRANSLATED_BY_EMAIL')." "; ?><br />
        <?php echo Text::_('COM_JDOWNLOADS_TRANSLATED_BY_URL')." "; ?>  
        </div>
        </fieldset>
    </div>     

    <div> 
        <fieldset style="background-color: #ffffff; margin-top:5px;" class="uploadform">
        <legend> <?php echo Text::_('Credits')." "; ?> </legend> 
            <div class="infotext">
            <ul>
                <li><a href="https://github.com/pasnox/oxygen-icons-png">Oxygen Icons by Oxygen Team</a> for some folder icons</li>
                <li><a href="https://www.deviantart.com/franksouza183">FS Ubuntu Icons</a> for some folder icons</li>
                <li><a href="https://dreamstale.com">Dreamstale.com</a> for file type icon sets</li>
                <li><a href="http://lokeshdhakar.com/projects/lightbox2/">Lokesh Dhakar</a> for his 'Lightbox' JavaScript</li>
            </ul>
            </div>
        </fieldset>
    </div>    
    
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="info" />
    <input type="hidden" name="view" value="info" />
    <input type="hidden" name="hidemainmenu" value="0" />
    
</form>
