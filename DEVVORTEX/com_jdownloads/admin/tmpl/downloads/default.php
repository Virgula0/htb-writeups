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

use Joomla\CMS\Button\FeaturedButton;
use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\UCM\UCMType;
use Joomla\CMS\Router\Route; 
use Joomla\String\StringHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Filesystem\File;

// Required for columns selection
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('jquery.framework');
HTMLHelper::_('bootstrap.framework');

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

$app    = Factory::getApplication();
$user   = $app->getIdentity();

$userId = $user->get('id');

$params = $this->state->params;

// Path to the layouts folder 
$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';
$options['base_path'] = $basePath;

// Path to the mime type image folder (for file symbols) 
switch ($params->get('selected_file_type_icon_set'))
{
    case 1:
        $file_pic_folder = 'images/jdownloads/fileimages/';
        break;
    case 2:
        $file_pic_folder = 'images/jdownloads/fileimages/flat_1/';
        break;
    case 3:
        $file_pic_folder = 'images/jdownloads/fileimages/flat_2/';
        break;
}

// Path to the image folders  
$thumbnails_folder = Uri::root().'images/jdownloads/screenshots/thumbnails/';
$screenshots_folder = Uri::root().'images/jdownloads/screenshots/';

// Path to the preview file symbol
$preview_symbol = Uri::root().'administrator/components/com_jdownloads/assets/images/external_blue.gif';

$extern_symbol = Uri::root().'administrator/components/com_jdownloads/assets/images/link_extern.gif';

// Path to the preview files folder
$previews_folder =  Uri::root().basename($params->get('files_uploaddir')).'/'.$params->get('preview_files_folder_name').'/';

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';

if (strpos($listOrder, 'publish_up') !== false){
    $orderingColumn = 'publish_up';
} elseif (strpos($listOrder, 'publish_down') !== false){
    $orderingColumn = 'publish_down';
} elseif (strpos($listOrder, 'modified') !== false){
    $orderingColumn = 'modified';
} else {
    $orderingColumn = 'created';
}

$amount_preview_files = $this->state->get('amount_previews', 0);
$amount_images      = (int)$params->get('be_amount_of_pics_in_downloads_list', 10);
$view_preview_file  = (int)$params->get('view_preview_file_in_downloads_list', 1);
$view_price_field   = (int)$params->get('view_price_field_in_downloads_list', 1);


