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
 
    $app        = Factory::getApplication();
    
    $db         = Factory::getDBO(); 
    $document   = Factory::getDocument();
    $jinput     = Factory::getApplication()->input;
    
    $user       = Factory::getUser();
    $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;    
    
    // Get jD user limits and settings
    $jd_user_settings = $this->user_rules;
    
    $listOrder = str_replace('a.', '', $this->escape($this->state->get('list.ordering')));    
    $listDirn  = $this->escape($this->state->get('list.direction'));    
    
    // Create shortcuts to some parameters.
    $params           = $this->params;
    $items            = $this->items;

    $html             = '';
    $html_files     = '';
    $body             = '';
    $footer_text      = '';
    
    
    $jdownloads_root_dir_name = basename($params->get('files_uploaddir'));
    
    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($params->get('selected_file_type_icon_set'));
    
    $checkbox_top_always_added = false;
    
    $date_format = JDHelper::getDateFormat();

    $layout_has_checkbox = false;
    $layout_has_download = false;

    // Get the layout data            
    $layout_files = $this->layout;
    if ($layout_files){
        // Unused language placeholders must at first get removed from layout
        $layout_files_text        = JDHelper::removeUnusedLanguageSubstring($layout_files->template_text);
        $header                   = JDHelper::removeUnusedLanguageSubstring($layout_files->template_header_text);
        $subheader                = JDHelper::removeUnusedLanguageSubstring($layout_files->template_subheader_text);
        $footer                   = JDHelper::removeUnusedLanguageSubstring($layout_files->template_footer_text);
    } else {
        // We have not a valid layout data
        echo '<big>No valid layout found for Downloads!</big>';
    }
    
    // Check that comments table exist - get DB prefix string
    $prefix = $db->getPrefix();
    // Sometimes wrong uppercase prefix result string - so we fix it
    $prefix2 = strtolower($prefix);
    $tablelist = $db->getTableList();
    $comments_table_exist = false;
    if (in_array($prefix.'jcomments', $tablelist ) || in_array($prefix2.'jcomments', $tablelist )){
        $comments_table_exist = true;
    }        
    
    if ($layout_files->symbol_off == 0 ) {
        $use_mini_icons = true;
    } else {
        $use_mini_icons = false; 
    }             
    
    // We may not use in this listing checkboxes for mass downloads, since we have not a category layout with the required checkbox placeholders.
    // So will view this listing always with download links.
    // Deactivate at first the setting when it is used - it is not used, we does nothing.
    if ($layout_files->checkbox_off == 0){
        $layout_files->checkbox_off = 1;
        $layout_has_checkbox = true;
        // Find out whether we have checkboxes AND download placeholders
        if (strpos($layout_files->template_text, '{url_download}')){
            // We have a layout also with download placeholder 
            $layout_has_download = true;
        }       
    } else {
        if (strpos($layout_files->template_text, '{url_download}')){
            // We have a layout also with download placeholder 
            $layout_has_download = true;
        }  
    }              
    
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
              
    $total_downloads  = $this->pagination->total;
    
    // Get current category menu ID when exist and all needed menu IDs for the header links
    $menuItemids = JDHelper::getMenuItemids();
    
    // Get all other menu category IDs so we can use it when we needs it
    $cat_link_itemids = JDHelper::getAllJDCategoryMenuIDs();
    
    // "Home" menu link itemid
    $root_itemid =  $menuItemids['root'];

    // Make sure, that we have a valid menu itemid (we have not a category here)
    $category_menu_itemid = $root_itemid;
        
    $html = '<div class="jd-item-page'.$this->pageclass_sfx.'">';
    
    if ($this->params->get('show_page_heading')) {
        $html .= '<h1>'.$this->escape($this->params->get('page_heading')).'</h1>';
    } 
    
    // ==========================================
    // HEADER SECTION
    // ==========================================

    if ($header != ''){
        
        // component title - not more used. So we must replace the placeholder from layout with spaces!
        $header = str_replace('{component_title}', '', $header);
        
        // Cart option active?
        if ($this->cartplugin && $params->get('use_shopping_cart_plugin')){
            $cart_link = '<div class="cart_cartstatus">
                          <a href="'.$this->current_url.'#jdownloadscart'.'">'.Text::_('COM_JDOWNLOADS_YOUR_CART').': <span class="simpleCart_quantity"></span> '.Text::_('COM_JDOWNLOADS_ITEMS').'</a>
                      </div>';
                      
            $header = str_replace('{cart_link}', $cart_link, $header);                      
        } else {
            $header = str_replace('{cart_link}', '', $header);                      
        }
        
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

        if ($menuItemids['upper'] > 1 && $menuItemids['upper'] != $menuItemids['base']){   // 1 is 'root'
            // exists a single category menu link for the category a level up? 
            $level_up_cat_itemid = JDHelper::getSingleCategoryMenuID($cat_link_itemids, $menuItemids['upper'], $root_itemid);
            $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=category&amp;catid='.$menuItemids['upper'].'&amp;Itemid='.$level_up_cat_itemid);
        } else {
            $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=categories&amp;Itemid='.$menuItemids['root']);
        }
        $header = str_replace('{upper_link}', '<a href="'.$upper_link.'"  title="'.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_upper_symbol.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT').'</span>'.'</a>', $header);    
        
        // create category listbox and viewed it when it is activated in configuration
        if ($params->get('show_header_catlist')){
            
            // get current selected cat id from listbox
            if ($this->state->get('only_uncategorised')){
                $catlistid = 1;
            } else {
                $catlistid = -1;
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
            $catlistid = 0;        
        }
        $html .= $header;  
    }

    // ==========================================
    // SUB HEADER SECTION
    // ==========================================

    if ($subheader != ''){

        // Display number of sub categories only when > 0 
        if ($total_downloads == 0){
            $total_files_text = '';
        } else {
            $total_files_text = Text::_('COM_JDOWNLOADS_NUMBER_OF_DOWNLOADS_LABEL').': '.$total_downloads;
        }
        
        // Display at first the list title
        switch ($catlistid){
            case '-2': 
                $subheader = str_replace('{subheader_title}', Text::_('COM_JDOWNLOADS_SELECT_NEWEST_DOWNLOADS'), $subheader);
                break;
            case '-3': 
                $subheader = str_replace('{subheader_title}', Text::_('COM_JDOWNLOADS_SELECT_HOTTEST_DOWNLOADS'), $subheader);
                $catlistid = -3;
                break;
            case '-1': 
            case '0': 
                $subheader = str_replace('{subheader_title}', Text::_('COM_JDOWNLOADS_FRONTEND_SUBTITLE_OVER_ALL_DOWNLOADS'), $subheader);
                break;
        } 
        
        // Display pagination            
        $subheader = JDHelper::insertPagination( $this->pagination, $subheader, $params->get('option_navigate_top'), $this->params->get('show_pagination'), $this->params->get('show_pagination_results') );

        // Display amount of files - we use the sub categories placeholder
            $subheader = str_replace('{count_of_sub_categories}', $total_files_text, $subheader); 

        // Display sort order bar
        if ($params->get('view_sort_order') && $total_downloads > 1 && $this->params->get('show_sort_order_bar') != '0'
        || (!$params->get('view_sort_order') && $this->pagination->pagesTotal > 1 && $this->params->get('show_sort_order_bar') == '1') )
        {
           // We must have at minimum a single field for sorting
           $sortorder_fields = $params->get('sortorder_fields', array());
           
           if ($sortorder_fields){
               if (!is_array($sortorder_fields)){
                   $sortorder_fields = explode(',', $sortorder_fields);
               }
           } else {
               $sortorder_fields = array();
           }
           
           if (count($sortorder_fields)){
               $limitstart = $this->pagination->limitstart;
               
               // create form
               $sort_form = '<form action="'.htmlspecialchars(Uri::getInstance()->toString()).'" method="post" name="adminForm" id="adminForm">';
               $sort_form_hidden = '<input type="hidden" name="filter_order" value="" />
                                   <input type="hidden" name="filter_order_Dir" value="" />
                                   <input type="hidden" name="limitstart" value="" /></form>';
                              
               $ordering = '<span class="jd-list-ordering" id="ordering1">'.HTMLHelper::_('grid.sort', Text::_('COM_JDOWNLOADS_FE_SORT_ORDER_DEFAULT'), 'ordering', $listDirn, $listOrder).' | </span>';
               $title    = '<span class="jd-list-title" id="ordering2">'.HTMLHelper::_('grid.sort', Text::_('COM_JDOWNLOADS_FE_SORT_ORDER_NAME'), 'title', $listDirn, $listOrder).' | </span>';
               $author   = '<span class="jd-list-author" id="ordering3">'.HTMLHelper::_('grid.sort', Text::_('COM_JDOWNLOADS_FE_SORT_ORDER_AUTHOR'), 'author', $listDirn, $listOrder).' | </span>';               
               $date     = '<span class="jd-list-date" id="ordering4">'.HTMLHelper::_('grid.sort', Text::_('COM_JDOWNLOADS_FE_SORT_ORDER_DATE'), 'created', $listDirn, $listOrder).' | </span>';
               $hits     = '<span class="jd-list-hits" id="ordering5">'.HTMLHelper::_('grid.sort', Text::_('COM_JDOWNLOADS_FE_SORT_ORDER_HITS'), 'downloads', $listDirn, $listOrder).' | </span>';               
               $featured = '<span class="jd-list-featured" id="ordering6">'.HTMLHelper::_('grid.sort', Text::_('COM_JDOWNLOADS_FE_SORT_ORDER_FEATURED'), 'featured', $listDirn, $listOrder).' | </span>';
               //$ratings  = '<span class="jd-list-ratings" id="ordering7">'.HTMLHelper::_('grid.sort', Text::_('COM_JDOWNLOADS_FE_SORT_ORDER_RATINGS'), 'downloads', $listDirn, $listOrder).' | </span>';               

               $listorder_bar = $sort_form
                                .Text::_('COM_JDOWNLOADS_FE_SORT_ORDER_TITLE').' '
                                .'<br />';
                                
               foreach ($sortorder_fields as $sfield) {
                    switch ($sfield) {
                        case 0:
                            $listorder_bar = $listorder_bar.$ordering;
                            break;
                        case 1:
                            $listorder_bar = $listorder_bar.$title;
                            break;
                        case 2:
                            $listorder_bar = $listorder_bar.$author;
                            break;
                        case 3:
                            $listorder_bar = $listorder_bar.$date;
                            break;
                        case 4:
                            $listorder_bar = $listorder_bar.$hits;
                            break;
                        case 5:
                            $listorder_bar = $listorder_bar.$featured;
                            break;
                        /*case 6:
                            $listorder_bar = $listorder_bar.$ratings;
                            break; */                                                                                                                               
                    }
               }
               // remove | at the end
               $len = strlen($listorder_bar);
               $pos = strripos($listorder_bar, "|");
               $diff = $len - $pos;
               if ($pos > 0 && $diff == 9){
                   $listorder_bar = substr($listorder_bar, 0, ($len - $diff)).'</span>';  
               } 
               // add hidden fields
               $listorder_bar = $listorder_bar.$sort_form_hidden;
                                  
               $subheader = str_replace('{sort_order}', $listorder_bar, $subheader);
           } else {
               $subheader = str_replace('{sort_order}', '', $subheader);          
           }
        } else {   
           $subheader = str_replace('{sort_order}', '', $subheader);          
        }    
        
        // replace both Google adsense placeholder with script
        $subheader = JDHelper::insertGoogleAdsenseCode($subheader);
        $html .= $subheader;            
    }
    
    $formid = $total_downloads + 1;
    
    // ==========================================
    // BODY SECTION - VIEW THE DOWNLOADS DATA
    // ==========================================
    
    if ($this->cartplugin && $params->get('use_shopping_cart_plugin')){
    	$html_files = '<div class="cart_product_content">';
    } else {
		$html_files = '';
	}

    if ($layout_files_text != ''){
        
        // Build the mini image symbols when used in layout ( 0 = activated !!! )
        if ($use_mini_icons) {
            $msize =  $params->get('info_icons_size');
            $pic_date = '<img src="'.Uri::base().'images/jdownloads/miniimages/date.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DATE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DATE').'" />&nbsp;';
            $pic_license = '<img src="'.Uri::base().'images/jdownloads/miniimages/license.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'" />&nbsp;';
            $pic_author = '<img src="'.Uri::base().'images/jdownloads/miniimages/contact.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_AUTHOR').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_AUTHOR').'" />&nbsp;';
            $pic_website = '<img src="'.Uri::base().'images/jdownloads/miniimages/weblink.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_WEBSITE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_WEBSITE').'" />&nbsp;';
            $pic_system = '<img src="'.Uri::base().'images/jdownloads/miniimages/system.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_SYSTEM').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_SYSTEM').'" />&nbsp;';
            $pic_language = '<img src="'.Uri::base().'images/jdownloads/miniimages/language.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LANGUAGE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LANGUAGE').'" />&nbsp;';
            $pic_downloads = '<img src="'.Uri::base().'images/jdownloads/miniimages/download.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DOWNLOAD').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DOWNLOAD_HITS').'" />&nbsp;';
            $pic_price = '<img src="'.Uri::base().'images/jdownloads/miniimages/currency.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_PRICE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_PRICE').'" />&nbsp;';
            $pic_size = '<img src="'.Uri::base().'images/jdownloads/miniimages/stuff.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_FILESIZE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_FILESIZE').'" />&nbsp;';
        } else {
		    $pic_date       = '';
		    $pic_license    = '';
		    $pic_author     = '';
		    $pic_website    = '';
		    $pic_system     = '';
		    $pic_language   = '';
		    $pic_downloads  = '';
		    $pic_price      = '';
		    $pic_size       = '';
        } 
        
        
        // Build a little pic for extern links
        $extern_url_pic = '<img src="'.Uri::base().'components/com_jdownloads/assets/images/link_extern.gif" alt="external" />';        

        // ===========================================
        // Display now the files (Downloads)
        // ===========================================
        
        for ($i = 0; $i < count($items); $i++) {
            
            // Build the categories path for the file
            if ($items[$i]->category_cat_dir_parent){
                $category_dir = $items[$i]->category_cat_dir_parent.'/'.$items[$i]->category_cat_dir;
            } elseif ($items[$i]->category_cat_dir) {
                $category_dir = $items[$i]->category_cat_dir;
            } else {
                // we have an uncategorised download so we must add the defined folder for this
                $category_dir = $params->get('uncategorised_files_folder_name');
            }              
            
            // When user has access: get data to publish the edit icon and publish data as tooltip
            if ($items[$i]->params->get('access-edit')){
                $editIcon = JDHelper::getEditIcon($items[$i]);
            } else {
                $editIcon = '';
            }            
            
            $has_no_file = false;
            $file_id = $items[$i]->id;
            
            // when we have not a menu item to the singel download, we need a menu item from the assigned category, or at lates the root itemid
            if ($items[$i]->menuf_itemid){
                $file_itemid =  (int)$items[$i]->menuf_itemid;
            } else {
                $file_itemid = $category_menu_itemid;
            }             
            
            if (!$items[$i]->url_download && !$items[$i]->other_file_id && !$items[$i]->extern_file){
               // only a document without file
               //$userinfo = Text::_('COM_JDOWNLOADS_FRONTEND_ONLY_DOCUMENT_USER_INFO');
               $has_no_file = true;           
            }            
            
            // use the activated/selected "files" layout text to build the output for every download
            $html_file = $layout_files_text;

            // add the content plugin event 'before display content'
            if (strpos($html_file, '{before_display_content}') > 0){
                $html_file = str_replace('{before_display_content}', $items[$i]->event->beforeDisplayContent, $html_file);
            } else {
                $html_file = $items[$i]->event->beforeDisplayContent.$html_file;    
            }

            // for the 'after display title' event can we only use a placeholder - a fix position is not really given
            $html_file = str_replace('{after_display_title}', $items[$i]->event->afterDisplayTitle, $html_file);           
           
            $html_file = str_replace('{file_id}',$items[$i]->id, $html_file);

            // replace 'featured' placeholders
            if ($items[$i]->featured){
                // Add the css classes
				if ($params->get('use_featured_classes')){
                    $html_file = str_replace('{featured_class}', 'jd_featured', $html_file);
                    $html_file = str_replace('{featured_detail_class}', 'jd_featured_detail', $html_file);            
				} else {
					$html_file = str_replace('{featured_class}', '', $html_file);
                    $html_file = str_replace('{featured_detail_class}', '', $html_file);	
				}            
                // add the pic
                if ($params->get('featured_pic_filename')){
                    $featured_pic = '<img class="jd_featured_star" src="'.URI::base().'images/jdownloads/featuredimages/'.$params->get('featured_pic_filename').'" width="'.$params->get('featured_pic_size').'" height="'.$params->get('featured_pic_size_height').'" alt="'.substr($params->get('featured_pic_filename'),0,-4).'" />';
                    $html_file = str_replace('{featured_pic}', $featured_pic, $html_file);
                } else {
                    $html_file = str_replace('{featured_pic}', '', $html_file);
                }                
            } else {
                $html_file = str_replace('{featured_class}', '', $html_file);
                $html_file = str_replace('{featured_detail_class}', '', $html_file);
                $html_file = str_replace('{featured_pic}', '', $html_file);
            }
            
            // make sure that we have a {url_download} placeholder
            if (!strpos($html_file, '{url_download}')){
                // try to use the checkbox placeholder
                $html_file = str_replace('{checkbox_list}', '{url_download}', $html_file);
            }
            
            // render the tags
            if ($params->get('show_tags', 1) && !empty($items[$i]->tags->itemTags)){ 
                $items[$i]->tagLayout = new FileLayout('joomla.content.tags');
                $html_file = str_replace('{tags}', $items[$i]->tagLayout->render($items[$i]->tags->itemTags), $html_file);
                $html_file = str_replace('{tags_title}', Text::_('COM_JDOWNLOADS_TAGS_LABEL'), $html_file);
            } else {
                $html_file = str_replace('{tags}', '', $html_file);
                $html_file = str_replace('{tags_title}', '', $html_file);
            }
            
            // Insert the Joomla Fields data when used 
            if (isset($items[$i]->jcfields) && count((array)$items[$i]->jcfields)){
                foreach ($items[$i]->jcfields as $field){
                    if ($params->get('remove_field_title_when_empty') && !$field->value){
                        $html_file = str_replace('{jdfield_title '.$field->id.'}', '', $html_file);  // Remove label placeholder
                        $html_file = str_replace('{jdfield '.$field->id.'}', '', $html_file);        // Remove value placeholder
                    } else {
                        $html_file = str_replace('{jdfield_title '.$field->id.'}', $field->label, $html_file);  // Insert label
                        $html_file = str_replace('{jdfield '.$field->id.'}', $field->value, $html_file);        // Insert value
                    }
                }
                
                // In the layout could still exist not required field placeholders
                $results = JDHelper::searchFieldPlaceholder($html_file);
                if ($results){
                    foreach ($results as $result){
                        $html_file = str_replace($result[0], '', $html_file);   // Remove label and value placeholder
                    }
                } 
            } else {
                // In the layout could still exist not required field placeholders
                $results = JDHelper::searchFieldPlaceholder($html_file);
                if ($results){
                    foreach ($results as $result){
                        $html_file = str_replace($result[0], '', $html_file);   // Remove label and value placeholder
                    }
                }
            }            
            
            // Files title row info only view when it is the first file
            if ($i > 0){
                // Remove all html tags in top cat output
                if ($pos_end = strpos($html_file, '{files_title_end}')){
                    $pos_beg = strpos($html_file, '{files_title_begin}');
                    $html_file = substr_replace($html_file, '', $pos_beg, ($pos_end - $pos_beg) + 17);
                }
            } else {
                $html_file = str_replace('{files_title_text}', Text::_('COM_JDOWNLOADS_FE_FILELIST_TITLE_OVER_FILES_LIST'), $html_file);
                
                // Add pagination
                $html_file = JDHelper::insertPagination( $this->pagination, $html_file, $params->get('option_navigate_top'), $this->params->get('show_pagination'), $this->params->get('show_pagination_results') );
                
                $html_file = str_replace('{files_title_end}', '', $html_file);
                $html_file = str_replace('{files_title_begin}', '', $html_file);
            } 
     
            // Create file titles
            $html_file = JDHelper::buildFieldTitles($html_file, $items[$i]);
            
            // Create category title
            $html_file = str_replace('{category_title}', Text::_('COM_JDOWNLOADS_CATEGORY_LABEL'), $html_file);
            $html_file = str_replace('{category_name}', $items[$i]->category_title, $html_file);
            
            // Insert language associations
            if ($params->get('show_associations') && (!empty($items[$i]->associations))){
                $association_info = '<dd class="jd_associations">'.Text::_('COM_JDOWNLOADS_ASSOCIATION_HINT');
                
                foreach ($items[$i]->associations as $association){
                    if ($params->get('flags', 1) && $association['language']->image){
                        $flag = HTMLHelper::_('image', 'mod_languages/' . $association['language']->image . '.gif', $association['language']->title_native, array('title' => $association['language']->title_native), true);
                        $line = '&nbsp;<a href="'.Route::_($association['item']).'">'.$flag.'</a>&nbsp';
                    } else {
                        $class = 'label label-association label-' . $association['language']->sef;
                        $line  = '&nbsp;<a class="'.$class.'" href="'.Route::_($association['item']).'">'.strtoupper($association['language']->sef).'</a>&nbsp';
                    }
                    $association_info .= $line;
                }
                $association_info .= '</dd>';
                
            } else {
                $association_info = '';
            }
            
            $html_file = str_replace('{show_association}', $association_info, $html_file);             
            
            // Create filename
            if ($items[$i]->url_download){
                $html_file = str_replace('{file_name}', JDHelper::getShorterFilename($this->escape(strip_tags($items[$i]->url_download))), $html_file);
            } elseif (isset($items[$i]->filename_from_other_download) && $items[$i]->filename_from_other_download != ''){
                $html_file = str_replace('{file_name}', JDHelper::getShorterFilename($this->escape(strip_tags($items[$i]->filename_from_other_download))), $html_file);
            } else {
                $html_file = str_replace('{file_name}', '', $html_file);
            }             
             
            // Replace both Google adsense placeholder with script
            $html_file = JDHelper::insertGoogleAdsenseCode($html_file);

            // Report download link
            if ($jd_user_settings->view_report_form){
                $report_link = '<a href="'.Route::_("index.php?option=com_jdownloads&amp;view=report&amp;id=".$items[$i]->slug."&amp;catid=".$items[$i]->catid."&amp;Itemid=".$root_itemid).'" rel="nofollow">'.Text::_('COM_JDOWNLOADS_FRONTEND_REPORT_FILE_LINK_TEXT').'</a>';                
                $html_file = str_replace('{report_link}', $report_link, $html_file);
            } else {
                $html_file = str_replace('{report_link}', '', $html_file);
            }
            
            // View sum comments 
            if ($params->get('view_sum_jcomments') && $params->get('jcomments_active')){
                if ($comments_table_exist){
                    $db->setQuery('SELECT COUNT(*) from #__jcomments WHERE object_group = \'com_jdownloads\' AND object_id = '.$items[$i]->id);
                    $sum_comments = $db->loadResult();
                    if ($sum_comments >= 0){
                        $comments = sprintf(Text::_('COM_JDOWNLOADS_FRONTEND_JCOMMENTS_VIEW_SUM_TEXT'), $sum_comments); 
                        $html_file = str_replace('{sum_jcomments}', $comments, $html_file);
                    } else {
                        $html_file = str_replace('{sum_jcomments}', '', $html_file);
                    }
                } else {
                    $html_file = str_replace('{sum_jcomments}', '', $html_file);
                }    
            } else {   
                $html_file = str_replace('{sum_jcomments}', '', $html_file);
            }    

            if ($items[$i]->release == '' ) {
                $html_file = str_replace('{release}', '', $html_file);
            } else {
                $html_file = str_replace('{release}', $items[$i]->release.' ', $html_file);
            }

            // Display the thumbnails
            $html_file = JDHelper::placeThumbs($html_file, $items[$i]->images, 'list');
            
            // We change the old lightbox tag type to the new and added data-alt
            $html_file = str_replace('rel="lightbox"', 'data-lightbox="lightbox'.$items[$i]->id.'" data-alt="lightbox'.substr($items[$i]->images,0,-4).$i.'"', $html_file);                                                                
                                                                                                                 
            if ($params->get('auto_file_short_description') && $params->get('auto_file_short_description_value') > 0){
                 if (strlen($items[$i]->description) > $params->get('auto_file_short_description_value')){ 
                     $shorted_text=preg_replace("/[^ ]*$/", '..', substr($items[$i]->description, 0, $params->get('auto_file_short_description_value')));
                     $html_file = str_replace('{description}', $shorted_text, $html_file);
                 } else {
                     $html_file = str_replace('{description}', $items[$i]->description, $html_file);
                 }    
            } else {
                 $html_file = str_replace('{description}', $items[$i]->description, $html_file);
            }             
            
            // Compute for HOT symbol            
            if ($params->get('loads_is_file_hot') > 0 && $items[$i]->downloads >= $params->get('loads_is_file_hot') ){
                $html_file = str_replace('{pic_is_hot}', '<span class="jdbutton '.$status_color_hot.' jstatus">'.Text::_('COM_JDOWNLOADS_HOT').'</span>', $html_file);
            } else {    
                $html_file = str_replace('{pic_is_hot}', '', $html_file);
            }

            // Compute for NEW symbol
            $days_diff = JDHelper::computeDateDifference(date('Y-m-d H:i:s'), $items[$i]->created);
            if ($params->get('days_is_file_new') > 0 && $days_diff <= $params->get('days_is_file_new')){
                $html_file = str_replace('{pic_is_new}', '<span class="jdbutton '.$status_color_new.' jstatus">'.Text::_('COM_JDOWNLOADS_NEW').'</span>', $html_file);
            } else {    
                $html_file = str_replace('{pic_is_new}', '', $html_file);
            }
            
            // compute for UPDATED symbol
            // view it only when in the download is activated the 'updated' option
            if ($items[$i]->update_active) {
                $days_diff = JDHelper::computeDateDifference(date('Y-m-d H:i:s'), $items[$i]->modified);
                if ($params->get('days_is_file_updated') > 0 && $days_diff >= 0 && $days_diff <= $params->get('days_is_file_updated')){
                    $html_file = str_replace('{pic_is_updated}', '<span class="jdbutton '.$status_color_updated.' jstatus">'.Text::_('COM_JDOWNLOADS_UPDATED').'</span>', $html_file);
                } else {    
                    $html_file = str_replace('{pic_is_updated}', '', $html_file);
                }
            } else {
               $html_file = str_replace('{pic_is_updated}', '', $html_file);
            }    
                
            // media player
            if ($items[$i]->preview_filename){
                // we use the preview file when exist  
                $is_preview = true;
                $items[$i]->itemtype = JDHelper::getFileExtension($items[$i]->preview_filename);
                $is_playable    = JDHelper::isPlayable($items[$i]->preview_filename);
                $extern_media = false;
            } else {                  
                $is_preview = false;
                if ($items[$i]->extern_file){
                    $extern_media = true;
                    $items[$i]->itemtype = JDHelper::getFileExtension($items[$i]->extern_file);
                    $is_playable    = JDHelper::isPlayable($items[$i]->extern_file);
                } else {    
                    $items[$i]->itemtype = JDHelper::getFileExtension($items[$i]->url_download);
                    $is_playable    = JDHelper::isPlayable($items[$i]->url_download);
                    $extern_media = false;
                }  
            }            

            if ( $is_playable ){
                
                if ($params->get('html5player_use')){
                    // we will use the new HTML5 player option
                    if ($extern_media){
                        $media_path = $items[$i]->extern_file;
                    } else {        
                        if ($is_preview){
                            // we need the relative path to the "previews" folder
                            $media_path = $jdownloads_root_dir_name.'/'.$params->get('preview_files_folder_name').'/'.$items[$i]->preview_filename;
                        } else {
                            // we use the normal download file for the player
                            $media_path = $jdownloads_root_dir_name.'/'.$category_dir.'/'.$items[$i]->url_download;
                        }   
                    }    
                            
                    // create the HTML5 player
                    $player = JDHelper::getHTML5Player($items[$i], $media_path);
                    
                    // we use the player for video files only in listings, when the option allowed this
                    if ($params->get('html5player_view_video_only_in_details') && $items[$i]->itemtype != 'mp3' && $items[$i]->itemtype != 'wav' && $items[$i]->itemtype != 'oga'){
                        $html_file = str_replace('{mp3_player}', '', $html_file);
                        $html_file = str_replace('{preview_player}', '', $html_file);
                    } else {                            
                        if ($items[$i]->itemtype == 'mp4' || $items[$i]->itemtype == 'webm' || $items[$i]->itemtype == 'ogg' || $items[$i]->itemtype == 'ogv' || $items[$i]->itemtype == 'mp3' || $items[$i]->itemtype == 'wav' || $items[$i]->itemtype == 'oga'){
                            // We will replace at first the old placeholder when exist
                            if (strpos($html_file, '{mp3_player}')){
                                $html_file = str_replace('{mp3_player}', $player, $html_file);
                                $html_file = str_replace('{preview_player}', '', $html_file);
                            } else {                
                                $html_file = str_replace('{preview_player}', $player, $html_file);
                            }    
                        } else {
                            $html_file = str_replace('{mp3_player}', '', $html_file);
                            $html_file = str_replace('{preview_player}', '', $html_file);
                        }    
                    } 
                
                } else {
            
                    if ($params->get('flowplayer_use')){
                        // we will use the new flowplayer option
                        if ($extern_media){
                            $media_path = $items[$i]->extern_file;
                        } else {        
                            if ($is_preview){
                                // we need the relative path to the "previews" folder
                                $media_path = $jdownloads_root_dir_name.'/'.$params->get('preview_files_folder_name').'/'.$items[$i]->preview_filename;
                            } else {
                                // we use the normal download file for the player
                                $media_path = $jdownloads_root_dir_name.'/'.$category_dir.'/'.$items[$i]->url_download;
                            }   
                        }    

                        $ipadcode = '';

                        if ($items[$i]->itemtype == 'mp3'){
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
                        
                        $player = '<a href="'.$media_path.'" style="display:block;width:'.$params->get('flowplayer_playerwidth').'px;height:'.$playerheight.'px;" class="player" id="player'.$items[$i]->id.'"></a>';
                        $player .= '<script language="JavaScript">
                        // install flowplayer into container
                                    flowplayer("player'.$items[$i]->id.'", "'.Uri::base().'components/com_jdownloads/assets/flowplayer/flowplayer-3.2.16.swf",  
                                     {  
                            plugins: {
                                controls: {
                                    // insert at first the config settings
                                    // and now the basics
                                    fullscreen: '.$fullscreen.',
                                    height: '.(int)$params->get('flowplayer_playerheight_audio').',
                                    autoHide: '.$autohide.',
                                }
                                
                            },
                            clip: {
                                autoPlay: false,
                                // optional: when playback starts close the first audio playback
                                 onBeforeBegin: function() {
                                    $f("player'.$items[$i]->id.'").close();
                                }
                            }
                        })'.$ipadcode.'; </script>';
                        // the 'ipad code' above is only required for ipad/iphone users
                        
                        // we use the player for video files only in listings, when the option allowed this
                        if ($params->get('flowplayer_view_video_only_in_details') && $items[$i]->itemtype != 'mp3'){ 
                            $html_file = str_replace('{mp3_player}', '', $html_file);
                            $html_file = str_replace('{preview_player}', '', $html_file);            
                        } else {    
                            if ($items[$i]->itemtype == 'mp4' || $items[$i]->itemtype == 'flv' || $items[$i]->itemtype == 'mp3'){    
                                // We will replace at first the old placeholder when exist
                                if (strpos($html_file, '{mp3_player}')){
                                    $html_file = str_replace('{mp3_player}', $player, $html_file);
                                    $html_file = str_replace('{preview_player}', '', $html_file);
                                } else {
                                    $html_file = str_replace('{preview_player}', $player, $html_file);
                                }                                
                            } else {
                                $html_file = str_replace('{mp3_player}', '', $html_file);
                                $html_file = str_replace('{preview_player}', '', $html_file);
                            }
                        }
                    }
                }
            } 
        
            if ($params->get('mp3_view_id3_info') && $items[$i]->itemtype == 'mp3' && !$extern_media){
                // read mp3 infos
                if ($is_preview){
                    // get the path to the preview file
                    $mp3_path_abs = $params->get('files_uploaddir').'/'.$params->get('preview_files_folder_name').'/'.$items[$i]->preview_filename;
                } else {
                    // get the path to the downloads file
                    $mp3_path_abs = $params->get('files_uploaddir').'/'.$category_dir.'/'.$items[$i]->url_download;
                }
                
                $info = JDHelper::getID3v2Tags($mp3_path_abs);         
                if ($info){
                    // Add it
                    $mp3_info = '<div class="jd_mp3_id3tag_wrapper" style="max-width:'.(int)$params->get('html5player_audio_width').'px; ">'.stripslashes($params->get('mp3_info_layout')).'</div>';
                    $mp3_info = str_replace('{name_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_TITLE'), $mp3_info);
                    if ($is_preview){
                        $mp3_info = str_replace('{name}', $items[$i]->preview_filename, $mp3_info);
                    } else {
                        $mp3_info = str_replace('{name}', $items[$i]->url_download, $mp3_info);
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
                    $html_file = str_replace('{mp3_id3_tag}', $mp3_info, $html_file); 
                }     
            }
        
            $html_file = str_replace('{mp3_player}', '', $html_file);
            $html_file = str_replace('{preview_player}', '', $html_file);
            $html_file = str_replace('{mp3_id3_tag}', '', $html_file);             

            // Replace the {preview_url}
            if ($items[$i]->preview_filename){
                // We need the relative path to the "previews" folder
                $media_path = $jdownloads_root_dir_name.'/'.$params->get('preview_files_folder_name').'/'.$items[$i]->preview_filename;
                $html_file = str_replace('{preview_url}', $media_path, $html_file);
            } else {
                $html_file = str_replace('{preview_url}', '', $html_file);
            }              
            
            // Replace the placeholder {information_header}
            $html_file = str_replace('{information_header}', Text::_('COM_JDOWNLOADS_INFORMATION'), $html_file);
            
            // Build the license info data and build link
            if ($items[$i]->license == '') $items[$i]->license = 0;
            
            $lic_data = '';

            if ($items[$i]->license_url != '') {
                 $lic_data = $pic_license.'<a href="'.$items[$i]->license_url.'" target="_blank" rel="nofollow" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'">'.$items[$i]->license_title.'</a> '.$extern_url_pic;
            } else {
                if ($items[$i]->license_title != '') {
                    if ($items[$i]->license_text != '') {
                        $lic_data = $pic_license.$items[$i]->license_title;
                        $lic_data .= HTMLHelper::_('tooltip', $items[$i]->license_text, $items[$i]->license_title);
                    } else {
                        $lic_data = $pic_license.$items[$i]->license_title;
                    }
                } else {
                    $lic_data = '';
                }
            }
            $html_file = str_replace('{license_text}', $lic_data, $html_file);
            
            // Check box in "All downloads" not used
            $html_file = str_replace('{checkbox_list}', '', $html_file);

            $html_file = str_replace('{cat_id}', $items[$i]->catid, $html_file);
            $html_file = str_replace('{cat_title}', $items[$i]->category_title, $html_file);
            
            // File size
            if ($items[$i]->size == '' || $items[$i]->size == '0 B') {
                $html_file = str_replace('{size}', '', $html_file);
                $html_file = str_replace('{filesize_value}', '', $html_file);
            } else {
                $html_file = str_replace('{size}', $pic_size.$items[$i]->size, $html_file);
                $html_file = str_replace('{filesize_value}', $pic_size.$items[$i]->size, $html_file);
            } 
            
            // Price
            if ($items[$i]->price != '') {
                $html_file = str_replace('{price_value}', $pic_price.$items[$i]->price, $html_file);
            } else {
                $html_file = str_replace('{price_value}', '', $html_file);
            }

            // File date
            if ($items[$i]->file_date != '0000-00-00 00:00:00' && $items[$i]->file_date != null) {
                 if ($this->params->get('show_date') == 0){ 
                     $filedate_data = $pic_date.HTMLHelper::_('date',$items[$i]->file_date, $date_format['long']);
                 } else {
                     $filedate_data = $pic_date.HTMLHelper::_('date',$items[$i]->file_date, $date_format['short']);
                 }    
            } else {
                 $filedate_data = '';
            }
            $html_file = str_replace('{file_date}',$filedate_data, $html_file);
            
            // Creation date
            if ($items[$i]->created != '0000-00-00 00:00:00' && $items[$i]->created != null) {
                if ($this->params->get('show_date') == 0){ 
                    // Use 'normal' date-time format field
                    $date_data = $pic_date.HTMLHelper::_('date',$items[$i]->created, $date_format['long']);
                } else {
                    // Use 'short' date-time format field
                    $date_data = $pic_date.HTMLHelper::_('date',$items[$i]->created, $date_format['short']);
                }    
            } else {
                 $date_data = '';
            }
            
            $html_file = str_replace('{date_added}',$date_data, $html_file);
            $html_file = str_replace('{created_date_value}',$date_data, $html_file);
            
            // Created by
            if ($items[$i]->creator){
                $html_file = str_replace('{created_by_value}', $items[$i]->creator, $html_file);
            } else {
                $html_file = str_replace('{created_by_value}', '', $html_file);
            }                
            if ($items[$i]->modifier){
                $html_file = str_replace('{modified_by_value}', $items[$i]->modifier, $html_file);
            } else {                              
                $html_file = str_replace('{modified_by_value}', '', $html_file);
            }
            
            // Modified_date
            if ($items[$i]->modified != '0000-00-00 00:00:00' && $items[$i]->modified != null) {
                if ($this->params->get('show_date') == 0){ 
                    $modified_data = $pic_date.HTMLHelper::_('date',$items[$i]->modified, $date_format['long']);
                } else {
                    $modified_data = $pic_date.HTMLHelper::_('date',$items[$i]->modified, $date_format['short']);
                }    
            } else {
                $modified_data = '';
            }
            $html_file = str_replace('{modified_date_value}',$modified_data, $html_file);

            $user_can_see_download_url = 0;   
            $download_link = '';
            $license_pword_captcha_reqd = 0;
           
            // Only view download-url when user has correct access level
            if ($items[$i]->params->get('access-download') == true){ 
                
                $user_can_see_download_url++;
                
                $blank_window = '';
                $blank_window1 = '';
                $blank_window2 = '';
                
                // Get file extension
                $view_types = array();
                $view_types = explode(',', $params->get('file_types_view'));
                $only_file_name = basename($items[$i]->url_download);
                $fileextension = JDHelper::getFileExtension($only_file_name);
                if (in_array($fileextension, $view_types)){
                    $blank_window = 'target="_blank"';
                }    
                
                // Check is set link to a new window?
                if ($items[$i]->extern_file && $items[$i]->extern_site   ){
                    $blank_window = 'target="_blank"';
                }
				
                // Clear links
				$link_summary = '';
				$link_direct = '';
				$link_detail = '';
				
                // Check if options prescribe the detail display
                $require_licence_password_catpcha = $items[$i]->license_agree || $items[$i]->password || $jd_user_settings->view_captcha;

                // Direct download without summary page?
                if ($params->get('direct_download') == '0'){
                    $url_task = 'summary';
                    $download_link = Route::_(RouteHelper::getOtherRoute($items[$i]->slug, $items[$i]->catid, $items[$i]->language, $url_task));
					$link_summary = $download_link;
                } else {
                    if ($require_licence_password_catpcha) {
                        // User must agree the license - fill out a password field - or fill out the captcha human check - so we must view the summary page!
                        $url_task = 'summary';
                        $download_link = Route::_(RouteHelper::getOtherRoute($items[$i]->slug, $items[$i]->catid, $items[$i]->language, $url_task));
						// Link to summary page
                        $link_summary = $download_link;
                    } else {     
                        // Direct download
                        $url_task = 'download.send';  
                        $download_link = Route::_('index.php?option=com_jdownloads&amp;task=download.send&amp;id='.$items[$i]->id.'&amp;catid='.$items[$i]->catid.'&amp;m=0');
						// Link to download directly
                        $link_direct = $download_link;  
                    }    
                }    
                
                // Need to setup for license etc check separately to force summary view
                if ($require_licence_password_catpcha) {
					// We have license or password or captcha
                    $license_pword_captcha_reqd = '1';  
				} else {
					// We do NOT have license or password or captcha
                    $license_pword_captcha_reqd = '0';  
                }                    
                
                // When we have not a menu item to the single download, we need a menu item from the assigned category, or at lates the root itemid
                if ($items[$i]->menuf_itemid){
                    $file_itemid =  (int)$items[$i]->menuf_itemid;
                } else {
                    $file_itemid = $category_menu_itemid;
                }                      
                
                // Create Download Button
                // And add action hints for better accessibility check result! 
                if ($url_task == 'download.send'){ 
                    $download_link_text = '<a '.$blank_window.' href="'.$download_link.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_DIRECT').'" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color.' '.$download_size_listings.'">';
                } else {
                    $download_link_text = '<a href="'.$download_link.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_SUMMARY').'" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color.' '.$download_size_listings.'">';
                }    
                
                // When Download has no file or Download is not enabled then do not show button
                if ($has_no_file || !$items[$i]->state){
                    // Remove download button placeholder
                    $html_file = str_replace('{url_download}', '', $html_file);  
                } else {
                     // Insert here the complete download link for the Download Button 
                     $html_file = str_replace('{url_download}',$download_link_text.$pic_downloads.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'</a>', $html_file);  
                }    
                
                // View mirrors number 1 - but only when is published
                if ($items[$i]->mirror_1 && $items[$i]->state) {
                    if ($items[$i]->extern_site_mirror_1 && $url_task == 'download.send'){
                        $blank_window1 = 'target="_blank"';
                    }
                    $mirror1_link_dum = Route::_('index.php?option=com_jdownloads&amp;task=download.send&amp;id='.$items[$i]->id.'&amp;catid='.$items[$i]->catid.'&amp;m=1');
                    $mirror1_link = '<a '.$blank_window1.' href="'.$mirror1_link_dum.'" alt="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color_mirror1.' '.$download_size_mirror.'">'.Text::_('COM_JDOWNLOADS_FRONTEND_MIRROR_URL_TITLE_1').'</a>'; 
                    $html_file = str_replace('{mirror_1}', $mirror1_link, $html_file);
                } else {
                    $html_file = str_replace('{mirror_1}', '', $html_file);
                }
                 
                // View mirrors number 2 - but only when is published
                if ($items[$i]->mirror_2 && $items[$i]->state) {
                    if ($items[$i]->extern_site_mirror_2 && $url_task == 'download.send'){
                        $blank_window2 = 'target="_blank"';
                    }
                    $mirror2_link_dum = Route::_('index.php?option=com_jdownloads&amp;task=download.send&amp;id='.$items[$i]->id.'&amp;catid='.$items[$i]->catid.'&amp;m=2');
                    $mirror2_link = '<a '.$blank_window2.' href="'.$mirror2_link_dum.'" alt="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color_mirror2.' '.$download_size_mirror.'">'.Text::_('COM_JDOWNLOADS_FRONTEND_MIRROR_URL_TITLE_2').'</a>'; 
                    $html_file = str_replace('{mirror_2}', $mirror2_link, $html_file);
                } else {
                    $html_file = str_replace('{mirror_2}', '', $html_file);
                }            
            } else {
                $html_file = str_replace('{url_download}', '', $html_file);
                $html_file = str_replace('{mirror_1}', '', $html_file); 
                $html_file = str_replace('{mirror_2}', '', $html_file);
                 
                $blank_window = '';
                $link_summary = '';
                $link_direct = '';
                $link_detail = ''; 
            }

            // -------------------------- Start of sorting out links in title and symbol pic -------------------
            
            // $link_detail has link for details view
            // $link_summary has link for summary view
            // $link_direct has link for direct download
            
			// Now get the link in the title 
            $title_link = Route::_(RouteHelper::getDownloadRoute($items[$i]->slug, $items[$i]->catid, $items[$i]->language));
			
            // Used in placeholder {link_to_details} This is the Read More link
            $detail_link_text = '<a href="'.$title_link.'">'.Text::_('COM_JDOWNLOADS_FE_DETAILS_LINK_TEXT_TO_DETAILS').'</a>';  
			
            if ($params->get('view_detailsite')){
                $title_link_text = '<a href="'.$title_link.'">'.$this->escape($items[$i]->title).'</a>';
			}  else {
				$title_link_text = $this->escape($items[$i]->title);
			}
	
			// Use if detail link required
            $link_detail = $title_link;   
			
	        // Sort out which link is required and place in in $link_required
			if ($params->get('direct_download')) {
				if ($params->get('view_detailsite')) {
					$link_required = $link_detail;   //direct download = Yes and view_detailsite = Yes
				} else {
					  //direct download = Yes and view_detailsite = No
					if ($has_no_file) {
						$link_required = $link_detail;  //special case when no file so force link to detail
                } else {
						$link_required = $link_direct;   //normally direct download
					}
                }
            } else {
                if ($params->get('view_detailsite')){
					// Direct download = No and view_detailsite = Yes
				    $link_required = $link_detail;
                } else {                                                                                                                           
					// Direct download = No and view_detailsite = No
					if (!$has_no_file) {
						// Special case when no file force link to summary
                        $link_required = $link_summary;  
                    } else {
				        // Normally link to detail
						$link_required = $link_detail;
                    }
                }
            }
			
			// If licence, password or captcha required and Download has a file then force summary view 
			if ($license_pword_captcha_reqd  && !$has_no_file) {
				$link_required = $link_summary;
            }    
			
            // See what we need for link in symbol
			if ($params->get('link_in_symbols')){
				$pic_link = '<a '.$blank_window.' href="'.$link_required.'">';
				$pic_end = '</a>';
            } else {
                $pic_link = '';
                $pic_end = '';
            }
			
            // Check there is a symbol pic
            if ($items[$i]->file_pic != '' ) {
                $filepic = $pic_link.'<img src="'.$file_pic_folder.$items[$i]->file_pic.'" style="text-align:top;border:0px;" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" alt="'.substr($items[$i]->file_pic,0,-4).$i.'" />'.$pic_end;
            } else {
                $filepic = '';
            }
            $html_file = str_replace('{file_pic}', $filepic, $html_file);
		
        	// Now we check whether we need a link in the title to start the download process, a link to the detail page or the title without a link.
            if ($params->get('use_download_title_as_download_link')){
				$download_link_text = '<a '.$blank_window.' href="'.$link_required.'" title="'.JText::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jd_download_url">'.$items[$i]->title.'</a>';
			} elseif ($params->get('view_detailsite')) {
                $download_link_text = $title_link_text;
            } else {
				$download_link_text = $items[$i]->title;
            }             

            // Add edit symbol as defined earlier;            
			$download_link_text .= $editIcon;   
			$html_file = str_replace('{file_title}',$download_link_text, $html_file);
            
            // Add access level
            $html_file = str_replace('{access_title}', Text::_('COM_JDOWNLOADS_ACCESS'), $html_file);
            $userhelper = new UserGroupsHelper();
            $user_group  = $userhelper->load($items[$i]->access);              
            $html_file = str_replace('{access}', $user_group->title, $html_file);	
			
            //--------------------- End of sorting out the title and symbol links ---------------------

            // The link to detail view is always displayed - when not required must be removed the placeholder from the layout
            // Used in placeholder {link_to_details} This is the Read More link
            $html_file = str_replace('{link_to_details}', $detail_link_text, $html_file);	  
            
            // Build website url
            if (!$items[$i]->url_home == '') {
                 if (strpos($items[$i]->url_home, 'http://') !== false) {    
                     $html_file = str_replace('{url_home}',$pic_website.'<a href="'.$items[$i]->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $html_file);
                     $html_file = str_replace('{author_url_text} ',$pic_website.'<a href="'.$items[$i]->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $html_file);
                 } else {
                     $html_file = str_replace('{url_home}',$pic_website.'<a href="http://'.$items[$i]->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $html_file);
                     $html_file = str_replace('{author_url_text}',$pic_website.'<a href="http://'.$items[$i]->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $html_file);
                 }    
            } else {
                $html_file = str_replace('{url_home}', '', $html_file);
                $html_file = str_replace('{author_url_text}', '', $html_file);
            }

            // Encode is link a mail
            if (strpos($items[$i]->url_author, '@') && $params->get('mail_cloaking')){
                if (!$items[$i]->author) { 
                    $mail_encode = HTMLHelper::_('email.cloak', $items[$i]->url_author);
                } else {
                    $mail_encode = HTMLHelper::_('email.cloak',$items[$i]->url_author, true, $items[$i]->author, false);
                }        
            } else {
                $mail_encode = '';
            }
                    
            // Build author link
            if ($items[$i]->author <> ''){
                if ($items[$i]->url_author <> '') {
                    if ($mail_encode) {
                        $link_author = $pic_author.$mail_encode;
                    } else {
                        if (strpos($items[$i]->url_author, 'http://') !== false) {    
                            $link_author = $pic_author.'<a href="'.$items[$i]->url_author.'" target="_blank">'.$items[$i]->author.'</a> '.$extern_url_pic;
                        } else {
                            $link_author = $pic_author.'<a href="http://'.$items[$i]->url_author.'" target="_blank">'.$items[$i]->author.'</a> '.$extern_url_pic;
                        }        
                    }
                    $html_file = str_replace('{author}',$link_author, $html_file);
                    $html_file = str_replace('{author_text}',$link_author, $html_file);
                    $html_file = str_replace('{url_author}', '', $html_file);
                } else {
                    $link_author = $pic_author.$items[$i]->author;
                    $html_file = str_replace('{author}',$link_author, $html_file);
                    $html_file = str_replace('{author_text}',$link_author, $html_file);
                    $html_file = str_replace('{url_author}', '', $html_file);
                }
            } else {
                $html_file = str_replace('{url_author}', $pic_author.$items[$i]->url_author, $html_file);
                $html_file = str_replace('{author}','', $html_file);
                $html_file = str_replace('{author_text}','', $html_file); 
            }

            // Set system value
            $file_sys_values = explode(',' , JDHelper::getOnlyLanguageSubstring($params->get('system_list')));
            if ($items[$i]->system == 0 ) {
                $html_file = str_replace('{system}', '', $html_file);
                 $html_file = str_replace('{system_text}', '', $html_file); 
            } else {
                $html_file = str_replace('{system}', $pic_system.$file_sys_values[$items[$i]->system], $html_file);
                $html_file = str_replace('{system_text}', $pic_system.$file_sys_values[$items[$i]->system], $html_file);
            }

            // Set language value
            $file_lang_values = explode(',' , JDHelper::getOnlyLanguageSubstring($params->get('language_list')));
            if ($items[$i]->file_language == 0 ) {
                $html_file = str_replace('{language}', '', $html_file);
                $html_file = str_replace('{language_text}', '', $html_file);
            } else {
                $html_file = str_replace('{language}', $pic_language.$file_lang_values[$items[$i]->file_language], $html_file);
                $html_file = str_replace('{language_text}', $pic_language.$file_lang_values[$items[$i]->file_language], $html_file);
            }

            // Insert rating system
            if ($params->get('view_ratings')){
                $rating_system = JDHelper::getRatings($items[$i]->id, $user_can_see_download_url, $items[$i]->rating_count, $items[$i]->rating_sum);
                $html_file = str_replace('{rating}', $rating_system, $html_file);
                $html_file = str_replace('{rating_title}', Text::_('COM_JDOWNLOADS_RATING_LABEL'), $html_file);
            } else {
                $html_file = str_replace('{rating}', '', $html_file);
                $html_file = str_replace('{rating_title}', '', $html_file);
            }
            
            // Remove the old custom fields placeholder
            for ($x=1; $x<15; $x++){
                $html_file = str_replace("{custom_title_$x}", '', $html_file);
                $html_file = str_replace("{custom_value_$x}", '', $html_file);
            }             
            
            $html_file = str_replace('{downloads}', $pic_downloads.JDHelper::strToNumber((int)$items[$i]->downloads), $html_file);
            $html_file = str_replace('{hits_value}', $pic_downloads.JDHelper::strToNumber((int)$items[$i]->downloads), $html_file);            
            $html_file = str_replace('{ordering}', $items[$i]->ordering, $html_file);
            $html_file = str_replace('{published}', $items[$i]->published, $html_file);
            
            // Support for content plugins 
            if ($params->get('activate_general_plugin_support')) {  
                $html_file = HTMLHelper::_('content.prepare', $html_file, '', 'com_jdownloads.downloads');
            }

            // Add the content plugin event 'after display content'
            if (strpos($html_file, '{after_display_content}') > 0){
                $html_file = str_replace('{after_display_content}', $items[$i]->event->afterDisplayContent, $html_file);
                $event = '';
            } else {
                $event = $items[$i]->event->afterDisplayContent;    
            }

            $html_files .= $html_file;
            
            // Finally add the 'after display content' event output when required
            $html_files .= $event;
        }

        // Display only downloads area when it exist data here
        if ($total_downloads > 0){
            $body = $html_files;
        } else {
            $no_files_msg = '';
            if ($params->get('view_no_file_message_in_empty_category')){
                $no_files_msg = '<br />'.Text::_('COM_JDOWNLOADS_FRONTEND_NOFILES').'<br /><br />';            
            } 
            $body = $no_files_msg;
        }    

        // Check box in "All downloads" not used
        $body = str_replace('{checkbox_top}', '', $body);                    
        
        // View submit button only when checkboxes are activated
        $button = '<input class="button" type="submit" name="weiter" value="'.Text::_('COM_JDOWNLOADS_FORM_BUTTON_TEXT').'"/>';
        
        // View only submit button when user has correct access level and checkboxes are used in layout
        if ($layout_files->checkbox_off == 0 && !empty($items)) {
            $body = str_replace('{form_button}', $button, $body);
        } else {
            $body = str_replace('{form_button}', '', $body);
        }        
        
        $html .= $body;   
        $html .= '</div>';  
        
    }    
        
  
    // ==========================================
    // FOOTER SECTION  
    // ==========================================

    // Display pagination for the Downloads when the placeholder is placed in the footer area from the Downloads layout 
    $footer = JDHelper::insertPagination( $this->pagination, $footer, $params->get('option_navigate_bottom'), $this->params->get('show_pagination'), $this->params->get('show_pagination_results') );

    // Components footer text
    if ($params->get('downloads_footer_text') != '') {
        $footer_text = stripslashes(JDHelper::getOnlyLanguageSubstring($params->get('downloads_footer_text')));
        
        // Replace both Google adsense placeholder with script
        $footer_text = JDHelper::insertGoogleAdsenseCode($footer_text);
        $html .= $footer_text;
    }
    
    // Back button
    if ($params->get('view_back_button')){
        $footer = str_replace('{back_link}', '<a href="javascript:history.go(-1)">'.Text::_('COM_JDOWNLOADS_FRONTEND_BACK_BUTTON').'</a>', $footer); 
    } else {
        $footer = str_replace('{back_link}', '', $footer);
    }    
    
    // Cart option active?
    if ($this->cartplugin && $params->get('use_shopping_cart_plugin')){
        $cart = '<div class="clr"></div>
        <a id="jdownloadscart"></a>

        <h2>'.Text::_('COM_JDOWNLOADS_YOUR_CART').':
            (<span class="simpleCart_quantity"></span> '.Text::_('COM_JDOWNLOADS_ITEMS').')</h2>

        <div class="cart_yourcart">
            <div class="cart_yourcart_items">
                <div class="simpleCart_items">
                
                </div>
                <div class="cart_totals">
                    <div class="cart_summary"><span class="cart_checkout_label">'.Text::_('COM_JDOWNLOADS_SUB_TOTAL').':</span> <span class="simpleCart_total"></span></div>
                    
                    <div class="cart_summary cart_summary_total"><span class="cart_checkout_label">'.Text::_('COM_JDOWNLOADS_TOTAL').':</span> <span class="simpleCart_grandTotal"></span></div>
                </div>

                <div class="cart_buttons">
                    <a href="javascript:;" class="simpleCart_empty btn button"><i class="icon-trash"></i> '.Text::_('COM_JDOWNLOADS_EMPTY_CART').'<span></span></a>
                    <a href="javascript:;" class="simpleCart_checkout btn button btn-danger"><i class="icon-cart"></i> '.Text::_('COM_JDOWNLOADS_CHECKOUT').'<span></span></a>
                </div>
            </div>
        </div>';
        
        $footer = str_replace('{cart}', $cart, $footer);
    } else {
        $footer = str_replace('{cart}', '', $footer);
    }
    
    $footer .= JDHelper::checkCom();
   
    $html .= $footer; 
    
    if ($this->cartplugin && $params->get('use_shopping_cart_plugin')){
    	$html .= '</div>';
    }

    // Remove empty html tags
    if ($params->get('remove_empty_tags')){
        $html = JDHelper::removeEmptyTags($html);
    }
    
    // ==========================================
    // VIEW THE BUILDED OUTPUT
    // ==========================================

    if ( !$params->get('offline') ) {
            echo $html;
    } else {
        // Admins can view it always
        if ($is_admin) {
            echo $html;     
        } else {
            // Build the offline message
            $html = '';
            // Offline message
            if ($params->get('offline_text') != '') {
                $html .= JDHelper::getOnlyLanguageSubstring($params->get('offline_text'));
            }
            echo $html;    
        }
    }     
    
?>