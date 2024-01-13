<?php
/**
 * @package jDownloads
 * @version 3.7  
 * @copyright (C) 2007 - 2017 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
 
defined('_JEXEC') or die('Restricted access');

setlocale(LC_ALL, 'C.UTF-8', 'C');

use Joomla\String\StringHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Version;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

    // For Tooltip
    HTMLHelper::_('bootstrap.tooltip');
    
    HTMLHelper::_('behavior.keepalive');
    HTMLHelper::_('behavior.formvalidator');
    
    $db         = Factory::getDBO(); 
    $document   = Factory::getDocument();
    $jinput     = Factory::getApplication()->input;
    $app        = Factory::getApplication();    
    
    $user       = Factory::getUser();
    $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;    
    
	// Check if it is still a Joomla 4 version.
    // Required for reCaptcha processing, as we no longer have reCaptcha v2 available as of Joomla 5.
	$cms_version = new Version();
	$cms_version_5 = $cms_version->isCompatible(5);
    
    // Get jD user limits and settings
    $jd_user_settings = $this->user_rules;
    
    // Create shortcuts to some parameters.
    $params               = $this->params;
    $files                = $this->items;
    $user_rules           = $this->user_rules;
    $is_mirror            = $this->state->get('download.mirror.id');
    $fileid               = $this->state->get('download.id');
    $catid                = $this->state->get('download.catid');
    $sum_selected_files   = $this->state->get('sum_selected_files');
    $sum_selected_volume  = $this->state->get('sum_selected_volume');
    $sum_files_prices     = $this->state->get('sum_files_prices');
    $must_confirm_license = $this->state->get('must_confirm_license');
    $directlink           = $this->state->get('directlink_used');
    $marked_files_id      = $this->state->get('download.marked_files.id');
        
    $html               = '';
    $footer_text        = '';
    $layout             = '';
    $license_text       = '';
    $license_url        = '';
    $countdown          = '';
    $zip_file_info      = '';
    $sum_aup_points     = $sum_files_prices;
    $aup_valid          = true;
    $user_random_id     = 0;
    $has_licenses       = false;
    $must_confirm       = false;
    $extern_site        = false;
    $open_in_blank_page = false;
    $directlink         = false;
    $total_consumed     = false;
    $may_download       = false;
    $zip_files_array    = array();
    
    $password_used          = false;
    $password_valid         = false;
    $password_invalid_msg   = '';
    
    $captcha_valid          = false;
    $captcha_invalid_msg    = '';
    
    $licenses_exist         = false;
    
    // Get the needed layout
    $layout = $this->layout;
    if ($layout){
        // Unused language placeholders must at first get removed from layout
        $layout_text = JDHelper::removeUnusedLanguageSubstring($layout->template_text);
        $header      = JDHelper::removeUnusedLanguageSubstring($layout->template_header_text);
        $subheader   = JDHelper::removeUnusedLanguageSubstring($layout->template_subheader_text);
        $footer      = JDHelper::removeUnusedLanguageSubstring($layout->template_footer_text);
    } else {
        // We have not a valid layout data
        echo '<big>No valid layout found!</big>';
    }     
    
    // Check at first whether we have a single download and it is used the files password option
    // If so, then can we not use Captcha
    if ($this->state->get('download.id') && $this->items[0]->password_md5 != ''){
        $password_used = true;
        JDHelper::writeSessionEncoded('1', 'jd_password_run');
        $password_input = $jinput->getString('password_input', '');
        if ($password_input != ''){
            if (hash('sha256', $password_input) == $this->items[0]->password_md5){
                $password_valid = true;
                JDHelper::writeSessionEncoded('2', 'jd_password_run');
            } else {
                $password_invalid_msg = Text::_('COM_JDOWNLOADS_PASSWORD_INVALID');
            }    
        }
        // We need this switch to handle the data output 
        $captcha_valid = true;
        JDHelper::writeSessionEncoded('0', 'jd_captcha_run');
    } else {
        // Captcha check
        if ($this->user_rules->view_captcha){
            
            // Get the activated captcha plugin from global config
            $active_captcha = $app->getCfg('captcha');
            
            // Get captcha plugin
            PluginHelper::importPlugin('captcha');
            $plugin = PluginHelper::getPlugin('captcha', $active_captcha);
            
            if ($plugin){
                // Get plugin params
                $pluginParams = new Registry($plugin->params);
                $public_key = $pluginParams->get('public_key', '');        

                $dummy = $jinput->getString('g-recaptcha-response');
	               
                // We now check whether the user has already used the captcha or had it displayed.
	            if (isset($dummy)){
	                if (!$cms_version_5){                            
                        // Handling only for reCaptcha v2 and Joomla 4.
                        $captcha_res = $app->triggerEvent('onCheckAnswer', array($dummy));
                    } else {
                        // Handling only for reCaptcha v3 and Joomla 5.
                        $captcha_res = $app->triggerEvent('onInit', array('dynamic_recaptcha_invisible_1'));
	                }
                    
                    if (!$captcha_res[0]){
	                    // First time or not successful so we will need will initiate it.
                        JDHelper::writeSessionEncoded('1', 'jd_captcha_run');
	                
                        // Init again for next try.                        
	                    if ($active_captcha == 'recaptcha'){
	                        // Handling only for reCaptcha v2 and Joomla 4.
                            $app->triggerEvent('onInit', array('dynamic_recaptcha_1'));
	                    } else {
	                        // Handling only for reCaptcha v3 and Joomla 5.
                            $app->triggerEvent('onInit', array('dynamic_recaptcha_invisible_1'));
	                    }
                        
	                    $captcha_invalid_msg = Text::_('COM_JDOWNLOADS_FIELD_CAPTCHA_INCORRECT_HINT');
	                } else {
	                    // Successful, so we save this.
                        JDHelper::writeSessionEncoded('2', 'jd_captcha_run');
	                    $captcha_valid = true;
	                }
	            
                } else {
	            
                    // Init for first try
	                JDHelper::writeSessionEncoded('1', 'jd_captcha_run');
	                
	                if ($active_captcha == 'recaptcha'){
	                    $exist_event = $app->triggerEvent('onInit', array('dynamic_recaptcha_1'));
	                } else {
	                    $exist_event = $app->triggerEvent('onInit', array('dynamic_recaptcha_invisible_1'));
	                }
	                
	                // When plugin event not exist, we must do the work without it. But give NOT a public info about this problem.
	                if (!$exist_event){
	                    $captcha_valid = true;
	                    JDHelper::writeSessionEncoded('2', 'jd_captcha_run');
	                }
                }
            } else {
                // ReCaptcha plugin not activated - so we can not use it.
                $captcha_valid = true;
            }
        } else {
            // We need this switch to handle the data output 
            $captcha_valid = true;
        }
        // Not used - so must set it to true
        $password_valid = true;
    }    
   
    // Required for captcha
    $form_uri = Uri::getInstance()->toString();
    //$form_uri = $form_uri->toString();
    $form_uri = $this->escape($form_uri);
    
    // Get CSS button settings
    $menu_color             = $params->get('css_menu_button_color');
    $menu_size              = $params->get('css_menu_button_size');
    $status_color_hot       = $params->get('css_button_color_hot');
    $status_color_new       = $params->get('css_button_color_new');
    $status_color_updated   = $params->get('css_button_color_updated');
    $download_color         = $params->get('css_button_color_download');
    $download_size          = $params->get('css_button_size_download');
    $download_size_mirror   = $params->get('css_button_size_download_mirror');        
    $download_color_mirror1 = $params->get('css_button_color_mirror1');        
    $download_color_mirror2 = $params->get('css_button_color_mirror2');
    $download_size_listings = $params->get('css_button_size_download_small');
    
    if ($params->get('css_buttons_with_font_symbols')){
        $span_home_symbol   = '<span class="icon-home-2 jd-menu-icon"> </span>';
        $span_search_symbol = '<span class="icon-search jd-menu-icon"> </span>';
        $span_upper_symbol  = '<span class="icon-arrow-up-2 jd-menu-icon"> </span>';
        $span_upload_symbol = '<span class="icon-new jd-menu-icon"> </span>';
    } else {
        $span_home_symbol   = '';
        $span_search_symbol = '';
        $span_upper_symbol  = '';
        $span_upload_symbol = '';
    }         
    
    // Build random value for zip filename
    if (count($files) > 1) {
        $user_random_id = JDHelper::buildRandomNumber();
    }        
    
    // We need the filed id when not used checkboxes
    if (!$marked_files_id){
        $marked_files_id = array($fileid);
    }
    $marked_files_id_string = implode(',', $marked_files_id);
    
    // We must compute up to this point, what this user has downloaded before and compare it then later with the defined user limitations 
    // Important: Please note, that we can check it only for registered users. By visitors it is not really useful, then we have here only a changeable IP.  

    $total_consumed = JDHelper::getUserLimits($user_rules, $marked_files_id);
    
    // When $total_consumed['limits_info'] has a value, we must check whether this user may download the selected files
    // If so, then the result is: TRUE - otherwise: the limitations message
    // Has $total_consumed['limits_info'] not any value, it exists not any limitations for this user  

    if ($total_consumed['limits_info']){ 
        $may_download = JDHelper::checkUserDownloadLimits($user_rules, $total_consumed, $sum_selected_files, $sum_selected_volume, $marked_files_id);
    } else {
        $may_download = true;
    }
    
    // Check whether user has enough points from alphauserpoints (when used and installed)                
    if ($may_download === true && $params->get('use_alphauserpoints')){
        $aup_result = JDHelper::checkUserPoints($sum_aup_points, $marked_files_id);
        if ($aup_result['may_download'] === true){
            $may_download = true;
        } else {
            $may_download = $aup_result['points_info']; 
        }    
    }    
    
    // Write data in session
    if ($may_download === true){
        if ($user_random_id){    
            JDHelper::writeSessionEncoded($user_random_id, 'jd_random_id');
            JDHelper::writeSessionEncoded($marked_files_id_string, 'jd_list');
            JDHelper::writeSessionClear('jd_fileid');
        } else {
            // Single file download
            if ($fileid){
                JDHelper::writeSessionEncoded($fileid, 'jd_fileid');    
            } else {
                JDHelper::writeSessionEncoded($marked_files_id[0], 'jd_fileid');    
            }
            JDHelper::writeSessionClear('jd_random_id');
            JDHelper::writeSessionClear('jd_list');                        
        }
        JDHelper::writeSessionEncoded($catid, 'jd_catid');
    }                    
    
    // Get current category menu ID when exist and all needed menu IDs for the header links
    $menuItemids = JDHelper::getMenuItemids($catid);
    
    // Get all other menu category IDs so we can use it when we need it
    $cat_link_itemids = JDHelper::getAllJDCategoryMenuIDs();
    
    // "Home" menu link itemid
    $root_itemid =  $menuItemids['root'];

    $Itemid = JDHelper::getSingleCategoryMenuID($cat_link_itemids, $catid, $root_itemid);
    
    $html = '<div class="jd-item-page'.$this->pageclass_sfx.'">';
        
    if ($this->params->get('show_page_heading')) {
        $html .= '<h1>'.$this->escape($this->params->get('page_heading')).'</h1>';
    }            
    
    // ==========================================
    // HEADER SECTION
    // ==========================================

    if ($header != ''){
        
        // Component title
        $header = str_replace('{component_title}', $document->getTitle('title'), $header);
        
        // Replace both Google adsense placeholder with script
        $header = JDHelper::insertGoogleAdsenseCode($header); 
        
        // Components description
        if ($params->get('downloads_titletext') != '') {
            $header_text = stripslashes(JDHelper::getOnlyLanguageSubstring($params->get('downloads_titletext')));
            // Replace both Google adsense placeholder with script
            $header_text = JDHelper::insertGoogleAdsenseCode($header_text);
            $header .= $header_text;
        }
        
        // Check $Itemid exist
        if (!isset($menuItemids['search'])) $menuItemids['search'] = $menuItemids['root'];
        if (!isset($menuItemids['upload'])) $menuItemids['upload'] = $menuItemids['root'];
        
        // Build home link        
        $home_link = '<a href="'.Route::_('index.php?option=com_jdownloads&amp;Itemid='.$menuItemids['root']).'" title="'.Text::_('COM_JDOWNLOADS_HOME_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_home_symbol.Text::_('COM_JDOWNLOADS_HOME_LINKTEXT').'</span>'.'</a>';
        
        // Build search link
        $search_link = '<a href="'.Route::_('index.php?option=com_jdownloads&amp;view=search&amp;Itemid='.$menuItemids['search']).'" title="'.Text::_('COM_JDOWNLOADS_SEARCH_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_search_symbol.Text::_('COM_JDOWNLOADS_SEARCH_LINKTEXT').'</span>'.'</a>';        

        // Build frontend upload link
        $upload_link = '<a href="'.Route::_('index.php?option=com_jdownloads&amp;view=form&amp;layout=edit&amp;Itemid='.$menuItemids['upload']).'"  title="'.Text::_('COM_JDOWNLOADS_UPLOAD_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_upload_symbol.Text::_('COM_JDOWNLOADS_UPLOAD_LINKTEXT').'</span>'.'</a>';
        
        $header = str_replace('{home_link}', $home_link, $header);
        $header = str_replace('{search_link}', $search_link, $header);

        if ($jd_user_settings->uploads_view_upload_icon){
            if ($this->view_upload_button){
                $header = str_replace('{upload_link}', $upload_link, $header);
            } else {
                $header = str_replace('{upload_link}', '', $header);
            }            
        } else {
            $header = str_replace('{upload_link}', '', $header);
        }    

        if ($menuItemids['upper'] > 1){   // 1 is 'root'
            // Exists a single category menu link for the category a level up? 
            $level_up_cat_itemid = JDHelper::getSingleCategoryMenuID($cat_link_itemids, $menuItemids['upper'], $root_itemid);
            $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=category&amp;catid='.$menuItemids['upper'].'&amp;Itemid='.$level_up_cat_itemid);
        } else {
            $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=categories&amp;Itemid='.$menuItemids['root']);
        }
        $header = str_replace('{upper_link}', '<a href="'.$upper_link.'"  title="'.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_upper_symbol.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT').'</span>'.'</a>', $header);    
        
        // Create category listbox and viewed it when it is activated in configuration
        if ($params->get('show_header_catlist')){
            
            // Get current selected cat id from listbox
            $catlistid = $jinput->get('catid', '0', 'integer');
            
            $orderby_pri = '';
            $data = JDHelper::buildCategorySelectBox($catlistid, $cat_link_itemids, $root_itemid, $params->get('view_empty_categories', 1), $orderby_pri );
            
            // Build special selectable URLs for category listbox
            $root_url       = Route::_('index.php?option=com_jdownloads&Itemid='.$root_itemid);
            $allfiles_url   = str_replace('Itemid[0]', 'Itemid', Route::_('index.php?option=com_jdownloads&view=downloads&Itemid='.$root_itemid));
            $topfiles_url   = str_replace('Itemid[0]', 'Itemid', Route::_('index.php?option=com_jdownloads&view=downloads&type=top&Itemid='.$root_itemid));
            $newfiles_url   = str_replace('Itemid[0]', 'Itemid', Route::_('index.php?option=com_jdownloads&view=downloads&type=new&Itemid='.$root_itemid));
            
            $listbox = HTMLHelper::_('select.genericlist', $data['options'], 'cat_list', 'class="form-select" title="'.Text::_('COM_JDOWNLOADS_SELECT_A_VIEW').'" onchange="gocat(\''.$root_url.'\', \''.$allfiles_url.'\', \''.$topfiles_url.'\',  \''.$newfiles_url.'\'  ,\''.$data['url'].'\')"', 'value', 'text', $data['selected'] ); 
            
            $header = str_replace('{category_listbox}', '<form name="go_cat" id="go_cat" method="post">'.$listbox.'</form>', $header);
        } else {                                                                        
            $header = str_replace('{category_listbox}', '', $header);         
        }
        $html .= $header;  
    }

    // ==========================================
    // SUB HEADER SECTION
    // ==========================================

    if ($subheader != ''){

        $subheader = str_replace('{summary_title}', Text::_('COM_JDOWNLOADS_FRONTEND_HEADER_SUMMARY_TITLE'), $subheader);

        // Remove this placeholder when it is used not for files layout
        $subheader = str_replace('{summary_title}', '', $subheader); 
        
        // Replace both Google adsense placeholder with script
        $subheader = JDHelper::insertGoogleAdsenseCode($subheader);                  
        $html .= $subheader;            
    }
    
    // ==========================================
    // BODY SECTION - VIEW THE DOWNLOADS DATA
    // ==========================================
    
    $html_files = '';
    $id_text = '';

    if ($layout_text != ''){
    
        $event = $this->event->beforeDisplayContent;        
        
        $html_sum = $event.$layout_text;

        // summary pic
        $sumpic = '<img src="'.URI::base().'components/com_jdownloads/assets/images/summary.png" width="'.$params->get('cat_pic_size').'" height="'.$params->get('cat_pic_size_height').'" style="border:0px;" alt="summary" /> ';
        $html_sum = str_replace('{summary_pic}', $sumpic, $html_sum);    
        
        // info text
        $html_sum = str_replace('{title_text}', Text::_('COM_JDOWNLOADS_FE_SUMMARY_PAGE_TITLE_TEXT'), $html_sum);
        
        // ==============================================================================
        // User may not download this files - limits reached. So we view only the message
        // ==============================================================================
        if ($may_download !== true){
           $html_sum = str_replace('{download_link}', $may_download, $html_sum);
           
           // replace both Google adsense placeholder with script
           $html_sum = JDHelper::insertGoogleAdsenseCode($html_sum);                  
           
            // remove all other (not used) place holders
            $html_sum = str_replace('{info_zip_file_size}', '', $html_sum);
            $html_sum = str_replace('{license_text}', '', $html_sum);
            $html_sum = str_replace('{license_title}', '', $html_sum);
            $html_sum = str_replace('{license_checkbox}', '', $html_sum);
            $html_sum = str_replace('{download_liste}', '', $html_sum);
            $html_sum = str_replace('{external_download_info}', '', $html_sum);
            $html_sum = str_replace('{aup_points_info}', '', $html_sum);
            $html_sum = str_replace('{captcha}', '', $html_sum);
            $html_sum = str_replace('{password}', '', $html_sum);
           
        } else {
            // ============================
            // user may download this files            
            // ============================
            $files_list = '<div class="jd_summary_list">';

            // when exists - no checkbox was used  
            if ($fileid){
                $directlink = true;
                $id_text = $fileid;        
                $filename = Route::_('index.php?option=com_jdownloads&amp;task=download.send&amp;id='.$fileid.'&amp;catid='.$catid.'&amp;m='.$is_mirror.'&amp;Itemid='.$Itemid);
                if ($files[0]->license && $files[0]->license_agree) $must_confirm = true;
                $download_link = $filename;
                $file_title = ' - '.$files[0]->title;       
            }
            
            // move in text for view the files list
            $number = 0;
            if (!$id_text){
                $number = count($marked_files_id);
                if ( $number > 1 ){
                   $id_text = implode(',', $marked_files_id);
                } else {
                   $id_text = $marked_files_id[0];
                }
            }                 
            
            // Add password protection when used but then is not possible to use the captcha
            if ($password_used){
                if ($password_valid === false){
                    $password = '<div class="container pt-5" style="padding:5px; text-align:center;">';    
                    $password .= '<div id="jd_container" class="alert alert-secondary">';
                        if ($password_invalid_msg == ''){
                            $password .= Text::_('COM_JDOWNLOADS_PASSWORD_DESC');
                        } else {
                            $password .= $password_invalid_msg;
                        }  
                        $password .= '<form action="'.$form_uri.'" method="post" id="summary" class="form-validate" enctype="multipart/form-data" accept-charset="utf-8">';
                        $password .= '<br /><input type="text" name="password_input" size="20" value="">';
                        $password .= '<input type="hidden" name="f_file_id" value="'.$fileid.'">';
                        $password .= '<input type="hidden" name="f_cat_id" value="'.$catid.'">';
                        $password .= '<input type="hidden" name="f_marked_files_id" value="'.implode(',',$marked_files_id).'">';
                        $password .= '<input type="submit" name="submit" id="jd_password" class="button" value="'.Text::_('COM_JDOWNLOADS_FORM_BUTTON_TEXT').'" />';
                        $password .= HTMLHelper::_('form.token').'</form></div></div>';
                        $html_sum = str_replace('{password}', $password, $html_sum);
                    
                } else {
                    $html_sum = str_replace('{password}', '', $html_sum);
                }
                $html_sum = str_replace('{captcha}', '', $html_sum);
            } else {
                // For a Joomla 4 installation we will use a reCaptcha v2.
                if ($this->user_rules->view_captcha && !$cms_version_5){
                    $captcha = "";
                    if (!$captcha_valid){
                        if ($active_captcha == 'recaptcha'){
                            $captchadiv = '<div class="jd_recaptcha">';
                            $captchadiv .= Text::_('COM_JDOWNLOADS_FIELD_CAPTCHA_HINT_VERSION_2');
                            $captchadiv .= '<div class="g-recaptcha" data-sitekey="'.$public_key.'"></div>';
                                                 
                            $captcha .= '<form action="'.$form_uri.'" method="post" id="summary" class="form-validate" enctype="multipart/form-data" accept-charset="utf-8">';
                            $captcha .= $captchadiv; 
                            $captcha .= '<div id="dynamic_recaptcha_1"></div>';
                            if ($captcha_invalid_msg != ''){
                                $captcha .= $captcha_invalid_msg;
                            }                         
                        } else {
                            $captchadiv = '<div class="jd_recaptcha">';
                            $captchadiv .= Text::_('COM_JDOWNLOADS_FIELD_CAPTCHA_HINT_VERSION_2');
                            $captchadiv .= '<div class="g-recaptcha" data-sitekey="'.$public_key.'"></div>';

                            $captcha .= '<form action="'.$form_uri.'" method="post" id="summary" class="form-validate" enctype="multipart/form-data" accept-charset="utf-8">';
                            $captcha .= $captchadiv; 
                            $captcha .= '<div id="dynamic_recaptcha_invisible_1"></div>';
                            if ($captcha_invalid_msg != ''){
                                $captcha .= $captcha_invalid_msg;
                            } 
                        }     
                        
                        $captcha .= '<input type="hidden" name="f_file_id" value="'.$fileid.'">';
                        $captcha .= '<input type="hidden" name="f_cat_id" value="'.$catid.'">';
                        $captcha .= '<input type="hidden" name="f_marked_files_id" value="'.implode(',',$marked_files_id).'">';
                        $captcha .= '<br /><input type="submit" name="submit" id="jd_captcha" class="button" value="'.Text::_('COM_JDOWNLOADS_FORM_BUTTON_TEXT').'" />';
                        $captcha .= HTMLHelper::_('form.token').'</form></div>';

                        $html_sum = str_replace('{captcha}', $captcha, $html_sum);
                    } else {
                        $html_sum = str_replace('{captcha}', '', $html_sum);
                    }   
                
                } else {
                    // For a Joomla 5 installation we need to use reCaptcha v3.
                    if (!$cms_version_5){
                        $html_sum = str_replace('{captcha}', '', $html_sum);
                    } elseif (!$captcha_valid) {
                        $captchadiv = '<div class="jd_recaptcha">';
                        $captcha = '<form action="'.$form_uri.'" method="post" id="summary" class="form-validate" enctype="multipart/form-data" accept-charset="utf-8">';
                        $captcha .= $this->form->renderField('captcha');
                        $captcha .= $captchadiv; 
                        $captcha .= '<div id="dynamic_recaptcha_invisible_1" class="required g-recaptcha" data-sitekey="'.$public_key.'" data-badge="bottomright" data-size="invisible" data-tabindex="0" data-callback="" data-expired-callback="" data-error-callback=""></div>';
                        
                        if ($captcha_invalid_msg != ''){
                            $captcha .= $captcha_invalid_msg;
                        } 
                        
                        $captcha .= '<input type="hidden" name="f_file_id" value="'.$fileid.'">';
                        $captcha .= '<input type="hidden" name="f_cat_id" value="'.$catid.'">';
                        $captcha .= '<input type="hidden" name="f_marked_files_id" value="'.implode(',',$marked_files_id).'">';
                        $captcha .= '<input type="submit" name="submit" id="jd_captcha" class="button" value="'.Text::_('COM_JDOWNLOADS_FORM_BUTTON_TEXT').'" />';
                        $captcha .= HTMLHelper::_('form.token').'</form></div>';

                        $html_sum = str_replace('{captcha}', $captcha, $html_sum);
                        
                    } else {
                        $html_sum = str_replace('{captcha}', '', $html_sum);
                    }
                }
                $html_sum = str_replace('{password}', '', $html_sum);   
            }
            
            $files_list .= '
                <div class="divTable jd_div_table">
                    <div class="divTableHeading">
                        <div class="divTableRow">
                            <div class="divTableHead">'.Text::_('COM_JDOWNLOADS_TITLE').'</div>
                            <div class="divTableHead">'.Text::_('COM_JDOWNLOADS_FE_DETAILS_FILE_NAME_TITLE').'</div>
                            <div class="divTableHead">'.Text::_('COM_JDOWNLOADS_FE_DETAILS_LICENSE_TITLE').'</div>
                            <div class="divTableHead">'.Text::_('COM_JDOWNLOADS_FE_DETAILS_FILESIZE_TITLE').'</div>
                        </div>
                    </div>
                    <div class="divTableBody">';
            
            // build the information list about the selected files
            for ($i=0; $i<count($files); $i++){
               
                if (!$files[$i]->url_download && $files[$i]->other_file_id > 0 && $files[$i]->other_file_name != ''){
                    // Special situation when a file from other Download was assigned
                    $filename_text = JDHelper::getShorterFilename($files[$i]->other_file_name);
                    $filesize      = $files[$i]->other_file_size;
                } else {
                    $filename_text = JDHelper::getShorterFilename($files[$i]->url_download);
                    $filesize      = $files[$i]->size;
                }
                
               // Get license name
               if ($files[$i]->license > 0){  
                   $has_licenses = true;
                   if ($files[$i]->license_agree){
                       $must_confirm = true;
                       $license_text = stripslashes($files[$i]->license_text);
                       $license_url  = $files[$i]->license_url;
                   } 
                   
                   if ($files[$i]->license_url){
                       // With link to license
                       $files_list .= '<div class="divTableRow">
                                            <div class="divTableCell">'.$this->escape($files[$i]->title.' '.$files[$i]->release).'</div>
                                            <div class="divTableCell">'.$filename_text.'</div>
                                            <div class="divTableCell"><a href="'.$files[$i]->license_url.'" target="_blank">'.$this->escape($files[$i]->license_title).'</a></div>
                                            <div class="divTableCell">'.$this->escape($filesize).'</div>
                                       </div>';
                   } else {
                       // Only with license title
                       $files_list .= '<div class="divTableRow">
                                            <div class="divTableCell">'.$this->escape($files[$i]->title.' '.$files[$i]->release).'</div>
                                            <div class="divTableCell">'.$filename_text.'</div>
                                            <div class="divTableCell">'.$this->escape($files[$i]->license_title).'</div>
                                            <div class="divTableCell">'.$this->escape($filesize).'</div>
                                       </div>';
                   }   
               } else {
                   // No license
                       $files_list .= '<div class="divTableRow">
                                            <div class="divTableCell">'.$this->escape($files[$i]->title.' '.$files[$i]->release).'</div>
                                            <div class="divTableCell">'.$filename_text.'</div>
                                            <div class="divTableCell">'.Text::_('COM_JDOWNLOADS_NONE').'</div>
                                            <div class="divTableCell">'.$this->escape($filesize).'</div>
                                       </div>';
               }
            }
            
            $files_list .= '</div></div></div>';
                     
            $html_sum = str_replace('{download_liste}', $files_list, $html_sum);
            
            // set flag when link must opened in a new browser window 
            if (!$is_mirror && $i == 1 && $files[0]->extern_site){
                $extern_site = true;    
            }
            if ($is_mirror == 1 && $i == 1 && $files[0]->extern_site_mirror_1){
                $extern_site = true;    
            }
            if ($is_mirror == 2 && $i == 1 && $files[0]->extern_site_mirror_2){
                $extern_site = true;    
            }
            // get file extension  when only one file selected - set flag when link must opened in a new browser window 
            if (count($files) == 1 && $files[0]->url_download) {
                $view_types = array();
                $view_types = explode(',', $params->get('file_types_view'));
                $fileextension = strtolower(substr(strrchr($files[0]->url_download,"."),1));
                if (in_array($fileextension, $view_types)){
                    $open_in_blank_page = true;
                }
            }
            
            // when mass download with checkboxes
            if (!$directlink){ 
                // more as one file is selected - zip it in a temp file
                $download_dir = $params->get('files_uploaddir').'/';
                $zip_dir = $params->get('files_uploaddir').'/'.$params->get('tempzipfiles_folder_name').'/';
                
                if (count($files) > 1) {
                    
                    for ($i=0; $i<count($files); $i++) {
                        // get file url
                        $filename = $files[$i]->url_download;
                        if ($files[$i]->category_cat_dir_parent){
                            $cat_dir = $files[$i]->category_cat_dir_parent.'/'.$files[$i]->category_cat_dir.'/';
                        } else {
                            $cat_dir = $files[$i]->category_cat_dir.'/';
                        }     
                        if ($files[$i]->url_download != ''){
                            $zip_files_array[] = $download_dir.$cat_dir.$filename;
                        }
                        
                        // Check whether a license is selected for later confirmation
                        if ( (int)$files[$i]->license > 0){
                            $licenses_exist = true;
                        }
                    }

                    $zip_destination = $zip_dir.$params->get('zipfile_prefix').$user_random_id.'.zip';
                    
                    // create the temp zip file
                    $success  = JDHelper::createZipFile($zip_files_array, $zip_destination, true);
                    // if not success display error
                    if (!$success){
                        $html_sum = str_replace('{info_zip_file_size}', Text::_('COM_JDOWNLOADS_FRONTEND_SUMMARY_ZIP_ERROR'), $html_sum); 
                        $html_sum = str_replace('{download_link}', '', $html_sum); 
                    } else {
                        // success 
                        $zip_size = JDHelper::getFileSize($zip_destination);
                        $zip_file_info = Text::_('COM_JDOWNLOADS_FRONTEND_SUMMARY_ZIP_FILESIZE').': <strong>'.$zip_size.'</strong>';
                        
                        // delete before older temporary zip files
                        $del_ok = JDHelper::deleteOldZipFiles($zip_dir);
                        
                        $download_link = Route::_('index.php?option=com_jdownloads&amp;task=download.send&catid='.$catid.'&list='.$id_text.'&amp;user='.$user_random_id.'&amp;Itemid='.$Itemid); 
                    }
                } else {
                    // only one file selected
                    $download_link = Route::_('index.php?option=com_jdownloads&amp;task=download.send&id='.(int)$files[0]->id.'&catid='.$files[0]->catid.'&amp;Itemid='.$Itemid);
                    $file_title = ' - '.$files[0]->title;
                }
            }
            
            // info about temp zip file size (when used)
            $html_sum = str_replace('{info_zip_file_size}', $zip_file_info, $html_sum);        
                
            // replace both Google adsense placeholder with script
            $html_sum = JDHelper::insertGoogleAdsenseCode($html_sum);                  
            
            // build countdown timer
            if ($user_rules->countdown_timer_duration > 0 && $user_rules->countdown_timer_msg != ''){
                $countdown_msg = JDHelper::getOnlyLanguageSubstring($user_rules->countdown_timer_msg);
                $countdown = '<script type="text/javascript"> counter='.(int)$user_rules->countdown_timer_duration.'; active=setInterval("countdown2()",1000);
                               function countdown2(){
                                  if (counter >0){
                                      counter-=1;
                                      document.getElementById("countdown").innerHTML=sprintf(\''.$countdown_msg.'\',counter);
                                  } else {
                                      document.getElementById("countdown").innerHTML=\''.'{link}'.'\'
                                      window.clearInterval(active);
                                  }    
                                } </script>';
            }

            // View AlphaUserPoints result
            if ($params->get('use_alphauserpoints')){
                $html_sum = str_replace('{aup_points_info}', $aup_result['points_info'], $html_sum); 
            } else {
                $html_sum = str_replace('{aup_points_info}', '', $html_sum); 
            }    
            
            // We may view all other data only when this switches are true
            if ($captcha_valid && $password_valid){        
                 
                if (count($files) > 1) {
                    
                    // mass download
                    if ($must_confirm && $licenses_exist){
                        $html_sum = str_replace('{license_title}','', $html_sum);
                        $html_sum = str_replace('{license_text}', '', $html_sum);
                        $agree_form = '<form action="'.$download_link.'" method="post" name="jd_agreeForm" id="jd_agreeForm" >';
                        $agree_form .= '<input type="checkbox" name="license_agree" onclick="enableDownloadButton(this)" /> ';

						if (isset($files[$i]->license_url)){
							$agree_form .= Text::_('COM_JDOWNLOADS_FRONTEND_VIEW_AGREE_TEXT_URL').'<br /><br />';
						} else {
							$agree_form .= Text::_('COM_JDOWNLOADS_FRONTEND_VIEW_AGREE_TEXT').'<br /><br />'; 
						}
			
                        $agree_form .= '<input type="submit" name="submit" id="jd_license_submit" class="jdbutton '.$download_color.' '.$download_size.'" value="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" disabled="disabled" />';
                        $agree_form .= HTMLHelper::_('form.token')."</form>";
                    } else {
                        $html_sum = str_replace('{license_text}', '', $html_sum);
                        $html_sum = str_replace('{license_title}', '', $html_sum);
                        $html_sum = str_replace('{license_checkbox}', '', $html_sum);
                    }
                    
                    $link = '<div id="countdown" style="text-align:center"><a href="'.$download_link.'" target="_self" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_ZIP').'" class="jdbutton '.$download_color.' '.$download_size.'">'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'</a></div>'; 
                        
                    if ($countdown){
                       if ($must_confirm){
                           $countdown = str_replace('{link}', $agree_form, $countdown);
                           $html_sum = str_replace('{license_checkbox}', '<div id="countdown">'.$countdown.'</div>', $html_sum);
                           $html_sum = str_replace('{download_link}', '', $html_sum);
                       } else {
                             $countdown = str_replace('{link}', $link, $countdown);
                             $html_sum = str_replace('{download_link}', '<div id="countdown">'.$countdown.'</div>', $html_sum);
                       }    
                    } else {    
                       if ($must_confirm){
                           $html_sum = str_replace('{license_checkbox}', $agree_form, $html_sum);
                           $html_sum = str_replace('{download_link}', '', $html_sum);
                       } else {   
                           $html_sum = str_replace('{download_link}', $link, $html_sum);
                       }
                    }    
                    $html_sum = str_replace('{external_download_info}', '', $html_sum);
                } else {
                    // Single download          
                    if ($must_confirm){
                        if ($license_text != ''){
                            $html_sum = str_replace('{license_title}', Text::_('COM_JDOWNLOADS_FE_SUMMARY_LICENSE_VIEW_TITLE'), $html_sum);
                            $html_sum = str_replace('{license_text}', '<div id="jd_license_text">'.$license_text.'</div>', $html_sum);
                        } else {
                            $html_sum = str_replace('{license_title}', '', $html_sum);
                            $html_sum = str_replace('{license_text}', '', $html_sum);
                        }    
                        $agree_form = '<form action="'.$download_link.'" method="post" name="jd_agreeForm" id="jd_agreeForm" >';
						$agree_form .= '<input type="checkbox" name="license_agree" onclick="enableDownloadButton(this)" /> ';

						if ($license_url != '') {
							$agree_form .= Text::_('COM_JDOWNLOADS_FRONTEND_VIEW_AGREE_TEXT_URL').'<br /><br />';
						} else {
							$agree_form .= Text::_('COM_JDOWNLOADS_FRONTEND_VIEW_AGREE_TEXT').'<br /><br />'; 
						}

                        $agree_form .= '<input type="submit" name="submit" id="jd_license_submit" class="jdbutton '.$download_color.' '.$download_size.'" value="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" disabled="disabled" />';
                        $agree_form .= HTMLHelper::_('form.token')."</form>";
                    } else {
                        $html_sum = str_replace('{license_text}', '', $html_sum);
                        $html_sum = str_replace('{license_title}', '', $html_sum);
                        $html_sum = str_replace('{license_checkbox}', '', $html_sum);
                    }            
                     
                    if ($open_in_blank_page || $extern_site){
                        $targed = '_blank';
                        if ($extern_site){
                            $html_sum = str_replace('{external_download_info}', Text::_('COM_JDOWNLOADS_FRONTEND_DOWNLOAD_GO_TO_OTHER_SITE_INFO'), $html_sum);
                        } else {
                            $html_sum = str_replace('{external_download_info}', '', $html_sum);
                        }    
                    } else {
                        $targed = '_self';
                        $html_sum = str_replace('{external_download_info}', '', $html_sum);
                    }                    
                
                    $link = '<div id="countdown" style="text-align:center"><a href="'.$download_link.'" target="'.$targed.'" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_ZIP').'" class="jdbutton '.$download_color.' '.$download_size.'">'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'</a></div>'; 

                    if ($countdown){
                         if ($must_confirm){
                             $countdown = str_replace('{link}', $agree_form, $countdown);
                             $html_sum = str_replace('{license_checkbox}', '<div id="countdown">'.$countdown.'</div>', $html_sum);
                             $html_sum = str_replace('{download_link}', '', $html_sum);
                         } else {
                             $countdown = str_replace('{link}', $link, $countdown);
                             $html_sum = str_replace('{download_link}', '<div id="countdown">'.$countdown.'</div>', $html_sum); 
                         }
                    } else {    
                         if ($must_confirm){
                             $html_sum = str_replace('{license_checkbox}', $agree_form, $html_sum);
                             $html_sum = str_replace('{download_link}', '', $html_sum);
                         } else {   
                             $html_sum = str_replace('{download_link}', $link, $html_sum);
                         }    
                            
                    }
                }
           } else {
                // remove all other (not used) place holders
                $html_sum = str_replace('{info_zip_file_size}', '', $html_sum);
                $html_sum = str_replace('{license_text}', '', $html_sum);
                $html_sum = str_replace('{license_title}', '', $html_sum);
                $html_sum = str_replace('{license_checkbox}', '', $html_sum);
                $html_sum = str_replace('{download_liste}', '', $html_sum);
                $html_sum = str_replace('{external_download_info}', '', $html_sum);
                $html_sum = str_replace('{aup_points_info}', '', $html_sum);
                $html_sum = str_replace('{download_link}', '', $html_sum);
           }    
        }
        
        // view the plugins event data
        $html_sum .= $this->event->afterDisplayContent;        
        
        // view user his limits when activated
        if ($user_rules->view_user_his_limits && $user_rules->view_user_his_limits_msg != '' && $total_consumed['limits_info'] != '' && !$user->guest){
            $html_sum = str_replace('{user_limitations}', $total_consumed['limits_info'], $html_sum);
        } else {
            $html_sum = str_replace('{user_limitations}', '', $html_sum);
        }
        
        // Report download link
        if ($jd_user_settings->view_report_form && count($files) == 1){
            // Create also link for report link when only one file selected
            $report_link = '<a href="'.Route::_("index.php?option=com_jdownloads&amp;view=report&amp;id=".(int)$files[0]->id."&amp;catid=".$files[0]->catid."&amp;Itemid=".$root_itemid).'" rel="nofollow">'.Text::_('COM_JDOWNLOADS_FRONTEND_REPORT_FILE_LINK_TEXT').'</a>';                
            $html_sum = str_replace('{report_link}', $report_link, $html_sum);
        } else {
            $html_sum = str_replace('{report_link}', '', $html_sum);
        }         
    
        $html .= $html_sum;
        
    }    
    

    // ==========================================
    // FOOTER SECTION  
    // ==========================================

    // components footer text
    if ($params->get('downloads_footer_text') != '') {
        $footer_text = stripslashes(JDHelper::getOnlyLanguageSubstring($params->get('downloads_footer_text')));

        // replace both Google adsense placeholder with script
        $footer_text = JDHelper::insertGoogleAdsenseCode($footer_text);                  
        $html .= $footer_text;    
    }
    
    // back button
    if ($params->get('view_back_button')){
        $footer = str_replace('{back_link}', '<a href="javascript:history.go(-1)">'.Text::_('COM_JDOWNLOADS_FRONTEND_BACK_BUTTON').'</a>', $footer); 
    } else {
        $footer = str_replace('{back_link}', '', $footer);
    }    
    
    $footer .= JDHelper::checkCom();
   
    $html .= $footer; 
    
    $html .= '</div>';
    
    // support for global content plugins
    if ($params->get('activate_general_plugin_support')) {  
        $html = HTMLHelper::_('content.prepare', $html, '', 'com_jdownloads.summary');
    }
    
    // remove empty html tags
    if ($params->get('remove_empty_tags')){
        $html = JDHelper::removeEmptyTags($html);
    }    
    
    
    // ==========================================
    // VIEW THE BUILDED OUTPUT
    // ==========================================

    if ( !$params->get('offline') ) {
            echo $html;
    } else {
        // admins can view it always
        if ($is_admin) {
            echo $html;     
        } else {
            // build the offline message
            $html = '';
            // offline message
            if ($params->get('offline_text') != '') {
                $html .= JDHelper::getOnlyLanguageSubstring($params->get('offline_text'));
            }
            echo $html;    
        }
    }     

?>