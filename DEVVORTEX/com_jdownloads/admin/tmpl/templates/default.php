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

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Session\Session;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

// Required for columns selection
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');    

$app         = Factory::getApplication();
$user        = $app->getIdentity();
$userId      = $user->get('id');

$params = $this->state->params;

// Path to the layouts folder 
$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts';
$options['base_path'] = $basePath;

$layout_previews_url = 'https://www.jdownloads.net/help-server/layout-previews/';

$listOrder = str_replace(' ' . $this->state->get('list.direction'), '', $this->state->get('list.fullordering') ?? '');
$listDirn  = $this->escape($this->state->get('list.direction'));

$canOrder  = $user->authorise('core.edit.state', 'com_jdownloads');
   
?>
<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&view=templates&type='.$this->jd_tmpl_type.'');?>" method="POST" name="adminForm" id="adminForm">

<div>
    <nav class="navbar navbar-expand-sm bg-primary">
        <div class="container-fluid">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link text-light <?php echo $this->active1; ?>" href="index.php?option=com_jdownloads&amp;view=templates&type=1"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP1' ) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light <?php echo $this->active8; ?>" href="index.php?option=com_jdownloads&amp;view=templates&type=8"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP8' ) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light <?php echo $this->active4; ?>" href="index.php?option=com_jdownloads&amp;view=templates&type=4"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP4' ) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light <?php echo $this->active2; ?>" href="index.php?option=com_jdownloads&amp;view=templates&type=2"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP2' ) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light <?php echo $this->active5; ?>" href="index.php?option=com_jdownloads&amp;view=templates&type=5"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP5' ) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light <?php echo $this->active3; ?>" href="index.php?option=com_jdownloads&amp;view=templates&type=3"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP3' ) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light" href="index.php?option=com_jdownloads&amp;view=cssedit"><?php echo Text::_( 'COM_JDOWNLOADS_BACKEND_EDIT_CSS_TITLE' ) ?></a>
                </li>
            </ul>
        </div>
    </nav>
</div>

