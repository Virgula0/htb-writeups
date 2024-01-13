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

defined('_JEXEC') or die('Restricted access');

setlocale(LC_ALL, 'C.UTF-8', 'C');

use Joomla\String\StringHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Helper\UserGroupsHelper;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
    
    $app      = Factory::getApplication();
    $params   = $app->getParams();
    
    $db         = Factory::getDBO(); 
    $document   = Factory::getDocument();
    $jinput     = Factory::getApplication()->input;
    $user       = Factory::getUser();

    // Create shortcuts to some parameters.
    $item               = $this->item;
    $item_params        = $item->params;
    $canEdit            = $item->params->get('access-edit');
    $jd_user_settings   = $this->user_rules;
    
    $html           = '';
    $body           = '';
    $footer_text    = '';
    
    $date_format = JDHelper::getDateFormat();
    
    $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;
    
    $jdownloads_root_dir_name = basename($params->get('files_uploaddir'));
    
    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($params->get('selected_file_type_icon_set'));
    
    $file_path = '';
    if ($item->url_download){
        if ($item->catid > 1){
            if ($item->category_cat_dir_parent){
                $file_path = $params->get('files_uploaddir').'/'.$item->category_cat_dir_parent.'/'.$item->category_cat_dir.'/'.$item->url_download;
            } else {
                $file_path = $params->get('files_uploaddir').'/'.$item->category_cat_dir.'/'.$item->url_download;
            }
        } else {
           // Download is 'uncategorized'
           $file_path = $params->get('files_uploaddir').'/'.$params->get('uncategorised_files_folder_name').'/'.$item->url_download; 
        }    
    }
    
    if ($item->category_cat_dir_parent){
        $category_dir = $item->category_cat_dir_parent.'/'.$item->category_cat_dir;
    } elseif ($item->category_cat_dir) {
        $category_dir = $item->category_cat_dir;
    } else {
        // we have an uncategorised download so we must add the defined folder for this
        $category_dir = $params->get('uncategorised_files_folder_name');
    }   
    
    // 'download details' layout            
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
    
    $catid              = (int) $item->catid;
    $is_detail          = true;
    $is_showcats        = false;
    $is_one_cat         = false;
    $has_no_file        = false;
    $extern_media       = false;
    $no_file_info       = '';
    
    // has this download a file or an extern file or is used a file from other download?
    if (!$item->url_download && !$item->other_file_id && !$item->extern_file){
        // only a document without file
        $no_file_info = Text::_('COM_JDOWNLOADS_FRONTEND_ONLY_DOCUMENT_USER_INFO');
        $has_no_file = true;
    }

    // get current category menu ID when exist and all needed menu IDs for the header links
    $menuItemids = JDHelper::getMenuItemids($catid);
    
    // get all other menu category IDs so we can use it when we needs it
    $cat_link_itemids = JDHelper::getAllJDCategoryMenuIDs();
    
    // "Home" menu link itemid
    $root_itemid =  $menuItemids['root'];

    // make sure, that we have a valid menu itemid for the here viewed base category
    // if (!$this->category->menu_itemid) $this->category->menu_itemid = $root_itemid; 
    
    $menu_active =  $app->getMenu()->getActive(); 
    $file_itemid = $menu_active->id; 
    
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
    
    $html = '<div class="jd-item-page'.$this->pageclass_sfx.'">';
    
    if ($this->params->get('show_page_heading')) {
        $html .= '<h1>'.$this->escape($this->params->get('page_heading')).'</h1>';
    }
    
    if ($params->get('show_associations') && (!empty($item->associations))){
        $association_info = '<dd class="jd_associations">'.Text::_('COM_JDOWNLOADS_ASSOCIATION_HINT');
        
        foreach ($item->associations as $association){
            if ($params->get('flags', 1) && $association['language']->image){
                $flag = HTMLHelper::_('image', 'mod_languages/' . $association['language']->image . '.gif', $association['language']->title_native, array('title' => $association['language']->title_native), true);
                $line = '<a style="margin-left:5px;" href="'.Route::_($association['item']).'">'.$flag.'</a>';
            } else {
                $class = 'label label-association label-' . $association['language']->sef;
                $line  = '<a style="margin-left:5px;" class="'.$class.'" href="'.Route::_($association['item']).'">'.strtoupper($association['language']->sef).'</a>';
            }
            $association_info .= $line;
        }
        $association_info .= '</dd>';
        
    } else {
        $association_info = '';
    } 
    
     
    // ==========================================
    // HEADER SECTION
    // ==========================================

    if ($header != ''){
        
        //$menuItemids = JDHelper::getMenuItemids($catid);
        
        // component title - not more used. So we must replace the placeholder from layout with spaces!
        $header = str_replace('{component_title}', '', $header);
        
        // replace both Google adsense placeholder with script
        $header = JDHelper::insertGoogleAdsenseCode($header);
        
        // components description
        if ($params->get('downloads_titletext') != '') {
            $header_text = stripslashes(JDHelper::getOnlyLanguageSubstring($params->get('downloads_titletext')));
            
            // replace both Google adsense placeholder with script
            $header_text = JDHelper::insertGoogleAdsenseCode($header_text);
        
            $header .= $header_text;
        }
        
        // check $Itemid exist
        if (!isset($menuItemids['search'])) $menuItemids['search'] = $menuItemids['root'];
        if (!isset($menuItemids['upload'])) $menuItemids['upload'] = $menuItemids['root'];
        
        // build home link        
        $home_link = '<a href="'.Route::_('index.php?option=com_jdownloads&amp;Itemid='.$menuItemids['root']).'" title="'.Text::_('COM_JDOWNLOADS_HOME_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_home_symbol.Text::_('COM_JDOWNLOADS_HOME_LINKTEXT').'</span>'.'</a>';

        // build search link
        $search_link = '<a href="'.Route::_('index.php?option=com_jdownloads&amp;view=search&amp;Itemid='.$menuItemids['search']).'" title="'.Text::_('COM_JDOWNLOADS_SEARCH_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_search_symbol.Text::_('COM_JDOWNLOADS_SEARCH_LINKTEXT').'</span>'.'</a>';        

        // build frontend upload link
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

        // build upper link
        if ($is_detail){
            if ($catid == 1){
                $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=downloads&amp;type=uncategorised&amp;Itemid='.$menuItemids['root']);
            } elseif ($catid == -1) {
                $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=downloads&amp;Itemid='.$menuItemids['root']);
            } else {    
                $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=category&amp;catid='.$catid.'&amp;Itemid='.$menuItemids['root']);
            }    
            $header = str_replace('{upper_link}', '<a href="'.$upper_link.'"  title="'.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_upper_symbol.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT').'</span>'.'</a>', $header);    
            
        } else { 
            // get parent category (access must be present then we are always in a sub category from it)
            $db->setQuery("SELECT parent_id FROM #__jdownloads_categories WHERE id = '$catid'");
            $parent_cat_id = $db->loadResult();
            if ($parent_cat_id){
                $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=category&amp;catid='.$parent_cat_id.'&amp;Itemid='.$menuItemids['root']);
                $header = str_replace('{upper_link}', '<a href="'.$upper_link.'"  title="'.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_upper_symbol.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT').'</span>'.'</a>', $header);    
            } else {
                // we are in a sub category - so we link to the main
                if ($is_one_cat){
                    $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=categories&amp;Itemid='.$menuItemids['root']);
                    $header = str_replace('{upper_link}', '<a href="'.$upper_link.'"  title="'.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_upper_symbol.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT').'</span>'.'</a>', $header);    
                } else {
                  $header = str_replace('{upper_link}', '', $header);
                }  
            }    
        }
        
        // create category listbox and viewed it when it is activated in configuration
        if ($params->get('show_header_catlist')){
            
            // get current selected cat id from listbox
            $catlistid = $jinput->get('catid', '0', 'integer');
            
            // When he not exist try it with the catid from $item
            if (!$catlistid){
                $catlistid = $catid;
            }
            
            // get current sort order and direction
            $orderby_pri = $this->params->get('orderby_pri');
            
            // when empty get the state params
            $listordering = $this->state->get('list.ordering');
            if (!$orderby_pri && !empty($listordering)){
                $state_ordering = $this->state->get('list.ordering');
                $state_direction = $this->state->get('list.direction');
                if ($state_ordering == 'c.title'){
                    if ($state_direction== 'DESC'){
                        $orderby_pri = 'ralpha';
                    } else {
                        $orderby_pri = 'alpha';
                    }  
                }    
            }             
            $data = JDHelper::buildCategorySelectBox($catlistid, $cat_link_itemids, $root_itemid, $params->get('view_empty_categories, 1'), $orderby_pri );            
            
            // build special selectable URLs for category listbox
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
        
        // replace both Google adsense placeholder with script
        $subheader = JDHelper::insertGoogleAdsenseCode($subheader);
        
        if ($is_detail){
            $subheader = str_replace('{detail_title}', Text::_('COM_JDOWNLOADS_FRONTEND_SUBTITLE_OVER_DETAIL'), $subheader); 
        } 
        $html .= $subheader;            
    }
    
    // ==========================================
    // BODY SECTION - VIEW THE DOWNLOAD DATA
    // ==========================================
    
    if ($layout_text != ''){
 
        // use the activated/selected "details" layout text to build the output for this download
 
        $body = $layout_text;
        
        // build a little pic for extern links
        $extern_url_pic = '<img src="'.URI::base().'components/com_jdownloads/assets/images/link_extern.gif" alt="external" />';        
        
        // create field labels
        $body = JDHelper::buildFieldTitles($body, $this->item);

        // tabs or sliders when the placeholders are used
        if ((int)$params->get('use_tabs_type') > 0){
           if ((int)$params->get('use_tabs_type') == 1){
                // use slides
               $body = str_replace('{tabs begin}', HTMLHelper::_('bootstrap.startAccordion', 'jdpane', 'panel1'), $body);
               if (strpos($body, '{tab description}') !== false){
                   $body = str_replace('{tab description}', HTMLHelper::_('bootstrap.addSlide', 'jdpane', Text::_('COM_JDOWNLOADS_FE_TAB_DESCRIPTION_TITLE'), 'panel1'), $body); 
                   $body = str_replace('{tab description end}', HTMLHelper::_('bootstrap.endSlide'), $body);
               }
               if (strpos($body, '{tab pics}') !== false){
                   $body = str_replace('{tab pics}', HTMLHelper::_('bootstrap.addSlide', 'jdpane', Text::_('COM_JDOWNLOADS_FE_TAB_PICS_TITLE'), 'panel2'), $body); 
                   $body = str_replace('{tab pics end}', HTMLHelper::_('bootstrap.endSlide'), $body);
               }
               if (strpos($body, '{tab mp3}') !== false){
                   $body = str_replace('{tab mp3}', HTMLHelper::_('bootstrap.addSlide', 'jdpane', Text::_('COM_JDOWNLOADS_FE_TAB_AUDIO_TITLE'), 'panel3'), $body);
                   $body = str_replace('{tab mp3 end}', HTMLHelper::_('bootstrap.endSlide'), $body);
               }
               if (strpos($body, '{tab data}') !== false){
                   $body = str_replace('{tab data}', HTMLHelper::_('bootstrap.addSlide', 'jdpane', Text::_('COM_JDOWNLOADS_FE_TAB_DATA_TITLE'), 'panel4'), $body);
                   $body = str_replace('{tab data end}', HTMLHelper::_('bootstrap.endSlide'), $body);
               }
               if (strpos($body, '{tab download}') !== false){
                   $body = str_replace('{tab download}', HTMLHelper::_('bootstrap.addSlide', 'jdpane', Text::_('COM_JDOWNLOADS_FE_TAB_DOWNLOAD_TITLE'), 'panel5'), $body); 
                   $body = str_replace('{tab download end}',HTMLHelper::_('bootstrap.endSlide'), $body);
               }    
               if (strpos($body, '{tab custom1}') !== false){
                   $body = str_replace('{tab custom1}', HTMLHelper::_('bootstrap.addSlide', 'jdpane', $params->get('additional_tab_title_1'), 'panel6'), $body); 
                   $body = str_replace('{tab custom1 end}', HTMLHelper::_('bootstrap.endSlide'), $body);
               }
               if (strpos($body, '{tab custom2}') !== false){
                   $body = str_replace('{tab custom2}', HTMLHelper::_('bootstrap.addSlide', 'jdpane', $params->get('additional_tab_title_2'), 'panel7'), $body); 
                   $body = str_replace('{tab custom2 end}', HTMLHelper::_('bootstrap.endSlide'), $body);
               }
               if (strpos($body, '{tab custom3}') !== false){
                   $body = str_replace('{tab custom3}', HTMLHelper::_('bootstrap.addSlide', 'jdpane', $params->get('additional_tab_title_3'), 'panel8'), $body); 
                   $body = str_replace('{tab custom3 end}',HTMLHelper::_('bootstrap.endSlide'), $body);
               }
               $body = str_replace('{tabs end}', HTMLHelper::_('bootstrap.endAccordion'), $body);            
           } else {
               // use tabs
               $body = str_replace('{tabs begin}', HTMLHelper::_('bootstrap.startTabSet', 'jdpane', array('active' => 'panel1')), $body);
               if (strpos($body, '{tab description}') !== false){
                   $body = str_replace('{tab description}', HTMLHelper::_('bootstrap.addTab', 'jdpane', 'panel1', Text::_('COM_JDOWNLOADS_FE_TAB_DESCRIPTION_TITLE', true)), $body); 
                   $body = str_replace('{tab description end}', HTMLHelper::_('bootstrap.endTab'), $body);
               }
               if (strpos($body, '{tab pics}') !== false){
                   $body = str_replace('{tab pics}', HTMLHelper::_('bootstrap.addTab', 'jdpane', 'panel2', Text::_('COM_JDOWNLOADS_FE_TAB_PICS_TITLE', true)), $body); 
                   $body = str_replace('{tab pics end}', HTMLHelper::_('bootstrap.endTab'), $body);
               }
               if (strpos($body, '{tab mp3}') !== false){
                   $body = str_replace('{tab mp3}', HTMLHelper::_('bootstrap.addTab', 'jdpane', 'panel3', Text::_('COM_JDOWNLOADS_FE_TAB_AUDIO_TITLE', true)), $body); 
                   $body = str_replace('{tab mp3 end}', HTMLHelper::_('bootstrap.endTab'), $body);
               }    
               if (strpos($body, '{tab data}') !== false){
                   $body = str_replace('{tab data}', HTMLHelper::_('bootstrap.addTab', 'jdpane', 'panel4', Text::_('COM_JDOWNLOADS_FE_TAB_DATA_TITLE', true)), $body); 
                   $body = str_replace('{tab data end}', HTMLHelper::_('bootstrap.endTab'), $body);
               }
               if (strpos($body, '{tab download}') !== false){
                   $body = str_replace('{tab download}', HTMLHelper::_('bootstrap.addTab', 'jdpane', 'panel5', Text::_('COM_JDOWNLOADS_FE_TAB_DOWNLOAD_TITLE', true)), $body); 
                   $body = str_replace('{tab download end}', HTMLHelper::_('bootstrap.endTab'), $body);
               }
               if (strpos($body, '{tab custom1}') !== false){
                   $body = str_replace('{tab custom1}', HTMLHelper::_('bootstrap.addTab', 'jdpane', 'panel6', $params->get('additional_tab_title_1'), true), $body); 
                   $body = str_replace('{tab custom1 end}', HTMLHelper::_('bootstrap.endTab'), $body);
               }
               if (strpos($body, '{tab custom2}') !== false){
                   $body = str_replace('{tab custom2}', HTMLHelper::_('bootstrap.addTab', 'jdpane', 'panel7', $params->get('additional_tab_title_2'), true), $body); 
                   $body = str_replace('{tab custom2 end}', HTMLHelper::_('bootstrap.endTab'), $body);
               }
               if (strpos($body, '{tab custom3}') !== false){
                   $body = str_replace('{tab custom3}', HTMLHelper::_('bootstrap.addTab', 'jdpane', 'panel8', $params->get('additional_tab_title_3'), true), $body); 
                   $body = str_replace('{tab custom3 end}', HTMLHelper::_('bootstrap.endTab'), $body);
               }
               $body = str_replace('{tabs end}', HTMLHelper::_('bootstrap.endTabSet'), $body);      
           }
        } else {
           // delete the placeholders 
           $body = str_replace('{tabs begin}', '', $body);
           $body = str_replace('{tab description}', '', $body);
           $body = str_replace('{tab description end}', '', $body);
           $body = str_replace('{tab pics}', '', $body);
           $body = str_replace('{tab pics end}', '', $body);
           $body = str_replace('{tab mp3}', '', $body);
           $body = str_replace('{tab mp3 end}', '', $body);
           $body = str_replace('{tab data}', '', $body);
           $body = str_replace('{tab data end}', '', $body);
           $body = str_replace('{tab download}', '', $body);
           $body = str_replace('{tab download end}', '', $body);
           $body = str_replace('{tab custom1}', '', $body);
           $body = str_replace('{tab custom1 end}', '', $body);      
           $body = str_replace('{tab custom2}', '', $body);
           $body = str_replace('{tab custom2 end}', '', $body);
           $body = str_replace('{tab custom3}', '', $body);
           $body = str_replace('{tab custom3 end}', '', $body);
           $body = str_replace('{tabs end}', '', $body);      
        }    

        // Remove the old custom fields placeholder
        for ($x=1; $x<15; $x++){
            $body = str_replace("{custom_title_$x}", '', $body);
            $body = str_replace("{custom_value_$x}", '', $body);
        } 
        
        // get data to publish the edit icon and publish data as tooltip
        if ($canEdit){
            $editIcon = JDHelper::getEditIcon($this->item);
        } else {
            $editIcon = '';
        }   
            
        // add the content plugin event 'before display content'
        if (strpos($body, '{before_display_content}') > 0){
            $body = str_replace('{before_display_content}', $item->event->beforeDisplayContent, $body);
        } else {
            $body = $item->event->beforeDisplayContent.$body;    
        }

        // for the 'after display title' event can we only use a placeholder - a fix position is not really given
        $body = str_replace('{after_display_title}', $item->event->afterDisplayTitle, $body);
        
        $body = str_replace('{file_id}', $item->id, $body);
        
        // replace 'featured' placeholders
        if ($item->featured){
            // add the css class
			if ($params->get('use_featured_classes')){
                $body = str_replace('{featured_class}', 'jd_featured', $body);
                $body = str_replace('{featured_detail_class}', 'jd_featured_detail', $body);            
			} else {
				$body = str_replace('{featured_class}', '', $body);
                $body = str_replace('{featured_detail_class}', '', $body);	
			}            
            // add the pic
            if ($params->get('featured_pic_filename')){
                $featured_pic = '<img class="jd_featured_star" src="'.URI::base().'images/jdownloads/featuredimages/'.$params->get('featured_pic_filename').'" width="'.$params->get('featured_pic_size').'" height="'.$params->get('featured_pic_size_height').'" alt="'.substr($params->get('featured_pic_filename'),0,-4).'" />';
                $body = str_replace('{featured_pic}', $featured_pic, $body);
            } else {
                $body = str_replace('{featured_pic}', '', $body);
            }
        } else {
            $body = str_replace('{featured_class}', '', $body);
            $body = str_replace('{featured_detail_class}', '', $body);
            $body = str_replace('{featured_pic}', '', $body);
        }        
        
        $body = str_replace('{price_value}', $item->price, $body);
        $body = str_replace('{views_value}',JDHelper::strToNumber((int)$item->views), $body);
        $body = str_replace('{details_block_title}', Text::_('COM_JDOWNLOADS_FE_DETAILS_DATA_BLOCK_TITLE'), $body);
        if ($item->url_download){
            $body = str_replace('{file_name}', JDHelper::getShorterFilename($this->escape(strip_tags($item->url_download))), $body);
        } elseif (isset($item->filename_from_other_download) && $item->filename_from_other_download != ''){            
            $body = str_replace('{file_name}', JDHelper::getShorterFilename($this->escape(strip_tags($item->filename_from_other_download))), $body);
        } else {
            $body = str_replace('{file_name}', '', $body);
        }   

        $body = str_replace('{category_title}', Text::_('COM_JDOWNLOADS_CATEGORY_LABEL'), $body);
        $body = str_replace('{category_name}', $item->category_title, $body);
        
        $body = str_replace('{file_title}', $item->title.' '.$editIcon, $body);
        
        // Add access level  
        $body = str_replace('{access_title}', Text::_('COM_JDOWNLOADS_ACCESS'), $body);
        $userhelper = new UserGroupsHelper();
        $user_group  = $userhelper->load($item->access);
        $body = str_replace('{access}', $user_group->title, $body);
        
        if ($item->size == '0 B'){
            $body = str_replace('{filesize_value}', '', $body);
        } else {
            $body = str_replace('{filesize_value}', $item->size ?? '', $body);
        } 
        
        // Insert language associations
        $body = str_replace('{show_association}', $association_info, $body);
        
        $body = str_replace('{created_by_value}', $item->creator ?? '', $body);    
        $body = str_replace('{modified_by_value}', $item->modifier ?? '', $body);
        $body = str_replace('{hits_value}',JDHelper::strToNumber((int)$item->downloads), $body);         
        $body = str_replace('{md5_value}',$item->md5_value, $body);
        $body = str_replace('{sha1_value}',$item->sha1_value, $body);
        $body = str_replace('{changelog_value}', $item->changelog, $body);
        
        if ($item_params->get('show_tags', 1) && !empty($item->tags->itemTags)){ 
            $item->tagLayout = new FileLayout('joomla.content.tags');
            $body = str_replace('{tags}', $item->tagLayout->render($item->tags->itemTags), $body);
            $body = str_replace('{tags_title}', Text::_('COM_JDOWNLOADS_TAGS_LABEL'), $body);
        } else {
            $body = str_replace('{tags}', '', $body);
            $body = str_replace('{tags_title}', '', $body);
        }
        
        // Insert the Joomla Fields data when used 
        if (isset($item->jcfields) && count((array)$item->jcfields)){
            foreach ($item->jcfields as $field){
                if ($params->get('remove_field_title_when_empty') && !$field->value){
                    $body = str_replace('{jdfield_title '.$field->id.'}', '', $body);  // Remove label placeholder
                    $body = str_replace('{jdfield '.$field->id.'}', '', $body);        // Remove value placeholder
                } else {
                    $body = str_replace('{jdfield_title '.$field->id.'}', $field->label, $body);  // Insert label
                    $body = str_replace('{jdfield '.$field->id.'}', $field->value, $body);        // Insert value
                }
            }
            
            // In the layout could still exist not required field placeholders
            $results = JDHelper::searchFieldPlaceholder($body);
            if ($results){
                foreach ($results as $result){
                    $body = str_replace($result[0], '', $body);   // Remove label and value placeholder
                }
            } 
        } else {
            // In the layout could still exist not required field placeholders
            $results = JDHelper::searchFieldPlaceholder($body);
            if ($results){
                foreach ($results as $result){
                    $body = str_replace($result[0], '', $body);   // Remove label and value placeholder
                }
            }
        } 
        
        $body = str_replace('{cat_title}', $item->category_title, $body);  

        // replace both Google adsense placeholder with script
        $body = JDHelper::insertGoogleAdsenseCode($body);
        
        // report download link
        if ($this->user_rules->view_report_form){
           $report_link = '<a href="'.Route::_("index.php?option=com_jdownloads&amp;view=report&amp;id=".$item->slug."&amp;catid=".$item->catid."&amp;Itemid=".$root_itemid).'" rel="nofollow">'.Text::_('COM_JDOWNLOADS_FRONTEND_REPORT_FILE_LINK_TEXT').'</a>';
           $body = str_replace('{report_link}', $report_link, $body);
        } else {
           $body = str_replace('{report_link}', '', $body);
        }

        // get icon file pic
        if ($item->file_pic != '' ) {
            $fpicsize = $params->get('file_pic_size');
            $fpicsize_height = $params->get('file_pic_size_height');
            $this->itempic = '<img src="'.$file_pic_folder.$item->file_pic.'" style="text-align:top;border:0px;" width="'.$fpicsize.'" height="'.$fpicsize_height.'"  alt="'.substr($item->title,0,-4).'" /> ';
        } else {
            $this->itempic = '';
        }
        $body = str_replace('{file_pic}',$this->itempic, $body);
        
        if ($item->release) {
            $body = str_replace('{release}', $item->release.' ', $body);        
        } else {
            $body = str_replace('{release}', '', $body);        
        }

        // description
        if (!$item->description_long){
            $body = str_replace('{description_long}', $item->description, $body); 
        } else {
            $body = str_replace('{description_long}', $item->description_long, $body);
        }
        
        // place the images
        $body = JDHelper::placeThumbs($body, $item->images, 'detail');
        
        // we change the old lightbox tag type to the new and added data-alt
        $body = str_replace('rel="lightbox"', 'data-lightbox="lightbox'.$item->id.'" data-alt="lightbox'.substr($item->images,0,-4).'"', $body);
        
        // pics for: new file / hot file /updated
        $hotpic = '<img src="'.URI::base().'images/jdownloads/hotimages/'.$params->get('picname_is_file_hot').'" alt="hotpic" />';
        $newpic = '<img src="'.URI::base().'images/jdownloads/newimages/'.$params->get('picname_is_file_new').'" alt="newpic" />';
        $updatepic = '<img src="'.URI::base().'images/jdownloads/updimages/'.$params->get('picname_is_file_updated').'" alt="updatepic" />';
        
        // compute for HOT symbol
        if ($params->get('loads_is_file_hot') > 0 && $item->downloads >= $params->get('loads_is_file_hot') ){
            $body = str_replace('{pic_is_hot}', '<span class="jdbutton '.$status_color_hot.' jstatus">'.Text::_('COM_JDOWNLOADS_HOT').'</span>', $body);
        } else {    
            $body = str_replace('{pic_is_hot}', '', $body);
        }
        
        // compute for NEW symbol
        $days_diff = JDHelper::computeDateDifference(date('Y-m-d H:i:s'), $item->created);
        if ($params->get('days_is_file_new') > 0 && $days_diff <= $params->get('days_is_file_new')){
            $body = str_replace('{pic_is_new}', '<span class="jdbutton '.$status_color_new.' jstatus">'.Text::_('COM_JDOWNLOADS_NEW').'</span>', $body);
        } else {    
            $body = str_replace('{pic_is_new}', '', $body);
        }
        
        // compute for UPDATED symbol
        // view it only when in the download is activated the 'updated' option
        if ($item->update_active) {
            $days_diff = JDHelper::computeDateDifference(date('Y-m-d H:i:s'), $item->modified);
            if ($params->get('days_is_file_updated') > 0 && $days_diff >= 0 && $days_diff <= $params->get('days_is_file_updated')){
                $body = str_replace('{pic_is_updated}', '<span class="jdbutton '.$status_color_updated.' jstatus">'.Text::_('COM_JDOWNLOADS_UPDATED').'</span>', $body);
            } else {    
                $body = str_replace('{pic_is_updated}', '', $body);
            }
        } else {
           $body = str_replace('{pic_is_updated}', '', $body);
        }    
        
        // replace the placeholder {information_header}
        $body = str_replace('{information_header}', Text::_('COM_JDOWNLOADS_INFORMATION'), $body);
        
        // build the license info data and build link
        if ($item->license == '') $item->license = 0;
        $lic_data = '';

        if ($item->license_url != '') {
             $lic_data = '<a href="'.$item->license_url.'" target="_blank" rel="nofollow" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'">'.$item->license_title.'</a> '.$extern_url_pic;
        } else {
            if ($item->license_title != '') {
                 if ($item->license_text != '') {
                      $lic_data = $item->license_title;
                      $lic_data .= HTMLHelper::_('tooltip', $item->license_text, $item->license_title);
                 } else {
                      $lic_data = $item->license_title;
                 }
            } else {
                $lic_data = '';
            }
        }
        $body = str_replace('{license_text}', $lic_data, $body);
        
        if ($item->modified != '0000-00-00 00:00:00' && $item->modified != null) {
            $modified_data = HTMLHelper::_('date',$item->modified, $date_format['long']);
        } else {
            $modified_data = '';
        }
        $body = str_replace('{modified_date_value}',$modified_data, $body);
        
        // remove placeholder from a older version (not more used)
        $body = str_replace('{download_time}','', $body);    

        // File date
        if ($item->file_date != '0000-00-00 00:00:00' && $item->file_date != null) {
            if ($item_params->get('show_date') == 0){ 
             $this->itemdate_data = HTMLHelper::_('date',$item->file_date, $date_format['long']);
        } else {
                $this->itemdate_data = HTMLHelper::_('date',$item->file_date, $date_format['short']);
            }    
             } else {
                $this->itemdate_data = '';
             }
        $body = str_replace('{file_date}',$this->itemdate_data, $body);

        // Creation date    
        if ($item->created != '0000-00-00 00:00:00' && $item->created != null) {
            $date_data = HTMLHelper::_('date',$item->created, $date_format['long']);
        } else {
            $date_data = '';
        }
        $body = str_replace('{created_date_value}',$date_data, $body);
        
        // Required as switch for rating.
        $user_can_see_download_url = 0; 

        // When we have a simple document, view only the info not any buttons.
        if ($has_no_file){
            // Possible display variation with text in the download button
            //$empty_download_button = '<span class="jdbutton '.$download_color.' '.$download_size.'">'.$no_file_info.'</span>';
            
            $body = str_replace('{url_download}', $no_file_info, $body);
            $body = str_replace('{mirror_1}', '', $body);
            $body = str_replace('{mirror_2}', '', $body);
        } else {
            // only view download link when user has correct access level
            if ($item->params->get('access-download') == true){     
                
                $blank_window  = '';
                $blank_window1 = '';
                $blank_window2 = '';
                
                // Required as switch for rating. 
                $user_can_see_download_url = 1;
                
                // get file extension
                $view_types = array();
                $view_types = explode(',', $params->get('file_types_view'));
                $only_file_name = basename($item->url_download);
                $this->itemextension = JDHelper::getFileExtension($only_file_name);
                if (in_array($this->itemextension, $view_types)){
                    $blank_window = 'target="_blank"';
                }    
                // Check is set link to a new window?
                if ($item->extern_file && $item->extern_site   ){
                    $blank_window = 'target="_blank"';
                }
                // is 'direct download' activated?
                if ($params->get('direct_download') == '0'){ 
                    // When not, we must link to the summary page
                    $url_task = 'summary';
                    $blank_window = '';
                    $download_link = Route::_(RouteHelper::getOtherRoute($item->slug, $item->catid, $item->language, $url_task));
                } else {
                    if ($item->license_agree || $item->password || $this->user_rules->view_captcha) {
                         // User must agree the license - fill out a password field - or fill out the captcha human check - so we must view the summary page!
                        $url_task = 'summary';
                        $download_link = Route::_(RouteHelper::getOtherRoute($item->slug, $item->catid, $item->language, $url_task));
                    } else {     
                        // Start the download promptly
                        $url_task = 'download.send';
                        $download_link = Route::_(RouteHelper::getOtherRoute($item->slug, $item->catid, $item->language, $url_task));
                    }
                } 
                
                if ($url_task == 'download.send'){
                    $download_link_text = '<a '.$blank_window.' href="'.$download_link.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_DIRECT').'" class="jdbutton '.$download_color.' '.$download_size.'">'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'</a>';
                } else {
                    $download_link_text = '<a '.$blank_window.' href="'.$download_link.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_SUMMARY').'" class="jdbutton '.$download_color.' '.$download_size.'">'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'</a>';
                }
                $body = str_replace('{url_download}', $download_link_text, $body);
                
                // Mirrors
                if ($item->mirror_1) {
                    if ($item->extern_site_mirror_1 && $url_task == 'download.send'){
                        $blank_window1 = 'target="_blank"';
                    }
                    $mirror1_link_dum = Route::_('index.php?option=com_jdownloads&amp;task=download.send&amp;id='.$item->id.'&amp;catid='.$item->catid.'&amp;m=1');
                    $mirror1_link = '<a '.$blank_window1.' href="'.$mirror1_link_dum.'" class="jdbutton '.$download_color_mirror1.' '.$download_size_mirror.'">'.Text::_('COM_JDOWNLOADS_FRONTEND_MIRROR_URL_TITLE_1').'</a>'; 
                    $body = str_replace('{mirror_1}', $mirror1_link, $body);
                } else {
                    $body = str_replace('{mirror_1}', '', $body);
                }
                if ($item->mirror_2) {
                    if ($item->extern_site_mirror_2 && $url_task == 'download.send'){
                        $blank_window2 = 'target="_blank"';
                    }            
                    $mirror2_link_dum = Route::_('index.php?option=com_jdownloads&amp;task=download.send&amp;id='.$item->id.'&amp;catid='.$item->catid.'&amp;m=2');
                    $mirror2_link = '<a '.$blank_window2.' href="'.$mirror2_link_dum.'" class="jdbutton '.$download_color_mirror2.' '.$download_size_mirror.'">'.Text::_('COM_JDOWNLOADS_FRONTEND_MIRROR_URL_TITLE_2').'</a>'; 
                    $body = str_replace('{mirror_2}', $mirror2_link, $body);
                } else {
                    $body = str_replace('{mirror_2}', '', $body);
                }            
            } else {
                // visitor has not access to download this item - so we will inform him
                if (!$user->guest){
                    // user is always logged in but has no access - so add a special info that only special members has access
                    $regg = Text::_('COM_JDOWNLOADS_FRONTEND_FILE_ACCESS_REGGED2');
                } else {
                    $regg = Text::_('COM_JDOWNLOADS_FRONTEND_FILE_ACCESS_REGGED');
                }
                $regg = '<div class="'.$params->get('css_button_color_download').' '.$params->get('css_button_size_download').'">'.$regg.'</div>';
                     
                $body = str_replace('{url_download}', $regg, $body);
                $body = str_replace('{mirror_1}', '', $body); 
                $body = str_replace('{mirror_2}', '', $body);
                
            }    
        }
        // build website url
        if (!$item->url_home == '') {
             if (strpos($item->url_home, 'http://') !== false or strpos($item->url_home, 'https://') !== false) {    
                 $body = str_replace('{author_url_text}', '<a href="'.$item->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $body);
             } else {
                 $body = str_replace('{author_url_text}', '<a href="http://'.$item->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $body);
             }    
        } else {
            $body = str_replace('{author_url_text}', '', $body);
        }

        // encode is link a mail
        $link_author = '';
        if (strpos($item->url_author, '@') && $params->get('mail_cloaking')){
            if (!$item->author) { 
                $mail_encode = HTMLHelper::_('email.cloak',$item->url_author);
            } else {
                $mail_encode = HTMLHelper::_('email.cloak',$item->url_author, true, $item->author, false);
            }        
        } else {
            $mail_encode = '';
        }
                        
        // build author link
        if ($item->author <> ''){
             if ($item->url_author <> '') {
                  if ($mail_encode) {
                      $link_author = $mail_encode;
                  } else {
                      if (strpos($item->url_author, 'http://') !== false or strpos($item->url_author, 'https://') !== false) {
                         $link_author = '<a href="'.$item->url_author.'" target="_blank">'.$item->author.'</a> '.$extern_url_pic;
                      } else {
                         $link_author = '<a href="http://'.$item->url_author.'" target="_blank">'.$item->author.'</a>  '.$extern_url_pic;
                      }        
                  }
                  $body = str_replace('{author_text}',$link_author, $body);
                  $body = str_replace('{url_author}', '', $body);
             } else {
                  $link_author = $item->author;
                  $body = str_replace('{author_text}',$link_author, $body);
                  $body = str_replace('{url_author}', '', $body);
             }
        } else {
            $body = str_replace('{url_author}', $item->url_author, $body);
            $body = str_replace('{author_text}','', $body);
        }

        // set system value
        $this->item_sys_values = explode(',' , JDHelper::getOnlyLanguageSubstring($params->get('system_list')));
        if ($item->system == 0 ) {
             $body = str_replace('{system_text}', '', $body);
        } else {
             $body = str_replace('{system_text}', $this->item_sys_values[$item->system], $body);
        }

        // set language value
        $this->item_lang_values = explode(',' , JDHelper::getOnlyLanguageSubstring($params->get('language_list')));
        if ($item->file_language == 0 ) {
            $body = str_replace('{language_text}', '', $body);
        } else {
            $body = str_replace('{language_text}', $this->item_lang_values[$item->file_language], $body);
        }
        
        // media player
        if ($item->preview_filename){
            // we use the preview file when exist  
            $is_preview = true;
            $item->itemtype = JDHelper::getFileExtension($item->preview_filename);
            $is_playable    = JDHelper::isPlayable($item->preview_filename);
        } else {                  
            $is_preview = false;
            if ($item->extern_file){
                $extern_media = true;
                $item->itemtype = JDHelper::getFileExtension($item->extern_file);
                $is_playable    = JDHelper::isPlayable($item->extern_file);
            } else {    
                $item->itemtype = JDHelper::getFileExtension($item->url_download);
                $is_playable    = JDHelper::isPlayable($item->url_download);
                $extern_media = false;
            }  
        }
            
        if ( $is_playable ){
            
               if ($params->get('html5player_use')){
                    // we will use the new HTML5 player option
                    if ($extern_media){
                        $media_path = $item->extern_file;
                    } else {        
                        if ($is_preview){
                            // we need the relative path to the "previews" folder
                            $media_path = $jdownloads_root_dir_name.'/'.$params->get('preview_files_folder_name').'/'.$item->preview_filename;
                        } else {
                            // we use the normal download file for the player
                            $media_path = $jdownloads_root_dir_name.'/'.$category_dir.'/'.$item->url_download;
                        }   
                    }    
                            
                    // create the HTML5 player
                    $player = JDHelper::getHTML5Player($this->item, $media_path);
                    
                    if ($item->itemtype == 'mp4' || $item->itemtype == 'webm' || $item->itemtype == 'ogg' || $item->itemtype == 'ogv' || $item->itemtype == 'mp3' || $item->itemtype == 'wav' || $item->itemtype == 'oga'){
                        // We will replace at first the old placeholder when exist
                        if (strpos($body, '{mp3_player}')){
                            $body = str_replace('{mp3_player}', $player, $body);
                            $body = str_replace('{preview_player}', '', $body);
                        } else {                
                            $body = str_replace('{preview_player}', $player, $body);
                        }    
                    } else {
                        $body = str_replace('{mp3_player}', '', $body);        
                        $body = str_replace('{preview_player}', '', $body);       
                    }
                
               } else {
            
                    if ( $params->get('flowplayer_use') ){
                        // we will use the new flowplayer option
                        if ($extern_media){
                            $media_path = $item->extern_file;
                        } else {        
                            if ($is_preview){
                                // we need the relative path to the "previews" folder
                                $media_path = $jdownloads_root_dir_name.'/'.$params->get('preview_files_folder_name').'/'.$item->preview_filename;
                            } else {
                                // we use the normal download file for the player
                                $media_path = $jdownloads_root_dir_name.'/'.$category_dir.'/'.$item->url_download;
                            }   
                        }    

                        $ipadcode = '';
                        
                        if ($item->itemtype == 'mp3'){
                            $fullscreen = 'false';
                            $autohide = 'false';
                            $playerheight = (int)$params->get('flowplayer_playerheight_audio');
                            // we must use also the ipad plugin identifier when required
                            // see http://flowplayer.blacktrash.org/test/ipad-audio.html and http://flash.flowplayer.org/plugins/javascript/ipad.html
                            if ($this->ipad_user){
                               $ipadcode = '.ipad();'; 
                            }    
                        } else {
                            $fullscreen = 'true';
                            $autohide = 'true';
                            $playerheight = (int)$params->get('flowplayer_playerheight');
                        }
                        
                        $player = '<a href="'.$media_path.'" style="display:block;width:'.$params->get('flowplayer_playerwidth').'px;height:'.$playerheight.'px;" id="player" class="player"></a>';
                        $player .= '<script language="JavaScript">
                        // install flowplayer into container
                                    flowplayer("player", "'.URI::base().'components/com_jdownloads/assets/flowplayer/flowplayer-3.2.16.swf",
                                     {
             
                            plugins: {
                                controls: {
                                    // insert at first the config settings
                                    // and now the basics
                                    fullscreen: '.$fullscreen.',
                                    height: '.(int)$params->get('flowplayer_playerheight_audio').',
                                    autoHide: '.$autohide.'
                                }
                            },

                            clip: {
                                autoPlay: false,
                                // optional: when playback starts close the first audio playback
                                onBeforeBegin: function() {
                                    $f("player").close();
                                }
                            }
                        })'.$ipadcode.'; </script>'; // the 'ipad code' is only required for ipad/iphone users 

                        if ($item->itemtype == 'mp4' || $item->itemtype == 'flv' || $item->itemtype == 'mp3'){    
                            // We will replace at first the old placeholder when exist
                            if (strpos($body, '{mp3_player}')){
                                $body = str_replace('{mp3_player}', $player, $body);
                                $body = str_replace('{preview_player}', '', $body);
                            } else {
                                $body = str_replace('{preview_player}', $player, $body);
                            }                                
                        } else {
                            $body = str_replace('{mp3_player}', '', $body);
                            $body = str_replace('{preview_player}', '', $body);
                        }                        
                    }
               }
        } 

        if ($params->get('mp3_view_id3_info') && $item->itemtype == 'mp3' && !$extern_media){
            // read mp3 infos
            if ($is_preview){
                // get the path to the preview file
                $mp3_path_abs = $params->get('files_uploaddir').'/'.$params->get('preview_files_folder_name').'/'.$item->preview_filename;
            } else {
                // get the path to the downloads file
                $mp3_path_abs = $params->get('files_uploaddir').'/'.$category_dir.'/'.$item->url_download;
            }
            
            $info = JDHelper::getID3v2Tags($mp3_path_abs);         
            if ($info){
                // add it
                $mp3_info = '<div class="jd_mp3_id3tag_wrapper" style="max-width:'.(int)$params->get('html5player_audio_width').'px; ">'.stripslashes($params->get('mp3_info_layout')).'</div>';
                $mp3_info = str_replace('{name_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_TITLE'), $mp3_info);
                if ($is_preview){
                    $mp3_info = str_replace('{name}', $item->preview_filename, $mp3_info);
                } else {
                    $mp3_info = str_replace('{name}', $item->url_download, $mp3_info);
                } 
                $mp3_info = str_replace('{album_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_ALBUM'), $mp3_info);
                $mp3_info = str_replace('{album}', $info['TALB'], $mp3_info);
                $mp3_info = str_replace('{artist_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_ARTIST'), $mp3_info);
                $mp3_info = str_replace('{artist}', $info['TPE1'], $mp3_info);
                $mp3_info = str_replace('{genre_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_GENRE'), $mp3_info);
                $mp3_info = str_replace('{genre}', $info['TCON'], $mp3_info);
                $mp3_info = str_replace('{year_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_YEAR'), $mp3_info);
                $mp3_info = str_replace('{year}', $info['TYER'], $mp3_info);
                $mp3_info = str_replace('{length_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_LENGTH'), $mp3_info);
                $mp3_info = str_replace('{length}', $info['TLEN'].' '.Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_MINS'), $mp3_info);
                $body = str_replace('{mp3_id3_tag}', $mp3_info, $body); 
            }     
        }

        $body = str_replace('{mp3_player}', '', $body);
        $body = str_replace('{preview_player}', '', $body);
        $body = str_replace('{mp3_id3_tag}', '', $body);             

        // replace the {preview_url}
        if ($item->preview_filename){
            // we need the relative path to the "previews" folder
            $media_path = $jdownloads_root_dir_name.'/'.$params->get('preview_files_folder_name').'/'.$item->preview_filename;
            $body = str_replace('{preview_url}', $media_path, $body);
        } else {
            $body = str_replace('{preview_url}', '', $body);
        }         
        
        // insert rating system
        if ($params->get('view_ratings')){
            $rating_system = JDHelper::getRatings($item->id, $user_can_see_download_url, $item->rating_count, $item->rating_sum);
            $body = str_replace('{rating}', $rating_system, $body);
            $body = str_replace('{rating_title}', Text::_('COM_JDOWNLOADS_RATING_LABEL'), $body);
        } else {
            $body = str_replace('{rating}', '', $body);
            $body = str_replace('{rating_title}', '', $body);
        } 

        // remove empty html tags
        if ($params->get('remove_empty_tags')){
            $body = JDHelper::removeEmptyTags($body);
        }
             
        // Option for JComments integration
        if ($params->get('jcomments_active')){
            $jcomments = JPATH_BASE.'/components/com_jcomments/jcomments.php';
            if (file_exists($jcomments)) {
                require_once($jcomments);
                $obj_id = $item->id;
                $obj_title = $item->title;
                $body .= JComments::show($obj_id, 'com_jdownloads', $obj_title);
            }    
        }
        
        // add the content plugin event 'after display content'
        if (strpos($body, '{after_display_content}') > 0){
        	$body = str_replace('{after_display_content}', $item->event->afterDisplayContent, $body);
            $event = '';
        } else {
            $event = $item->event->afterDisplayContent;    
        }
        
        $html .= $body; 

        // finaly add the 'after display content' event output when required
        $html .= $event;        

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
    $html   .= $footer; 
    
    $html .= '</div>';

    // support for global content plugins
    if ($params->get('activate_general_plugin_support')) {  
        $html = HTMLHelper::_('content.prepare', $html, '', 'com_jdownloads.download');
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