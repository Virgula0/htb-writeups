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

defined('_JEXEC') or die;

use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Component\ComponentHelper;

// Required for columns selection
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

HTMLHelper::_('behavior.multiselect');

$app    = Factory::getApplication();
$user   = $app->getIdentity();
$userId = $user->get('id');

$params = $this->state->params;

// Path to the layouts folder 
$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';
$options['base_path'] = $basePath;

$listOrder = str_replace(' ' . $this->state->get('list.direction'), '', $this->state->get('list.fullordering') ?? '');
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';

$canOrder    = $user->authorise('core.edit.state', 'com_jdownloads');

if ($saveOrder){
    $saveOrderingUrl = 'index.php?option=com_jdownloads&task=licenses.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}

?>
<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&view=licenses');?>" method="POST" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php
                // Search tools bar
                echo LayoutHelper::render('searchtools.default', array('view' => $this), $basePath, $options);
                ?>
                
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('COM_JDOWNLOADS_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table itemList" id="licenseList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_JDOWNLOADS_DOWNLOADS_TABLE_CAPTION'); ?>
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?></span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'COM_JDOWNLOADS_ORDERING', 'icon-menu-2'); ?>
                                </th>
                                
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_STATUS', 'a.published', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" style="min-width:200px">
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_TITLE', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_DESCRIPTION', 'a.description', $listDirn, $listOrder ); ?>
                                </th> 
                                <th scope="col" class="w-25 d-none d-lg-table-cell">
                                    <?php echo HtmlHelper::_('searchtools.sort',  'COM_JDOWNLOADS_LICLIST_LINK', 'a.url', $listDirn, $listOrder); ?>
                                </th>  
                                <?php 
                                if (Multilanguage::isEnabled()) : ?>
                                    <th scope="col" class="w-10 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_LANGUAGE', 'language', $listDirn, $listOrder); ?>
                                    </th>
                                <?php endif; ?>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>

                        <tbody <?php if ($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
                        <?php foreach ($this->items as $i => $item) :
                            $item->max_ordering = 0;
                            $ordering   = ($listOrder == 'a.ordering');
                            $canCreate  = $user->authorise('core.create',     'com_jdownloads');
                            $canEdit    = $user->authorise('core.edit',       'com_jdownloads');
                            $canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
                            $canChange  = $user->authorise('core.edit.state', 'com_jdownloads') && $canCheckin;
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group="">
                                
                                <td class="text-center">
                                    <?php echo HtmlHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                                </td>
                                
                                <td class="text-center d-none d-md-table-cell">
                                    <?php
                                    $iconClass = '';
                                    if (!$canChange)
                                    {
                                        $iconClass = ' inactive';
                                    }
                                    elseif (!$saveOrder)
                                    {
                                        $iconClass = ' inactive" title="' . Text::_('COM_JDOWNLOADS_ORDERING_DISABLED');
                                    }
                                    ?>
                                    <span class="sortable-handler<?php echo $iconClass ?>">
                                        <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                    </span>
                                    <?php if ($canChange && $saveOrder) : ?>
                                        <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
                                    <?php endif; ?>
                                </td>

                                <td class="download-status text-center">
                                    <div class="btn-group">
                                        <?php echo HtmlHelper::_('jgrid.published', $item->published, $i, 'licenses.', $canChange); ?>
                                    </div>
                                </td>
                                
                                <td class="has-context">
                                    <div class="pull-left break-word">
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HtmlHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'licenses.', $canCheckin); ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($item->language == '*'):?>
                                            <?php $language = Text::alt('COM_JDOWNLOADS_ALL', 'language'); ?>
                                        <?php else:?>
                                            <?php $language = $item->language_title ? $this->escape($item->language_title) : Text::_('COM_JDOWNLOADS_UNDEFINED'); ?>
                                        <?php endif;?>
                                        
                                        <?php 
                                        if ($canEdit) : ?>
                                            <a class="hasTooltip" href="<?php echo ROUTE::_('index.php?option=com_jdownloads&task=license.edit&id=' . $item->id); ?>" title="<?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_TITLE'); ?>">
                                                <?php echo $this->escape($item->title); ?></a>
                                        <?php else : ?>
                                            <span><?php echo $this->escape($item->title); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="small w-5 d-none d-lg-table-cell" style="text-align:center;">
                                    <?php
                                    if ($item->description != '') {
                                        $description_short = HtmlHelper::_('string.truncate', $this->escape(strip_tags($item->description)), 400, true, false); // Do not cut off words; HTML not allowed;
                                        echo HtmlHelper::_('tooltip', $description_short, Text::_(''), Uri::root().'administrator/components/com_jdownloads/assets/images/tooltip_blue.gif'); 
                                    }
                                    ?>
                                </td>

                                <td class="small w-25 d-none d-lg-table-cell">
                                    <?php 
                                    if ($item->url != ''){
                                        $url_short = HtmlHelper::_('string.truncate', $this->escape($item->url), 35, false, false); // May cut off words; HTML not allowed;
                                        echo '<a href="'.$this->escape($item->url).'" target="_blank">'.$url_short.'<span class="icon-out-2" aria-hidden="true"></span></a>'; 
                                    } ?>
                                </td> 
                                
                                <?php if (Multilanguage::isEnabled()) : ?>
                                    <td class="small w-5 d-none d-md-table-cell">
                                        <?php if ($item->language == '*'):?>
                                            <?php echo Text::alt('COM_JDOWNLOADS_ALL', 'language'); ?>
                                        <?php else:?>
                                            <?php echo $item->language_title ? HTMLHelper::_('image', 'mod_languages/' . $item->language_image . '.gif', $item->language_title, array('title' => $item->language_title), true) . '&nbsp;' . $this->escape($item->language_title) : Text::_('COM_JDOWNLOADS_UNDEFINED'); ?>
                                        <?php endif;?>
                                    </td>
                                <?php endif; ?>
                                
                                <td class="d-none d-lg-table-cell">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>      
                        </tbody>
                    </table>
        
        <?php // Load the batch processing form. ?>
        <?php if ($user->authorise('core.create', 'com_jdownloads')
                    && $user->authorise('core.edit', 'com_jdownloads')
                    && $user->authorise('core.edit.state', 'com_jdownloads')) : ?>
                    <?php echo HtmlHelper::_(
                            'bootstrap.renderModal',
                            'collapseModal',
                            array(
                                'title' => Text::_('COM_JDOWNLOADS_BATCH_OPTIONS'),
                                'footer' => $this->loadTemplate('batch_footer')
                            ),
                            $this->loadTemplate('batch')
                        ); ?>
        <?php endif; ?>
    <?php endif;?>
    
    <?php echo $this->pagination->getListFooter(); ?>
    
    <!-- Display the amount of listed items -->
    <div class="alert alert-info text-center">
        <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
        <?php echo Text::sprintf('COM_JDOWNLOADS_BE_LICENSES_LIST_TOTAL_TEXT', $this->pagination->total); ?>
    </div>           
                            
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="hidemainmenu" value="0">
    <?php echo HtmlHelper::_('form.token'); ?>    
</div>
</form>
