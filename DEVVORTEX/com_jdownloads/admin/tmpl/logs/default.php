<?php
/*
 * @package Joomla
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 * @component jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2021 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

// Required for columns selection
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();
$user   = $app->getIdentity();
$userId = $user->get('id');

// Path to the layouts folder 
$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';
$options['base_path'] = $basePath;

$listOrder = str_replace(' ' . $this->state->get('list.direction'), '', $this->state->get('list.fullordering') ?? '');
$listDirn  = $this->escape($this->state->get('list.direction')); 
 
$canOrder  = $user->authorise('core.edit.state', 'com_jdownloads'); 

?>
<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&view=logs');?>" method="POST" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
    
            <?php
            // Search tools bar
            echo LayoutHelper::render('searchtools.default', array('view' => $this), $basePath, $options); ?>
    
            <?php 
                if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <?php echo Text::_('COM_JDOWNLOADS_NO_MATCHING_RESULTS'); ?>
                    </div>
            <?php
                else : ?>

                    <div class="alert alert-info" style=""><?php echo $this->logs_header_info; ?></div>
                    <div class="clr"> </div> 
                    
                    <table class="table table-striped" id="logsList">
                        <thead>
    	                    <tr>
			                    <td class="w-1 text-center">
                                    <?php echo HtmlHelper::_('grid.checkall'); ?>
                                </td>
			                    
                                <th scope="col" class="w-12">
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_LOGS_COL_DATE_LABEL', 'a.log_datetime', $listDirn, $listOrder ); ?>
                                </th>
                                <th scope="col" class="w-12">
                                    <?php echo  Text::_('COM_JDOWNLOADS_LOGS_COL_USER_LABEL'); ?>
                                </th>
			                    
                                <th scope="col" class="w-10 text-center d-none d-lg-table-cell">
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_LOGS_COL_IP_LABEL', 'a.log_ip', $listDirn, $listOrder ); ?>
                                </th>
                                
                                <th scope="col" class="w-25">
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_LOGS_COL_FILETITLE_LABEL', 'a.log_title', $listDirn, $listOrder ); ?>
                                </th>

                                <th scope="col" class="w-19 d-none d-lg-table-cell">
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_LOGS_COL_FILENAME_LABEL', 'a.log_file_name', $listDirn, $listOrder ); ?>
                                </th>            

                                <th scope="col" class="w-5 text-center d-none d-lg-table-cell">
                                    <?php echo  Text::_('COM_JDOWNLOADS_LOGS_COL_FILESIZE_LABEL'); ?>
                                </th>
                                
                                <th scope="col" class="w-8 d-none d-lg-table-cell">                        
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_LOGS_COL_TYPE_LABEL', 'a.type', $listDirn, $listOrder ); ?>
                                </th>
                                
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_ID', 'a.id', $listDirn, $listOrder ); ?>
                                </th>
                            </tr>	
	                    </thead>
		                <tbody>	
		                    <?php 
                                foreach ($this->items as $i => $item) {
                                    ?>
                                    <tr class="row<?php echo $i % 2; ?>">
                                        
                                        <td class="text-center">
                                            <?php echo HtmlHelper::_('grid.id', $i, $item->id); ?>
                                        </td>
                                        
                                        <td class="">
                                            <?php echo HtmlHelper::_('date',$this->escape($item->log_datetime), Text::_('DATE_FORMAT_LC5')); ?>
                                        </td>
                                        
                                        <td class="">
                                        <?php
                                        if ($item->username == ''){
                                            echo Text::_('COM_JDOWNLOADS_LOGS_COL_USER_ANONYMOUS');
                                        } else {
                                            echo $this->escape($item->username);
                                        } ?>
                                        </td>

                                        <td class="text-center d-none d-lg-table-cell">
                                            <?php echo $item->log_ip; ?>
                                        </td>

                                        <td class="">
                                            <?php echo  $this->escape($item->log_title); ?>
                                        </td>
                                        
                                        <td class="d-none d-lg-table-cell">
                                            <?php echo  $this->escape($item->log_file_name); ?>
                                        </td>

                                        <td class="text-center d-none d-lg-table-cell">
                                            <?php echo  $this->escape($item->log_file_size); ?>
                                        </td>

                                        <td class="d-none d-lg-table-cell">
                                        <?php 
                                        if ($item->type == '1'){
                                            echo Text::_('COM_JDOWNLOADS_LOGS_COL_TYPE_DOWNLOAD');
                                        } else {
                                            echo Text::_('COM_JDOWNLOADS_LOGS_COL_TYPE_UPLOAD');
                                        } ?> 
                                        </td>    
                                        
                                        <td class="text-center d-none d-lg-table-cell">
                                            <?php echo (int) $item->id; ?>
                                        </td>
		                            </tr>
		                    <?php } ?>
	                    </tbody>
	                </table>
                <?php endif;?>
        
        <?php echo $this->pagination->getListFooter(); ?>
        
        <!-- Display the amount of listed items -->
        <div class="alert alert-info text-center">
            <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::sprintf('COM_JDOWNLOADS_BE_LOGS_LIST_TOTAL_TEXT', $this->pagination->total); ?>
        </div>        

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="hidemainmenu" value="0">
    <?php echo HtmlHelper::_('form.token'); ?>    
    </div>   
</form>
