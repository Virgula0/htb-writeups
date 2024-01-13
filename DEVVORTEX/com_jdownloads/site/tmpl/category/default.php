<?php
/**
 * @package jDownloads
 * @version 4.0  
 * @copyright (C) 2007 - 2023 - Arno Betz - www.jdownloads.com
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

// Calculate number of items including sub-categories using a recursive anonymous function
$countSubItems = function( $item ) use ( &$countSubItems ) {
    $children = $item->getChildren();
    if( count($children) == 0 ) {
        return $item->numitems;
    } else {
        $subItems = 0;
        foreach ($children as $child) {
            $subItems += $countSubItems($child);
        }
        return $subItems = $item->numitems + $subItems;
    }
};

 
    $app    = Factory::getApplication();
    $params = $app->getParams();

    HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers');

    // For Tooltip
    HTMLHelper::_('bootstrap.tooltip');
    // Required for sub categories pagination
    HTMLHelper::_('jquery.framework');
    
    $db         = Factory::getDBO(); 
    $document   = Factory::getDocument();
    $jinput     = Factory::getApplication()->input;
    $user       = Factory::getUser();
    $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;

    $jdownloads_root_dir_name = basename($params->get('files_uploaddir'));
    
    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($params->get('selected_file_type_icon_set'));
    
    // Get jD user limits and settings
    $jd_user_settings = $this->jd_user_settings;
    
    $listOrder = str_replace('a.', '', $this->escape($this->state->get('list.ordering')));    
    $listDirn  = $this->escape($this->state->get('list.direction'));
    
    // Create shortcuts to some parameters.
    $cat_params = $this->category->params;
    $files      = $this->items;
    $category   = $this->category;
    $canEdit    = $this->category->params->get('access-create');
    $layouts    = $this->layouts;

    $html                       = '';
    $body                       = '';
    $cat_footer_text            = '';
    $cat_layout                 = '';
    $mail_encode                = '';
    $link_author                = '';
    $listorder_bar              = '';
    
    $user_can_see_download_url = 0;
    $is_admin                   = false;
    
    $date_format = JDHelper::getDateFormat();
    
    $result = $app->triggerEvent('onContentAfterTitle', array('com_jdownloads.category', &$this->category, &$this->params, 0));

    $catAfterDisplayTitle = trim(implode("\n", $result));

    $result = $app->triggerEvent('onContentBeforeDisplay', array('com_jdownloads.category', &$this->category, &$this->params, 0));
    $catBeforeDisplayContent = trim(implode("\n", $result));

    $result = $app->triggerEvent('onContentAfterDisplay', array('com_jdownloads.category', &$this->category, &$this->params, 0));
    $catAfterDisplayContent = trim(implode("\n", $result));
    
    // Get the needed 'sub categories' layout           
    $subcat_layout = $layouts['subcategory'];
    if ($subcat_layout){
        // We need here not all fields
        // But unused language placeholders must at first get removed from layout
        $cats_before            = JDHelper::removeUnusedLanguageSubstring($subcat_layout->template_before_text); 
        $cats_layout_text       = JDHelper::removeUnusedLanguageSubstring($subcat_layout->template_text);
        $cats_after             = JDHelper::removeUnusedLanguageSubstring($subcat_layout->template_after_text);
    } else {
        // We have not a valid layout data
        echo '<big>No valid layout found for Categories!</big>';
    }

    // Get the needed 'category' layout            
    $cat_layout = $layouts['category'];
    if ($cat_layout){
        // Unused language placeholders must at first get removed from layout
        $cat_layout_before_text = JDHelper::removeUnusedLanguageSubstring($cat_layout->template_before_text);
        $cat_layout_text        = JDHelper::removeUnusedLanguageSubstring($cat_layout->template_text);
        $cat_after              = JDHelper::removeUnusedLanguageSubstring($cat_layout->template_after_text);
        $cat_header             = JDHelper::removeUnusedLanguageSubstring($cat_layout->template_header_text);
        $cat_subheader          = JDHelper::removeUnusedLanguageSubstring($cat_layout->template_subheader_text);
        $cat_footer             = JDHelper::removeUnusedLanguageSubstring($cat_layout->template_footer_text);
    } else {
        // We have not a valid layout data
        echo '<big>No valid layout found for Category!</big>';
    }
    
    // Get the needed 'files' layout            
    $layout_files = $layouts['files'];
    if ($layout_files){
        // Unused language placeholders must at first get removed from layout
        $files_layout_text        = JDHelper::removeUnusedLanguageSubstring($layout_files->template_text);
        $layout_files_header      = JDHelper::removeUnusedLanguageSubstring($layout_files->template_header_text);
        $layout_files_subheader   = JDHelper::removeUnusedLanguageSubstring($layout_files->template_subheader_text);
        $layout_files_footer      = JDHelper::removeUnusedLanguageSubstring($layout_files->template_footer_text);
    } else {
        // We have not a valid layout data
        echo '<big>No valid layout found for files!</big>';
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
    
    // Required to add later access name
    $userhelper = new UserGroupsHelper();
    
    // The current category ID to be displayed
    $catid                      = (int)$this->category->id;
    
    $total_downloads            = $this->pagination->total; 
    $total_subcategories        = count($this->children[$this->category->id]);
    
    // The amount of total files for all subcategoeries are stored in:
    // $this->children[$this->category->id][$i]->numitems;
    // The amount of total subcategories for this category (all levels) are stored in:
    // $this->children[$this->category->id][$i]->subcatitems;    
    
    if ($category->cat_dir_parent){
        $category_dir = $category->cat_dir_parent.'/'.$category->cat_dir;
    } else {
        $category_dir = $category->cat_dir;
    }    
    
    // Get current category menu ID when exist and all needed menu IDs for the header links
    $menuItemids = JDHelper::getMenuItemids($catid);
    
    // Get all other menu category IDs so we can use it when we needs it
    $cat_link_itemids = JDHelper::getAllJDCategoryMenuIDs();
    
    // Which use shall be use for "Overview" link item in header (type=all categories or a link to single category when it is existent as menu item)
    if ($this->params->get('use_type_all_categories_as_base_link')){
        $root_menu_itemid = (int)$menuItemids['base'];
    } else {
        $root_menu_itemid = (int)$menuItemids['root'];
    }

    // Make sure, that we have a valid menu itemid for the here viewed base category
    if (!$this->category->menu_itemid) $this->category->menu_itemid = $root_menu_itemid;
    
    $html = '<div class="jd-item-page'.$this->pageclass_sfx.'">';
    
    if ($this->params->get('show_page_heading')) {
        $html .= '<h1>'.$this->escape($this->params->get('page_heading')).'</h1>';
    }         
     
    // ==========================================
    // HEADER SECTION
    // ==========================================

    if ($cat_header != ''){
       
        // Component title - not more used. So we must replace the placeholder from layout with spaces!
        $cat_header = str_replace('{component_title}', '', $cat_header);
        
        // Replace both Google adsense placeholder with script
        $cat_header = JDHelper::insertGoogleAdsenseCode($cat_header);        
        
        // Components description
        if ($params->get('downloads_titletext') != '') {
            $cat_header_text = stripslashes(JDHelper::getOnlyLanguageSubstring($params->get('downloads_titletext')));
            
            // Replace both Google adsense placeholder with script
            $cat_header_text = JDHelper::insertGoogleAdsenseCode($cat_header_text);        
            $cat_header .= $cat_header_text;
        }
        
        // Check $Itemid exist
        if (!isset($menuItemids['search'])) $menuItemids['search'] = $menuItemids['root'];
        if (!isset($menuItemids['upload'])) $menuItemids['upload'] = $menuItemids['root'];
        
        // Build home link        
        $home_link = '<a href="'.Route::_('index.php?option=com_jdownloads&amp;Itemid='.$root_menu_itemid).'" title="'.Text::_('COM_JDOWNLOADS_HOME_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_home_symbol.Text::_('COM_JDOWNLOADS_HOME_LINKTEXT').'</span>'.'</a>';
        
        // Build search link
        $search_link = '<a href="'.Route::_('index.php?option=com_jdownloads&amp;view=search&amp;Itemid='.$menuItemids['search']).'" title="'.Text::_('COM_JDOWNLOADS_SEARCH_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_search_symbol.Text::_('COM_JDOWNLOADS_SEARCH_LINKTEXT').'</span>'.'</a>';        
        
        // Build frontend upload link
        $upload_link = '<a href="'.Route::_('index.php?option=com_jdownloads&amp;view=form&amp;layout=edit&amp;catid='.$catid.'&amp;Itemid='.$menuItemids['upload']).'"  title="'.Text::_('COM_JDOWNLOADS_UPLOAD_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_upload_symbol.Text::_('COM_JDOWNLOADS_UPLOAD_LINKTEXT').'</span>'.'</a>';
        
        $cat_header = str_replace('{home_link}', $home_link, $cat_header);
        $cat_header = str_replace('{search_link}', $search_link, $cat_header);

        if ($jd_user_settings->uploads_view_upload_icon){
            if ($this->view_upload_button){
                $cat_header = str_replace('{upload_link}', $upload_link, $cat_header);
            } else {
                $cat_header = str_replace('{upload_link}', '', $cat_header);
            }             
        } else {
            $cat_header = str_replace('{upload_link}', '', $cat_header);
        }    

        // This value is not a menu Itemid but a category ID
        if ($menuItemids['upper'] > 1){   // 1 is 'root' category
            // Exists a single category menu link for the category a level up? 
            $level_up_cat_itemid = JDHelper::getSingleCategoryMenuID($cat_link_itemids, $menuItemids['upper'], $root_menu_itemid);
            $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=category&amp;catid='.$menuItemids['upper'].'&amp;Itemid='.$level_up_cat_itemid);
        } else {
            $upper_link = Route::_('index.php?option=com_jdownloads&amp;view=categories&amp;Itemid='.$menuItemids['base']);
        }
        $cat_header = str_replace('{upper_link}', '<a href="'.$upper_link.'"  title="'.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT_HINT').'">'.'<span class="jdbutton '.$menu_color.' '.$menu_size.'">'.$span_upper_symbol.Text::_('COM_JDOWNLOADS_UPPER_LINKTEXT').'</span>'.'</a>', $cat_header);    
                    
        // Create category listbox and viewed it when it is activated in configuration
        if ($params->get('show_header_catlist')){
            
            // Get current selected cat id from listbox
            $catlistid = $jinput->get('catid', '0', 'integer');
            $subcat_listid = $jinput->get('subcatid', '0', 'integer');
            
            // Get current sort order and direction
            $orderby_pri = $this->params->get('orderby_pri');
            // When empty get the state params
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
            $main_categories = JDHelper::buildCategorySelectBox($catlistid, $cat_link_itemids, $root_menu_itemid, $params->get('view_empty_categories, 1'), $orderby_pri );            
            
            // Build special selectable URLs for category listbox
            $root_url       = Route::_('index.php?option=com_jdownloads&Itemid='.$root_menu_itemid);
            $allfiles_url   = str_replace('Itemid[0]', 'Itemid', Route::_('index.php?option=com_jdownloads&view=downloads&Itemid='.$root_menu_itemid));
            $topfiles_url   = str_replace('Itemid[0]', 'Itemid', Route::_('index.php?option=com_jdownloads&view=downloads&type=top&Itemid='.$root_menu_itemid));
            $newfiles_url   = str_replace('Itemid[0]', 'Itemid', Route::_('index.php?option=com_jdownloads&view=downloads&type=new&Itemid='.$root_menu_itemid));
            
            $listbox = HTMLHelper::_('select.genericlist', $main_categories['options'], 'cat_list', 'class="form-select" title="'.Text::_('COM_JDOWNLOADS_SELECT_A_VIEW').'" onchange="gocat(\''.$root_url.'\', \''.$allfiles_url.'\', \''.$topfiles_url.'\',  \''.$newfiles_url.'\'  ,\''.$main_categories['url'].'\')"', 'value', 'text', $main_categories['selected'] ); 
            
            $cat_header = str_replace('{category_listbox}', '<form name="go_cat" id="go_cat" method="post">'.$listbox.'</form>', $cat_header);
            
            // Build the sub categories listbox when required
            if ($this->children && strpos($cat_header, '{sub_category_listbox}')){
                $sub_categories = JDHelper::buildSubCategorySelectBox($catlistid, $subcat_listid, $this->children, $cat_link_itemids, $root_menu_itemid, $params->get('view_empty_categories'), $orderby_pri );            
                // Check whether we have an extra menu item for this sub category
                $single_sub_cat_menu = JDHelper::array_multi_search($catlistid, $cat_link_itemids, 'catid');
                if ($single_sub_cat_menu){
                    $sub_cat_url = $single_sub_cat_menu[0]['link'];
                } else {
                    $sub_cat_url = $root_url;
                }
                $listbox = HTMLHelper::_('select.genericlist', $sub_categories['options'], 'sub_cat_list', 'class="inputbox" title="'.Text::_('COM_JDOWNLOADS_FE_SELECT_SUB_CATEGORY').'" onchange="gosubcat(\''.$sub_cat_url.'\', \''.$sub_categories['url'].'\')"', 'value', 'text', $sub_categories['selected'] );     
                $cat_header = str_replace('{sub_category_listbox}', '<form name="go_sub_cat" id="go_sub_cat" method="post">'.$listbox.'</form>', $cat_header);    
            } else {
                $cat_header = str_replace('{sub_category_listbox}', '', $cat_header);
            }
            
        } else {                                                                        
            $cat_header = str_replace('{category_listbox}', '', $cat_header);         
            $cat_header = str_replace('{sub_category_listbox}', '', $cat_header);         
        }
        
        $html .= $cat_header;  

    }

    // ==========================================
    // SUB HEADER SECTION
    // ==========================================

    if ($layout_files_subheader != ''){
        
        // To be compatible to the 3.2 series we use also here the subheader part from the files layout ($layout_files_subheader).
        
        $cat_subheader = $layout_files_subheader;
        
        // Display number of sub categories only when > 0 
        if ($total_subcategories == 0){
            $total_subcats_text = '';
        } else {
            $total_subcats_text = Text::_('COM_JDOWNLOADS_NUMBER_OF_SUBCATS_LABEL').': '.$total_subcategories;
        }
        
        $cat_subheader = str_replace('{subheader_title}', Text::_('COM_JDOWNLOADS_FRONTEND_SUBTITLE_OVER_ONE_CAT').': '.$this->category->title, $cat_subheader);
        
        // Display number of sub categories from the current category
        $cat_subheader = str_replace('{count_of_sub_categories}', $total_subcats_text, $cat_subheader);
        
         // Display category title
        $cat_subheader = str_replace('{subheader_title}', $this->category->title, $cat_subheader);
        
		// Display pagination for the Downloads from the current category when it is an old category layout (< 3.9)
        $cat_subheader = JDHelper::insertPagination( $this->pagination, $cat_subheader, $params->get('option_navigate_top'), $this->params->get('show_pagination'), $this->params->get('show_pagination_results') );

        // Display sort order bar for the Downloads from the current category when it is an old category layout (< 3.9)
        if ($params->get('view_sort_order') && $total_downloads > 1 && $this->params->get('show_sort_order_bar') != '0'
        || (!$params->get('view_sort_order') && $this->pagination->pagesTotal > 1 && $this->params->get('show_sort_order_bar') == '1') )
        {
           // We must have at minimum a single field for sorting
           $sortorder_fields = $params->get('sortorder_fields');
           
           if ($sortorder_fields){
               if (!is_array($sortorder_fields)){
               	   $sortorder_fields = explode(',', $sortorder_fields);
               }
           } else {
               $sortorder_fields = array();
           }
           
           if (count($sortorder_fields)){
               $limitstart = $this->pagination->limitstart;
               
               // Create form for sort order bar
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

               $listorder_bar = $sort_form.Text::_('COM_JDOWNLOADS_FE_SORT_ORDER_TITLE').' <br />';
                                
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
               // Remove | at the end
               $len = StringHelper::strlen($listorder_bar);
               $pos = strripos($listorder_bar, "|");
               $diff = $len - $pos;
               if ($pos > 0 && $diff == 9){
                   $listorder_bar = StringHelper::substr($listorder_bar, 0, ($len - $diff)).'</span>';  
               } 
               // Add hidden fields
               $listorder_bar = $listorder_bar.$sort_form_hidden;
                                  
               $cat_subheader = str_replace('{sort_order}', $listorder_bar, $cat_subheader);
           } else {
               $cat_subheader = str_replace('{sort_order}', '', $cat_subheader);          
           }
        } else {   
           $cat_subheader = str_replace('{sort_order}', '', $cat_subheader);          
        }    
           
        // Replace both Google adsense placeholder with script
        $cat_subheader = JDHelper::insertGoogleAdsenseCode($cat_subheader);        
        $html .= $cat_subheader;            
    }
    
    // ==========================================
    // BODY SECTION - VIEW THE SELECTED CATEGORY DATA
    // ==========================================
    
    if ($cat_layout_text != ''){
 
        $body_cat = $cat_layout_before_text;
        $body_cat .= $cat_layout_text;
   
        // ===================
        // Category Data
        // ===================
   
        // Get category pic
        if ($this->category->pic != '' ) {
            $catpic = '<img src="'.URI::base().'images/jdownloads/catimages/'.$this->category->pic.'" style="text-align:top;border:0px;" width="'.$params->get('cat_pic_size').'" height="'.$params->get('cat_pic_size_height').'" alt="'.substr($this->category->pic,0,-4).'"/>';
        } else {
            $catpic = '';
        }

        // Display category info  - but make sure, that this option only used with single column layouts
        if ($params->get('view_category_info')){
            $body_cat = str_replace('{cat_title}', $this->category->title, $body_cat);

            $show_description = $this->params->get('show_description');
            if ($show_description){
                // We truncate nothing here as it would not be useful
                $body_cat = str_replace('{cat_description}', $this->category->description, $body_cat);
            } else {
                $body_cat = str_replace('{cat_description}', '', $body_cat);
            }
                
            $body_cat = str_replace('{cat_id}', $this->category->id, $body_cat);
            $body_cat = str_replace('{cat_pic}', $catpic, $body_cat);
            $body_cat = str_replace('{sum_subcats}', Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_SUBCATS').' '.$total_subcategories, $body_cat);
            $body_cat = str_replace('{sum_files_cat}', Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_FILES').' '.$total_downloads, $body_cat);
            $body_cat = str_replace('{cat_info_begin}', '', $body_cat); 
            $body_cat = str_replace('{cat_info_end}', '', $body_cat);
            
            $body_cat = str_replace('{after_display_title}', $catAfterDisplayTitle, $body_cat);
            $body_cat = str_replace('{before_display_content}', $catBeforeDisplayContent, $body_cat);
            $body_cat = str_replace('{after_display_content}', $catAfterDisplayContent, $body_cat);
            
            // Add access level
            $body_cat = str_replace('{access_title}', Text::_('COM_JDOWNLOADS_ACCESS').':', $body_cat);  
            $user_group = $userhelper->load($this->category->access);
            $body_cat = str_replace('{access}', $user_group->title, $body_cat);

            // In 3.9 'show_cat_tags' does not exist so note use of default
            
            if ($this->params->get('show_cat_tags', 1) && !empty($this->category->tags->itemTags)){
                $this->category->tagLayout = new FileLayout('joomla.content.tags'); 
                $body_cat = str_replace('{tags}', $this->category->tagLayout->render($this->category->tags->itemTags), $body_cat);
                $body_cat = str_replace('{tags_title}', Text::_('COM_JDOWNLOADS_TAGS_LABEL'), $body_cat);    
            } else {
                $body_cat = str_replace('{tags}', '', $body_cat);
                $body_cat = str_replace('{tags_title}', '', $body_cat); 
            }
            
            // Remove all title html tags in top cat output
            if ($pos_end = strpos($body_cat, '{cat_title_end}')){
                $pos_beg = strpos($body_cat, '{cat_title_begin}');
                $body_cat = substr_replace($body_cat, '', $pos_beg, ($pos_end - $pos_beg) + 15);
            }

        }  else {
            
            // Do not display category info 
            $body_cat = str_replace('{cat_title}', '', $body_cat);
            $body_cat = str_replace('{tags}', '', $body_cat);
            $body_cat = str_replace('{tags_title}', '', $body_cat);
            
            // Remove all title html tags in top cat output
            if ($pos_end = strpos($body_cat, '{cat_title_end}')){
                $pos_beg = strpos($body_cat, '{cat_title_begin}');
                $body_cat = substr_replace($body_cat, '', $pos_beg, ($pos_end - $pos_beg) + 15);
            } 
            
            // Remove all html tags in top cat output
            if ($pos_end = strpos($body_cat, '{cat_info_end}')){
                $pos_beg = strpos($body_cat, '{cat_info_begin}');
                $body_cat = substr_replace($body_cat, '', $pos_beg, ($pos_end - $pos_beg) + 14);
            } else {
                $body_cat = str_replace('{cat_description}', '', $body_cat);
                $body_cat = str_replace('{cat_pic}', '', $body_cat);
                $body_cat = str_replace('{sum_subcats}', '', $body_cat);
                $body_cat = str_replace('{sum_files_cat}', '', $body_cat);
            }
        }
        
        // Replace both Google adsense placeholder with script
        $body_cat = JDHelper::insertGoogleAdsenseCode($body_cat);        
        $body_cat .= $cat_after; 
       
        // ===================
        // Sub Categories Data
        // ===================
                
        // Build the data for the sub categories javascript pagination 
        $labelprev  = Text::_('COM_JDOWNLOADS_JS_PAGINATION_PREV');
        $labelnext  = Text::_('COM_JDOWNLOADS_JS_PAGINATION_NEXT');
        $labelstart = Text::_('COM_JDOWNLOADS_JS_PAGINATION_START');
        $labelend   = Text::_('COM_JDOWNLOADS_JS_PAGINATION_END');
        $labelnumber = Text::_('COM_JDOWNLOADS_JS_PAGINATION_AMOUNT');
        $labelpage  = Text::_('COM_JDOWNLOADS_FRONTEND_HEADER_PAGENAVI_PAGE_TEXT');
        $labelof    = Text::_('COM_JDOWNLOADS_FRONTEND_HEADER_PAGENAVI_TO_TEXT');
        
        if (strpos($subcat_layout->template_after_text, '</table') !== false){
            // Old layout with tables
            $subcats_pagenav = '<script type="text/javascript">
                '."
                var pager = new Pager('results',".(int)$params->get('amount_subcats_per_page_in_pagination').",'pager','pageNavPosition','".$labelprev."','".$labelnext."','".$labelstart."','".$labelend."','".$labelnumber."','".$total_subcategories."'); 
                pager.init(); 
                pager.showPage(1);
            </script>";
        } else {               
            // New divs layout
            $subcats_pagenav = '<script type="text/javascript">
                '."
                var pager = new jddiv.Pager();
                jQuery(document).ready(function() {
                    pager.paragraphsPerPage = ".(int)$params->get('amount_subcats_per_page_in_pagination')."; // set amount elements per page
                    pager.pagingContainer = jQuery('#results'); // set of main container
                    pager.paragraphs = jQuery('div.jd_subcat_pagination_inner_wrapper', pager.pagingContainer); // set of required containers
                    pager.labelPage  = '".$labelpage."';
                    pager.labelOf    = '".$labelof."';
                    pager.labelStart = '".$labelstart."';
                    pager.labelEnd   = '".$labelend."';
                    pager.labelPrev  = '".$labelprev."';
                    pager.labelNext  = '".$labelnext."';
                    pager.showPage(1);
                    });
            </script>";
        }
        
        if (!isset($show_empty_categories)){
            $show_empty_categories = true;
        }        

        $i             = 0;
        $w             = 0;
        $paging        = '';
        $subcat_itemid = '';
        
        $truncated_cat_desc_len  = $params->get('auto_cat_short_description_value');
                
        if ($total_subcategories > 0){
            
            $body_subcats = $cats_before;
            
            $parentCatid = $this->category->id;
            $max_cats = count($this->children[$this->category->id]);
             
            for ($i=0; $i < $total_subcategories; $i++){
            
                $body_subcats .= $cats_layout_text;
                
                // Check whether we must build the output
                if ($show_empty_categories || $params->get('view_empty_categories') || $this->children[$parentCatid][$i]->getNumItems(true) || count($this->children[$parentCatid][$i]->getChildren())){            
            
                    // Exists a single category menu link for this subcat? 
                    if ($this->children[$parentCatid][$i]->menu_itemid){
                        $subcat_itemid =  (int)$this->children[$parentCatid][$i]->menu_itemid;
                    } else {
                        $subcat_itemid = $root_menu_itemid;
                    }    
                     
                    // Display cat info
                    $catlink = Route::_("index.php?option=com_jdownloads&amp;view=category&amp;catid=".$this->children[$parentCatid][$i]->id."&amp;Itemid=".$subcat_itemid);                        
                     
                    // Display category symbol/pic 
					if ($params->get('link_in_symbols')){
							$pic_link = '<a href="'.$catlink.'">';
							$pic_end = '</a>';
						} else {
							$pic_link = '';
							$pic_end = '';
						}
                    if ($this->children[$parentCatid][$i]->pic != '' ) {
                        $catpic = $pic_link.'<img src="'.URI::base().'images/jdownloads/catimages/'.$this->children[$parentCatid][$i]->pic.'" style="text-align:top;border:0px;" width="'.$params->get('cat_pic_size').'" height="'.$params->get('cat_pic_size_height').'" alt="'.substr($this->children[$parentCatid][$i]->pic,0,-4).$i.'"/>'.$pic_end;
                    } else {
                        $catpic = '';
                    }
                    
                    // Does 'show_cat_tags' exist but note default setting
                    if ($this->params->get('show_cat_tags', 1) && !empty($this->children[$parentCatid][$i]->tags->itemTags)){
                        $this->children[$parentCatid][$i]->tagLayout = new FileLayout('joomla.content.tags'); 
                        $body_subcats = str_replace('{tags}', $this->children[$parentCatid][$i]->tagLayout->render($this->children[$parentCatid][$i]->tags->itemTags), $body_subcats);
                        $body_subcats = str_replace('{tags_title}', Text::_('COM_JDOWNLOADS_TAGS_LABEL'), $body_subcats);        
                    } else {
                        $body_subcats = str_replace('{tags}', '', $body_subcats);
                        $body_subcats = str_replace('{tags_title}', '', $body_subcats);
                    }                                              

                    // More than one column   ********************************************************
                    if ($subcat_layout->cols > 1 && strpos($cats_layout_text, '{cat_title1}')){
                        $a = 0;     
                        
                        for ($a=0; $a < $subcat_layout->cols; $a++){

                            if ($a >= $total_subcategories || $i == $total_subcategories || $w == $total_subcategories){
                                continue;
                            }
    
                            // Exists a single category menu link for this subcat? 
                            if ($this->children[$parentCatid][$i]->menu_itemid){
                                $subcat_itemid =  (int)$this->children[$parentCatid][$i]->menu_itemid;
                            } else {
                                $subcat_itemid = $root_menu_itemid;
                            }                        
                            
                            // Display cat info
                            $catlink = Route::_("index.php?option=com_jdownloads&amp;view=category&amp;catid=".$this->children[$parentCatid][$i]->id."&amp;Itemid=".$subcat_itemid);
                            
							// Display category symbol/pic 
							if ($params->get('link_in_symbols')){
								$pic_link = '<a href="'.$catlink.'">';
								$pic_end = '</a>';
							} else {
								$pic_link = '';
								$pic_end = '';
							}
                            
                            // Show symbol - also as url                                                                                                   
                            if ($this->children[$parentCatid][$i]->pic != '' ) {
                                $catpic = $pic_link.'<img src="'.URI::base().'images/jdownloads/catimages/'.$this->children[$parentCatid][$i]->pic.'" style="text-align:top;border:0px;" width="'.$params->get('cat_pic_size').'" height="'.$params->get('cat_pic_size_height').'" alt="'.substr($this->children[$parentCatid][$i]->pic,0,-4).$i.'"/>'.$pic_end;
                            } else {
                                $catpic = '';
                            }                     
                           
                            $x = $a + 1;
                            $x = (string)$x;

                            if ($i < count($this->children[$this->category->id])){
                                if ($a == 0){
                                    $body_subcats = str_replace("{cat_title$x}", '<a href="'.$catlink.'">'.$this->children[$parentCatid][$i]->title.'</a>', $body_subcats);
                                } else {
                                    $body_subcats = str_replace("{cat_title$x}", '<a href="'.$catlink.'">'.$this->children[$parentCatid][$i]->title.'</a>', $body_subcats);
                                }
                                 
                                $body_subcats = str_replace("{cat_pic$x}", $catpic, $body_subcats);

                                if ($this->params->get('show_subcat_desc')){
                                    $body_subcats = str_replace("{cat_description$x}", $this->children[$parentCatid][$i]->description, $body_subcats);
                                } else {
                                   $body_subcats = str_replace("{cat_description$x}", '', $body_subcats); 
                                }
                                    
                                $this->children[$parentCatid][$i]->numitems = $countSubItems($this->children[$parentCatid][$i]);
                                
                                $body_subcats = str_replace("{sum_subcats$x}", Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_SUBCATS').' '.$this->children[$parentCatid][$i]->subcatitems, $body_subcats);
                                $body_subcats = str_replace("{sum_files_cat$x}", Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_FILES').' '.$this->children[$parentCatid][$i]->numitems, $body_subcats);
                                // Add access level
                                $body_subcats = str_replace("{access_title$x}", Text::_('COM_JDOWNLOADS_ACCESS').':', $body_subcats);  
                                $user_group = $userhelper->load($this->children[$parentCatid][$i]->access);
                                $body_subcats = str_replace("{access$x}", $user_group->title, $body_subcats);
                            } else {
                                $body_subcats = str_replace("{cat_title$x}", '', $body_subcats);
                                $body_subcats = str_replace("{cat_pic$x}", '', $body_subcats);
                                $body_subcats = str_replace("{cat_description$x}", '', $body_subcats);
                                $body_subcats = str_replace("{access_title$x}", '', $body_subcats);
                                $body_subcats = str_replace("{access$x}", '', $body_subcats);
                            }
                            
                            $w = $i+1;
                            
                            if (($a + 1) < $subcat_layout->cols && isset($this->children[$parentCatid][($w)])){
                                $i++;

                                // Exists a single category menu link for this subcat? 
                                if ($this->children[$parentCatid][$i]->menu_itemid){
                                    $subcat_itemid =  (int)$this->children[$parentCatid][$i]->menu_itemid;
                                } else {
                                    $subcat_itemid = $root_menu_itemid;
                                }
                                                             
                                $catlink = Route::_("index.php?option=com_jdownloads&amp;view=category&amp;catid=".$this->children[$parentCatid][$i]->id."&amp;Itemid=".$subcat_itemid);
                                
                                // Show symbol - also as url                                                                                                                  
                                if ($this->children[$parentCatid][$i]->pic != '' ) {
                                    $catpic = $pic_link.'<img src="'.URI::base().'images/jdownloads/catimages/'.$this->children[$parentCatid][$i]->pic.'" style="text-align:top;border:0px;" width="'.$params->get('cat_pic_size').'" height="'.$params->get('cat_pic_size_height').'" alt="'.substr($this->children[$parentCatid][$i]->pic,0,-4).$i.'"/>'.$pic_end;
                                } else {
                                    $catpic = '';
                                }
                            }  
                        }
                        
                        for ($b=1; $b < 10; $b++){
                            $x = (string)$b;
                            $body_subcats = str_replace("{cat_title$x}", '', $body_subcats);
                            $body_subcats = str_replace("{cat_pic$x}", '', $body_subcats);
                            $body_subcats = str_replace("{sum_files_cat$x}", '', $body_subcats); 
                            $body_subcats = str_replace("{sum_subcats$x}", '', $body_subcats);
                            $body_subcats = str_replace("{cat_description$x}", '', $body_subcats);
                            $body_subcats = str_replace("{access_title$x}", '', $body_subcats);
                            $body_subcats = str_replace("{access$x}", '', $body_subcats);
                        }
                        
                        $body_subcats = str_replace('{subcats_title_text}', Text::_('COM_JDOWNLOADS_FE_FILELIST_TITLE_OVER_SUBCATS_LIST'), $body_subcats);             
                        
                    } else {
                        $body_subcats = str_replace('{cat_title}', '<a href="'.$catlink.'">'.$this->children[$parentCatid][$i]->title.'</a>', $body_subcats);
                        $body_subcats = str_replace('{sum_subcats}', Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_SUBCATS').' '.$this->children[$parentCatid][$i]->subcatitems, $body_subcats);
                        
                        $this->children[$parentCatid][$i]->numitems = $countSubItems($this->children[$parentCatid][$i]);
                        $body_subcats = str_replace('{sum_files_cat}', Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_FILES').' '.$this->children[$parentCatid][$i]->numitems, $body_subcats);
                    }
                       
                    if ($this->params->get('show_subcat_desc')){
                        if ($this->children[$parentCatid][$i]->description != '' && $truncated_cat_desc_len){
                            if (StringHelper::strlen($this->children[$parentCatid][$i]->description) > $truncated_cat_desc_len){ 
                                $shorted_text = HTMLHelper::_('string.truncate', $this->children[$parentCatid][$i]->description, $truncated_cat_desc_len, true, true); // Do not cut off words; HTML allowed;
                                $body_subcats = str_replace('{cat_description}', $shorted_text, $body_subcats);
                            } else {
                                $body_subcats = str_replace('{cat_description}', $this->children[$parentCatid][$i]->description, $body_subcats);
                            }    
                        } else {
                            $body_subcats = str_replace('{cat_description}', $this->children[$parentCatid][$i]->description, $body_subcats);
                        }
                    } else {
                        $body_subcats = str_replace('{cat_description}', '', $body_subcats); 
                    }    
                    
                    // Add access information 
                    $body_subcats = str_replace('{access_title}', Text::_('COM_JDOWNLOADS_ACCESS').':', $body_subcats);
                    $user_group = $userhelper->load($this->children[$parentCatid][$i]->access);
                    $body_subcats = str_replace('{access}', $user_group->title, $body_subcats);
                    
                    $body_subcats = str_replace('{cat_pic}', $catpic, $body_subcats);
                    $body_subcats = str_replace('{cat_info_begin}', '', $body_subcats); 
                    $body_subcats = str_replace('{cat_info_end}', '', $body_subcats);
                    
                    if ($i > 0){
                        // Remove all title html tags in top categories output
                        if ($pos_end = strpos($body_subcats, '{cat_title_end}')){
                            $pos_beg = strpos($body_subcats, '{cat_title_begin}');
                            $body_subcats = substr_replace($body_subcats, '', $pos_beg, ($pos_end - $pos_beg) + 15);
                        } 
                    } else {
                        $body_subcats = str_replace('{subcats_title_text}', Text::_('COM_JDOWNLOADS_FE_FILELIST_TITLE_OVER_SUBCATS_LIST'), $body_subcats);             
                        $body_subcats = str_replace('{cat_title_begin}', '', $body_subcats); 
                        $body_subcats = str_replace('{cat_title_end}', '', $body_subcats);
                    }
                } else {
                    // We have an empty category - so we do not need any layout for this category
                    $body_subcats = '';
                    $max_cats --;
                } 
            }
            $body_subcats = str_replace('{files}', '', $body_subcats);
            $body_subcats = str_replace('{checkbox_top}', '', $body_subcats);
            $body_subcats = str_replace('{form_hidden}', '', $body_subcats);
            $body_subcats = str_replace('{form_button}', '', $body_subcats);
            
            $body_subcats .= $cats_after;
            
            if ($params->get('use_pagination_subcategories') && ($total_subcategories > (int)$params->get('amount_subcats_per_page_in_pagination'))){
                // Add pagination script
                $body_subcats .= $subcats_pagenav;
            }
            
            // Replace both Google adsense placeholder with script
            $body_subcats = JDHelper::insertGoogleAdsenseCode($body_subcats);        

            // Support for content plugins 
            if ($params->get('activate_general_plugin_support')) {
                $body_subcats = HTMLHelper::_('content.prepare', $body_subcats);
            }
            
            $body_cat = str_replace('{sub_categories}', $body_subcats, $body_cat);
        } else {
            $body_cat = str_replace('{sub_categories}', '', $body_cat);
        }                     
    }

    $formid = $this->category->id;

    // ===================
    // Downloads List
    // ===================

    $html_files = '';

    if ($files_layout_text != ''){
        
        // Build the mini image symbols when used in layout ( 0 = activated !!! )
        if ($use_mini_icons) {
            $msize =  $params->get('info_icons_size');
            $pic_date = '<img src="'.URI::base().'images/jdownloads/miniimages/date.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DATE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DATE').'" />&nbsp;';
            $pic_license = '<img src="'.URI::base().'images/jdownloads/miniimages/license.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'" />&nbsp;';
            $pic_author = '<img src="'.URI::base().'images/jdownloads/miniimages/contact.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_AUTHOR').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_AUTHOR').'" />&nbsp;';
            $pic_website = '<img src="'.URI::base().'images/jdownloads/miniimages/weblink.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_WEBSITE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_WEBSITE').'" />&nbsp;';
            $pic_system = '<img src="'.URI::base().'images/jdownloads/miniimages/system.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_SYSTEM').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_SYSTEM').'" />&nbsp;';
            $pic_language = '<img src="'.URI::base().'images/jdownloads/miniimages/language.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LANGUAGE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LANGUAGE').'" />&nbsp;';
            $pic_downloads = '<img src="'.URI::base().'images/jdownloads/miniimages/download.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DOWNLOAD').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_DOWNLOAD_HITS').'" />&nbsp;';
            $pic_price = '<img src="'.URI::base().'images/jdownloads/miniimages/currency.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_PRICE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_PRICE').'" />&nbsp;';
            $pic_size = '<img src="'.URI::base().'images/jdownloads/miniimages/stuff.png" style="text-align:middle;border:0px;" width="'.$msize.'" height="'.$msize.'"  alt="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_FILESIZE').'" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_FILESIZE').'" />&nbsp;';
        } else {
            $pic_date = '';
            $pic_license = '';
            $pic_author = '';
            $pic_website = '';
            $pic_system = '';
            $pic_language = '';
            $pic_downloads = '';
            $pic_price = '';
            $pic_size = '';
        }

        
        // Build a little pic for extern links
        $extern_url_pic = '<img src="'.URI::base().'components/com_jdownloads/assets/images/link_extern.gif" alt="external" />';        
        
        // ===========================================
        // Display now the categories files (downloads)
        
        // Check at first whether we have at minimum one Download with a downloadable file
        // Otherwise we need not the top and footer checkbox part for multi download layouts
        $amount_downloads_with_file = 0;
        
        $truncated_file_desc_len = $params->get('auto_file_short_description_value');
        
        for ($i = 0; $i < count($files); $i++) {
            if ($files[$i]->url_download){
                $amount_downloads_with_file ++;
            }
        }
            
        // Start now with the Downloads    
        for ($i = 0; $i < count($files); $i++) {
            
            // When user has access: get data to publish the edit icon and publish data as tooltip
            if ($files[$i]->params->get('access-edit')){
                $editIcon = JDHelper::getEditIcon($files[$i]);
            } else {
                $editIcon = '';
            }
            
            $has_no_file = false;
            $file_id = $files[$i]->id;

            // When we have not a menu item to the singel download, we need a menu item from the assigned category, or at lates the root itemid
            if ($files[$i]->menuf_itemid){
                $file_itemid =  (int)$files[$i]->menuf_itemid;
            } else {
                $file_itemid = $this->category->menu_itemid;
            }             

            // Checkbox is only viewed, when we have not an external file - and not only a document
            if (!$files[$i]->extern_file && ($files[$i]->other_file_id || $files[$i]->url_download)){
                // We can view the download link only when user can download it
                if ($files[$i]->params->get('access-download') == true){                
                    $checkbox_list = '<input type="checkbox" class="jd_files_checkbox" id="cb'.$i.'" name="cb_arr[]" title="'.Text::_('COM_JDOWNLOADS_DOWNLOADS_CHECKBOX_HINT').'" value="'.$file_id.'" onclick="istChecked(this.checked,'.$formid.');"/>';
                } else {
                    // We will give the user a hint 
                    $userinfo = Text::_('COM_JDOWNLOADS_FRONTEND_FILE_NO_PERMISSIONS');
                    $checkbox_list = HTMLHelper::_('tooltip', $userinfo, '', URI::base().'components/com_jdownloads/assets/images/tooltip.png' );
                }    
            } else {
                if (!$files[$i]->url_download && !$files[$i]->other_file_id && !$files[$i]->extern_file){
                    // Only a document without file
                    $userinfo = Text::_('COM_JDOWNLOADS_FRONTEND_ONLY_DOCUMENT_USER_INFO');
                    $has_no_file = true;
                } else {
                    // External file 
                    $userinfo = Text::_('COM_JDOWNLOADS_FRONTEND_EXTERN_FILE_USER_INFO');
                }    
                $checkbox_list = HTMLHelper::_('tooltip', $userinfo, '', URI::base().'components/com_jdownloads/assets/images/tooltip.png' );
            }    
            
            // Use the activated/selected "files" layout text to build the output for every download
            $html_file = $files_layout_text;
            
            // add the content plugin event 'before display content'
            if (strpos($files_layout_text, '{before_display_content}') > 0){
                $html_file = str_replace('{before_display_content}', $files[$i]->event->beforeDisplayContent, $html_file);
            } else {
                $html_file = $files[$i]->event->beforeDisplayContent.$html_file;    
            }

            // For the 'after display title' event can we only use a placeholder - a fix position is not really given
            $html_file = str_replace('{after_display_title}', $files[$i]->event->afterDisplayTitle, $html_file);
            
            $html_file = str_replace('{file_id}',$files[$i]->id, $html_file);
            
            // Replace 'featured' placeholders
            if ($files[$i]->featured){
                // Add the css class
				if ($params->get('use_featured_classes')){
                    $html_file = str_replace('{featured_class}', 'jd_featured', $html_file);
                    $html_file = str_replace('{featured_detail_class}', 'jd_featured_detail', $html_file);
				} else {
					$html_file = str_replace('{featured_class}', '', $html_file);
                    $html_file = str_replace('{featured_detail_class}', '', $html_file);	
				}
                // Add the pic
                if ($params->get('featured_pic_filename')){
                    $featured_pic = '<img class="jd_featured_star" src="'.URI::base().'images/jdownloads/featuredimages/'.$params->get('featured_pic_filename').'" width="'.$params->get('featured_pic_size').'" height="'.$params->get('featured_pic_size_height').'" alt="'.substr($params->get('featured_pic_filename'),0,-4).'"/>';
                    $html_file = str_replace('{featured_pic}', $featured_pic, $html_file);
                } else {
                    $html_file = str_replace('{featured_pic}', '', $html_file);
                }
            } else {
                $html_file = str_replace('{featured_class}', '', $html_file);
                $html_file = str_replace('{featured_detail_class}', '', $html_file);
                $html_file = str_replace('{featured_pic}', '', $html_file);
            }
            
            if ($this->params->get('show_tags', 1) && !empty($files[$i]->tags->itemTags)){
                $files[$i]->tagLayout = new FileLayout('joomla.content.tags'); 
                $html_file = str_replace('{tags}', $files[$i]->tagLayout->render($files[$i]->tags->itemTags), $html_file);   
                $html_file = str_replace('{tags_title}', Text::_('COM_JDOWNLOADS_TAGS_LABEL'), $html_file);   
            } else {
                $html_file = str_replace('{tags}', '', $html_file);
                $html_file = str_replace('{tags_title}', '', $html_file);
            }            
            
            // Files title row info only view when it is the first file
            if ($i > 0){
                // Remove all html tags in top cat output
                if ($pos_end = strpos($html_file, '{files_title_end}')){
                    $pos_beg = strpos($html_file, '{files_title_begin}');
                    $html_file = substr_replace($html_file, '', $pos_beg, ($pos_end - $pos_beg) + 17);
                }
            } else {
                $html_file = str_replace('{files_title_text}', Text::_('COM_JDOWNLOADS_FE_FILELIST_TITLE_OVER_FILES_LIST').' '.$total_downloads, $html_file);
                $html_file = str_replace('{files_title_end}', '', $html_file);
                $html_file = str_replace('{files_title_begin}', '', $html_file);
            } 
     
            // Create file titles
            $html_file = JDHelper::buildFieldTitles($html_file, $files[$i]);
            
            // Create filename
            if ($files[$i]->url_download){
                $html_file = str_replace('{file_name}', JDHelper::getShorterFilename($this->escape(strip_tags($files[$i]->url_download))), $html_file);
            } elseif (isset($files[$i]->filename_from_other_download) && $files[$i]->filename_from_other_download != ''){    
                $html_file = str_replace('{file_name}', JDHelper::getShorterFilename($this->escape(strip_tags($files[$i]->filename_from_other_download))), $html_file);
            } else {
                $html_file = str_replace('{file_name}', '', $html_file);
            }
            
            // Replace both Google adsense placeholder with script
            $html_file = JDHelper::insertGoogleAdsenseCode($html_file);        
            
            // Report download link
            if ($jd_user_settings->view_report_form){
                $report_link = '<a href="'.Route::_("index.php?option=com_jdownloads&amp;view=report&amp;id=".$files[$i]->slug."&amp;catid=".$files[$i]->catid."&amp;Itemid=".$root_menu_itemid).'" rel="nofollow">'.Text::_('COM_JDOWNLOADS_FRONTEND_REPORT_FILE_LINK_TEXT').'</a>';
                $html_file = str_replace('{report_link}', $report_link, $html_file);
            } else {
                $html_file = str_replace('{report_link}', '', $html_file);
            }
            
            // View sum comments 
            if ($params->get('view_sum_jcomments') && $params->get('jcomments_active')){
                if ($comments_table_exist){
                    $db->setQuery('SELECT COUNT(*) from #__jcomments WHERE object_group = \'com_jdownloads\' AND object_id = '.$files[$i]->id);
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

            if ($files[$i]->release == '' ) {
                $html_file = str_replace('{release}', '', $html_file);
            } else {
                $html_file = str_replace('{release}', $files[$i]->release.' ', $html_file);
            }

            $html_file = str_replace('{category_title}', Text::_('COM_JDOWNLOADS_CATEGORY_LABEL'), $html_file);
            $html_file = str_replace('{category_name}', $category->title, $html_file);
            
            // Insert language associations
            if ($params->get('show_associations') && (!empty($files[$i]->associations))){
                $association_info = '<dd class="jd_associations">'.Text::_('COM_JDOWNLOADS_ASSOCIATION_HINT');
                
                foreach ($files[$i]->associations as $association){
                    $url = Route::_($association['item']);
                    
                    if ($params->get('flags', 1) && $association['language']->image){
                        $flag = HTMLHelper::_('image', 'mod_languages/' . $association['language']->image . '.gif', $association['language']->title_native, array('title' => $association['language']->title_native), true);
                        $line = '<a style="margin-left:5px;" href="'.$url.'">'.$flag.'</a>';
                    } else {
                        $class = 'label label-association label-' . $association['language']->sef;
                        $line  = '<a style="margin-left:5px;" class="'.$class.'" href="'.$url.'">'.strtoupper($association['language']->sef).'</a>';
                    }
                    $association_info .= $line;
                }
                $association_info .= '</dd>';
                
            } else {
                $association_info = '';
            }
            
            $html_file = str_replace('{show_association}', $association_info, $html_file);
            
            // Display the thumbnails
            $html_file = JDHelper::placeThumbs($html_file, $files[$i]->images, 'list');                                                    

            // We change the old lightbox tag type to the new
            $html_file = str_replace('rel="lightbox"', 'data-lightbox="lightbox'.$files[$i]->id.'" data-alt="lightbox'.substr($files[$i]->images,0,-4).$i.'"', $html_file);
        
            // Check whether we must truncate the files description
            if ($files[$i]->description != '' && $truncated_file_desc_len){
                if (StringHelper::strlen($files[$i]->description) > $truncated_file_desc_len){ 
                    $shorted_text = HTMLHelper::_('string.truncate', $files[$i]->description, $truncated_file_desc_len, true, true); // Do not cut off words; HTML allowed;
                    $html_file = str_replace('{description}', $shorted_text, $html_file);
                } else {
                    $html_file = str_replace('{description}', $files[$i]->description, $html_file);
                }    
            } else {
                 $html_file = str_replace('{description}', $files[$i]->description, $html_file);
            }   

            // Compute for HOT symbol            
            if ($params->get('loads_is_file_hot')> 0 && $files[$i]->downloads >= $params->get('loads_is_file_hot') ){
                $html_file = str_replace('{pic_is_hot}', '<span class="jdbutton '.$status_color_hot.' jstatus">'.Text::_('COM_JDOWNLOADS_HOT').'</span>', $html_file);
            } else {    
                $html_file = str_replace('{pic_is_hot}', '', $html_file);
            }

            // Compute for NEW symbol
            $days_diff = JDHelper::computeDateDifference(date('Y-m-d H:i:s'), $files[$i]->created);
            if ($params->get('days_is_file_new') > 0 && $days_diff <= $params->get('days_is_file_new')){
                $html_file = str_replace('{pic_is_new}', '<span class="jdbutton '.$status_color_new.' jstatus">'.Text::_('COM_JDOWNLOADS_NEW').'</span>', $html_file);
            } else {    
                $html_file = str_replace('{pic_is_new}', '', $html_file);
            }
            
            // Compute for UPDATED symbol
            // View it only when in the Download is activated the 'updated' option
            if ($files[$i]->update_active) {
                $days_diff = JDHelper::computeDateDifference(date('Y-m-d H:i:s'), $files[$i]->modified);
                if ($params->get('days_is_file_updated') > 0 && $days_diff >= 0 && $days_diff <= $params->get('days_is_file_updated')){
                    $html_file = str_replace('{pic_is_updated}', '<span class="jdbutton '.$status_color_updated.' jstatus">'.Text::_('COM_JDOWNLOADS_UPDATED').'</span>', $html_file);
                } else {    
                    $html_file = str_replace('{pic_is_updated}', '', $html_file);
                }
            } else {
               $html_file = str_replace('{pic_is_updated}', '', $html_file);
            }    
            
            // Media player
            if ($files[$i]->preview_filename){
                // we use the preview file when exist  
                $is_preview = true;
                $files[$i]->itemtype = JDHelper::getFileExtension($files[$i]->preview_filename);
                $is_playable    = JDHelper::isPlayable($files[$i]->preview_filename);
                $extern_media = false;
            } else {                  
                $is_preview = false;
                if ($files[$i]->extern_file){
                    $extern_media = true;
                    $files[$i]->itemtype = JDHelper::getFileExtension($files[$i]->extern_file);
                    $is_playable    = JDHelper::isPlayable($files[$i]->extern_file);
                } else {    
                    $files[$i]->itemtype = JDHelper::getFileExtension($files[$i]->url_download);
                    $is_playable    = JDHelper::isPlayable($files[$i]->url_download);
                    $extern_media = false;
                }  
            }            
            
            if ( $is_playable ){
                
                if ($params->get('html5player_use')){
                    // we will use the new HTML5 player option
                    if ($extern_media){
                        $media_path = $files[$i]->extern_file;
                    } else {        
                        if ($is_preview){
                            // we need the relative path to the "previews" folder
                            $media_path = $jdownloads_root_dir_name.'/'.$params->get('preview_files_folder_name').'/'.$files[$i]->preview_filename;
                        } else {
                            // we use the normal download file for the player
                            $media_path = $jdownloads_root_dir_name.'/'.$category_dir.'/'.$files[$i]->url_download;
                        }   
                    }    
                            
                    // create the HTML5 player
                    $player = JDHelper::getHTML5Player($files[$i], $media_path);
                    
                    // we use the player for video files only in listings, when the option allowed this
                    if ($params->get('html5player_view_video_only_in_details') && $files[$i]->itemtype != 'mp3' && $files[$i]->itemtype != 'wav' && $files[$i]->itemtype != 'oga'){
                        $html_file = str_replace('{mp3_player}', '', $html_file);
                        $html_file = str_replace('{preview_player}', '', $html_file);
                    } else {                            
                        if ($files[$i]->itemtype == 'mp4' || $files[$i]->itemtype == 'webm' || $files[$i]->itemtype == 'ogg' || $files[$i]->itemtype == 'ogv' || $files[$i]->itemtype == 'mp3' || $files[$i]->itemtype == 'wav' || $files[$i]->itemtype == 'oga'){
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
                            $media_path = $files[$i]->extern_file;
                        } else {        
                            if ($is_preview){
                                // we need the relative path to the "previews" folder
                                $media_path = $jdownloads_root_dir_name.'/'.$params->get('preview_files_folder_name').'/'.$files[$i]->preview_filename;
                            } else {
                                // we use the normal download file for the player
                                $media_path = $jdownloads_root_dir_name.'/'.$category_dir.'/'.$files[$i]->url_download;
                            }   
                        }    

                        $ipadcode = '';

                        if ($files[$i]->itemtype == 'mp3'){
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
                        
                        $player = '<a href="'.$media_path.'" style="display:block;width:'.$params->get('flowplayer_playerwidth').'px;height:'.$playerheight.'px;" class="player" id="player'.$files[$i]->id.'"></a>';
                        $player .= '<script language="JavaScript">
                        // install flowplayer into container
                                    flowplayer("player'.$files[$i]->id.'", "'.URI::base().'components/com_jdownloads/assets/flowplayer/flowplayer-3.2.16.swf",  
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
                                    $f("player'.$files[$i]->id.'").close();
                                }
                            }
                        })'.$ipadcode.'; </script>';
                        // the 'ipad code' above is only required for ipad/iphone users                
                        
                        // we use the player for video files only in listings, when the option allowed this
                        if ($params->get('flowplayer_view_video_only_in_details') && $files[$i]->itemtype != 'mp3'){ 
                            $html_file = str_replace('{mp3_player}', '', $html_file);
                            $html_file = str_replace('{preview_player}', '', $html_file);            
                        } else {    
                            if ($files[$i]->itemtype == 'mp4' || $files[$i]->itemtype == 'flv' || $files[$i]->itemtype == 'mp3'){    
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

            if ($params->get('mp3_view_id3_info') && $files[$i]->itemtype == 'mp3' && !$extern_media){
                // read mp3 infos
                if ($is_preview){
                    // get the path to the preview file
                    $mp3_path_abs = $params->get('files_uploaddir').'/'.$params->get('preview_files_folder_name').'/'.$files[$i]->preview_filename;
                } else {
                    // get the path to the downloads file
                    $mp3_path_abs = $params->get('files_uploaddir').'/'.$category_dir.'/'.$files[$i]->url_download;
                }
                
                $info = JDHelper::getID3v2Tags($mp3_path_abs);         
                if ($info){
                    // add it
                    $mp3_info = '<div class="jd_mp3_id3tag_wrapper" style="max-width:'.(int)$params->get('html5player_audio_width').'px; ">'.stripslashes($params->get('mp3_info_layout')).'</div>';
                    $mp3_info = str_replace('{name_title}', Text::_('COM_JDOWNLOADS_FE_VIEW_ID3_TITLE'), $mp3_info);
                    if ($is_preview){
                        $mp3_info = str_replace('{name}', $files[$i]->preview_filename, $mp3_info);
                    } else {
                        $mp3_info = str_replace('{name}', $files[$i]->url_download, $mp3_info);
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
            $html_file = str_replace('{mp3_id3_tag}', '', $html_file);
            $html_file = str_replace('{preview_player}', '', $html_file);             

            // replace the {preview_url}
            if ($files[$i]->preview_filename){
                // we need the relative path to the "previews" folder
                $media_path = $jdownloads_root_dir_name.'/'.$params->get('preview_files_folder_name').'/'.$files[$i]->preview_filename;
                $html_file = str_replace('{preview_url}', $media_path, $html_file);
            } else {
                $html_file = str_replace('{preview_url}', '', $html_file);
            }   
            
            // replace the placeholder {information_header}
            $html_file = str_replace('{information_header}', Text::_('COM_JDOWNLOADS_INFORMATION'), $html_file);
            
            // build the license info data and build link
            if ($files[$i]->license == '') $files[$i]->license = 0;
            $lic_data = '';

            if ($files[$i]->license_url != '') {
                 $lic_data = $pic_license.'<a href="'.$files[$i]->license_url.'" target="_blank" rel="nofollow" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_MINI_ICON_ALT_LICENCE').'">'.$files[$i]->license_title.'</a> '.$extern_url_pic;
            } else {
                if ($files[$i]->license_title != '') {
                     if ($files[$i]->license_text != '') {
                          $lic_data = $pic_license.$files[$i]->license_title;
                          $lic_data .= HTMLHelper::_('tooltip', $files[$i]->license_text, $files[$i]->license_title);
                     } else {
                          $lic_data = $pic_license.$files[$i]->license_title;
                     }
                } else {
                    $lic_data = '';
                }
            }
            $html_file = str_replace('{license_text}', $lic_data, $html_file);
            
            // display checkboxes only, when the user have the correct access permissions and it is activated in layout ( = 0 !! )
            if ( $layout_files->checkbox_off == 0 ) {
                 $html_file = str_replace('{checkbox_list}',$checkbox_list, $html_file);
            } else {
                 $html_file = str_replace('{checkbox_list}','', $html_file);
            }

            $html_file = str_replace('{cat_id}', $files[$i]->catid, $html_file);
            
            // file size
            if ($files[$i]->size == '' || $files[$i]->size == '0 B') {
                $html_file = str_replace('{size}', '', $html_file);
                $html_file = str_replace('{filesize_value}', '', $html_file);
            } else {
                $html_file = str_replace('{size}', $pic_size.$files[$i]->size, $html_file);
                $html_file = str_replace('{filesize_value}', $pic_size.$files[$i]->size, $html_file);
            }
            
            // price
            if ($files[$i]->price != '') {
                $html_file = str_replace('{price_value}', $pic_price.$files[$i]->price, $html_file);
            } else {
                $html_file = str_replace('{price_value}', '', $html_file);
            }

            // file_date
            if ($files[$i]->file_date != '0000-00-00 00:00:00' && $files[$i]->file_date != null) {
                 if ($this->params->get('show_date') == 0){ 
                     $filedate_data = $pic_date.HTMLHelper::_('date',$files[$i]->file_date, $date_format['long']);
                 } else {
                     $filedate_data = $pic_date.HTMLHelper::_('date',$files[$i]->file_date, $date_format['short']);
                 }    
            } else {
                 $filedate_data = '';
            }
            $html_file = str_replace('{file_date}',$filedate_data, $html_file);
            
            // date_added
            if ($files[$i]->created != '0000-00-00 00:00:00' && $files[$i]->created != null) {
                if ($this->params->get('show_date') == 0){ 
                    // use 'normal' date-time format field
                    $date_data = $pic_date.HTMLHelper::_('date',$files[$i]->created, $date_format['long']);
                } else {
                    // use 'short' date-time format field
                    $date_data = $pic_date.HTMLHelper::_('date',$files[$i]->created, $date_format['short']);
                }    
            } else {
                 $date_data = '';
            }
            $html_file = str_replace('{date_added}',$date_data, $html_file);
            $html_file = str_replace('{created_date_value}',$date_data, $html_file);
            
            if ($files[$i]->creator){
                $html_file = str_replace('{created_by_value}', $files[$i]->creator, $html_file);
            } else {
                $html_file = str_replace('{created_by_value}', '', $html_file);
            }                
            if ($files[$i]->modifier){
                $html_file = str_replace('{modified_by_value}', $files[$i]->modifier, $html_file);
            } else {                              
                $html_file = str_replace('{modified_by_value}', '', $html_file);
            }
            
            // modified_date
            if ($files[$i]->modified != '0000-00-00 00:00:00' && $files[$i]->modified != null) {
                if ($this->params->get('show_date') == 0){ 
                    $modified_data = $pic_date.HTMLHelper::_('date',$files[$i]->modified, $date_format['long']);
                } else {
                    $modified_data = $pic_date.HTMLHelper::_('date',$files[$i]->modified, $date_format['short']);
                }    
            } else {
                $modified_data = '';
            }
            $html_file = str_replace('{modified_date_value}',$modified_data, $html_file);

            $user_can_see_download_url = 0;
            
            // Clear links
			$download_link = ''; 
            $link_summary = '';
			$link_direct = '';
			$link_detail = '';
            
            $license_pword_captcha_reqd = 0;
            
            // Only view download-url when user has correct access level
            if ($files[$i]->params->get('access-download') == true){ 
                $user_can_see_download_url++;
                $blank_window = '';
                $blank_window1 = '';
                $blank_window2 = '';
                
                // Get file extension
                $view_types = array();
                $view_types = explode(',', $params->get('file_types_view'));
                $only_file_name = basename($files[$i]->url_download);
                $fileextension = JDHelper::getFileExtension($only_file_name);
                if (in_array($fileextension, $view_types)){
                    $blank_window = 'target="_blank"';
                }    
                
                // Check is set link to a new window?
                if ($files[$i]->extern_file && $files[$i]->extern_site   ){
                    $blank_window = 'target="_blank"';
                }

                // Direct download without summary page?
                if ($params->get('direct_download') == '0'){
                     $url_task = 'summary';
                     $download_link = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, $url_task));
					 $link_summary = $download_link;
                } else {
                     if ($files[$i]->license_agree || $files[$i]->password || $jd_user_settings->view_captcha) {
                         // User must agree the license - fill out a password field - or fill out the captcha human check - so we must view the summary page!
                         $url_task = 'summary';
                         $download_link = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, $url_task));
						 $link_summary = $download_link;
                     } else {     
                         // This is the direct download 
                         $url_task = 'download.send';  
                         $download_link = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, $url_task));
						 $link_direct = $download_link;
                     }    
                }
                 
                // Create Download Button and add action hints for better accessibility check result! 
                if ($url_task == 'download.send'){ 
                    $download_link_text = '<a '.$blank_window.' href="'.$download_link.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_DIRECT').'" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color.' '.$download_size_listings.'">';
					$link_direct_text = '<a '.$blank_window.' href="'.$link_direct.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_DIRECT').'" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color.' '.$download_size_listings.'">';
                }
				
                // Create Download Button and add action hints for better accessibility check result! 
                if ($url_task == 'summary'){
					$download_link_text = '<a href="'.$download_link.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_SUMMARY').'" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color.' '.$download_size_listings.'">';                  
                    $link_summary_text =  '<a href="'.$link_summary.'" aria-label="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL_ARIA_SUMMARY').'" title="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color.' '.$download_size_listings.'">';           
		        }
				
                // Need to setup for license etc check separately to force summary view
                if ($files[$i]->license_agree || $files[$i]->password || $jd_user_settings->view_captcha) {
					// We have license or password or captcha
                    $license_pword_captcha_reqd = '1';  
                } else {
					// We do NOT have license or password or captcha
                    $license_pword_captcha_reqd = '0';  
				}
				
                // When we do not really have a file, then do not show download button
                if ($has_no_file || !$files[$i]->state){
                    // Remove download placeholder
                    $html_file = str_replace('{url_download}', '', $html_file); 
                } else {
                    // Insert here the complete download link 
                    $html_file = str_replace('{url_download}',$download_link_text.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'</a>', $html_file);  
                } 
                                                                        
                // View mirrors number 1 - but only when is published
                if ($files[$i]->mirror_1 && $files[$i]->state) {
                    if ($files[$i]->extern_site_mirror_1 && $url_task == 'download.send'){
                        $blank_window1 = 'target="_blank"';
                    }
                    $mirror1_link_dum = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, $url_task, 1));
                    $mirror1_link = '<a '.$blank_window1.' href="'.$mirror1_link_dum.'" alt="'.Text::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jdbutton '.$download_color_mirror1.' '.$download_size_mirror.'">'.Text::_('COM_JDOWNLOADS_FRONTEND_MIRROR_URL_TITLE_1').'</a>'; 
                    
                    $html_file = str_replace('{mirror_1}', $mirror1_link, $html_file);
                } else {
                    $html_file = str_replace('{mirror_1}', '', $html_file);
                }                    

                // View mirrors number 2 - but only when is published
                if ($files[$i]->mirror_2 && $files[$i]->state) {
                    if ($files[$i]->extern_site_mirror_2 && $url_task == 'download.send'){
                        $blank_window2 = 'target="_blank"';
                    }
                    $mirror2_link_dum = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, $url_task, 2));
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
            
            //---------------------- Start of sorting out links for title and symbol V4 -------------------             
            
            // $link_detail has link for details view
            // $link_summary has link for summary view
            // $link_direct has link for direct download
            
            // First get the link in the title                       
            $title_link = Route::_(RouteHelper::getDownloadRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language));
			
            // Used in placeholder {link_to_details} This is the Read More link
            $detail_link_text = '<a href="'.$title_link.'">'.Text::_('COM_JDOWNLOADS_FE_DETAILS_LINK_TEXT_TO_DETAILS').'</a>';  //used in placeholder {link_to_details} this is the Read More link	
			
            $download_name = $this->escape($files[$i]->title);
			
            if ($params->get('view_detailsite')){
				$title_link_text = '<a href="'.$title_link.'">'.$this->escape($files[$i]->title).'</a>';	
			} else {
				$title_link_text = $this->escape($files[$i]->title);	
			}
            $detail_link_text = '<a href="'.$title_link.'">'.Text::_('COM_JDOWNLOADS_FE_DETAILS_LINK_TEXT_TO_DETAILS').'</a>';
            
            // Use if detail link required
            $link_detail = $title_link;   
			
            // Sort out which link is required and place that link in $link_required
			if ($params->get('direct_download')) {
				if ($params->get('view_detailsite')) {
					// Direct download = Yes and view_detailsite = Yes
                    $link_required = $link_detail;   
				} else {
					// Direct download = Yes and view_detailsite = No
					if ($has_no_file) {
						// Special case when no file so force link to detail
                        $link_required = $link_detail;  
                    } else {
					    // Normally direct download	
                        $link_required = $link_direct;   
					}
                }
			} else {
                if ($params->get('view_detailsite')){
					// Direct download = No and view_detailsite = Yes
				    $link_required = $link_detail;
                } else {
					// Direct download = No and view_detailsite = No
					if (!$has_no_file) {
						// Special case when file so force link to summary
                        $link_required = $link_summary;  
                    } else {
						// Normally link to detail
						$link_required = $link_detail;
                    }
                }
            }
			
            // If licence, password or captcha required and Download has a file then force summary view 
			if (($license_pword_captcha_reqd == '1')  && (!$has_no_file)) {
				// Has licence, password or captcha, and has a file
				if ($params->get('view_detailsite')) {
					// License etc required, has a file and view_detailsite = Yes				     
					$link_required = $link_detail;  
		        } else {
					// License etc required, has a file and view_detailsite = No			         
					$link_required = $link_summary;
            	}    
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
            if ($files[$i]->file_pic != '' ) {
                $filepic = $pic_link.'<img src="'.$file_pic_folder.$files[$i]->file_pic.'" style="text-align:top;border:0px;" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" alt="'.substr($files[$i]->file_pic,0,-4).$i.'"/>'.$pic_end;
            } else {
                $filepic = '';
            }
            $html_file = str_replace('{file_pic}', $filepic, $html_file);
			
            // Now we check whether we need a link in the title to start the download process, a link to the detail page or the title without a link
			if ($params->get('use_download_title_as_download_link')){
				$download_link_text = '<a href="'.$link_required.'" title="'.JText::_('COM_JDOWNLOADS_LINKTEXT_DOWNLOAD_URL').'" class="jd_download_url">'.$files[$i]->title.'</a>';
			} elseif ($params->get('view_detailsite')) {
                $download_link_text = $title_link_text;
            } else {
				$download_link_text = $files[$i]->title;
            }
            
            // Add edit symbol as defined earlier              
			$download_link_text .= $editIcon;
			$html_file = str_replace('{file_title}',$download_link_text, $html_file);
            
            // Add access level
            $html_file = str_replace('{access_title}', Text::_('COM_JDOWNLOADS_ACCESS').':', $html_file);  
            $user_group = $userhelper->load($files[$i]->access);
            $html_file = str_replace('{access}', $user_group->title, $html_file);
            
            //--------------------- End of sorting out the title and symbol links ---------------------            
            
            // The link to detail view is always displayed - when not required must be removed the placeholder from the layout
            $html_file = str_replace('{link_to_details}', $detail_link_text, $html_file);
            
            // Build website url
            if (!$files[$i]->url_home == '') {
                 if (strpos($files[$i]->url_home, 'http://') !== false) {    
                     $html_file = str_replace('{url_home}',$pic_website.'<a href="'.$files[$i]->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $html_file);
                     $html_file = str_replace('{author_url_text} ',$pic_website.'<a href="'.$files[$i]->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $html_file);
                 } else {
                     $html_file = str_replace('{url_home}',$pic_website.'<a href="http://'.$files[$i]->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $html_file);
                     $html_file = str_replace('{author_url_text}',$pic_website.'<a href="http://'.$files[$i]->url_home.'" target="_blank" title="'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'">'.Text::_('COM_JDOWNLOADS_FRONTEND_HOMEPAGE').'</a> '.$extern_url_pic, $html_file);
                 }    
            } else {
                $html_file = str_replace('{url_home}', '', $html_file);
                $html_file = str_replace('{author_url_text}', '', $html_file);
            }

            // encode is link a mail
            if (strpos($files[$i]->url_author, '@') && $params->get('mail_cloaking')){
                if (!$files[$i]->author) { 
                    $mail_encode = HTMLHelper::_('email.cloak', $files[$i]->url_author);
                } else {
                    $mail_encode = HTMLHelper::_('email.cloak',$files[$i]->url_author, true, $files[$i]->author, false);
                }        
            }
                    
            // build author link
            if ($files[$i]->author <> ''){
                if ($files[$i]->url_author <> '') {
                    if ($mail_encode) {
                        $link_author = $pic_author.$mail_encode;
                    } else {
                        if (strpos($files[$i]->url_author, 'http://') !== false) {    
                            $link_author = $pic_author.'<a href="'.$files[$i]->url_author.'" target="_blank">'.$files[$i]->author.'</a> '.$extern_url_pic;
                        } else {
                            $link_author = $pic_author.'<a href="http://'.$files[$i]->url_author.'" target="_blank">'.$files[$i]->author.'</a> '.$extern_url_pic;
                        }        
                    }
                    $html_file = str_replace('{author}',$link_author, $html_file);
                    $html_file = str_replace('{author_text}',$link_author, $html_file);
                    $html_file = str_replace('{url_author}', '', $html_file);
                } else {
                    $link_author = $pic_author.$files[$i]->author;
                    $html_file = str_replace('{author}',$link_author, $html_file);
                    $html_file = str_replace('{author_text}',$link_author, $html_file);
                    $html_file = str_replace('{url_author}', '', $html_file);
                }
            } else {
                $html_file = str_replace('{url_author}', $pic_author.$files[$i]->url_author, $html_file);
                $html_file = str_replace('{author}','', $html_file);
                $html_file = str_replace('{author_text}','', $html_file); 
            }

            // Set system value
            $file_sys_values = explode(',' , JDHelper::getOnlyLanguageSubstring($params->get('system_list')));
            if ($files[$i]->system == 0 ) {
                $html_file = str_replace('{system}', '', $html_file);
                 $html_file = str_replace('{system_text}', '', $html_file); 
            } else {
                $html_file = str_replace('{system}', $pic_system.$file_sys_values[$files[$i]->system], $html_file);
                $html_file = str_replace('{system_text}', $pic_system.$file_sys_values[$files[$i]->system], $html_file);
            }

            // Set file language value
            $file_lang_values = explode(',' ,JDHelper::getOnlyLanguageSubstring($params->get('language_list')));
            if ($files[$i]->file_language == 0 ) {
                $html_file = str_replace('{language}', '', $html_file);
                $html_file = str_replace('{language_text}', '', $html_file);
            } else {
                $html_file = str_replace('{language}', $pic_language.$file_lang_values[$files[$i]->file_language], $html_file);
                $html_file = str_replace('{language_text}', $pic_language.$file_lang_values[$files[$i]->file_language], $html_file);
            }

            // Insert rating system
            if ($params->get('view_ratings')){
                $rating_system = JDHelper::getRatings($files[$i]->id, $user_can_see_download_url, $files[$i]->rating_count, $files[$i]->rating_sum);
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
            
            // Insert the Joomla Fields data when used 
            if (isset($files[$i]->jcfields) && count((array)$files[$i]->jcfields)){
                $fields = $files[$i]->jcfields;
                foreach ($fields as $field){
                    if ($params->get('remove_field_title_when_empty') && !$field->value){
                        // Remove label placeholder
                        $html_file = str_replace('{jdfield_title '.$field->id.'}', '', $html_file);   
                        // Remove value placeholder
                        $html_file = str_replace('{jdfield '.$field->id.'}', '', $html_file);         
                    } else {
                        // Insert label
                        $html_file = str_replace('{jdfield_title '.$field->id.'}', $field->label, $html_file);  
                        // Insert value
                        $html_file = str_replace('{jdfield '.$field->id.'}', $field->value, $html_file);        
                    }
                }
                
                // In the layout could still exist not required field placeholders
                $results = JDHelper::searchFieldPlaceholder($html_file);
                if ($results){
                    foreach ($results as $result){
                        // Remove label and value placeholder
                        $html_file = str_replace($result[0], '', $html_file);   
                    }
                } 
            } else {
                // In the layout could still exist not required field placeholders
                $results = JDHelper::searchFieldPlaceholder($html_file);
                if ($results){
                    foreach ($results as $result){
                        // Remove label and value placeholder
                        $html_file = str_replace($result[0], '', $html_file);
                    }
                }
            }
            
            $html_file = str_replace('{downloads}',$pic_downloads.JDHelper::strToNumber((int)$files[$i]->downloads), $html_file);
            $html_file = str_replace('{hits_value}',$pic_downloads.JDHelper::strToNumber((int)$files[$i]->downloads), $html_file);
            $html_file = str_replace('{ordering}',$files[$i]->ordering, $html_file);
            $html_file = str_replace('{published}',$files[$i]->published, $html_file);
            
            // Support for content plugins 
            if ($params->get('activate_general_plugin_support')) {  
                $html_file = HTMLHelper::_('content.prepare', $html_file, '', 'com_jdownloads.download');
            }

            // Add the content plugin event 'after display content'
            if (strpos($html_file, '{after_display_content}') > 0){
                $html_file = str_replace('{after_display_content}', $files[$i]->event->afterDisplayContent, $html_file);
                $event = '';
            } else {
                $event = $files[$i]->event->afterDisplayContent;    
            }

            $html_files .= $html_file;
            
            // Finaly add the 'after display content' event output when required
            $html_files .= $event;
        }

        // Add template_before_text and template_after_text
        $html_files = $layout_files->template_before_text.$html_files.$layout_files->template_after_text;
        
        // Display only downloads area when it exist data here
        if ($total_downloads > 0){
            $body_cat = str_replace('{files}', $html_files, $body_cat);
        } else {
            $no_files_msg = '';
            if ($params->get('view_no_file_message_in_empty_category')){
                $no_files_msg = '<br />'.Text::_('COM_JDOWNLOADS_FRONTEND_NOFILES').'<br /><br />';            
            } 
            $body_cat = str_replace('{files}', $no_files_msg, $body_cat);
        }    

        $checkbox_top_is_form = false;
        
        // Display top checkbox only when the user can download any files here - right access permissions
        if ($user_can_see_download_url && $amount_downloads_with_file){ 
            $checkbox_top = '<form name="down'.$formid.'" action="'.Route::_('index.php?option=com_jdownloads&amp;view=summary&amp;Itemid='.$file_itemid).'"
                    onsubmit="return pruefen('.$formid.',\''.Text::_('COM_JDOWNLOADS_JAVASCRIPT_TEXT_1').'\');" method="post">
                    '.JDHelper::getOnlyLanguageSubstring($params->get('checkbox_top_text')).'<input type="checkbox" class="jd_files_checkbox" name="toggle" title="'.Text::_('COM_JDOWNLOADS_CATEGORY_CHECKBOX_HINT').'"
                    value="" onclick="checkAlle('.$i.','.$formid.');">';
            
            // View top checkbox only when activated in layout
            if ($layout_files->checkbox_off == 0 && !empty($files)) {
               $body_cat = str_replace('{checkbox_top}', $checkbox_top, $body_cat);
               $checkbox_top_is_form = true;
            } else {
               $body_cat = str_replace('{checkbox_top}', '', $body_cat);
            }   
        } else {
            // View message for missing access permissions
            if (!$user_can_see_download_url){
                if ($user->guest){
                    $regg = str_replace('<br />', '', Text::_('COM_JDOWNLOADS_FRONTEND_CAT_ACCESS_REGGED'));
                } else {
                    $regg = str_replace('<br />', '', Text::_('COM_JDOWNLOADS_FRONTEND_FILE_ACCESS_REGGED_LIST'));
                }               
            
                if ($total_downloads > 0){
                    $body_cat = str_replace('{checkbox_top}', '<span class="label label-info" style="margin-top: 5px;"><strong>'.$regg.'</strong></span>', $body_cat);                    
                } else {
                    $body_cat = str_replace('{checkbox_top}', '', $body_cat);                    
                }    
            } else {
                $body_cat = str_replace('{checkbox_top}', '', $body_cat);                    
            }
        }
        
        $form_hidden = '<input type="hidden" name="boxchecked" value=""/> ';
        $body_cat = str_replace('{form_hidden}', $form_hidden, $body_cat);
        $body_cat .= '<input type="hidden" name="catid" value="'.$catid.'"/>';
        $body_cat .= HTMLHelper::_( 'form.token' );
        if ($checkbox_top_is_form){
            $body_cat .= '</form>';
        }        
                
        // view submit button only when checkboxes are activated
        $button = '<input class="button" type="submit" name="weiter" value="'.Text::_('COM_JDOWNLOADS_FORM_BUTTON_TEXT').'"/>';
        
        // view only submit button when user has correct access level and checkboxes are used in layout
        if ($layout_files->checkbox_off == 0 && !empty($files) && ($user_can_see_download_url && $amount_downloads_with_file)) {
            $body_cat = str_replace('{form_button}', $button, $body_cat);
        } else {
            $body_cat = str_replace('{form_button}', '', $body_cat);
        }        
        
        $html .= $body_cat;   
    }    
        
    // ==========================================
    // FOOTER SECTION  
    // ==========================================

    // Display pagination for the Downloads from the current category when the placeholder is placed in the footer area from the category layout 
    $cat_footer = JDHelper::insertPagination( $this->pagination, $cat_footer, $params->get('option_navigate_bottom'), $this->params->get('show_pagination'), $this->params->get('show_pagination_results') );
    
    // components footer text
    if ($params->get('downloads_footer_text') != '') {
        $cat_footer_text = stripslashes(JDHelper::getOnlyLanguageSubstring($params->get('downloads_footer_text')));
        
        // replace both Google adsense placeholder with script
        $cat_footer_text = JDHelper::insertGoogleAdsenseCode($cat_footer_text);   
        $html .= $cat_footer_text;
    }
    
    // back button
    if ($params->get('view_back_button')){
        $cat_footer = str_replace('{back_link}', '<a href="javascript:history.go(-1)">'.Text::_('COM_JDOWNLOADS_FRONTEND_BACK_BUTTON').'</a>', $cat_footer); 
    } else {
        $cat_footer = str_replace('{back_link}', '', $cat_footer);
    }    
    
    $cat_footer .= JDHelper::checkCom();
   
    $html .= $cat_footer; 
    
    $html .= '</div>';

    // remove empty html tags
    if ($params->get('remove_empty_tags')){
        $html = JDHelper::removeEmptyTags($html);
    }
    
    // ==========================================
    // VIEW THE BUILDED OUTPUT
    // ==========================================

    if (!$params->get('offline')) {
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