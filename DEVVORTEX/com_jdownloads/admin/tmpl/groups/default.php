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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Access\Access;

// Required for columns selection
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');
    
HtmlHelper::_('bootstrap.tooltip');

$user		= Factory::getApplication()->getIdentity();

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));

?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		Joomla.submitform(task);
	}
</script>

<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&view=groups');?>" method="post" name="adminForm" id="adminForm">

    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
            <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filterButton' => false))); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                    
                <?php else : ?>
                    
                    <div class="alert alert-info" style="margin-top:10px;"><?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_GROUP_TITLE_INFO'); ?> </div>    
                    <div class="clr"> </div>
    
                    <table class="table" id="groupsList">
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>

                                <th scope="col">
                                    <?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_GROUP_TITLE'); ?>
                                </th>

                                <th scope="col" class="w-10 text-center">
                                    <?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_IMPORTANCE'); ?>
                                </th> 

                                <th scope="col" class="w-5 text-center">
                                    <?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_USERS_IN_GROUP'); ?>
                                </th>

                                <th scope="col" class="w-5 text-center d-none d-lg-table-cell">
                                    <?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_CAPTCHA'); ?>
                                </th>                    

                                <th scope="col" class="w-5 text-center d-none d-lg-table-cell">
                                    <?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_FORM'); ?>                    
                                </th> 
                                 
                                <th scope="col" class="w-5 text-center d-none d-lg-table-cell">
                                    <?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_MUST_FORM_FILL_OUT'); ?>                    
                                </th>

                                <th scope="col" class="w-5 text-center d-none d-xl-table-cell">
                                    <?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_REPORT_FORM'); ?>
                                </th>
                                
                                <th scope="col" class="w-5 text-center d-none d-xl-table-cell">
                                    <?php echo Text::_('COM_JDOWNLOADS_USERGROUPS_VIEW_COUNTDOWN'); ?>
                                </th>
                                    <th scope="col" class="w-5 text-center d-none d-md-table-cell">
                                    <?php echo Text::_('COM_JDOWNLOADS_USERGROUP_TAB_LIMITS'); ?>
                                </th>

                                <th scope="col" class="w-5 text-center d-none d-md-table-cell">
                                    <?php echo Text::_('COM_JDOWNLOADS_ID'); ?>
                                </th>
                            </tr>
                        </thead>
        
		                <tbody>
		                
                        <?php
                        foreach ($this->items as $i => $item) :
                            $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out==$user->get('id') || $item->checked_out==0;
			                $canEdit	= $user->authorise('core.admin', 'com_jdownloads') || $user->authorise('edit.user.limits', 'com_jdownloads');

			                // If this group is super admin and this user is not super admin, $canEdit is false   !!!
			                if (!$user->authorise('core.admin') && (Access::checkGroup($item->id, 'core.admin'))) {
				                $canEdit = false;
			                }
		                ?>
			                <tr class="row<?php echo $i % 2; ?>">
				                <td class="text-center" data-usercount="<?php echo $item->user_count; ?>">
					                <?php if ($canEdit) : ?>
						                <?php echo HtmlHelper::_('grid.id', $i, $item->id); ?>
					                <?php endif; ?>
				                </td>
                                
                                <th scope="row">
                                    <?php if ($item->checked_out) : ?>
                                        <?php echo HtmlHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'groups.', $canCheckin); ?>
                                    <?php endif; ?>                
                                
                                    <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level + 1)); ?>
                                    <?php if ($canEdit) : ?>
					                    <a href="<?php echo Route::_('index.php?option=com_jdownloads&task=group.edit&id=' . $item->jd_user_group_id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->title); ?>">
                                                <?php echo $this->escape($item->title); ?></a>
					                <?php else : ?>
						                        <?php echo $this->escape($item->title); ?>
					                <?php endif; ?>

				                </th>
                                <td class="text-center">
                                    <?php echo '<span class="badge bg-danger">'.$item->importance.'</span>'; ?>
                                </td>
                                
				                <td class="text-center btns itemnumber d-md-table-cell">
					                      <a class="badge <?php echo ($item->user_count > 0) ? 'bg-warning' : 'bg-primary'; ?>" href="<?php echo ROUTE::_('index.php?option=com_users&view=users&filter[group_id]=' . (int) $item->jd_user_group_id); ?>">
                                          <?php echo $item->user_count; ?></a>      
				                </td>

                                <td class="text-center btns itemnumber d-none d-lg-table-cell">
                                    <?php if ($item->view_captcha){
                                              echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                          } else {
                                              echo '<span class="badge bg-primary">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                          } 
                                    ?>
                                </td>                 
                                
                                <td class="text-center btns itemnumber d-none d-lg-table-cell">
                                    <?php if ($item->view_inquiry_form){
                                              echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                          } else {
                                              echo '<span class="badge bg-primary">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                          } 
                                    ?>
                                </td>                 

                                <td class="text-center btns itemnumber d-none d-lg-table-cell">
                                    <?php if ($item->must_form_fill_out){
                                              echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                          } else {
                                              echo '<span class="badge bg-primary">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                          } 
                                    ?>
                                </td>

                                <td class="text-center btns itemnumber d-none d-xl-table-cell">
                                    <?php 
                                    if ($item->view_report_form){
                                              echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                          } else {
                                              echo '<span class="badge bg-primary">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                          } 
                                    ?>
                                </td>                 

                                <td class="text-center btns itemnumber d-none d-xl-table-cell">
                                    <?php if ($item->countdown_timer_duration){
                                              echo '<span class="badge bg-warning">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                          } else {
                                              echo '<span class="badge bg-primary">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                          } 
                                    ?>
                                </td>                 

                                <td class="d-none d-md-table-cell text-center">
                                    <?php 
                                        // Check wheter exists limitations and then add the limitation informations
                                        $item->limit_info = '';
                                        if ($item->download_limit_daily > 0)          $item->limit_info .= Text::_('COM_JDOWNLOADS_USERGROUPS_DOWNLOAD_LIMIT_DAILY').': '.$item->download_limit_daily.' - ';
                                        if ($item->download_limit_weekly > 0)         $item->limit_info .= Text::_('COM_JDOWNLOADS_USERGROUPS_DOWNLOAD_LIMIT_WEEKLY').': '.$item->download_limit_weekly.' - ';
                                        if ($item->download_limit_monthly > 0)        $item->limit_info .= Text::_('COM_JDOWNLOADS_USERGROUPS_DOWNLOAD_LIMIT_MONTHLY').': '.$item->download_limit_monthly.' - ';
                                        if ($item->download_volume_limit_daily > 0)   $item->limit_info .= Text::_('COM_JDOWNLOADS_USERGROUPS_DOWNLOAD_VOLUME_LIMIT_DAILY').': '.$item->download_volume_limit_daily.'MB - ';
                                        if ($item->download_volume_limit_weekly > 0)  $item->limit_info .= Text::_('COM_JDOWNLOADS_USERGROUPS_DOWNLOAD_VOLUME_LIMIT_WEEKLY').': '.$item->download_volume_limit_weekly.'MB - ';
                                        if ($item->download_volume_limit_monthly > 0) $item->limit_info .= Text::_('COM_JDOWNLOADS_USERGROUPS_DOWNLOAD_VOLUME_LIMIT_MONTHLY').': '.$item->download_volume_limit_monthly.'MB - ';
                                        if ($item->how_many_times > 0)                $item->limit_info .= Text::_('COM_JDOWNLOADS_USERGROUPS_DOWNLOAD_HOW_MANY_TIMES').': '.$item->how_many_times.' - ';
                                        if ($item->upload_limit_daily > 0)            $item->limit_info .= Text::_('COM_JDOWNLOADS_USERGROUPS_UPLOAD_LIMIT_DAILY').': '.$item->upload_limit_daily.' - ';
                                        if ($item->transfer_speed_limit_kb > 0)       $item->limit_info .= Text::_('COM_JDOWNLOADS_USERGROUPS_DOWNLOAD_TRANSFER_SPEED_LIMIT').': '.$item->transfer_speed_limit_kb.'KB';
                                    
                                        if ($item->limit_info){
                                            // Remove ' - ' at the end when exist
                                            $pos = strripos($item->limit_info, ' - ');
                                            if ($pos > (strlen($item->limit_info) - 4)){
                                                $item->limit_info = substr($item->limit_info, 0, $pos);
                                            }
                                            echo '<span class="badge bg-warning" rel="tooltip" title="'.$item->limit_info.'">'.Text::_('COM_JDOWNLOADS_YES').'</span>';
                                        } else {
                                            echo '<span class="badge bg-primary">'.Text::_('COM_JDOWNLOADS_NO').'</span>';
                                        } 
                                    ?>
                                </td> 

				                <td class="d-none d-md-table-cell text-center">
					                <?php echo (int) $item->id; ?>
				                </td>
			                </tr>
			            <?php endforeach; ?>
		                </tbody>
	                </table>
    
                <?php endif;?>

                <?php echo $this->pagination->getListFooter(); ?>
    
        <!-- Display the amount of listed items -->
        <div class="alert alert-info text-center">
            <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::sprintf('COM_JDOWNLOADS_BE_GROUPS_LIST_TOTAL_TEXT', $this->pagination->total); ?>
        </div> 
    
	</div>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo HtmlHelper::_('form.token'); ?>
</form>
