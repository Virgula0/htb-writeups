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
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

HtmlHelper::_('bootstrap.tooltip');
?>

<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads');?>" method="post" name="adminForm" id="adminForm">
    
    <div class="row">
        <div class="col-md-12">
            <div class="h2"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_TITLE'); ?></div> 
            <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_1'); ?></div> 
			<div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_4'); ?></div>			
            <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_2'); ?></div> 
            <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_INFO_3'); ?></div> 
        </div>
    </div>

        <?php echo HtmlHelper::_('uitab.startTabSet', 'myTab', array('active' => 'default')); ?>
        <?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'default', 'jdownloads_fe.css'); ?>
            <div class="row">
                <div class="col-md-12">
                    <fieldset class="adminform">
                         <label id="csstext-lbl" class="" title="" for="csstext">
                         <strong><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_FIELD_TITLE').':</strong><span style="color:darkred;"> '.$this->cssfile; ?></span><br />
                         
                         <small><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_WRITE_STATUS_TEXT')." ";
                            if ($this->cssfile_writable) {
                                echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_YES');
                            } else {
                                echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_NO'); ?>
                                <br /><strong>
                                <?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_INFO'); ?></strong><br />
                        <?php } ?></small>
                        </label>
                        <textarea class="input_box" name="cssfile" cols="100" rows="20"><?php echo $this->csstext; ?></textarea>
                    </fieldset>
                </div>
            </div>        
        <?php echo HtmlHelper::_('uitab.endTab'); ?>
		
		<?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'rtl', 'jdownloads_fe_rtl.css'); ?>
			<div class="row">
                <div class="col-md-12">
                    <fieldset class="adminform">
                        <label id="csstext-lbl" class="" title="" for="csstext4">
                             <strong><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_FIELD_TITLE').':</strong><span style="color:darkred;"> '.$this->cssfile4; ?></span><br />
                             
                             <small><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_WRITE_STATUS_TEXT')." ";
                                if ($this->cssfile_writable4) {
                                    echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_YES');
                                } else {
                                    echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_NO'); ?>
                                    <br /><strong>
                                    <?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_INFO'); ?></strong><br />
                            <?php } ?></small>
                            
                            </label>
                        <textarea class="input_box" name="cssfile4" cols="100" rows="20"><?php echo $this->csstext4; ?></textarea>
                    </fieldset>
                </div>
            </div>      
        <?php echo HtmlHelper::_('uitab.endTab'); ?>
    
        <?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'buttons', 'jdownloads_buttons.css'); ?>
            <div class="row">
                <div class="col-md-12">
                    <fieldset class="adminform">
                        <label id="csstext-lbl" class="" title="" for="csstext2">
                             <strong><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_FIELD_TITLE').':</strong><span style="color:darkred;"> '.$this->cssfile2; ?></span><br />
                             
                             <small><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_WRITE_STATUS_TEXT')." ";
                                if ($this->cssfile_writable2) {
                                    echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_YES');
                                } else {
                                    echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_NO'); ?>
                                    <br /><strong>
                                    <?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_INFO'); ?></strong><br />
                            <?php } ?></small>
                            
                            </label>
                        <textarea class="input_box" name="cssfile2" cols="100" rows="20"><?php echo $this->csstext2; ?></textarea>
                    </fieldset>
                </div>
            </div>        
        <?php echo HtmlHelper::_('uitab.endTab'); ?>            
    
        <?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'custom', 'jdownloads_custom.css'); ?>
            <div class="row">
                <div class="col-md-12">
                    <fieldset class="adminform">
                         <label id="csstext-lbl" class="" title="" for="csstext3">
                         <strong><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_FIELD_TITLE').':</strong><span style="color:darkred;"> '.$this->cssfile3; ?></span><br />
                         
                         <small><?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_CSS_WRITE_STATUS_TEXT')." ";
                            if ($this->cssfile_writable3) {
                                echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_YES');
                            } else {
                                echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_NO'); ?>
                                <br /><strong>
                                <?php echo Text::_('COM_JDOWNLOADS_BACKEND_EDIT_LANG_CSS_FILE_WRITABLE_INFO'); ?></strong><br />
                        <?php } ?></small>
                        
                        </label>
                        <textarea class="input_box" name="cssfile3" cols="100" rows="20"><?php echo $this->csstext3; ?></textarea>
                    </fieldset>
                </div>
            </div>        
        <?php echo HtmlHelper::_('uitab.endTab'); ?>
        <?php echo HtmlHelper::_('uitab.endTabSet'); ?>                                                
    
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="view" value="cssedit" />
    <input type="hidden" name="hidemainmenu" value="0" />
    
    <?php echo HtmlHelper::_('form.token'); ?>
   </form>
