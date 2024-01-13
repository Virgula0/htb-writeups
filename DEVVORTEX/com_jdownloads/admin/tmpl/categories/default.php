<?php
/**
 * @package jDownloads
 * @version 3.8  
 * @copyright (C) 2007 - 2018 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\String\Inflector;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Associations;

// Required for columns selection
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

HTMLHelper::_('behavior.multiselect');

$params = $this->state->params;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

$app       = Factory::getApplication();
$user      = $app->getIdentity();
$userId    = $user->get('id');
$root      = URI::root();

// Path to the layouts folder 
$basePath = JPATH_ROOT.'/administrator/components/com_jdownloads/layouts';

// Path to the images folder (for file symbols) 
$cat_pic_folder = 'images/jdownloads/catimages/';

$options['base_path'] = $basePath;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = ($listOrder == 'a.lft' && strtolower($listDirn) == 'asc');
$columns   = 12;

$canOrder  = $user->authorise('core.edit.state', 'com_jdownloads');

if ($saveOrder && !empty($this->items)){
    $saveOrderingUrl = 'index.php?option=com_jdownloads&task=categories.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';;
    HTMLHelper::_('draggablelist.draggable');
}

$assoc = Associations::isEnabled();
?>

<form action="<?php echo Route::_('index.php?option=com_jdownloads&view=categories');?>" method="POST" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
        
        <?php 
            // Display a warning when default menu item not exist.
            if (!$this->exist_menu_item) { ?>
                <div class="alert alert-error">
                    <?php
                        echo Text::_('COM_JDOWNLOADS_MISSING_MAIN_MENU_HINT');
                    ?>
                </div> 
        <?php } ?>   
        
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
                    <table class="table" id="categoryList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_JDOWNLOADS_CATEGORIES_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                <thead>
                    <tr>
                        <td class="w-1 text-center">
                            <?php echo HTMLHelper::_('grid.checkall'); ?>
                        </td>
                        <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', '', 'a.lft', $listDirn, $listOrder, null, 'asc', 'COM_JDOWNLOADS_ORDERING', 'icon-menu-2'); ?>
                        </th>
                        <th scope="col" class="w-1 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_STATUS', 'a.published', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_CATSLIST_PIC', 'a.pic', $listDirn, $listOrder ); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_TITLE', 'a.title', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-3 text-center d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_DESCRIPTION', 'a.description', $listDirn, $listOrder ); ?>
                        </th>
                        <th scope="col" class="w-3 text-center d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_CATSLIST_PATH', 'a.cat_dir', $listDirn, $listOrder ); ?>
                        </th>
                        <?php if (isset($this->items[0]) && property_exists($this->items[0], 'count_published')) :
                            $columns++; ?>
                            <th scope="col" class="w-3 text-center d-none d-md-table-cell">
                                <i class="icon-publish hasTooltip" title="<?php echo Text::_('COM_JDOWNLOADS_PUBLISHED_DOWNLOADS'); ?>"></i>
                            </th>
                        <?php endif;?>
                        <?php if (isset($this->items[0]) && property_exists($this->items[0], 'count_unpublished')) :
                            $columns++; ?>
                            <th scope="col" class="w-3 text-center d-none d-md-table-cell">
                                <i class="icon-unpublish hasTooltip" title="<?php echo Text::_('COM_JDOWNLOADS_UNPUBLISHED_DOWNLOADS'); ?>"></i>
                            </th>
                        <?php endif;?>
                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                        </th>
                        <?php if ($assoc) :
                            $columns++; ?>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_HEADING_ASSOCIATION', 'association', $listDirn, $listOrder); ?>
                            </th>
                        <?php endif; ?>
                        <?php if (Multilanguage::isEnabled()) : ?>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'language_title', $listDirn, $listOrder); ?>
                            </th>
                        <?php endif; ?>
                        <th scope="col" class="w-5 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tfoot>

                </tfoot>
                <tbody <?php if ($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <?php
                        $orderkey   = array_search($item->id, $this->ordering[$item->parent_id]);
                        $canEdit    = $user->authorise('core.edit',       'com_jdownloads.category.' . $item->id);
                        $canCheckin = $user->authorise('core.admin',      'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
                        $canEditOwn = $user->authorise('core.edit.own',   'com_jdownloads.category.' . $item->id) && $item->created_user_id == $userId;
                        $canChange  = $user->authorise('core.edit.state', 'com_jdownloads.category.' . $item->id) && $canCheckin;

                        // Get the parents of item for sorting
                        if ($item->level > 1)
                        {
                            $parentsStr = "";
                            $_currentParentId = $item->parent_id;
                            $parentsStr = " " . $_currentParentId;
                            for ($i2 = 0; $i2 < $item->level; $i2++)
                            {
                                foreach ($this->ordering as $k => $v)
                                {
                                    $v = implode("-", $v);
                                    $v = "-" . $v . "-";
                                    if (strpos($v, "-" . $_currentParentId . "-") !== false)
                                    {
                                        $parentsStr .= " " . $k;
                                        $_currentParentId = $k;
                                        break;
                                    }
                                }
                            } 
                        }
                        else
                        {
                            $parentsStr = "";
                        }
                        ?>
                        <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->parent_id; ?>" data-item-id="<?php echo $item->id ?>" data-parents="<?php echo $parentsStr ?>" data-level="<?php echo $item->level ?>">
                            <td class="text-center">
                                <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
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
                                    $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                                }
                                ?>
                                <span class="sortable-handler<?php echo $iconClass ?>">
                                    <span class="icon-ellipsis-v"></span>
                                </span>
                                <?php if ($canChange && $saveOrder) : ?>
                                    <input type="text" class="hidden" name="order[]" size="5" value="<?php echo $item->lft; ?>">
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'categories.', $canChange); ?>
                            </td>
                            <td class="text-center btns d-none d-md-table-cell itemnumber">
                            <?php if ($item->pic != '') { 
                                $cat_pic_url = $cat_pic_folder.$this->escape($item->pic);
                                ?>
                                <img src="<?php echo URI::root().Route::_( $cat_pic_url ); ?>" width="28px" height="28px" style="vertical-align: middle; border:0px;"/>
                            <?php } ?>
                            </td>
                            <th scope="row">
                                <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
                                <?php if ($item->checked_out) : ?>
                                    <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'categories.', $canCheckin); ?>
                                <?php endif; ?>
                                <?php if ($canEdit || $canEditOwn) : ?>
                                    <a class="hasTooltip" href="<?php echo Route::_('index.php?option=com_jdownloads&task=category.edit&id=' . $item->id); ?>" title="<?php echo Text::_('COM_JDOWNLOADS_EDIT_CAT_EDIT'); ?>">
                                        <?php echo $this->escape($item->title); ?></a>
                                <?php else : ?>
                                    <?php echo $this->escape($item->title); ?>
                                <?php endif; ?>
                                <div class="small" title="">
                                    <?php if (empty($item->notes)) : ?>
                                        <?php echo Text::sprintf('COM_JDOWNLOADS_LIST_ALIAS', $this->escape($item->alias)); ?>
                                    <?php else : ?>
                                        <?php echo Text::sprintf('COM_JDOWNLOADS_LIST_ALIAS_NOTE', $this->escape($item->alias), $this->escape($item->notes)); ?>
                                    <?php endif; ?>
                                </div>
                            </th>
                            <td class="text-center btns d-none d-md-table-cell itemnumber">
                                <?php
                                    if ($item->description != '') {
                                        $description = HTMLHelper::_('string.truncate', $this->escape(strip_tags($item->description)), 400, true, false); // Do not cut off words; HTML not allowed;
                                    } else {
                                        $description = '';
                                    }

                                    if ($description != '') {
                                        echo HTMLHelper::_('tooltip', $description, '', URI::root().'administrator/components/com_jdownloads/assets/images/tooltip_blue.gif'); 
                                    }
                                ?>
                            </td>                            
                            <td class="text-center btns d-none d-md-table-cell itemnumber">
                                <?php
                                    if ($item->parent_id > 1) {
                                        echo HTMLHelper::_('tooltip', $params->get('files_uploaddir').'/'.$item->cat_dir_parent.'/'.$item->cat_dir, '', URI::root().'administrator/components/com_jdownloads/assets/images/tooltip_blue.gif'); 
                                    } else {
                                        echo HTMLHelper::_('tooltip', $params->get('files_uploaddir').'/'.$item->cat_dir, '', URI::root().'administrator/components/com_jdownloads/assets/images/tooltip_blue.gif');
                                    }    
                                ?>
                            </td>                            
                            <?php 
                            if (isset($this->items[0]) && property_exists($this->items[0], 'count_published')) : ?>
                                <td class="text-center btns d-none d-md-table-cell itemnumber">
                                    <a class="btn <?php echo ($item->count_published > 0) ? 'btn-success' : 'btn-secondary'; ?>" title="<?php echo Text::_('COM_JDOWNLOADS_PUBLISHED_DOWNLOADS');?>" href="<?php echo Route::_('index.php?option=com_jdownloads&view=downloads&filter[category_id]=' . (int) $item->id . '&filter[published]=1' . '&filter[level]=' . (int) $item->level);?>">
                                        <?php echo $item->count_published; ?></a>
                                </td>
                            <?php endif;?>
                            <?php if (isset($this->items[0]) && property_exists($this->items[0], 'count_unpublished')) : ?>
                                <td class="text-center btns d-none d-md-table-cell itemnumber">
                                    <a class="btn <?php echo ($item->count_unpublished > 0) ? 'btn-danger' : 'btn-secondary'; ?>" title="<?php echo Text::_('COM_JDOWNLOADS_UNPUBLISHED_DOWNLOADS');?>" href="<?php echo Route::_('index.php?option=com_jdownloads&view=downloads&filter[category_id]=' . (int) $item->id . '&filter[published]=0' . '&filter[level]=' . (int) $item->level);?>">
                                        <?php echo $item->count_unpublished; ?></a>
                                </td>
                            <?php endif;?>

                            <td class="btns d-none d-md-table-cell itemnumber">
                                <?php echo $this->escape($item->access_level); ?>
                            </td>
                            
                            <?php // Added to support the Joomla Language Associations
                                  if ($assoc) : ?>
                                        <td class="d-none d-md-table-cell">
                                            <?php if (isset($item->association)) : ?>
                                                <?php if ($item->association) : ?>
                                                    <?php echo HTMLHelper::_('jdownloadsadministrator.catAssociation', $item->id); ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                            <?php endif; ?>
                            
                            <?php if (Multilanguage::isEnabled()) : ?>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                </td>
                            <?php endif; ?>
                            
                            <td class="btns d-none d-md-table-cell itemnumber">
                                <span title="<?php echo sprintf('%d-%d', $item->lft, $item->rgt); ?>">
                                    <?php echo (int) $item->id; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php echo $this->pagination->getListFooter(); ?>
            
            <div class="alert alert-info text-center">
                <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::sprintf('COM_JDOWNLOADS_BE_CATEGORIES_LIST_TOTAL_TEXT', $this->pagination->total); ?>
            </div>
            
            <?php // Load the batch processing form. ?>
            <?php if ($user->authorise('core.create', 'com_jdownloads')
                        && $user->authorise('core.edit', 'com_jdownloads')
                        && $user->authorise('core.edit.state', 'com_jdownloads')) : ?>
                        <?php echo HTMLHelper::_(
                                'bootstrap.renderModal',
                                'collapseModal',
                                array(
                                    'title' => Text::_('COM_JDOWNLOADS_BATCH_CAT_OPTIONS'),
                                    'footer' => $this->loadTemplate('batch_footer')
                                ),
                                $this->loadTemplate('batch')
                            ); ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>
    <div>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <?php echo HTMLHelper::_('form.token'); ?>    
    </div>
</form>
