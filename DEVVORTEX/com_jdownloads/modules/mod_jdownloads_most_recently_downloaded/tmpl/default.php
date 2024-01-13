<?php
/**
* @version $Id: mod_jdownloads_most_recently_downloaded.php
* @package mod_jdownloads_most_recently_downloaded
* @copyright (C) 2008/2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

/** This Module shows the Most Recently Downloaded files from the component jDownloads. 
*   Support: www.jDownloads.com
*/

// This is a default layout without tables - you can also create an alternate layout and select it afterwards in the module configuration

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\Language\Text;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Module\JDownloadsMostRecentlyDownloaded\Site\Helper\JDownloadsMostRecentlyDownloadedHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($jdparams->get('selected_file_type_icon_set'));
    
    $html = '';
    $html = '<div style="width:100%;" class="jd_table moduletable'.$moduleclass_sfx.'">';
    
    $sum_files = count($files);
    if ($sum_view > $sum_files) $sum_view = $sum_files;
 
    if ($files) {
        if ($text_before <> ''){
			$html .= '<div class="jd_module_before" style="text-align:'.$alignment.'">'.$text_before.'</div>';			
        }
        for ($i=0; $i<$sum_view; $i++) {
            $html .= '<div style="clear:both;"></div>';
            $has_no_file = false;
            $cat_show_text2 = '';
            
            if (!$files[$i]->url_download && !$files[$i]->other_file_id && !$files[$i]->extern_file){
               // Only a document without file
               $has_no_file = true;           
            }
            // Get the first image as thumbnail when it exist           
            $thumbnail = ''; 
            $first_image = '';
            
            if (isset($files[$i]->images)){
                $images = explode("|", $files[$i]->images);
                if (isset($images[0])) $first_image = $images['0'];			
            }
			
            $version = $params->get('short_version', '');
            if ($sum_char > 0){
                $title_length = strlen($files[$i]->title) + strlen($files[$i]->release) + strlen($short_version) +1;
                if ($title_length > $sum_char){
                   $files[$i]->title = \Joomla\String\StringHelper::substr($files[$i]->title, 0, $sum_char).$short_char;
                   $files[$i]->release = '';
                }    
            }
            
			if ($cat_show && $files[$i]->catid > 1) {
				if ($cat_show_type == 'containing') {
					$cat_show_text2 = $files[$i]->category_title;
				} else {
                    if (isset($files[$i]->category_cat_dir_parent)){
                        $cat_show_text2 = $files[$i]->category_cat_dir_parent.'/'.$files[$i]->category_cat_dir;
                    } else {
                        if (isset($files[$i]->category_cat_dir)) $cat_show_text2 = $files[$i]->category_cat_dir;
                    }
				}
			}    

            // Create the link
            if ($files[$i]->link == '-'){
                // The user have the access to view this item
                if ($detail_view == '1'){
                    if ($detail_view_config == 0){                    
                        // The details view is deactivated in jD config so the
                        // Link must start directly the download process
                        if ($direct_download_config == 1){
                            if (!$has_no_file){
                                $link = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, 'download.send'));
                            } else {
                                // Create a link to the Downloads category as this download has not a file
                                if ($files[$i]->menu_cat_itemid){
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_cat_itemid);
                                } else {
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                                }
                            }   
                        } else {
                            // Link to the summary page
                            if (!$has_no_file){
                                $link = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, 'summary'));
                            } else {
                                // Create a link to the Downloads category as this download has not a file
                                if ($files[$i]->menu_cat_itemid){
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_cat_itemid);
                                } else {
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                                }
                            }   
                        }    
                    } else {
                        // Create a link to the details view
                        if ($files[$i]->menu_itemid){
                            $link = Route::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_itemid);                    
                        } else {
                            if ($files[$i]->menu_cat_itemid){
                                $link = Route::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_cat_itemid);                    
                            } else {
                            	$link = Route::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);                    
                            }    
                        }
                    }                       
                } else {    
                    // Create a link to the Downloads category
                    if ($files[$i]->menu_cat_itemid){
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_cat_itemid);
                    } else {
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                    }
                }    
            } else {
                // The user has NO access to view this item
                $link = $files[$i]->link;
            }
            
            if (!$files[$i]->release) $version = '';
            
            // Build icon
            $size = 0;
            $files_pic = '';
            $number = '';
            
            if ($view_pics && $files[$i]->file_pic != ''){
				$size = (int)$view_pics_size;
				if ($view_pics_link) {
					$pic_link = '<a href="'.$link.'">';
					$pic_end = '</a>';
				}
				else {
					$pic_link = '';
					$pic_end = '';
				}
				$files_pic = $pic_link.'<img src="'.$file_pic_folder.$files[$i]->file_pic.'" style="text-align:top;border:0px;" width="'.$size.'" height="'.$size.'" alt="'.substr($files[$i]->file_pic, 0, -4).'-'.$i.'"/>'.$pic_end; 
			}
            
            // Build number list
            if ($view_numerical_list){
                $num = $i+1;
                $number = "$num. ";
            }
            
            // Build version message including space char
			$version_msg = '';
			if ($files[$i]->release) {
				$version_msg = '&nbsp;'.$version.$files[$i]->release;
			} 
			
            $text = HTMLHelper::_('string.truncate', $files[$i]->description, $view_tooltip_length, true, false);
              
            // Build description in tooltip 
            if ($view_tooltip && $text != ''){
                $title   = htmlspecialchars($files[$i]->title, ENT_QUOTES, 'UTF-8');
                $tooltip = '<strong><small>' . htmlspecialchars(Text::_('MOD_JDOWNLOADS_MOST_RECENTLY_DOWNLOADED_DESCRIPTION_TITLE'), ENT_QUOTES, 'UTF-8') . '</strong><br>' . $text . '</small>';
                
                $link_text = '<a href="' . $link . '" class="">' . $title . '</a>'
                            . '<div role="tooltip" id="tip-' . (int) $files[$i]->id . '-' . (int) $files[$i]->catid . '">' . $tooltip . '</div>';
			} else {    
				$link_text = '<a href="'.$link.'">'.$files[$i]->title.'</a>'.$version_msg;
			}
			
            // Build date msg
			$link_div= '<div style="text-align:'.$alignment.'">'.$number.$files_pic.$link_text;
			$link_end = '</div>';
			$date_same_line = '';  // When date not shown or not set
			$date_own_line = '';
            if ($view_date) {
                if ($files[$i]->log_datetime){
                    if ($view_date_same_line){
						$date_same_line = '<scan class="jd_module_date_sameline" style="text-align:'.$date_alignment.';">&nbsp;'.$view_date_text.substr(HTMLHelper::Date($files[$i]->log_datetime,$date_format),0,10).'</scan>';
                    } else {
						$date_own_line= '<div class="jd_module_date_newline" style="text-align:'.$date_alignment.';">'.$view_date_text.substr(HTMLHelper::Date($files[$i]->log_datetime,$date_format),0,10).'</div>';
                    } 
                }    
            }
			// Add date to html
			if ($view_date_same_line) {
				$html .= $link_div.$date_same_line.$link_end;	// Date on same line
			} else {
				$html .= $link_div.$date_own_line.$link_end;	// Show date on separate line
			}
			// Show user name
			$user_name ='';
			if ($files[$i]->username){
				$user_name = $files[$i]->username;
			} else {
				$user_name = $view_user_name;
			}
			if ($view_user&&$user_name){
				$html .= '<div style="clear:both;"></div>';
                $html .= '<div style="text-align:'.$date_alignment.'" class="">'.$view_user_by.'&nbsp;'.$user_name.'</div>';
            }    		
            // Add category info on new line
            if ($cat_show_text2) {
				$html .= '<div style="clear:both;"></div>';
				if ($cat_show_as_link){
                    if ($files[$i]->menu_cat_itemid){
						$html .= '<div style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text.'<a href="index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_cat_itemid.'">'.$cat_show_text2.'</a></div>';
				    } else {
						$html .= '<div style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text.'<a href="index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid.'">'.$cat_show_text2.'</a></div>';
                    }    
                } else {    
					$html .= '<div style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text.$cat_show_text2.'</div>';
                }
			}
				$html .= '<div style="margin-bottom: 10px;></div>';
        }
		$html .= '<div style="clear:both;"></div>';
        if ($text_after <> ''){
            $html .= '<div class="jd_module_after" style="text-align:'.$alignment.'">'.$text_after.'</div>';
        }
    }
    echo $html.'</div>';
?>		