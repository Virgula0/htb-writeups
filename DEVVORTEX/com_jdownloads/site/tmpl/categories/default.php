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
    
    $db         = Factory::getDBO(); 
    $document   = Factory::getDocument();
    $jinput     = Factory::getApplication()->input;
    $app        = Factory::getApplication();    
    $user       = Factory::getUser();
    
    $jd_user_settings = $this->jd_user_settings;
    
    // For Tooltip
    HTMLHelper::_('bootstrap.tooltip');
    
    // Create shortcuts to some parameters.
    $params    = $this->params;
    $cats      = $this->items;
    
    $html           = '';
    $body           = '';
    $footer_text    = '';
    $layout         = '';
    
    $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;

    // Check that we have a layout
    $layout = $this->layout;
    if ($layout){
        // Unused language placeholders must at first get removed from layout
        $layout_cat_text = JDHelper::removeUnusedLanguageSubstring($layout->template_text);
        $cats_before     = JDHelper::removeUnusedLanguageSubstring($layout->template_before_text);
        $cats_after      = JDHelper::removeUnusedLanguageSubstring($layout->template_after_text);
        $header          = JDHelper::removeUnusedLanguageSubstring($layout->template_header_text);
        $subheader       = JDHelper::removeUnusedLanguageSubstring($layout->template_subheader_text);
        $footer          = JDHelper::removeUnusedLanguageSubstring($layout->template_footer_text);
        $bootstrap       = $layout->uses_bootstrap;
        $w3css           = $layout->uses_w3css;
    } else {
        // We have not a valid layout data
        echo '<big>No valid layout found for Categories!</big>';
    }
    
    $total_cats  = count($cats);
    
    // get current category menu ID when exist and all needed menu IDs for the header links
    $menuItemids = JDHelper::getMenuItemids(0);
    
    // get all other menu category IDs so we can use it when we needs it
    $cat_link_itemids = JDHelper::getAllJDCategoryMenuIDs();
    
    // "Home" menu link itemid
    $root_itemid =  $menuItemids['root'];

    // Get CSS button settings
    $menu_color             = $params->get('css_menu_button_color');
    $menu_size              = $params->get('css_menu_button_size');
    
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

    // Build page navigation
    $page_navi_links = $this->pagination->getPagesLinks(); 
    if ($page_navi_links){
        $page_navi_pages   = Text::_('COM_JDOWNLOADS_CATEGORIES').': '.$this->pagination->getPagesCounter(); 
        $page_navi_counter = $this->pagination->getResultsCounter(); 
        $page_limit_box    = $this->pagination->getLimitBox();  
    } 
       
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

        // We don't need a link to a parent category because it doesn't exist.
        $header = str_replace('{upper_link}', '', $header);    
                
        // Create category listbox and viewed it when it is activated in configuration
        if ($params->get('show_header_catlist')){

            // get current selected cat id from listbox
            $catlistid = $jinput->get('catid', '0', 'integer');
            
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

        // display number of sub categories only when > 0 
        if ($total_cats == 0){
            $total_subcats_text = Text::_('COM_JDOWNLOADS_NUMBER_OF_CATEGORIES_LABEL').': - ';
        } else {
            $total_subcats_text = Text::_('COM_JDOWNLOADS_NUMBER_OF_CATEGORIES_LABEL').': '.$total_cats;
        }
        
        // display category title
        $subheader = str_replace('{subheader_title}', Text::_('COM_JDOWNLOADS_FRONTEND_SUBTITLE_OVER_CATLIST'), $subheader);            

        // display pagination            
        if ($params->get('option_navigate_top') && $this->pagination->pagesTotal > 1 && $this->params->get('show_pagination') != '0' 
            || (!$params->get('option_navigate_top') && $this->pagination->pagesTotal > 1 && $this->params->get('show_pagination') == '1') )
        {
            $subheader = str_replace('{page_navigation}', $page_navi_links, $subheader);
            $subheader = str_replace('{page_navigation_results_counter}', $page_navi_counter, $subheader);
        
            if ($this->params->get('show_pagination_results') == null || $this->params->get('show_pagination_results') == '1'){
                $subheader = str_replace('{page_navigation_pages_counter}', $page_navi_pages, $subheader); 
            } else {
                $subheader = str_replace('{page_navigation_pages_counter}', '', $subheader);                
            }                                   
        
            $subheader = str_replace('{count_of_sub_categories}', $total_subcats_text, $subheader); 
        } else {
            $subheader = str_replace('{page_navigation}', '', $subheader);
            $subheader = str_replace('{page_navigation_results_counter}', '', $subheader);
            $subheader = str_replace('{page_navigation_pages_counter}', '', $subheader);                
            $subheader = str_replace('{count_of_sub_categories}', $total_subcats_text, $subheader);                
        }

        // remove this placeholder when it is used not for this layout
        $subheader = str_replace('{sort_order}', '', $subheader); 
        
        // replace both Google adsense placeholder with script
        $subheader = JDHelper::insertGoogleAdsenseCode($subheader);        
        $html .= $subheader;            
    }
    
    // ==========================================
    // BODY SECTION - VIEW THE CATEGORIES
    // ==========================================

    $html_cat = '';
    $metakey  = '';
    
    // Required to add later access name
    $userhelper = new UserGroupsHelper();
    
    $show_description       = $this->params->get('show_description');
    $truncated_cat_desc_len = $params->get('auto_cat_short_description_value');

    if ($total_cats < $this->pagination->limit){
        $amount = $total_cats;
    } else {
        if (($this->pagination->limitstart + $this->pagination->limit) > $total_cats){
            $amount = $total_cats; 
        } else {
            $amount = $this->pagination->limitstart + $this->pagination->limit;
        }
    }    

    if ($layout_cat_text != ''){

        if (!empty($cats)){
            
            $html_cat = $cats_before;

            for ($i=$this->pagination->limitstart; $i<$amount; $i++) {
            
                $html_cat .= $layout_cat_text;
               
                // Exist a single category menu link for this, when we must use it here
                if (!$cats[$i]->menu_itemid){
                    $cats[$i]->menu_itemid = $root_itemid;    
                }     
                $catlink = Route::_("index.php?option=com_jdownloads&amp;view=category&amp;catid=".$cats[$i]->id."&amp;Itemid=".$cats[$i]->menu_itemid);

                    // display category symbol/pic 
					if ($params->get('link_in_symbols')){
							$pic_link = '<a href="'.$catlink.'">';
							$pic_end = '</a>';
						} else {
							$pic_link = '';
							$pic_end = '';
						}
                //  display the categories icon with link - alt tag as picnameN eg folder0 for first, folder1 for second etc.
                if ($cats[$i]->pic != '' ) {
                    $catpic = $pic_link.'<img src="'.URI::base().'images/jdownloads/catimages/'.$cats[$i]->pic.'" style="text-align:top;border:0px;" width="'.$params->get('cat_pic_size').'" height="'.$params->get('cat_pic_size_height').'" alt="'.substr($cats[$i]->pic,0,-4).$i.'" />'.$pic_end;
                } else {
                    $catpic = '';
                }
               
                // more than one column   ********************************************************
                if ($layout->cols > 1 && strpos($layout_cat_text, '{cat_title1}')){
                    $a = 0;     

                    for ($a=0; $a < $layout->cols; $a++){
                       
                         $x = $a + 1;
                         $x = (string)$x;

                         if ( $i < $amount ){
                             if ($a == 0){
                                 $html_cat = str_replace("{cat_title$x}", '<a href="'.$catlink.'">'.$cats[$i]->title.'</a>', $html_cat);
                             } else {
                                 $html_cat = str_replace("{cat_title$x}", '<a href="'.$catlink.'">'.$cats[$i]->title.'</a>', $html_cat);
                             }
                             
                             $html_cat = str_replace("{cat_pic$x}", $catpic, $html_cat);

                             if ($show_description){
                                 if ($truncated_cat_desc_len > 0){
                                     if (StringHelper::strlen($cats[$i]->description) > $truncated_cat_desc_len){ 
                                         $shorted_text = HTMLHelper::_('string.truncate', $cats[$i]->description, $truncated_cat_desc_len, true, true); // Do not cut off words; HTML allowed;
                                         $html_cat = str_replace("{cat_description$x}", $shorted_text, $html_cat);
                                     } else {
                                         $html_cat = str_replace("{cat_description$x}", $cats[$i]->description, $html_cat);
                                     }    
                                 } else {
                                     $html_cat = str_replace("{cat_description$x}", $cats[$i]->description, $html_cat);
                                 }
                             } else {
                                 $html_cat = str_replace("{cat_description$x}", '', $html_cat);
                             }
                                
                             $cats[$i]->numitems = $countSubItems($cats[$i]);
                             
                             $html_cat = str_replace("{sum_subcats$x}", Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_SUBCATS').' '.$cats[$i]->subcatitems, $html_cat);
                             $html_cat = str_replace("{sum_files_cat$x}", Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_FILES').' '.(int)$cats[$i]->numitems, $html_cat);
                            
                             // tags creation
                             if ($this->params->get('show_cat_tags', 1) && !empty($cats[$i]->tags->itemTags)){
                                 $cats[$i]->tagLayout = new FileLayout('joomla.content.tags'); 
                                 $html_cat = str_replace("{tags$x}", $cats[$i]->tagLayout->render($cats[$i]->tags->itemTags), $html_cat); 
                                 $html_cat = str_replace("{tags_title$x}", Text::_('COM_JDOWNLOADS_TAGS_LABEL'), $html_cat); 
                             } else {
                                 $html_cat = str_replace("{tags$x}", '', $html_cat);
                                 $html_cat = str_replace("{tags_title$x}", '', $html_cat); 
                             }
                             
                             // Add access level
                             $html_cat = str_replace("{access_title$x}", Text::_('COM_JDOWNLOADS_ACCESS').':', $html_cat);  
                             $user_group = $userhelper->load($cats[$i]->access);
                             $html_cat = str_replace("{access$x}", $user_group->title, $html_cat);
                               
                         } else {
                            
                             $html_cat = str_replace("{cat_title$x}", '', $html_cat);
                             $html_cat = str_replace("{cat_pic$x}", '', $html_cat);
                             $html_cat = str_replace("{cat_description$x}", '', $html_cat);
                         }
                         if (($a + 1) < $layout->cols){
                             $i++;

                             if (isset($cats[$i])){
                                 // exists a single category menu link for this subcat? 
                                 if (!$cats[$i]->menu_itemid){
                                     $cats[$i]->menu_itemid = $root_itemid;    
                                 } 
                                                             
                                 $catlink = Route::_("index.php?option=com_jdownloads&amp;view=category&amp;catid=".$cats[$i]->id."&amp;Itemid=".$cats[$i]->menu_itemid);
                                
                                // Symbol anzeigen - auch als url                                                                                                                    
								// display category symbol/pic 
								if ($params->get('link_in_symbols')){
									$pic_link = '<a href="'.$catlink.'">';
									$pic_end = '</a>';
								} else {
									$pic_link = '';
									$pic_end = '';
								}
                                 if ($cats[$i]->pic != '' ) {
                                     $catpic = $pic_link.'<img src="'.URI::base().'images/jdownloads/catimages/'.$cats[$i]->pic.'" style="text-align:top;border:0px;" width="'.$params->get('cat_pic_size').'" height="'.$params->get('cat_pic_size_height').'" alt="'.substr($cats[$i]->pic,0,-4).$i.'" />'.$pic_end;
                                 } else {
                                     $catpic = '';
                                 }
                             } else {
                                 // we have more columns as rows so we need an empty layout
                                 break;
                             }
                         }  
                    }
                    
                    for ($b=1; $b < 10; $b++){
                        $x = (string)$b;
                        $html_cat = str_replace("{cat_title$x}", '', $html_cat);
                        $html_cat = str_replace("{cat_pic$x}", '', $html_cat);
                        $html_cat = str_replace("{sum_files_cat$x}", '', $html_cat); 
                        $html_cat = str_replace("{sum_subcats$x}", '', $html_cat);
                        $html_cat = str_replace("{tags$x}", '', $html_cat);
                        $html_cat = str_replace("{tags_title$x}", '', $html_cat);
						$html_cat = str_replace("{cat_description$x}", '', $html_cat);
                        $html_cat = str_replace("{access_title$x}", '', $html_cat);
                        $html_cat = str_replace("{access$x}", '', $html_cat);
                    }
                 
                } else {
                    // only single column layout
                    $html_cat = str_replace('{cat_title}', '<a href="'.$catlink.'">'.$cats[$i]->title.'</a>', $html_cat);
                     
                    if (!$cats[$i]->subcatitems){
                        $html_cat = str_replace('{sum_subcats}', Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_SUBCATS').' 0', $html_cat);
                    } else {
                        $html_cat = str_replace('{sum_subcats}', Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_SUBCATS').' '.$cats[$i]->subcatitems, $html_cat);
                    }
                    
                    $cats[$i]->numitems = $countSubItems($cats[$i]);
                    $html_cat = str_replace('{sum_files_cat}', Text::_('COM_JDOWNLOADS_FRONTEND_COUNT_FILES').' '.(int)$cats[$i]->numitems, $html_cat);
                 
	                // Tags creation
	                if ($this->params->get('show_cat_tags', 1) && !empty($cats[$i]->tags->itemTags)){
	                    $cats[$i]->tagLayout = new FileLayout('joomla.content.tags'); 
	                    $html_cat = str_replace('{tags}', $cats[$i]->tagLayout->render($cats[$i]->tags->itemTags), $html_cat); 
	                    $html_cat = str_replace('{tags_title}', Text::_('COM_JDOWNLOADS_TAGS_LABEL'), $html_cat); 
	                } else {
	                    $html_cat = str_replace('{tags}', '', $html_cat);
	                    $html_cat = str_replace('{tags_title}', '', $html_cat); 
	                }                 
                
                    // description
                    if ($show_description && isset($cats[$i])){
                        if ($truncated_cat_desc_len > 0){
                            if (StringHelper::strlen($cats[$i]->description) > $truncated_cat_desc_len){ 
                                $shorted_text = HTMLHelper::_('string.truncate', $cats[$i]->description, $truncated_cat_desc_len, true, true); // Do not cut off words; HTML allowed;
                                $html_cat = str_replace('{cat_description}', $shorted_text, $html_cat);
                            } else {
                                $html_cat = str_replace('{cat_description}', $cats[$i]->description, $html_cat);
                            }    
                        } else {
                            $html_cat = str_replace('{cat_description}', $cats[$i]->description, $html_cat);
                        }
                    } else {
                        $html_cat = str_replace('{cat_description}', '', $html_cat);
                    }
                }    
                 
                $html_cat = str_replace('{cat_pic}', $catpic, $html_cat);
                
                // Add access information 
                $html_cat = str_replace('{access_title}', Text::_('COM_JDOWNLOADS_ACCESS').':', $html_cat);
                $user_group = $userhelper->load($cats[$i]->access);
                $html_cat = str_replace('{access}', $user_group->title, $html_cat);
                
                // remove placeholders from old subcategories layouts - not more required
                $html_cat = str_replace('{cat_info_begin}', '', $html_cat); 
                $html_cat = str_replace('{cat_info_end}', '', $html_cat);

                if ($pos_end = strpos($html_cat, '{cat_title_end}')){
                    $pos_beg = strpos($html_cat, '{cat_title_begin}');
                    $html_cat = substr_replace($html_cat, '', $pos_beg, ($pos_end - $pos_beg) + 15);
                } 
                
                // replace both Google adsense placeholder with script
                $html_cat = JDHelper::insertGoogleAdsenseCode($html_cat);        
               
                // get meta keywords from every category
                if (isset($cats[$i]) && $cats[$i]->metakey){
                    $metakey = $metakey.' '.$cats[$i]->metakey; 
                }
            }
            
            $html_cat .= $cats_after;
                        
            // get meta keywords from global config or menu item
            $jmeta = $document->getMetaData( 'keywords' );
            if ($jmeta){
                // when it exists we will use it
                $document->setMetaData( 'keywords' , $jmeta);
            } else {
                // otherwise we will use the keywords from all categories
                $document->setMetaData( 'keywords' , strip_tags($metakey));
            }
            
        }
        
        $html .= $html_cat;   
    }

    // ==========================================
    // FOOTER SECTION  
    // ==========================================

    // display pagination            
    if ($params->get('option_navigate_bottom') && $this->pagination->pagesTotal > 1 && $this->params->get('show_pagination') != '0' 
        || (!$params->get('option_navigate_bottom') && $this->pagination->pagesTotal > 1 && $this->params->get('show_pagination') == '1') )
    {        
    
        $footer = str_replace('{page_navigation}', $page_navi_links, $footer);
        $footer = str_replace('{page_navigation_results_counter}', $page_navi_counter, $footer);
        
        if ($this->params->get('show_pagination_results') == null || $this->params->get('show_pagination_results') == '1'){
            $footer = str_replace('{page_navigation_pages_counter}', $page_navi_pages, $footer); 
        } else {
            $footer = str_replace('{page_navigation_pages_counter}', '', $footer);                
        }                                   
    } else {
        $footer = str_replace('{page_navigation}', '', $footer);
        $footer = str_replace('{page_navigation_results_counter}', '', $footer);
        $footer = str_replace('{page_navigation_pages_counter}', '', $footer);                
    }

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
        $html = HTMLHelper::_('content.prepare', $html, '', 'com_jdownloads.categories');
    }    
    
    // remove empty html tags
    if ($params->get('remove_empty_tags')){
        $html = JDHelper::removeEmptyTags($html);
    }    
    
    // ==========================================
    // VIEW THE BUILDED OUTPUT
    // ==========================================

    if ( !$params->get('offline') ) {
         if (isset($this->item->event->beforeDisplayContent)){   
            echo $this->item->event->beforeDisplayContent;
         }   
         echo $html;
         if (isset($this->item->event->afterDisplayContent)){   
            echo $this->item->event->afterDisplayContent;
         }         
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