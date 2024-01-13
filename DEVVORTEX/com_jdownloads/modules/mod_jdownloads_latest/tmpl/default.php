<?php
/**
* @version $Id: mod_jdownloads_latest.php
* @package mod_jdownloads_latest
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

// this is a default layout and used tables - you can also select an alternate tableless layout in the module configuration

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Module\JDownloadsTop\Site\Helper\JDownloadsLatestHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\Language\Text;
use Joomla\Filter\OutputFilter;


    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($jdparams->get('selected_file_type_icon_set'));
    $html = '';
    
    if ($files) {
		$html = '<div style="width:100%;" class="moduletable'.$moduleclass_sfx.'">';
		
        if ($text_before <> ''){
			$html .= '<div class="jd_module_before" style="text-align:'.$alignment.'">'.$text_before.'</div>';   
		}
		
        for ($i=0; $i<count($files); $i++) {
			$html .= '<div style="clear:both;"></div>';
            $has_no_file = false;
            if (!$files[$i]->url_download && !$files[$i]->other_file_id && !$files[$i]->extern_file){
               // only a document without file
               $has_no_file = true;           
            }
                         
            // get the first image as thumbnail when it exist           
            $thumbnail = ''; 
            $first_image = '';
            $images = explode("|",$files[$i]->images);
            if (isset($images[0])) $first_image = $images['0'];
			//get version label
            $version = $params->get('short_version', ''); 
            // shorten the file title?			
            if ($sum_char > 0){
				$gesamt = strlen($files[$i]->title) + strlen($files[$i]->release) + strlen($short_version) +1;
				if ($gesamt > $sum_char){
				   $files[$i]->title = \Joomla\String\StringHelper::substr($files[$i]->title, 0, $sum_char).$short_char;
				   $files[$i]->release = '';
				}    
			} 
			
            if ($cat_show && $files[$i]->catid > 1) {
                if ($cat_show_type == 'containing') {
                    $cat_show_text2 = $files[$i]->category_title;
                } else {
                    if ($files[$i]->category_cat_dir_parent){
                        $cat_show_text2 = $files[$i]->category_cat_dir_parent.'/'.$files[$i]->category_cat_dir;
                    } else {
                        $cat_show_text2 = $files[$i]->category_cat_dir;
                    }
                }
            } else {
                $cat_show_text2 = '';
            }  

            // create the link
            if ($files[$i]->link == '-'){
                // the user has access to view this item
                if ($detail_view == '1'){
                    if ($detail_view_config == 0){                    
                        // the details view is deactivated in jD config so the
                        // link must start directly the download process
                        if ($direct_download_config == 1){
                            if (!$has_no_file){
                                $link = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, 'download.send'));                    
                            } else {
                                // create a link to the Downloads category as this download has not a file
                                if ($files[$i]->menuc_cat_itemid){
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid);
                                } else {
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                                }                                
                            }   
                        } else {
                            // link to the summary page
                            if (!$has_no_file){
                                $link = Route::_(RouteHelper::getOtherRoute($files[$i]->slug, $files[$i]->catid, $files[$i]->language, 'summary'));
                            } else {
                                // create a link to the Downloads category as this download dopes not have a file
                                if ($files[$i]->menuc_cat_itemid){
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid);
                                } else {
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                                }
                            }   
                        }    
                    } else {
                        // create a link to the details view
                        if ($files[$i]->menuf_itemid){
                            $link = Route::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuf_itemid);                    
                        } else {
                            if ($files[$i]->menuc_cat_itemid){
                                $link = Route::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid);                    
                            } else {
                                $link = Route::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);                    
                            }
                        }
                    }                       
                } else {    
                    // create a link to the Downloads category
                    if ($files[$i]->menuc_cat_itemid){
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid);
                    } else {
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                    }
                }    
            } else {
				// The user has NO access to view this item
                $link = $files[$i]->link;
            }
            
            if (!$files[$i]->release) $version = '';
			
            // add mime file pic
            $size = 0;
			$files_pic = '';
			$number = '';
			
            if ($view_pics && $files[$i]->file_pic != ''){
				$size = (int)$view_pics_size;
				if ($view_pics_link){
					$pic_link = '<a href="'.$link.'">';
					$pic_end = '</a>';
				} else {
					$pic_link = '';
					$pic_end = '';
				}
				
                $files_pic = $pic_link.'<img src="'.$file_pic_folder.$files[$i]->file_pic.'" style="text-align:top;border:0px;" width="'.$size.'" height="'.$size.'" alt="'.substr($files[$i]->file_pic, 0, -4).'-'.$i.'" />'.$pic_end; 
			}
			
            if ($view_numerical_list){
				$num = $i+1;
				$number = "$num. ";
			}    
			
            // Build version message including space char
			$version_msg = '';
			if ($files[$i]->release) {
				$version_msg = '&nbsp;'.htmlspecialchars($version.$files[$i]->release, ENT_QUOTES, 'UTF-8');
			}

            $text = HTMLHelper::_('string.truncate', $files[$i]->description, $view_tooltip_length, true, false);
              
            // Build description in tooltip 
            if ($view_tooltip && $text != ''){
                $title   = htmlspecialchars($files[$i]->title, ENT_QUOTES, 'UTF-8');
                $tooltip = '<strong><small>' . htmlspecialchars(Text::_('MOD_JDOWNLOADS_LATEST_DESCRIPTION_TITLE'), ENT_QUOTES, 'UTF-8') . '</strong><br>' . $text . '</small>';
                
                $link_text = '<a href="' . $link . '" class="">' . $title . '</a>'
                            . '<div role="tooltip" id="tip-' . (int) $files[$i]->id . '-' . (int) $files[$i]->catid . '">' . $tooltip . '</div>';
			} else {    
				$link_text = '<a href="'.$link.'">'.$files[$i]->title.'</a>'.$version_msg;
			}    
            
            $link_div= '<div style="text-align:'.$alignment.'">'.$number.$files_pic.$link_text;
			$link_end = '</div>';
			
            // Build the creation date
            if ($view_date && $files[$i]->created) {
				$date_msg = HTMLHelper::date($files[$i]->created, $date_format);
				if ($view_date_same_line){
					$html .= $link_div.'<scan class ="jd_module_date_sameline">&nbsp;'.$view_date_text.$date_msg.'</scan>'.$link_end; //include space					
				} else {
					$html .= $link_div.'<div class ="jd_module_date_newline">'.$view_date_text.$date_msg.'</div>'.$link_end;
				}    
			} else {
				$html .= $link_div.$link_end;  //do not show date
			}				

            // Add the first download screenshot when exists and activated in options
            if ($view_thumbnails){
				if ($view_thumbnails_link) {
					$pic_link = '<a href="'.$link.'">';
					$pic_end = '</a>';
				} else {
					$pic_link = '';
					$pic_end = '';
				}	
                if ($first_image){
                    $thumbnail = $pic_link.'<img class="img" src="'.$thumbfolder.$first_image.'" style="text-align:top;padding:5px;border:'.$border.';" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" alt="'.substr($files[$i]->title, 0, -4).'-'.$i.'" />'.$pic_end;
                } else {
                    // use placeholder
                    if ($view_thumbnails_dummy){
                        $thumbnail = $pic_link.'<img class="img" src="'.$thumbfolder.'no_pic.png" style="text-align:top;padding:5px;border:'.$border.';" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" alt="no_pic-'.$i.'"/>'.$pic_end;    
                    }
                }
                if ($thumbnail) $html .= '<div style="text-align:'.$alignment.';">'.$thumbnail.'</div>';
            } 
			
			// Add category info 
            if ($cat_show_text2) {
				if ($cat_show_as_link){
                    if ($files[$i]->menuc_cat_itemid){
                        $html .= '<div style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text.'<a href="index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid.'">'.$cat_show_text2.'</a></div>';
				    } else {
                        $html .= '<div style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text.'<a href="index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid.'">'.$cat_show_text2.'</a></div>';
                    }    
                } else {    
				    $html .= '<div style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text.$cat_show_text2.'</div>';
                }
			}    
		}
		$html .= '<div style="clear:both;"></div>';
		if ($text_after <> ''){
			$html .= '<div class="jd_module_after" style="text-align:'.$alignment.'";>'.$text_after.'</div>';
		}
        echo $html.'</div>';
    }
?>