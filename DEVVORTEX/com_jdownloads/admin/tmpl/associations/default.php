<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_associations
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\AssociationsHelper;

$wa = $this->document->getWebAssetManager();
$wa->useScript('multiselect')
   ->useScript('com_jdownloads.admin-associations-default')
   ->useScript('table.columns');    

$listOrder        = $this->escape($this->state->get('list.ordering'));
$listDirn         = $this->escape($this->state->get('list.direction'));
$canManageCheckin = Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_checkin');

// Path to the layouts folder 
$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';
$options['base_path'] = $basePath;

$iconStates = array(
	0  => 'icon-times',
	1  => 'icon-check',
);

Text::script('COM_JDOWNLOADS_ASSOCIATIONS_PURGE_CONFIRM_PROMPT', true);

?>
<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&view=associations'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('searchtools.default', array('view' => $this), $basePath, $options); ?>
                <?php if ($this->state->get('itemtype') == '' || $this->state->get('language') == '') : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('COM_JDOWNLOADS_ASSOCIATIONS_NOTICE_NO_SELECTORS'); ?>
                    </div>
                <?php elseif (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="associationsList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_ASSOCIATIONS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                            <tr>
                                <?php if (!empty($this->typeSupports['state'])) : ?>
                                    <th scope="col" class="w-1 text-center">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'state', $listDirn, $listOrder); ?>
                                    </th>
                                <?php endif; ?>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo Text::_('JGRID_HEADING_LANGUAGE'); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo Text::_('COM_JDOWNLOADS_HEADING_ASSOCIATION'); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo Text::_('COM_JDOWNLOADS_HEADING_NO_ASSOCIATION'); ?>
                                </th>
                                <?php if (!empty($this->typeFields['menutype'])) : ?>
                                    <th scope="col" class="w-10">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_ASSOCIATIONS_HEADING_MENUTYPE', 'menutype_title', $listDirn, $listOrder); ?>
                                    </th>
                                <?php endif; ?>
                                <?php if (!empty($this->typeFields['access'])) : ?>
                                    <th scope="col" class="w-5 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                                    </th>
                                <?php endif; ?>
                                <th scope="col" class="w-1 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->items as $i => $item) :
                            $canCheckin = true;
                            $canEdit    = AssociationsHelper::allowEdit($this->extensionName, $this->typeName, $item->id);
                            $canCheckin = $canManageCheckin || AssociationsHelper::canCheckinItem($this->extensionName, $this->typeName, $item->id);
                            $isCheckout = AssociationsHelper::isCheckoutItem($this->extensionName, $this->typeName, $item->id);
                        ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <?php if (!empty($this->typeSupports['state'])) : ?>
                                    <td class="text-center">
                                        <span class="<?php echo $iconStates[$this->escape($item->state)]; ?>"></span>
                                    </td>
                                <?php endif; ?>
                                <th scope="row" class="has-context">
                                    <div class="break-word">
                                        <?php if (isset($item->level)) : ?>
                                            <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
                                        <?php endif; ?>
                                        <?php if ($canCheckin && $isCheckout) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'associations.', $canCheckin); ?>
                                        <?php endif; ?>
                                        <?php if ($canEdit) : ?>
                                            <a class="hasTooltip" href="<?php echo Route::_($this->editUri . '&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->title); ?>">
                                                <?php echo $this->escape($item->title); ?></a>
                                        <?php else : ?>
                                            <span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>"><?php echo $this->escape($item->title); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($this->typeFields['alias'])) : ?>
                                            <div class="small">
                                                <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($this->typeFields['catid'])) : ?>
                                            <div class="small">
                                                <?php echo Text::_('JCATEGORY') . ": " . $this->escape($item->category_title); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </th>
                                <td class="small">
                                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                </td>
                                <td>
                                    <?php echo AssociationsHelper::getAssociationHtmlList($this->extensionName, $this->typeName, (int) $item->id, $item->language, !$isCheckout, false); ?>
                                </td>
                                <td>
                                    <?php echo AssociationsHelper::getAssociationHtmlList($this->extensionName, $this->typeName, (int) $item->id, $item->language, !$isCheckout, true); ?>
                                </td>
                                <?php if (!empty($this->typeFields['menutype'])) : ?>
                                    <td class="small">
                                        <?php echo $this->escape($item->menutype_title); ?>
                                    </td>
                                <?php endif; ?>
                                <?php if (!empty($this->typeFields['access'])) : ?>
                                    <td class="small d-none d-md-table-cell">
                                        <?php echo $this->escape($item->access_level); ?>
                                    </td>
                                <?php endif; ?>
                                <td class="d-none d-md-table-cell">
                                    <?php echo $item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php // load the pagination. ?>
                    <?php echo $this->pagination->getListFooter(); ?>

                <?php endif; ?>
                <input type="hidden" name="task" value="">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
