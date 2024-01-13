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
 
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Version;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;


    // For Tooltip
    HTMLHelper::_('bootstrap.tooltip');
    
    HTMLHelper::_('behavior.keepalive');
    HTMLHelper::_('behavior.formvalidator');    
    
    $app    = Factory::getApplication();    
    $jinput = $app->input; 
    
	// Check if it is still a Joomla 4 version.
    // Required for reCaptcha processing, as we no longer have reCaptcha v2 available as of Joomla 5.
	$cms_version = new Version();
	$cms_version_5 = $cms_version->isCompatible(5);    

	$captcha_valid  = false;
    $captcha_invalid_msg = '';

    if ($this->user_rules->view_captcha){
        
        // Get the activated captcha plugin from global config
        $active_captcha = $app->getCfg('captcha');
        
        // Get captcha plugin
        PluginHelper::importPlugin('captcha');
        $plugin = PluginHelper::getPlugin('captcha', $active_captcha);

        // Get plugin param
        if (isset($plugin->params)){
	        $pluginParams = new Registry($plugin->params);
        	$public_key = $pluginParams->get('public_key');        
        } else {
            // Plugin not activated
            $public_key = '';
        }
			
		if (!$cms_version_5){
        	// Handling only for reCaptcha v2 and Joomla 4
	        $dummy = $jinput->getString('g-recaptcha-response');
	        if (!$dummy) $dummy = $jinput->getString('recaptcha_response_field');        
	        
	        // Check now whether user has used the captcha already
	        if (isset($dummy)){
	                $captcha_res = $app->triggerEvent('onCheckAnswer', array($dummy));
	                if (!$captcha_res[0]){
	                    // Init again for next try
	                    if ($active_captcha == 'recaptcha'){
	                            $app->triggerEvent('onInit', array('dynamic_recaptcha_1'));
	                    } else {
	                        $app->triggerEvent('onInit', array('dynamic_recaptcha_invisible_1'));
	                    }
	                    $captcha_invalid_msg = Text::_('COM_JDOWNLOADS_FIELD_CAPTCHA_INCORRECT_HINT');
	                } else {
	                    $captcha_valid = true;
	                }
	        } else {
	            // Init for first try
	            if ($active_captcha == 'recaptcha'){
	                $exist_event = $app->triggerEvent('onInit', array('dynamic_recaptcha_1'));
	            } else {
	                $exist_event = $app->triggerEvent('onInit', array('dynamic_recaptcha_invisible_1'));
	            }
	            
	            // When plugin event not exist, we must do the work without it. But give NOT a public info about this problem.
	            if (!$exist_event){
	                $captcha_valid = true;
	            }
	        }
		}    
    } else {
        // We need this switch to handle the data output 
        $captcha_valid = true;
    }
					
    
    // Required for captcha
    $form_uri = Uri::getInstance(); 
    $form_uri = $form_uri->toString();
    $form_uri = $this->escape($form_uri);    
    
    // Create shortcuts to some parameters.
    $params     = $this->params;
    
    $user       = Factory::getUser();    
    $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;    

?>
    <script type="text/javascript">
        Joomla.submitbutton = function(task) {
            if (task == 'report.cancel' || document.formvalidator.isValid(document.getElementById('adminForm'))) {
                Joomla.submitform(task);
            } else {
                alert('<?php echo $this->escape(Text::_('COM_JDOWNLOADS_VALIDATION_FORM_FAILED'));?>');
            }
        }
    </script>       


