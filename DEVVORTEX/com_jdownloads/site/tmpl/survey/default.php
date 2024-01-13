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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
    
    HTMLHelper::_('behavior.keepalive');
    HTMLHelper::_('behavior.formvalidator');

    $jinput = Factory::getApplication()->input; 
    
    $captcha_valid  = false;
    $captcha_invalid_msg = '';
    
    // Which fields must be viewed
    $survey_fields = (array)json_decode($this->user_rules->form_fieldset);

    if ($this->user_rules->view_captcha){
        // Already done?
        $captcha  = (int)JDHelper::getSessionDecoded('jd_captcha_run');
        if ($captcha == 2){
            // Succesful
            $captcha_valid = true;
        } else {
        
            // Get the activated captcha plugin from global config
            $active_captcha = $app->getCfg('captcha');
            
            // Get captcha plugin
            PluginHelper::importPlugin('captcha');
            $plugin = PluginHelper::getPlugin('captcha', $active_captcha);

            // Get plugin param
            if ($plugin){
                $pluginParams = new Registry($plugin->params);
                $public_key = $pluginParams->get('public_key');        
                
                $dummy = $jinput->getString('g-recaptcha-response');
                if (!$dummy) $dummy = $jinput->getString('recaptcha_response_field');        
                
                // check now whether user has used the captcha already
                if (isset($dummy)){
                        $captcha_res = Factory::getApplication()->triggerEvent('onCheckAnswer', $dummy);
                        if (!$captcha_res[0]){
                            // init again for next try
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
                    // init for first try
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
            } else {
                // ReCaptcha plugin not activated - so we can not use it.
                $captcha_valid = true;
            } 
        }   
    } else {
        // we need this switch to handle the data output 
        $captcha_valid = true;
    }    

    // required for captcha
    

    $form_uri = Uri::getInstance(); 
    $form_uri = $form_uri->toString();
    $form_uri = $this->escape($form_uri);    
    
    // Create shortcuts to some parameters.
    $params     = $this->state->params;
    
    $user       = Factory::getUser();
    $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;        

?>
    <script type="text/javascript">
        Joomla.submitbutton = function(task) {
            if (task == 'survey.skip' || document.formvalidator.isValid(document.getElementById('adminForm'))) {
                Joomla.submitform(task);
            } else if (task == 'survey.abort'){
                   window.history.go(-1);
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

<form action="<?php echo $form_uri; ?>" name="adminForm" method="post" id="adminForm" class="form-validate" accept-charset="utf-8">

        <legend>
            <?php echo Text::_('COM_JDOWNLOADS_SURVEY_TEXT'); ?>
        </legend>

        <fieldset>
            <?php echo '<div class="alert alert-info">'.Text::_('COM_JDOWNLOADS_SURVEY_INFO').'</div>'; ?>
            
            <?php
            if ($this->user_rules->inquiry_hint != ''){
                echo JDHelper::getOnlyLanguageSubstring($this->user_rules->inquiry_hint);
            } ?> 

            <?php 
                // view it only when captcha_valid var is false  
                if (!$captcha_valid){
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
                
                    <?php
                    if (in_array(1, $survey_fields)){ ?>
                        <div class="control-group">
                            <div class="control-label">
                            <?php echo $this->form->getLabel('name'); ?>
                            <div class="controls">
                            <?php echo $this->form->getInput('name'); ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>

                    <?php
                    if (in_array(2, $survey_fields)){ ?>
                        <div class="control-group">
                            <div class="control-label">
                            <?php echo $this->form->getLabel('company'); ?>
                            <div class="controls">
                            <?php echo $this->form->getInput('company'); ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>

                    <?php
                    if (in_array(3, $survey_fields)){ ?>
                        <div class="control-group">
                            <div class="control-label">
                            <?php echo $this->form->getLabel('country'); ?>
                            <div class="controls">
                            <?php echo $this->form->getInput('country'); ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>

                    <?php
                    if (in_array(4, $survey_fields)){ ?>
                        <div class="control-group">
                            <div class="control-label">
                            <?php echo $this->form->getLabel('address'); ?>
                            <div class="controls">    
                            <?php echo $this->form->getInput('address'); ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                    
                    <?php
                    if (in_array(5, $survey_fields)){ ?>
                        <div class="control-group">
                            <div class="control-label">
                            <?php echo $this->form->getLabel('email'); ?>
                            <div class="controls">
                            <?php echo $this->form->getInput('email'); ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                    
                    <div class="control-group">
                        <div class="control-label">
                        <?php echo $this->form->getLabel('catid'); ?>
                        <div class="controls">
                        <?php echo $this->form->getInput('catid'); ?>
                        </div>
                    </div>

                    <div class="control-group">
                        <div class="control-label">
                        <?php echo $this->form->getLabel('id'); ?>
                        <div class="controls">
                        <?php echo $this->form->getInput('id'); ?>
                        </div>
                    </div>                     

                    <div class="control-group">
                        <div class="control-label">
                        <?php echo $this->form->getLabel('cat_title'); ?>
                        <div class="controls">
                        <?php echo $this->form->getInput('cat_title'); ?>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <div class="control-label">
                        <?php echo $this->form->getLabel('title'); ?>
                        <div class="controls">
                        <?php echo $this->form->getInput('title'); ?>
                        </div>
                    </div>

                    <div class="control-group">
                        <div class="control-label">
                        <?php echo $this->form->getLabel('url_download'); ?>
                        <div class="controls">
                        <?php echo $this->form->getInput('url_download'); ?>
                        </div>
                    </div>
                    
                <?php
                    if ($this->user_rules->view_gdpr_dsgvo_option){ ?>
                        <div class="alert alert-danger">
                            <?php echo $this->form->getInput('gdpr_agreement'); ?>
                            <?php echo $this->form->getLabel('gdpr_agreement'); ?>
                            <div>
                            <?php echo Text::_('COM_JDOWNLOADS_SURVEY_GDPR_HINT'); ?>
                            </div>
                        </div>
                <?php } ?>                    

                <div class="formelm-buttons" style="padding-bottom: 15px;">
                    <button type="button" class="btn btn-success" onclick="Joomla.submitbutton('survey.send')">
                        <?php echo Text::_('COM_JDOWNLOADS_SEND'); ?>
                    </button>
                    <?php if (!$this->user_rules->must_form_fill_out){?>
                        <button type="button" class="btn btn-danger" onclick="Joomla.submitbutton('survey.skip')">
                            <?php echo Text::_('COM_JDOWNLOADS_SURVEY_SKIP'); ?>
                    </button> 
                    <?php } else { ?>
                        <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('survey.abort')">
                            <?php echo Text::_('COM_JDOWNLOADS_SURVEY_ABORT'); ?>
                        </button> 
                    <?php } ?>
                </div>
                
        <?php } ?>
        
        </fieldset>
        
        <input type="hidden" name="task" value="survey" />
        <input type="hidden" name="return" value="<?php echo $this->return_page;?>" />         
        
        <?php echo HtmlHelper::_('form.token'); ?>

    <div class="clr"></div>
    </form>
    
    <?php } 

    // back button
    /* if ($params->get('view_back_button')){
        echo '<a href="javascript:history.go(-1)">'.Text::_('COM_JDOWNLOADS_FRONTEND_BACK_BUTTON').'</a>'; 
    } */ ?>

    </div>