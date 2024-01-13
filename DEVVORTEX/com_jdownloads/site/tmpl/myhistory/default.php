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

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper; 
 
    // Create shortcuts to some parameters
    $jd_user_settings = $this->user_rules;
    $params           = $this->params;
    $items            = $this->items;
    
    $user = Factory::getUser();
    $user->authorise('core.admin') ? $is_admin = true : $is_admin = false;    
    $groups = $user->getAuthorisedViewLevels();
    
    $html        = '';
    $date_format = JDHelper::getDateFormat();
    
    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($params->get('selected_file_type_icon_set'));

    $list_header = '<div class="jd_history_cols_titles" style="">{col_title}</div>';
    
    // Layout parts with placeholder to build later the output
    $subheader = '<div class="page-header">
                    <h2 class="jd_files_subheader_title" style="">{subheader_title}</h2>
                  </div>
                  <div class="jd_clear"></div>
                  <div class="jd_files_title" style="">{amount_of_files}
                  </div>
                  <div class="jd_footer jd_page_nav" style="">{page_navigation}</div>';
                  
    
    $items_body = '<div class="jd_history_content_wrapper">
                     <div class="jd_clear" style="width:100%;">
                          <div class="jd_left" style="line-height:'.$params->get('file_pic_size_height').'px;">{file_pic}&#160; &#160;{file_title}&#160; &#160;{release}&#160; &#160;{file_name}&#160; &#160;{filesize_value}&#160; &#160;{price}</div>
                          <div class="jd_right" style="line-height:'.$params->get('file_pic_size_height').'px;">{log_date}</div>
                          <div class="jd_clear" style=""></div>
                     </div>
                   </div>
                   <div class="jd_clear" style=""></div>';


    $footer_area = '<div class="jd_footer jd_page_nav" style="">{page_navigation}</div>';
    
    $html = '<div class="jd-item-page'.$this->pageclass_sfx.'">';
    
    if ($this->params->get('show_page_heading')) {
        $html .= '<h1>'.$this->escape($this->params->get('page_heading')).'</h1>';
    } 
    
    // ==========================================
    // HEADER SECTION
    // ==========================================

    $total_downloads  = $this->pagination->total;

    // display number of sub categories only when > 0 
    if ($total_downloads == 0){
        $total_files_text = '';
    } else {
        $total_files_text = Text::sprintf('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_AMOUNT_DOWNLOADED_FILES', (int)$total_downloads);
    }
    
    $subheader = str_replace('{subheader_title}', Text::_('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_TITLE'), $subheader);
    
    $subheader = JDHelper::insertPagination( $this->pagination, $subheader, $params->get('option_navigate_top'), $this->params->get('show_pagination'), $this->params->get('show_pagination_results') );

    // Display amount of files - we use the sub categories placeholder
    $subheader = str_replace('{count_of_sub_categories}', $total_files_text, $subheader); 
    $subheader = str_replace('{amount_of_files}', $total_files_text, $subheader);                                                   

    $html .= $subheader;            
    
    // ==========================================
    // BODY SECTION - VIEW THE LOGS DATA
    // ==========================================
    
    $html_files = '';
    
    for ($i = 0; $i < count($items); $i++) {
        
        $html_file = $items_body;
        
        $file_id = $items[$i]->id;
        
        // File pic
        if ($items[$i]->file_pic != '' ) {
            $filepic = '<img src="'.$file_pic_folder.$items[$i]->file_pic.'" style="text-align:top;border:0px;" width="'.$params->get('file_pic_size').'" height="'.$params->get('file_pic_size_height').'" alt="'.substr($items[$i]->file_pic,0,-4).$i.'" />';
        } else {
            $filepic = '';
        }
        $html_file = str_replace('{file_pic}', $filepic, $html_file);

        // Check whether the user has category access right 
        if (in_array($items[$i]->category_access, $groups)){
            $cat_access = true; 
        } else { 
            $cat_access = false;
        }

        // // Check whether the user has File access right  
        if (in_array($items[$i]->access, $groups)){
            $file_access = true; 
        } else { 
            $file_access = false;
        }
        
        // We display only a link to detail view when file and category is still published and user has access rights - otherwise we display only the file title as information
        if ($cat_access && $file_access && $items[$i]->published && $items[$i]->category_published){
        $title_link = Route::_(RouteHelper::getDownloadRoute($items[$i]->slug, $items[$i]->catid, $items[$i]->language));
        $title_link_text = '<a href="'.$title_link.'">'.$this->escape($items[$i]->title).'</a>';
        $html_file = str_replace('{file_title}', $title_link_text, $html_file);
        } else {
            // display only the title without link
            $html_file = str_replace('{file_title}', $this->escape($items[$i]->title), $html_file);
        }
        
        // File version (release)
        if ($items[$i]->release == '' ) {
            $html_file = str_replace('{release}', '', $html_file);
        } else {
            $html_file = str_replace('{release}', $items[$i]->release.' ', $html_file);
        }
        
        // File name
        if ($items[$i]->url_download){
            
            $filename = JDHelper::getShorterFilename($this->escape($items[$i]->url_download));
            $html_file = str_replace('{file_name}', $filename, $html_file);
        } else {
            $html_file = str_replace('{file_name}', '', $html_file);
        }

        // Price
        if ($items[$i]->price){
            $price = $this->escape($items[$i]->price);
            $html_file = str_replace('{price}', $price, $html_file);
        } else {
            $html_file = str_replace('{price}', '', $html_file);
        }
        
        // File size
        if ($items[$i]->size == '' || $items[$i]->size == '0 B') {
            $html_file = str_replace('{filesize_value}', '', $html_file);
        } else {
            $html_file = str_replace('{filesize_value}', $items[$i]->size, $html_file);
        }
        
        // log date
        if ($items[$i]->log_datetime != '0000-00-00 00:00:00' && $items[$i]->log_datetime != null) {
             if ($this->params->get('show_date') == 0){ 
                 $filedate_data = HTMLHelper::_('date',$items[$i]->log_datetime, $date_format['long']);
             } else {
                 $filedate_data = HTMLHelper::_('date',$items[$i]->log_datetime, $date_format['short']);
             }    
        } else {
             $filedate_data = '';
        }

        $html_file = str_replace('{log_file_title}', '<b>'.$items[$i]->log_title.'</b> ('.basename($items[$i]->log_file_name).')', $html_file);
        $html_file = str_replace('{log_date}', $filedate_data, $html_file);
        
        $html_files .= $html_file;
    }
        
    $html .= $html_files;   
  
    // ==========================================
    // FOOTER SECTION  
    // ==========================================

    $footer = '';
    
    // Display pagination for the Downloads when the placeholder is placed in the footer area from the Downloads layout 
    $footer = JDHelper::insertPagination( $this->pagination, $footer, $params->get('option_navigate_bottom'), $this->params->get('show_pagination'), $this->params->get('show_pagination_results') );

    $footer .= JDHelper::checkCom();
    $html   .= $footer; 
    $html   .= '</div>';

    // ==========================================
    // VIEW THE BUILDED OUTPUT
    // ==========================================

    if ( !$this->params->get('offline') ) {
            echo $html;
    } else {
        // admins can view it always
        if ($is_admin) {
            echo $html;     
        } else {
            // build the offline message
            $html = '';
            // offline message
            if ($this->params->get('offline_text') != '') {
                $html .= JDHelper::getOnlyLanguageSubstring($this->params->get('offline_text'));
            }
            echo $html;    
        }
    }     
    
?>