<div class="row">
    <div class="col-md-12">
        <div id="j-main-container" class="j-main-container">
            <?php
            // Search tools bar
            echo LayoutHelper::render('searchtools.default', array('view' => $this, 'options' => array('filterButton' => false))); ?>
            
        <?php if (empty($this->items)) : ?>
                <div class="alert alert-info">
                    <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                    <?php echo Text::_('COM_JDOWNLOADS_NO_MATCHING_RESULTS'); ?>
                </div>
            
        <?php else : ?>

                <div class="alert alert-info" style="margin-top:10px;"><?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPLIST_LOCKED_DESC'); ?> </div>
                <div class="clr"> </div>
        
            <table class="table itemList" id="logsList">
                <caption class="visually-hidden">
                    <?php echo Text::_('COM_JDOWNLOADS_DOWNLOADS_TABLE_CAPTION'); ?>
                    <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?></span>,
                    <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                </caption>
                <thead>
                    <tr>
                        <th class="w-1 text-center">
                            <?php echo HtmlHelper::_('grid.checkall'); ?>
                        </th>
                        
                        <th scope="col" style="min-width:200px">
                            <?php echo HtmlHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_TEMPLIST_TITLE', 'a.template_name', $listDirn, $listOrder ); ?>
                        </th>

                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPLIST_PREVIEW');  ?> 
                        </th>
                        
                        <th scope="col" class="w-3 d-none d-lg-table-cell">
                            <?php echo  Text::_('COM_JDOWNLOADS_BACKEND_TEMPLIST_TYP'); ?>
                        </th>

                        <?php 
                            //Only for Categories and Downloads layouts
                            if ($this->jd_tmpl_type == 1 || $this->jd_tmpl_type == 2 || $this->jd_tmpl_type == 8) { ?>
                                <th class="text-center d-none d-md-table-cell">   
                                    <?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPLIST_COLS');  ?> 
                                </th>
                            <?php }
                        ?>
                            
                        <?php    
                            // Only for Downloads layouts
                            if ($this->jd_tmpl_type == 2) { ?>    
                                <th class="text-center d-none d-md-table-cell">   
                                    <?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_CHECKBOX_TITLE');  ?> 
                                </th>                            
                                
                                <th class="text-center d-none d-md-table-cell">   
                                    <?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_SYMBOLE_TITLE');  ?> 
                                </th>                            
                            <?php }
                        ?>
                        
                        <th class="text-center d-none d-lg-table-cell">
                            <?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPLIST_USES_BOOTSTRAP');  ?> 
                        </th>
                        
                        <th class="text-center d-none d-lg-table-cell">   
                            <?php echo Text::_('COM_JDOWNLOADS_BACKEND_TEMPLIST_USES_W3CSS');  ?> 
                        </th>
                        
                        <th class="text-center d-none d-lg-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_TEMPLIST_LOCKED', 'a.locked', $listDirn, $listOrder ); ?>
                        </th>
                        
                        <th class="text-center">                        
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_BACKEND_TEMPLIST_ACTIVE', 'a.template_active', $listDirn, $listOrder ); ?>
                        </th>

                        <th class="nowrap d-none d-lg-table-cell" style="width: 1%;">                        
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_JDOWNLOADS_ID', 'a.id', $listDirn, $listOrder ); ?>
                        </th>
                    </tr>    
                </thead>
                <tbody>    
                    <?php
                        foreach ($this->items as $i => $item) {
                            $link         = ROUTE::_( 'index.php?option=com_jdownloads&task=template.edit&id='.(int) $item->id.'&type='.(int) $item->template_typ);
                            $canCheckin   = $user->authorise('core.manage',     'com_checkin') || $item->checked_out==$user->get('id') || $item->checked_out==0;
                            $canChange    = $user->authorise('core.edit.state', 'com_jdownloads') && $canCheckin;
                            $canCreate    = $user->authorise('core.create',     'com_jdownloads');
                            $canEdit      = $user->authorise('core.edit',       'com_jdownloads');
                            ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                
                                <td class="center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                </td>
                                
                                <td>
                                <?php if ($item->checked_out) : ?>
                                <?php echo HTMLHelper::_('jgrid.checkedout', $i, $user->name, $item->checked_out_time, 'templates.', $canCheckin); ?>
                                <?php endif; ?>

                                <?php if ($canEdit) : ?>
                                    <a href="<?php echo $link; ?>">
                                        <?php echo $this->escape($item->template_name); ?></a>
                                <?php else : ?>
                                        <?php echo $this->escape($item->template_name); ?>
                                <?php endif; ?>
                                
                                    <p class="small">
                                        <?php echo ($item->note);?>
                                    </p>
                                </td>
                                
                                <td class="text-center d-none d-md-table-cell">
                                    
                                    <?php
                                        $img = 'layout_type_'.(int)$this->jd_tmpl_type.'_'.(int)$item->preview_id.'.gif';
                                        $thumb = 'layout_type_'.(int)$this->jd_tmpl_type.'_'.(int)$item->preview_id.'_thumb.gif';
                                        clearstatcache();
                                        if (JDownloadsHelper::url_check($layout_previews_url.$img)){
                                            if ($params->get('use_lightbox_function')){
                                                echo '<a href="'.$layout_previews_url.$img.'" data-lightbox="lightbox'.$item->id.'" data-title="'.Text::_('COM_JDOWNLOADS_BACKEND_TEMPEDIT_PREVIEW_NOTE').'" target="_blank"><img src="'.$layout_previews_url.$thumb.'" class="img-polaroid" alt="'.$thumb.'" style="width:50px; height:50px"></a>';    
                                            } else {
                                                echo '<a href="'.$layout_previews_url.$img.'" target="_blank"><img src="'.$layout_previews_url.$thumb.'" class="img-polaroid" alt="'.$thumb.'" style="width:50px; height:50px"></a>';    
                                            }
                                        } else {
                                            echo '<img src="'.$layout_previews_url.'no_pic.gif'.'" class="img-polaroid" alt="" style="width:50px; height:50px"></a>';
                                        }
                                    ?>
                                    
                                </td>
                                
                                <td class="d-none d-lg-table-cell text-center">
                                    <?php echo $this->temp_type_name[$item->template_typ]; ?>
                                </td>

                                <?php if ($this->jd_tmpl_type == 1 || $this->jd_tmpl_type == 2 || $this->jd_tmpl_type == 8) { ?>
                                    <td class="d-none d-md-table-cell text-center">   
                                        <?php echo '<span class="badge bg-info">'.(int)$this->escape($item->cols).'</span>'; ?> 
                                    </td>                        
                                <?php } ?>

                                <?php if ($this->jd_tmpl_type == 2) { ?>    
                                        <td class="d-none d-md-table-cell text-center">   
                                        <?php if (!$item->checkbox_off){
                                                  echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                              } else {
                                                  echo '<span class="">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                              } 
                                        ?>
                                        </td>                            

                                        <td class="d-none d-md-table-cell text-center">
                                        <?php if (!$item->symbol_off){
                                                  echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                              } else {
                                                  echo '<span class="">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                              } 
                                        ?>
                                        </td>                            
                                <?php } ?>
                                
                                <td class="d-none d-lg-table-cell text-center">   
                                        <?php if ($item->uses_bootstrap){
                                                  echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                              } else {
                                                  echo '<span class="">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                              } 
                                        ?>
                                </td>
                                    
                                <td class="d-none d-lg-table-cell text-center">   
                                        <?php if ($item->uses_w3css){
                                                  echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                              } else {
                                                  echo '<span class="">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                              } 
                                        ?>
                                </td>                                
                                
                                <td class="d-none d-lg-table-cell text-center"> 
                                    <?php
                                        if ($item->locked) {
                                                  echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                              } else {
                                                  echo '<span class="">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                              }
                                        ?>                
                                </td>
                        
                                <td class="text-center"> 
                                    <?php
                                        if ($item->template_active) {
                                                  echo '<span class="badge bg-success">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                              } else {
                                                  echo '<span class="">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                              }
                                    ?>
                                </td>
                                
                                <td class="nowrap d-none d-lg-table-cell">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                        <?php 
                        }
                        ?>
                </tbody>
            </table>
        <?php endif;?>
        
            <?php echo $this->pagination->getListFooter(); ?>
            
            <!-- Display the amount of listed items -->
            <div class="alert alert-info text-center">
                <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::sprintf('COM_JDOWNLOADS_BE_LAYOUTS_LIST_TOTAL_TEXT', $this->pagination->total); ?>
            </div> 
        </div>
    </div>    
</div>

<div>
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="type" value="<?php echo (int)$this->jd_tmpl_type; ?>" />
    <?php echo HTMLHelper::_('form.token'); ?>    
</div>
</form>