if ($saveOrder){
    $saveOrderingUrl = 'index.php?option=com_jdownloads&task=downloads.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}

$canOrder    = $user->authorise('core.edit.state', 'com_jdownloads');

// Added to support the Joomla Language Associations
$assoc = Associations::isEnabled();

?>
<form action="<?php echo Route::_('index.php?option=com_jdownloads&view=downloads');?>" method="POST" name="adminForm" id="adminForm">
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
                    <table class="table itemList" id="downloadList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_JDOWNLOADS_DOWNLOADS_TABLE_CAPTION'); ?>
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
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
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_FEATURED', 'a.featured', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_STATUS', 'a.published', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-1 text-center d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_PIC', 'a.file_pic', $listDirn, $listOrder ); ?>
                                </th>
                                <th scope="col" style="min-width:200px">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_TITLE', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_RELEASE', 'a.release', $listDirn, $listOrder ); ?>
                                </th>                        
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_DESCRIPTION', 'a.description', $listDirn, $listOrder ); ?>
                                </th> 
                                <th scope="col" class="d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_FILENAME', 'a.url_download', $listDirn, $listOrder ); ?>
                                </th> 
                                <?php if ($amount_preview_files && $view_preview_file): ?>
                                          <th scope="col" class="w-10 d-none d-lg-table-cell">
                                              <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_PREVIEW_FILE', 'a.preview_filename', $listDirn, $listOrder ); ?>
                                          </th>
                                <?php endif; ?>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort',  'COM_JDOWNLOADS_BACKEND_FILESLIST_AUTHOR', 'a.created_by', $listDirn, $listOrder); ?>
                                </th>                        
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort',  'COM_JDOWNLOADS_ACCESS', 'a.access', $listDirn, $listOrder); ?>
                                </th>
                                <?php // Added to support the Joomla Language Associations
                                    if ($assoc) : ?>
                                        <th scope="col" class="w-5 d-none d-lg-table-cell">
                                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_HEADING_ASSOCIATION', 'association', $listDirn, $listOrder); ?>
                                        </th>
                                <?php endif; ?>
					            <?php 
                                    if (Multilanguage::isEnabled()) : ?>
                                        <th scope="col" class="w-10 d-none d-lg-table-cell">
                                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_LANGUAGE', 'language', $listDirn, $listOrder); ?>
                                        </th>
                                    <?php endif; ?>
                                <th scope="col" class="w-10 d-none d-md-table-cell text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_HEADING_DATE_' . strtoupper($orderingColumn), 'a.' . $orderingColumn, $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-xl-table-cell text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_HITS', 'a.downloads', $listDirn, $listOrder); ?>
                                </th>
                                <?php if ($this->vote) : ?>
                                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_VOTES', 'rating_count', $listDirn, $listOrder); ?>
                                    </th>
                                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_RATINGS', 'rating', $listDirn, $listOrder); ?>
                                    </th>
                                <?php endif; ?>
                                <?php if ($view_price_field): ?>
                                    <th scope="col" class="w-3 d-none d-xl-table-cell">
	                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_FILESLIST_PRICE', 'a.price', $listDirn, $listOrder); ?>
	                                </th>
                                <?php endif; ?>    
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        
                        <tbody <?php if ($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
                        <?php foreach ($this->items as $i => $item) :
                            $item->max_ordering = 0;
                            $ordering   = ($listOrder == 'a.ordering');
                            $canCreate  = $user->authorise('core.create',     'com_jdownloads.category.' . $item->catid);
                            $canEdit    = $user->authorise('core.edit',       'com_jdownloads.download.' . $item->id);
                            $canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
                            $canEditOwn = $user->authorise('core.edit.own',   'com_jdownloads.download.' . $item->id) && $item->created_by == $userId;
                            $canChange  = $user->authorise('core.edit.state', 'com_jdownloads.download.' . $item->id) && $canCheckin;
                            $canEditCat    = $user->authorise('core.edit',       'com_jdownloads.category.' . $item->catid);
                            $canEditOwnCat = $user->authorise('core.edit.own',   'com_jdownloads.category.' . $item->catid) && $item->category_uid == $userId;
                            $canEditParCat    = $user->authorise('core.edit',       'com_jdownloads.category.' . $item->parent_category_id);
                            $canEditOwnParCat = $user->authorise('core.edit.own',   'com_jdownloads.category.' . $item->parent_category_id) && $item->parent_category_uid == $userId;
                            
                            // Build images array
                            $images = array();
                            $password = '';
                            if ($item->images) $images = explode('|', $item->images);
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->catid; ?>">
                            
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
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
                                    
                                <td class="text-center d-none d-md-table-cell">
                                    <?php
                                        $options = [
                                            'task_prefix' => 'downloads.',
                                            'disabled' => !$canChange,
                                            'id' => 'featured-' . $item->id
                                        ];
                                        echo (new FeaturedButton)->render((int) $item->featured, $i, $options);
                                    ?>
                                </td>
                                    
                                <td class="download-status text-center">
                                    <?php
                                        $options = [
                                            'task_prefix' => 'downloads.',
                                            'disabled' => !$canChange,
                                            'id' => 'state-' . $item->id
                                        ];
                                        echo (new PublishedButton)->render((int) $item->published, $i, $options, $item->publish_up, $item->publish_down);
                                    ?>
                                </td>

					            <!-- symbol -->
                                <td class="small d-none d-lg-table-cell text-center">
                                    <?php if ($item->file_pic != '') { 
                                        $file_pic_url = $file_pic_folder.$this->escape($item->file_pic);
                                        ?>
                                        <img src="<?php echo Uri::root().Route::_( $file_pic_url ); ?>" width="38px" height="38px" style="vertical-align: middle; border:0px" />
                                    <?php } ?>
                                </td>
                                
                                <th scope="row" class="has-context">
                                    <div class="break-word">
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'downloads.', $canCheckin); ?>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        if ($canEdit || $canEditOwn) : ?>
                                            <a class="hasTooltip" href="<?php echo Route::_('index.php?option=com_jdownloads&task=download.edit&id=' . $item->id); ?>" title="<?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_TITLE'); ?>">
                                                <?php if ($item->password != '') $password = ' <span class="badge bg-danger hasTooltip" title="'.Text::_('COM_JDOWNLOADS_DOWNLOADS_LIST_PASSWORD_HINT').'"> * </span>';
                                                    
                                                ?>
                                                <?php echo $this->escape($item->title).$password; ?></a>
                                        <?php else : ?>
                                            <span title="<?php echo Text::sprintf('COM_JDOWNLOADS_ALIAS', $this->escape($item->alias)); ?>"><?php echo $this->escape($item->title); ?></span>
                                        <?php endif; ?>
                                        <span class="small break-word">
                                            <?php if (empty($item->notes)) : ?>
                                                    <?php echo Text::sprintf('COM_JDOWNLOADS_LIST_ALIAS', $this->escape($item->alias)); ?>
                                            <?php else : ?>
                                                    <?php echo Text::sprintf('COM_JDOWNLOADS_LIST_ALIAS_NOTE', $this->escape($item->alias), $this->escape($item->notes)); ?>
                                            <?php endif; ?>
                                        </span>
                                        <div class="small">
                                            <?php
                                                $ParentCatUrl = Route::_('index.php?option=com_jdownloads&task=category.edit&id=' . $item->parent_category_id);
                                                $CurrentCatUrl = Route::_('index.php?option=com_jdownloads&task=category.edit&id=' . $item->catid);
                                                $EditCatTxt = Text::_('COM_JDOWNLOADS_EDIT_CAT_EDIT');

                                                    echo Text::_('COM_JDOWNLOADS_CATEGORY') . ': ';

                                                    if ($item->category_level != '1') :
                                                        if ($item->parent_category_level != '1') :
                                                            echo ' &#187; ';
                                                        endif;
                                                    endif;

                                                    if (Factory::getLanguage()->isRtl())
                                                    {
                                                        if ($canEditCat || $canEditOwnCat) :
                                                            echo '<a class="hasTooltip" href="' . $CurrentCatUrl . '" title="' . $EditCatTxt . '">';
                                                        endif;
                                                        echo $this->escape($item->category_title);
                                                        if ($canEditCat || $canEditOwnCat) :
                                                            echo '</a>';
                                                        endif;

                                                        if ($item->category_level != '1') :
                                                            echo ' &#171; ';
                                                            if ($canEditParCat || $canEditOwnParCat) :
                                                                echo '<a class="hasTooltip" href="' . $ParentCatUrl . '" title="' . $EditCatTxt . '">';
                                                            endif;
                                                            echo $this->escape($item->category_title_parent);
                                                            if ($canEditParCat || $canEditOwnParCat) :
                                                                echo '</a>';
                                                            endif;
                                                        endif;
                                                    }
                                                    else
                                                    {
                                                        if ($item->category_level != '1') :
                                                            if ($canEditParCat || $canEditOwnParCat) :
                                                                echo '<a class="hasTooltip" href="' . $ParentCatUrl . '" title="' . $EditCatTxt . '">';
                                                            endif;
                                                            echo $this->escape($item->category_title_parent);
                                                            if ($canEditParCat || $canEditOwnParCat) :
                                                                echo '</a>';
                                                            endif;
                                                            echo ' &#187; ';
                                                        endif;
                                                        if ($canEditCat || $canEditOwnCat) :
                                                            echo '<a class="hasTooltip" href="' . $CurrentCatUrl . '" title="' . $EditCatTxt . '">';
                                                        endif;
                                                        echo $this->escape($item->category_title);
                                                        if ($canEditCat || $canEditOwnCat) :
                                                            echo '</a>';
                                                        endif;
                                                    }
                                                ?>
                                        
                                        <?php 
                                        if ($images) { 
	                                          $all_images = count($images);
	                                          if ($all_images < $amount_images){
	                                              $numbers = $all_images;
	                                          } else {
	                                              $numbers = $amount_images;
	                                          }
	                                          
	                                          if ($amount_images > 0){ 
	                                              echo '<div class="small">';
	                                              
	                                              for ($i=0; $i < $numbers; $i++) {
	                                                  $img = $this->escape($images[$i]);
	                                                  if ($params->get('use_lightbox_function')){
	                                                      echo '<a href="'.$screenshots_folder.$img.'" data-lightbox="lightbox'.$item->id.'" data-title="'.$img.'" target="_blank"><img src="'.$thumbnails_folder.$img.'" class="img-thumbnail" alt="'.$img.'" style="width:30px; height:30px"></a>';    
	                                                  } else {
	                                                      echo '<a href="'.$screenshots_folder.$img.'" target="_blank"><img src="'.$thumbnails_folder.$img.'" class="img-thumbnail" alt="'.$img.'" style="width:30px; height:30px"></a>';    
	                                                  }
	                                              }
	                                              echo '</div>';
	                                          } else {
	                                              if (count($images) > 1){
	                                                  echo '<span class="icon-images" style="font-size:16px; padding-left:5px; padding-right:5px;"></span>';  
	                                              } else {
	                                                  echo '<span class="icon-image" style="font-size:16px; padding-left:5px; padding-right:5px;"></span>';  
	                                              }
	                                          }
                                        } ?>
                                        </div>
                                    </div>
                                </th>
                                
                                <td class="small w-10 d-none d-lg-table-cell">
                                    <?php echo $this->escape($item->release); ?>
                                </td>                        

					            <!-- description -->                    
                                <td class="small w-10 d-none d-lg-table-cell text-center">
                                    <?php
                                    $description = HTMLHelper::_('string.truncate', $this->escape(strip_tags($item->description)), 400, true, false); // Do not cut off words; HTML not allowed;
                                    if ($description != ''){
                                        echo HTMLHelper::_('tooltip', $description, '', Uri::root().'administrator/components/com_jdownloads/assets/images/tooltip_blue.gif');
                                    } ?>
                                </td>

					            <!-- File -->                    
                                <td class="small w-10 d-none d-md-table-cell text-center">
                                    <?php
                                    if ($item->url_download != ''){
                                        echo HTMLHelper::_('tooltip', $item->url_download, '', Uri::root().'administrator/components/com_jdownloads/assets/images/file_blue.gif'); 
                                    } elseif ($item->extern_file != ''){
                                        echo HTMLHelper::_('tooltip', $item->extern_file, '', Uri::root().'administrator/components/com_jdownloads/assets/images/external_orange.gif'); 
                                    } elseif ($item->other_file_id > 0){
                                        $title_tip = strip_tags(Text::sprintf('COM_JDOWNLOADS_BACKEND_FILESLIST_OTHER_DOWNLOADS_FILE_USED', ' '.$item->other_download_title));
                                        echo HTMLHelper::_('tooltip', ' '.$item->other_file_name, $title_tip, Uri::root().'administrator/components/com_jdownloads/assets/images/file_orange.gif', '', '', '', 'hasTip'); 
                                    } else {
                                        // only a document without any files     
                                        echo HTMLHelper::_('tooltip', Text::_('COM_JDOWNLOADS_DOCUMENT_DESC1'), '', Uri::root().'administrator/components/com_jdownloads/assets/images/tooltip_red.gif'); 
                                    }
                                    ?>
                                </td>
                                
                                <!-- Preview File -->                    
                                <?php if ($amount_preview_files && $view_preview_file): ?>
                                    <td class="small w-10 d-none d-lg-table-cell text-center">
                                        <?php

                                        if ($item->preview_filename != ''){

                                            $preview = $this->escape($item->preview_filename);
                                            $filename = basename($preview);
                                            $file_extension = strtolower(File::getExt($filename));
                                            $tooltip = Text::_('COM_JDOWNLOADS_BACKEND_FILESLIST_PREVIEW_TOOLTIP').'<br />'.$preview;
                                                                                                                                    
                                            // Modal window 
                                            $prevModal = 'prevModal'.$item->id;
                                            ?>

                                            <!-- Button trigger modal -->
                                            <button type="button" class="prev-pic-btn" role="Tooltip" data-bs-toggle="modal" data-original-title="<?php echo $tooltip; ?>" data-bs-target="#<?php echo $prevModal; ?>">
                                            </button>

                                            <!-- Modal -->
                                            <div class="modal fade" id="<?php echo $prevModal; ?>" tabindex="-1" aria-labelledby="<?php echo $prevModal; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h3 class="modal-title" id="exampleModalLabel"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESLIST_PREVIEW_FILE'); ?></h3>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('COM_JDOWNLOADS_TOOLBAR_CLOSE'); ?>"></button>
                                                        </div>
                                                    <div class="modal-body">
                                                    <p>
                                                    <?php 
                                                        switch($file_extension)
                                                        {
                                                            case 'mp4':
                                                            case 'webm':
                                                            case 'ogg':
                                                                echo '<video max-width:"100%"; height="auto" controls><source src="'.$previews_folder.$preview.'">Your browser does not support the video tag.</video>';    
                                                                break;
                                                            
                                                            case 'mp3':
                                                            case 'wav':
                                                                echo '<audio width="100%" height="auto" controls><source src="'.$previews_folder.$preview.'">Your browser does not support the audio tag.</video>';
                                                                break;
                                                        }
                                                    ?>
                                                    </p>
                                                        <div class="container">
                                                            <span class="label label-info"><?php echo $preview; ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><?php echo Text::_('COM_JDOWNLOADS_TOOLBAR_CLOSE'); ?></button>
                                                    </div>
                                                </div>
                                              </div>
                                            </div>
                                        <?php } ?>
                                    </td>
                                <?php endif; ?>

                                <!-- Author -->
                                <td class="small w-10 d-none d-md-table-cell">
                                        <a class="hasTooltip" href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->created_by); ?>" title="<?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESLIST_AUTHOR'); ?>">
                                        <?php echo $this->escape($item->author_name); ?></a>
                                </td>

                                <!-- Access -->                    
                                <td class="small w-10 d-none d-md-table-cell">
                                    <?php 
                                    
                                    if ($item->user_access && $item->single_user_access){
                                        $user_name = HTMLHelper::_('string.truncate', $this->escape($item->single_user_access_name), 15);
                                        ?>
                                        <a class="badge bg-danger hasTooltip" href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->user_access); ?>" title="<?php echo Text::_('COM_JDOWNLOADS_USER_ACCESS').':<br />'.$this->escape($item->single_user_access_name); ?>"><?php echo $user_name; ?></a>
                                        <?php
                                    } else {
                                        echo $this->escape($item->access_level);
                                    }
                                     ?>
                                </td>
                                
                                <?php // Added to support the Joomla Language Associations
                                    if ($assoc) : ?>
                                        <td class="small w-5 d-none d-lg-table-cell">
                                            <?php if ($item->association) : ?>
                                                <?php echo HTMLHelper::_('jdownloadsadministrator.association', $item->id); ?>
                                            <?php endif; ?>
                                        </td>
                                <?php endif; ?>

                                <!-- Download Language -->
                                <?php if (Multilanguage::isEnabled()) : ?>
                                    <td class="small w-5 d-none d-lg-table-cell">
                                        <?php if ($item->language == '*'):?>
                                            <?php echo Text::alt('COM_JDOWNLOADS_ALL', 'language'); ?>
                                        <?php else:?>
                                            <?php echo $item->language_title ? HTMLHelper::_('image', 'mod_languages/' . $item->language_image . '.gif', $item->language_title, array('title' => $item->language_title), true) . '&nbsp;' . $this->escape($item->language_title) : Text::_('COM_JDOWNLOADS_UNDEFINED'); ?>
                                        <?php endif;?>
                                    </td>
                                <?php endif; ?>

                                <!-- Date Created -->                    
                                <td class="text-center small w-5 d-none d-md-table-cell">
                                    <?php 
                                        $date = $item->{$orderingColumn};
                                        echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4')) : '-';
                                    ?>
                                </td>

                                <!-- Downloaded -->
                                <td class="text-center d-none d-xl-table-cell">
                                    <span class="badge bg-info">
                                        <?php echo (int) $item->downloads; ?>
                                    </span>
                                </td>

                                <!-- Votes / Ratings -->
                                <?php if ($this->vote) : ?>
                                    <td class="d-none d-lg-table-cell text-center">
                                        <span class="badge bg-success">
                                        <?php echo (int) $item->rating_count; ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-lg-table-cell text-center">
                                        <span class="badge bg-warning text-dark">
                                        <?php echo (int) $item->rating; ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                
                                <!-- Price -->
                                <?php if ($view_price_field): ?>
	                                <td class="text-center d-none d-xl-table-cell ">
	                                    <?php if ($item->price != ''): ?>
	                                    <span class="badge bg-success">
	                                        <?php echo $this->escape($item->price); ?>
	                                    </span>
	                                    <?php else:?>
	                                    <span class="badge bg-success">
	                                        <?php echo ''; ?>
	                                    </span>
	                                    <?php endif;?>
	                                </td>
                                <?php endif; ?>    

                                <!-- ID -->
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
                            <?php echo HTMLHelper::_(
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
                    <?php echo Text::sprintf('COM_JDOWNLOADS_BE_DOWNLOADS_LIST_TOTAL_TEXT', $this->pagination->total); ?>
                </div>          
                                       
                <input type="hidden" name="task" value="" />
                <input type="hidden" name="boxchecked" value="0" />
                <?php echo HTMLHelper::_('form.token'); ?>    
            </div>
        </div>
    </div>
</form>
