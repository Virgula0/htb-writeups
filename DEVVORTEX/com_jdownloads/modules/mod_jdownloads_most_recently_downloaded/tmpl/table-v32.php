<?php
/**
* @version $Id: mod_jdownloads_most_recently_downloaded.php
* @package mod_jdownloads_most_recently_downloaded
* @copyright (C) 2008/2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

/** This Modul shows the Most Recently Downloaded from the component jDownloads. 
*   Support: www.jDownloads.com
*/

// This is a default layout with tables - you can also create an alternate layout and select it afterwards in the module configuration

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\Language\Text;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Module\JDownloadsMostRecentlyDownloaded\Site\Helper\JDownloadsMostRecentlyDownloadedHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

    HTMLHelper::_('bootstrap.tooltip');
    
    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($jdparams->get('selected_file_type_icon_set'));
    
    $html = '';
    $html = '<table style="width:100%;" class="moduletable'.$moduleclass_sfx.'">';
    
    $sum_files = count($files);
    if ($sum_view > $sum_files) $sum_view = $sum_files;
    
    if ($files) {
        
        if ($text_before <> ''){
            $html .= '<tr><td class="td_jd_ldf_before" style="text-align:'.$alignment.';"><td>'.$text_before.'</td></tr>';   
        }
        
        for ($i=0; $i<$sum_view; $i++) {
            
            $has_no_file = false;
            
            if (!$files[$i]->url_download && !$files[$i]->other_file_id && !$files[$i]->extern_file){
               // only a document without file
               $has_no_file = true;           
            }
            // Get version label
            $version = $params->get('short_version', '');
 //           $version = $short_version;
            if ($sum_char > 0){
                $title_length = strlen($files[$i]->title) + strlen($files[$i]->release) + strlen($short_version) +1;
                if ($title_length > $sum_char){
                   $files[$i]->title = \Joomla\String\StringHelper::substr($files[$i]->title, 0, $sum_char).$short_char;
                   $files[$i]->release = '';
                }    
            }
            
			if ($cat_show && $files[$i]->catid > 1) {
				if ($cat_show_type == 'containing') {
					$cat_show_text2 = $cat_show_text.$files[$i]->cat_title;
				} else {
                    if ($files[$i]->cat_dir_parent){
                        $cat_show_text2 = $cat_show_text.$files[$i]->cat_dir_parent.'/'.$files[$i]->cat_dir;
                    } else {
                        $cat_show_text2 = $cat_show_text.$files[$i]->cat_dir;
                    }
				}
			} else {
                $cat_show_text2 = '';
            }    

            // create the link
            if ($files[$i]->link == '-'){
                // the user have the access to view this item
                if ($detail_view == '1'){
                    if ($detail_view_config == 0){                    
                        // the details view is deactivated in jD config so the
                        // link must start directly the download process
                        if ($direct_download_config == 1){
                            if (!$has_no_file){
                                $link = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, 'download.send'));
                            } else {
                                // create a link to the Downloads category as this download has not a file
                                if ($files[$i]->menu_cat_itemid){
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_cat_itemid);
                                } else {
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                                }
                            }   
                        } else {
                            // link to the summary page
                            if (!$has_no_file){
                                $link = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, 'summary'));
                            } else {
                                // create a link to the Downloads category as this download has not a file
                                if ($files[$i]->menu_cat_itemid){
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_cat_itemid);
                                } else {
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                                }
                            }   
                        }    
                    } else {
                        // create a link to the details view
                        if ($files[$i]->menu_itemid){
                            $link = Route::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_itemid);                    
                        } else {
                            $link = Route::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);                    
                        }
                    }                       
                } else {    
                    // create a link to the Downloads category
                    if ($files[$i]->menu_cat_itemid){
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_cat_itemid);
                    } else {
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                    }
                }    
            } else {
                $link = $files[$i]->link;
            }            
            
            if (!$files[$i]->release) $version = '';
            
            // build icon
            $size = 0;
            $files_pic = '';
            $number = '';
            
            if ($view_pics && $files[$i]->file_pic != ''){
                $size = (int)$view_pics_size;
                $files_pic = '<a href="'.$link.'"><img src="'.$file_pic_folder.$files[$i]->file_pic.'" style="text-align=top;border=0;" width="'.$size.'" height="'.$size.'" alt="'.substr($files[$i]->file_pic,0,-4).'-'.$i.'" /></a>'; 
            }
            
            // build number list
            if ($view_numerical_list){
                $num = $i+1;
                $number = "$num. ";
            }
            
            $text = HTMLHelper::_('string.truncate', $files[$i]->description, $view_tooltip_length, true, false);
              
            // Build description in tooltip 
            if ($view_tooltip && $text != ''){
                $title   = htmlspecialchars($files[$i]->title, ENT_QUOTES, 'UTF-8');
                $tooltip = '<strong><small>' . htmlspecialchars(Text::_('MOD_JDOWNLOADS_MOST_RECENTLY_DOWNLOADED_DESCRIPTION_TITLE'), ENT_QUOTES, 'UTF-8') . '</strong><br>' . $text . '</small>';

                $link_text = '<a href="' . $link . '" class="">' . $title . '</a>'
                            . '<div role="tooltip" id="tip-' . (int) $files[$i]->id . '-' . (int) $files[$i]->catid . '">' . $tooltip . '</div>';
            } else {    
                $link_text = '<a href="'.$link.'">'.$files[$i]->title.' '.$version.$files[$i]->release.'</a>';
            }
                
            $html .= '<tr style="vertical-align:top;"><td style="text-align='.$alignment.';">'.$number.$files_pic.$link_text.'</td>';
            
            if ($view_date) {
                    if ($view_date_text) $view_date_text .= '&nbsp;';
                    if ($view_date_same_line){
                        if ($view_user){
                            $html .= '<td style="text-align:'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_date_text.HTMLHelper::Date($files[$i]->log_datetime,$date_format,false).$view_user_by.' '.$files[$i]->username.'</td>';
                        } else {
                            $html .= '<td style="text-align:'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_date_text.HTMLHelper::Date($files[$i]->log_datetime,$date_format,false).'</td>';
                        }    
                    } else {
                        if ($view_user){
                            $html .= '</tr><tr><td style="text-align:'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_date_text.HTMLHelper::Date($files[$i]->log_datetime,$date_format,false).$view_user_by.' '.$files[$i]->username.'</td>';
                        } else {
                            $html .= '</tr><tr><td style="text-align:'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_date_text.HTMLHelper::Date($files[$i]->log_datetime,$date_format,false).'</td>';
                        }    
                    }    
            } else {
                if ($view_user){
                    $html .= '</tr><tr><td style="text-align:'.$date_alignment.'" class="td_jd_ldf_date_row">'.$view_user_by.' '.$files[$i]->username.'</td>';
                }
            }    
            $html .= '</tr>'; 
            
            // add category info 
            if ($cat_show_text2) {
                if ($cat_show_as_link){
                    if ($files[$i]->menu_cat_itemid){
                        $html .= '<tr style="vertical-align:top;"><td style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';"><a href="index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menu_cat_itemid.'">'.$cat_show_text2.'</a></td></tr>';
                    } else {
                        $html .= '<tr style="vertical-align:top;"><td style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';"><a href="index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid.'">'.$cat_show_text2.'</a></td></tr>';
                    }    
                } else {    
                    $html .= '<tr style="vertical-align:top;"><td style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text2.'</td></tr>';
                }                
            }
        
        }
        if ($text_after <> ''){
            $html .= '<tr><td class="td_jd_ldf_after" style="text-align:'.$alignment.';"><td>'.$text_after.'</td></tr>';   
        }
    }
    
    echo $html.'</table>';
?>		