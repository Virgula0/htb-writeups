<?php
/**
* @version $Id: mod_jdownloads_admin_stats.php v3.8
* @package mod_jdownloads_admin_stats
* @copyright (C) 2018Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

HTMLHelper::_('bootstrap.tooltip');

$url_add_download = Route::_('index.php?option=com_jdownloads&task=download.add');
$url_add_category = Route::_('index.php?option=com_jdownloads&task=category.add');
$url_add_tag      = Route::_('index.php?option=com_tags&task=tag.add');
$url_categories   = Route::_('index.php?option=com_jdownloads&view=categories');
$url_downloads    = Route::_('index.php?option=com_jdownloads&view=downloads');
 
?>

<?php if (!$sys_plugin){ ?>
            <div class="small alert alert-warning alert-dismissible fade show" role="alert"">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php
                    echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_SYS_PLUGIN_HINT');
                ?>
            </div>
<?php } ?>

<?php if ($check_system){ ?>

         <?php if (!$menu_item){ ?>
                        <div class="small alert alert-error alert-dismissible fade show" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <?php
                                echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_MAIN_MENU_CHECK_HINT');
                            ?>
                        </div>
         <?php } ?>           
         
         <?php if ($override_folder_found){ ?>
                    <div class="small alert alert-info alert-dismissible fade show" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <?php
                            echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_OVERRIDE_HINT');
                        ?>
                    </div>
         <?php } ?>
         
         <?php if ($override_folder_modules_found){ ?>
                    <div class="small alert alert-info alert-dismissible fade show" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <?php
                            echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_OVERRIDE_MOD_HINT');
                        ?>
                    </div>
         <?php } ?>
    
<?php } ?>

<div class="well well-small">

<?php echo HTMLHelper::_('uitab.startTabSet', 'JD-Admin-Stats', array('active' => 'tab1')); ?>

<?php if($params->get('view_statistics', 1)){  ?>    
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab1', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_STATISTICS')); ?>
        <table class="adminlist table">
            <thead>
                <tr>
                    <th class="title" style="width:70%;"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_TYPE'); ?></th>
                    <th class="nowrap text-center" style="width:10%;">
                                <i class="icon-plus hasTooltip" title="<?php echo Text::_('COM_JDOWNLOADS_ACTION_CREATE'); ?>"></i>
                    </th>
                    <th class="nowrap text-center" style="width:10%;">
                                <i class="icon-publish hasTooltip" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_PUBLISHED'); ?>"></i>
                    </th>
                    <th class="nowrap text-center" style="width:10%;">
                                <i class="icon-unpublish hasTooltip" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_UNPUBLISHED'); ?>"></i>
                    </th>
                    <th class="nowrap text-center" style="width:10%;">
                                <i class="icon-cube hasTooltip" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_TOTAL'); ?>"></i>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <h4><a href="<?php echo $url_categories; ?>"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORIES'); ?></h4>
                    </td>
                    <td class="text-center btns d-md-table-cell itemnumber">                        
                        </a> <a href="<?php echo $url_add_category; ?>">
                            <span class="icon-plus" title="<?php echo Text::_('COM_JDOWNLOADS_EDIT_CAT_ADD');?>" aria-hidden="true"></span></a>
                    </td>
                    <td class="text-center btns d-md-table-cell itemnumber">
                        <a class="small btn <?php echo ($statistics->num_published_categories > 0) ? 'btn-success' : 'btn-secondary'; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_PUBLISHED_CATEGORIES');?>" href="<?php echo Route::_('index.php?option=com_jdownloads&view=categories&filter[published]=1'); ?>">
                                       <?php echo ($statistics->num_published_categories) ?></a>
                    </td>
                    <td class="text-center btns d-md-table-cell itemnumber">
                        <a class="small btn <?php echo ($statistics->num_unpublished_categories > 0) ? 'btn-danger' : 'btn-secondary'; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_UNPUBLISHED_CATEGORIES');?>" href="<?php echo Route::_('index.php?option=com_jdownloads&view=categories&filter[published]=0'); ?>">
                                        <?php echo $statistics->num_unpublished_categories ?></a>
                    </td>
                    <td class="text-center btns d-md-table-cell itemnumber">
                        <a class="small btn <?php echo ($statistics->num_total_categories > 0) ? 'btn-info' : 'btn-secondary'; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORIES');?>" href="<?php echo Route::_('index.php?option=com_jdownloads&view=categories'); ?>">
                                        <?php echo $statistics->num_total_categories ?></a>
                    </td>
                </tr>
                <tr>
                    <td>
                    <h4><a href="<?php echo $url_downloads; ?>"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_DOWNLOADS'); ?></a></h4>
                    </td>
                    <td class="text-center btns d-md-table-cell itemnumber">                        
                        <a href="<?php echo $url_add_download; ?>">
                            <span class="icon-plus" title="<?php echo Text::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_ADD');?>" aria-hidden="true"></span></a>
                    </td>
                    <td class="text-center btns d-md-table-cell itemnumber">
                        <a class="small btn <?php echo ($statistics->num_published_downloads > 0) ? 'btn-success' : 'btn-secondary'; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_PUBLISHED_DOWNLOADS');?>" href="<?php echo Route::_('index.php?option=com_jdownloads&view=downloads&filter[published]=1'); ?>">
                                        <?php echo $statistics->num_published_downloads ?></a>
                    </td>
                    <td class="text-center btns d-md-table-cell itemnumber">
                        <a class="small btn <?php echo ($statistics->num_unpublished_downloads > 0) ? 'btn-danger' : 'btn-secondary'; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_UNPUBLISHED_DOWNLOADS');?>" href="<?php echo Route::_('index.php?option=com_jdownloads&view=downloads&filter[published]=0'); ?>">
                                        <?php echo $statistics->num_unpublished_downloads ?></a>
                    </td>
                    <td class="text-center btns d-md-table-cell itemnumber">
                        <a class="small btn <?php echo ($statistics->num_total_downloads > 0) ? 'btn-info' : 'btn-secondary'; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_DOWNLOADS');?>" href="<?php echo Route::_('index.php?option=com_jdownloads&view=downloads'); ?>">
                                        <?php echo $statistics->num_total_downloads ?></a>
                    </td>
                </tr>
                <tr style="border-top:3px solid #ddd;">
                    <td><h4><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_FEATURED'); ?></h4></td>
                    <td></td>
                    <td class="text-center btns d-md-table-cell itemnumber">
                        <a class="small btn <?php echo ($statistics->num_featured > 0) ? 'btn-success' : 'btn-secondary'; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_FEATURED_ITEMS');?>" href="<?php echo Route::_('index.php?option=com_jdownloads&view=downloads&filter[featured]=1'); ?>">
                                        <?php echo $statistics->num_featured; ?></a>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="5"><h4><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_CAT_TAGS'); ?></h4>
                        
                        <div class="text-start">
                        <?php if ($statistics->category_tags){
                                      foreach ($statistics->category_tags as $tag){
                                          if ($tag->count_cat){ ?>
                                              <a class="badge bg-info" style="" title="" href="<?php echo Route::_('index.php?option=com_jdownloads&view=categories&filter[tag]='.$tag->id); ?>"> 
                                                  <?php echo $tag->title; ?>
                                              </a>   
                                          <?php } ?>
                                          
                                <?php } ?>      
                                <?php } else { ?>
                                        <span class="badge bg-secondary"><?php echo Text::_('COM_JDOWNLOADS_NONE'); ?></span>
                                <?php } ?>
                        </div>                                                
                    </td>
                    
                </tr>
                <tr>
                    <td colspan="5"><h4><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_DOW_TAGS'); ?></h4>
                        
                        <div class="text-start">
                        <?php if ($statistics->download_tags){
                                  foreach ($statistics->download_tags as $tag){
                                      if ($tag->count_download){ ?>
                                          <a class="badge bg-info" style="" title="" href="<?php echo Route::_('index.php?option=com_jdownloads&view=downloads&filter[tag]='.$tag->id); ?>"> 
                                              <?php echo $tag->title; ?>
                                          </a>   
                                  <?php } ?>
                            <?php } ?>      
                        <?php } else { ?>
                                <span class="badge bg-secondary"><?php echo Text::_('COM_JDOWNLOADS_NONE'); ?></span>
                        <?php } ?>
                        </div>                                                
                    </td>
                </tr>
            </tbody>
            <tfoot>
                    <tr>
                        <td colspan="5">
                            <?php 
                                $statistics->sum_downloaded = JDownloadsHelper::strToNumber($statistics->sum_downloaded);
                                $result = sprintf(Text::_('MOD_JDOWNLOADS_ADMIN_STATS_TOTAL_DOWNLOADED'), '<span class="btn btn-info btn-sm">'.$statistics->sum_downloaded.'</span>'); ?>
                                <div class="text-center alert alert-info" role="alert"><?php echo $result; ?></div>
                                
                                
                        </td>
                    </tr>
                </tfoot>
        </table>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>

<?php if($params->get('view_latest', 1)){ ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab2', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_LATEST_ITEMS')); ?>
    <?php 
    if ($latest_items) : ?>
        <div class="row-striped">
        <?php foreach ($latest_items as $i => $item) : ?>
            <div class="row-fluid">
                <div class="span5 truncate">
                    <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'downloads.', false, 'cb', $item->publish_up, $item->publish_down); ?>
                    <?php if ($item->checked_out) : ?>
                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time); ?>
                    <?php endif; ?>

                    <strong class="row-title" style="" title="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($item->link) : ?>
                            <a href="<?php echo $item->link; ?>">
                                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </strong>
                    
                    <small class="hasTooltip" title="<?php echo HtmlHelper::_('tooltipText', 'MOD_JDOWNLOADS_ADMIN_STATS_CREATED_BY'); ?>">
                        <?php echo $item->author_name; ?>
                    </small>
                </div>
                <div class="span4 hidden-phone">
                        <?php if ($item->catlink) : ?>
                            <a class="hasTooltip" href="<?php echo $item->catlink; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?>">
                                <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                </div>
                <div>
                    <div class="small pull-right hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'JGLOBAL_FIELD_CREATED_LABEL'); ?>">
                        <span class="icon-calendar nowrap" aria-hidden="true"></span> <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="row-fluid">
            <div class="span12">
                <div class="alert alert-info"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_NO_MATCHING_RESULTS');?></div>
            </div>
        </div>
    <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>

<?php if($params->get('view_popular', 1)){ ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab3', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_POPULAR_ITEMS')); ?>    
    <?php 
    if ($popular_items) : ?>
        <div class="row-striped">
        <?php foreach ($popular_items as $i => $item) : ?>
            
            <?php // Calculate popular items ?>
            <?php $hits = (int) $item->hits; ?>
            <?php $hits_class = ($hits >= 10000 ? 'important' : ($hits >= 1000 ? 'warning' : ($hits >= 0 ? 'info' : ''))); ?>
        
            <div class="row-fluid">
                <div class="span5 truncate">
                    <span class="badge bg-<?php echo $hits_class; ?> hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'MOD_JDOWNLOADS_ADMIN_STATS_HITS'); ?>"><?php echo $item->hits; ?></span>
                    <?php if ($item->checked_out) : ?>
                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time); ?>
                    <?php endif; ?>

                    <strong class="row-title" style="" title="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($item->link) : ?>
                            <a href="<?php echo $item->link; ?>">
                                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </strong>
                    
                </div>
                <div class="span4 hidden-phone">
                        <?php if ($item->catlink) : ?>
                            <a class="hasTooltip" href="<?php echo $item->catlink; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?>">
                                <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                </div>
                <div>
                    <div class="small pull-right hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'JGLOBAL_FIELD_CREATED_LABEL'); ?>">
                        <span class="icon-calendar nowrap" aria-hidden="true"></span> <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="row-fluid">
            <div class="span12">
                <div class="alert alert-info"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_NO_MATCHING_RESULTS');?></div>
            </div>
        </div>
    <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>

<?php if($params->get('view_featured', 1)){ ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab4', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_FEATURED_ITEMS')); ?>        
    <?php 
    if ($featured_items) : ?>
        <div class="row-striped">
        <?php foreach ($featured_items as $i => $item) : ?>
            <div class="row-fluid">
                <div class="span5 truncate">
                    <span class="badge bg-warning"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_FEATURED'); ?></span>
                    <?php if ($item->checked_out) : ?>
                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time); ?>
                    <?php endif; ?>

                    <strong class="row-title" style="" title="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($item->link) : ?>
                            <a href="<?php echo $item->link; ?>">
                                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </strong>
                    
                </div>
                <div class="span4 hidden-phone">
                        <?php if ($item->catlink) : ?>
                            <a class="hasTooltip" href="<?php echo $item->catlink; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?>">
                                <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                </div>
                <div>
                    <div class="small pull-right hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'JGLOBAL_FIELD_CREATED_LABEL'); ?>">
                        <span class="icon-calendar nowrap" aria-hidden="true"></span> <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="row-fluid">
            <div class="span12">
                <div class="alert alert-info"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_NO_MATCHING_RESULTS');?></div>
            </div>
        </div>
    <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>

<?php if($params->get('view_most_rated', 1)){ ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab5', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_MOST_RATED_ITEMS')); ?>            
    <?php 
    if ($most_rated_items) : ?>
        <div class="row-striped">
        <?php foreach ($most_rated_items as $i => $item) : ?>
            <div class="row-fluid">
                <div class="span5 truncate">
                    <span class="badge bg-primary hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'MOD_JDOWNLOADS_ADMIN_STATS_MOST_RATED_ITEMS_COUNT_HINT'); ?>"><?php echo $item->rating_count; ?></span>
                    <span class="badge hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'MOD_JDOWNLOADS_ADMIN_STATS_MOST_RATED_ITEMS_VALUE_HINT'); ?>"><?php echo $item->ratenum; ?></span>
                    
                    <?php if ($item->checked_out) : ?>
                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time); ?>
                    <?php endif; ?>

                    <strong class="row-title" style="" title="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($item->link) : ?>
                            <a href="<?php echo $item->link; ?>">
                                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </strong>
                    
                </div>
                <div class="span4 hidden-phone">
                        <?php if ($item->catlink) : ?>
                            <a class="hasTooltip" href="<?php echo $item->catlink; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?>">
                                <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                </div>
                <div>
                    <div class="small pull-right hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'JGLOBAL_FIELD_CREATED_LABEL'); ?>">
                        <span class="icon-calendar nowrap" aria-hidden="true"></span> <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div> 
    <?php else : ?>
        <div class="row-fluid">
            <div class="span12">
                <div class="alert alert-info"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_NO_MATCHING_RESULTS');?></div>
            </div>
        </div>
    <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>

<?php if($params->get('view_top_rated', 1)){ ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab6', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_TOP_RATED_ITEMS')); ?>            
    <?php 
    if ($top_rated_items) : ?>
        <div class="row-striped">
        <?php foreach ($top_rated_items as $i => $item) : ?>
            <div class="row-fluid">
                <div class="span5 truncate">
                    <span class="badge bg-primary hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'MOD_JDOWNLOADS_ADMIN_STATS_MOST_RATED_ITEMS_VALUE_HINT'); ?>"><?php echo $item->ratenum; ?></span>
                    <span class="badge hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'MOD_JDOWNLOADS_ADMIN_STATS_MOST_RATED_ITEMS_COUNT_HINT'); ?>"><?php echo $item->rating_count; ?></span>
                    
                    <?php if ($item->checked_out) : ?>
                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time); ?>
                    <?php endif; ?>

                    <strong class="row-title" style="" title="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($item->link) : ?>
                            <a href="<?php echo $item->link; ?>">
                                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </strong>
                    
                </div>
                <div class="span4 hidden-phone">
                        <?php if ($item->catlink) : ?>
                            <a class="hasTooltip" href="<?php echo $item->catlink; ?>" title="<?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?>">
                                <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                </div>
                <div>
                    <div class="small pull-right hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'JGLOBAL_FIELD_CREATED_LABEL'); ?>">
                        <span class="icon-calendar nowrap" aria-hidden="true"></span> <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div> 
    <?php else : ?>
        <div class="row-fluid">
            <div class="span12">
                <div class="alert alert-info"><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_NO_MATCHING_RESULTS');?></div>
            </div>
        </div>
    <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>


<?php if($params->get('view_monitoring_log', 1)){ ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab7', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_VIEW_MONITORING_LOG_TAB')); ?>                
        <div>
            <?php 
            if ($monitoring_log != "") { 
                $log_file_url = Uri::base().'components/com_jdownloads/monitoring_logs.txt';
                ?> 
                <button type="button" class="btn btn-outline-info btn-sm"><a href="index.php?option=com_jdownloads&task=tools.deleteMonitoringLog"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_DELETE_LOG_LINK_TEXT');?></a></button>
                <button type="button" class="btn btn-outline-info btn-sm"><a href="<?php echo $log_file_url; ?>" download><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_DOWNLOAD_BUTTON'); ?></a></button>
                <?php echo '<div class="small alert alert-info">'.$monitoring_log.'</div>';
            } else { ?>
                <div class="row-fluid">
                    <div class="span12">
                        <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_NONE');?></div>
                    </div>
                </div>
            <?php } ?>        
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>

<?php if($params->get('view_restore_log', 1)){ ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab8', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_VIEW_RESTORE_LOG_TAB')); ?>                    
        <div>
            <?php 
            if ($restore_log != "") { 
                $log_file_url = Uri::base().'components/com_jdownloads/restore_logs.txt';
                ?> 
                <button type="button" class="btn btn-outline-info btn-sm"><a href="index.php?option=com_jdownloads&task=tools.deleteRestorationLog"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_DELETE_LOG_LINK_TEXT');?></a></button>
                <button type="button" class="btn btn-outline-info btn-sm"><a href="<?php echo $log_file_url; ?>" download><?php echo Text::_('MOD_JDOWNLOADS_ADMIN_STATS_DOWNLOAD_BUTTON'); ?></a></button>
                <?php echo '<div class="small alert alert-info">'.$restore_log.'</div>';
            } else { ?>
                <div class="row-fluid">
                    <div class="span12">
                        <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_NONE');?></div>
                    </div>
                </div>
            <?php } ?>        
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>

<?php if($params->get('view_install_log', 1)){ ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab9', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_VIEW_INSTALL_LOG_TAB')); ?>                        
        <div>
            <?php 
            if ($install_log != "") { 
                ?> 
                <button type="button" class="btn btn-outline-info btn-sm"><a href="index.php?option=com_jdownloads&task=tools.deleteInstallationLog"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_DELETE_LOG_LINK_TEXT'); ?></a></button>
                <?php echo '<div class="small alert alert-info">'.$install_log.'</div>';
            } else { ?>
                <div class="row-fluid">
                    <div class="span12">
                        <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_NONE');?></div>
                    </div>
                </div>
            <?php } ?>        
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>

<?php if($params->get('view_server_info', 1)){ ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'JD-Admin-Stats', 'tab10', Text::_('MOD_JDOWNLOADS_ADMIN_STATS_VIEW_SERVER_INFO_TAB')); ?>                        
       <div class="alert alert-info">
          <?php echo Text::_('COM_JDOWNLOADS_BACKEND_SERVER_INFOS_TAB_TITLE'); ?>
       </div>  
       <table class="jdadminpanel table" style="border:0px;">
          <tr class="row0">
             <td colspan="2" style="vertical-align: top; align-content: left; width:100%;">
                 <?php echo Text::_('COM_JDOWNLOADS_BACKEND_SERVER_INFOS_TAB_DESC').''; ?>
              </td>
          </tr>
          <tr class="row1">
             <td style="width:80%;">
             <?php echo Text::_('COM_JDOWNLOADS_BACKEND_SERVER_INFOS_TAB_FILE_UPLOADS'); ?>
             </td>
             <td style="text-align:right; width:20%;">
             <?php if (get_cfg_var('file_uploads')){ echo Text::_('COM_JDOWNLOADS_YES'); } else { echo Text::_('COM_JDOWNLOADS_NO'); } ?> 
             </td>
          </tr>
          <tr class="row0">  
             <td style="width:80%;">
             <?php echo Text::_('COM_JDOWNLOADS_BACKEND_SERVER_INFOS_TAB_MAX_FILESIZE'); ?>
             </td>
             <td style="text-align:right; width:20%;">
             <?php echo get_cfg_var ('upload_max_filesize'); ?>
             </td>
          </tr>  
          <tr class="row1">  
             <td style="width:80%;">
             <?php echo Text::_('COM_JDOWNLOADS_BACKEND_SERVER_INFOS_TAB_POST_MAX_SIZE'); ?>
             </td>
             <td style="text-align:right; width:20%;">
             <?php echo get_cfg_var ('post_max_size'); ?>
             </td>
          </tr>  
          <tr class="row0">  
             <td style="width:80%;">
             <?php echo Text::_('COM_JDOWNLOADS_BACKEND_SERVER_INFOS_TAB_MEMORY_LIMIT'); ?>
             </td>
             <td style="text-align:right; width:20%;">
             <?php echo get_cfg_var ('memory_limit'); ?>
             </td>
          </tr>  
          <tr class="row1">  
             <td style="width:80%;">
             <?php echo Text::_('COM_JDOWNLOADS_BACKEND_SERVER_INFOS_TAB_MAX_INPUT_TIME'); ?>
             </td>
             <td style="text-align:right; width:20%;">
             <?php echo get_cfg_var ('max_input_time'); ?>
             </td>
          </tr>  
          <tr class="row0">  
             <td style="width:80%;">
             <?php echo Text::_('COM_JDOWNLOADS_BACKEND_SERVER_INFOS_TAB_MAX_EXECUTION_TIME'); ?>
             </td>
             <td style="text-align:right; width:20%;">
             <?php echo get_cfg_var ('max_execution_time'); ?>
             </td>
          </tr>  
       </table>
       <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php } ?>

<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

<?php echo '</div>';?>