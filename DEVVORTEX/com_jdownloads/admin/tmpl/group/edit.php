<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

use JDownloads\Component\JDownloads\Administrator\Helper\JDownloadsHelper;

HtmlHelper::_('bootstrap.tooltip');

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');    
HTMLHelper::_('formbehavior.chosen', 'select');

$canDo = jdownloadsHelper::getActions();

// Path to the layouts folder 
$basePath = JPATH_ROOT .'/administrator/components/com_jdownloads/layouts'; 

$params = $this->state->params;

$group_id = $this->group_id;

$star = '<span class="star">*</span>';

?>
    <script type="text/javascript">
	    Joomla.submitbutton = function(task)
	    {
		    if (task == 'group.cancel' || document.formvalidator.isValid(document.getElementById('group-form'))) {
			    Joomla.submitform(task, document.getElementById('group-form'));
		    }
	    }
    </script>

<?php
    if ($canDo->get('edit.user.limits')) {
?>

<form action="<?php echo ROUTE::_('index.php?option=com_jdownloads&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="group-form" class="form-validate">
	
    <div class="alert alert-info"><?php echo Text::_('COM_JDOWNLOADS_MULTILANGUAGE_TEXT_FIELD_INFO'); ?> </div>
    
    <?php echo LayoutHelper::render('edit.title_groups', $this, $basePath); ?>
    
    <div class="form-horizontal">
        <?php echo HtmlHelper::_('uitab.startTabSet', 'myTab', array('active' => 'settings')); ?>

            <?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'settings', Text::_('COM_JDOWNLOADS_USERGROUPS_GROUP_SETTINGS')); ?>
                <div class="row">
                    <div class="col-md-12">
                        <?php echo LayoutHelper::render('edit.group_settings', $this, $basePath); ?>
                    </div>
                </div>
            <?php echo HtmlHelper::_('uitab.endTab'); ?>
        
            <?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'creation_settings', Text::_('COM_JDOWNLOADS_USERGROUPS_GROUP_CREATION_SETTINGS')); ?>
                <div class="row">
                    <div class="col-md-12">
                        <?php echo LayoutHelper::render('edit.group_creation_settings', $this, $basePath); ?>
                    </div>
                </div>
            <?php echo HtmlHelper::_('uitab.endTab'); ?>

            <?php echo HtmlHelper::_('uitab.addTab', 'myTab', 'limits', Text::_('COM_JDOWNLOADS_USERGROUP_TAB_LIMITS')); ?>
                 <div class="row">
                    <div class="col-md-12">
                <?php if ($group_id != 1 && $group_id != 9){
                          echo '<div class="alert alert-info">'.Text::_('COM_JDOWNLOADS_USERGROUPS_GROUP_LOG_OPTIONS_INFO').'</div>';
                      } ?> 
               
                        <?php if ($group_id == 1 || $group_id == 9){ 
                                  echo '<div class="alert alert-info">'.Text::_('COM_JDOWNLOADS_USERGROUPS_PUBLIC_NO_LIMITS_HINT').'</div>'; 
                              } ?> 
                        <?php echo LayoutHelper::render('edit.group_limits', $this, $basePath); ?>
                    </div>
                </div>
            <?php echo HtmlHelper::_('uitab.endTab'); ?>            
            
        <?php echo HtmlHelper::_('uitab.endTabSet'); ?>
    </div>
                
        <!-- To change the label tag data from a xml definition, we use this simple trick with 'str_replace' --> 
                
        <input type="hidden" name="task" value="" />
		<?php echo HtmlHelper::_('form.token'); ?>

    <div class="clr"></div>
</form>

<?php
   } else {
?>           
    <form action="index.php" method="post" name="adminForm" id="adminForm">
    <div>
            <div class="jdwarning">
                 <?php echo '<b>'.Text::_('COM_JDOWNLOADS_ALERTNOAUTHOR').'</b>'; ?>
            </div>

    </div>
    </form>           
<?php
   }    
?>
