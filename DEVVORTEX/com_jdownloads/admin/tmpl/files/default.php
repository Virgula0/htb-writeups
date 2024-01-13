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
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Helper\ModuleHelper;

HTMLHelper::_('behavior.multiselect');

// Path to the layouts folder 
$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';
$options['base_path'] = $basePath;

$app    = Factory::getApplication();
$user   = $app->getIdentity();
$userId = $user->get('id');

?>

<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&view=files');?>" method="post" name="adminForm" id="adminForm">

<div class="row">
    <div class="col-md-12">
        <div id="j-main-container" class="j-main-container">
            <?php
            // Search tools bar
            echo LayoutHelper::render('searchtools.default', array('view' => $this, 'options' => array('filterButton' => false))); ?>     

    
        <?php if (empty($this->items)) : ?>
            <div class="alert alert-no-items">
                <?php echo Text::_('COM_JDOWNLOADS_NO_MATCHING_RESULTS'); ?>
            </div>

        <?php else : ?>
            
            <div class="alert alert-info" style="margin-top:10px;"><?php echo Text::_('COM_JDOWNLOADS_MANAGE_FILES_DESC'); ?> </div>
            <div class="clr"> </div>            
                <table class="table table-striped" id="groupsList">
                    <thead>
                        <tr>
                            <th class="w-1 text-center">
                                <?php echo HtmlHelper::_('grid.checkall'); ?>
                            </th>
                            <th scope="col" class="w-65">
                                <?php echo Text::_('COM_JDOWNLOADS_MANAGE_FILES_TITLE_NAME'); ?>
                            </th>
                            <th scope="col" class="w-15 text-center d-none d-lg-table-cell">
                                <?php echo Text::_('COM_JDOWNLOADS_MANAGE_FILES_TITLE_DATE'); ?>
                            </th> 
                            <th scope="col" class="w-15 text-center d-none d-lg-table-cell">
                                <?php echo Text::_('COM_JDOWNLOADS_MANAGE_FILES_TITLE_SIZE'); ?> 
                            </th>
                            <th  scope="col" class="w-5">
                                <?php echo Text::_(''); ?>
                            </th>
                        </tr>    
                    </thead>
                    <tbody>  
                    <?php 
                        foreach ($this->items as $i => $item) {
                            $canCreate    = $user->authorise('core.create',     'com_jdownloads');
                            $canEdit      = $user->authorise('core.edit',       'com_jdownloads');
                        ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="center">
                                    <?php echo HtmlHelper::_('grid.id', $i, htmlspecialchars($item['name'])); ?>
                                </td>
                                <td class="w-65">
                                    <?php echo $this->escape($item['name']); ?>
                                </td>
                                <td class="w-15 d-none d-lg-table-cell text-center">
                                    <?php echo HtmlHelper::_('date', $this->escape($item['date']), Text::_('DATE_FORMAT_LC4')); ?>
                                </td>
                                <td class="w-15 d-none d-lg-table-cell text-center">
                                    <?php echo $this->escape($item['size']) ?>
                                </td>
                                <td class="w-5" style="text-align:right;">
                                    <?php echo ROUTE::_('<a class="btn btn-primary btn-sm" href="index.php?option=com_jdownloads&amp;task=download.edit&amp;file='.$item['name'].'">'.Text::_('COM_JDOWNLOADS_ACTION_CREATE').'</a>'); ?>
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
                <?php echo Text::sprintf('COM_JDOWNLOADS_BE_FILES_LIST_TOTAL_TEXT', $this->pagination->total); ?>
            </div> 
        </div>
    </div>
</div>
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <?php echo HtmlHelper::_('form.token'); ?>    
</form>