<div class="edit jd-item-page<?php echo $this->pageclass_sfx; ?>">

    <?php
    
    
    // view offline message - but admins can view it always    
    if ($params->get('offline') && !$is_admin){
        if ($params->get('offline_text') != '') {
            echo JDHelper::getOnlyLanguageSubstring($params->get('offline_text')).'</div>';
        }
    } else {         
    ?>

<form action="<?php echo $form_uri; ?>" name="adminForm" method="post" id="adminForm" class="form-validate form-vertical" accept-charset="utf-8">

        <fieldset>
            <?php echo '<div class="alert alert-info">'.Text::_('COM_JDOWNLOADS_REPORT_INFO').'</div>'; ?> 

            <legend>
                <?php echo Text::_('COM_JDOWNLOADS_FRONTEND_REPORT_FILE_LINK_TEXT'); ?>
            </legend>
        
            <?php 
                // view it only when captcha_valid var is false  
                if (!$captcha_valid && !$cms_version_5){
                    echo ' '.Text::_('COM_JDOWNLOADS_FORM_VERIFY_HUMAN'); 
                          
                    // add captcha
                    $captcha = '<div class="jd_recaptcha">';
                    $captcha .= '<div class="g-recaptcha" data-sitekey="'.$public_key.'"></div>';
                    
                    if ($active_captcha == 'recaptcha'){
                        $captcha .= '<div id="dynamic_recaptcha_1"></div>';
                    } else {
                        $captcha .= '<div id="dynamic_recaptcha_invisible_1"></div>';
                    }    
                    
                    $captcha .= '<br /><input type="submit" name="submit" id="jd_captcha" class="button" value="'.Text::_('COM_JDOWNLOADS_FORM_BUTTON_TEXT').'" />';

                    if ($captcha_invalid_msg != ''){
                        $captcha .= $captcha_invalid_msg;
                    } 
                    
                    $captcha .= '</div>'; 
                    
                    echo $captcha;
        
                } else {
                    ?>
                
				<div class="control-group">
                   	<div class="control-label">
                    <?php echo $this->form->getLabel('name'); ?>
                    <div class="controls">
                    <?php echo $this->form->getInput('name'); ?>
                    </div>
                </div>
                
                <div class="control-group">
                    <div class="control-label">
                    <?php echo $this->form->getLabel('email'); ?>
                    <div class="controls">
                    <?php echo $this->form->getInput('email'); ?>
                </div>
                
                <div class="control-group">
                    <div class="control-label">
                    <?php echo $this->form->getLabel('catid'); ?>
                    <div class="controls">
                    <?php echo $this->form->getInput('catid'); ?>
                </div>
                
                <div><hr></div>
                
                <div class="control-group">
                    <div class="control-label">
                    <?php echo $this->form->getLabel('id'); ?>
                    <div class="controls">
                    <?php echo $this->form->getInput('id'); ?>
                </div>                     

                <div class="control-group">
                    <div class="control-label">
                    <?php echo $this->form->getLabel('cat_title'); ?>
                    <div class="controls">
                    <?php echo $this->form->getInput('cat_title'); ?>
                </div>
                
                <div class="control-group">
                    <div class="control-label">
                    <?php echo $this->form->getLabel('title'); ?>
                    <div class="controls">
                    <?php echo $this->form->getInput('title'); ?>
                </div>

                <div class="control-group">
                    <div class="control-label">
                    <?php echo $this->form->getLabel('url_download'); ?>
                    <div class="controls">
                    <?php echo $this->form->getInput('url_download'); ?>
                </div>                    

                <div class="control-group">
                    <div class="control-label">
                    <?php echo $this->form->getLabel('reason'); ?>
                    <div class="controls">
                    <?php echo $this->form->getInput('reason'); ?>
                </div>

                <div class="control-group">
                    <div class="control-label">
                    <?php echo $this->form->getLabel('text'); ?>
                    <div class="controls">
                    <?php echo $this->form->getInput('text'); ?>
                </div>
                
                <?php if ($this->user_rules->view_captcha && $cms_version_5) { ?>
                    <?php echo $this->form->renderField('captcha'); ?>
                <?php } ?>                
                
                <div class="mb-2">
                    <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('report.send')">
                        <span class="icon-check" aria-hidden="true"></span>
                        <?php echo Text::_('COM_JDOWNLOADS_SEND'); ?>
                    </button>
                    <button type="button" class="btn btn-danger" onclick="Joomla.submitbutton('report.cancel')">
                        <span class="icon-times" aria-hidden="true"></span>
                        <?php echo Text::_('COM_JDOWNLOADS_CANCEL'); ?>
                    </button> 
                </div>
                
                <?php } ?>
        
        </fieldset>
        
        <input type="hidden" name="task" value="report" />
        <input type="hidden" name="return" value="<?php echo $this->return_page;?>" />         
        
        <?php echo HTMLHelper::_('form.token'); ?>

    <div class="clr"></div>
    </form>
    
    <?php } ?>

    </div>