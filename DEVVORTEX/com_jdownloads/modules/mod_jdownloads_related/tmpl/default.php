<?php
/**
* @version $Id: mod_jdownloads_related.php
* @package mod_jdownloads_related
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
* This module shows you some related downloads from the jDownloads component. 
* It is only for jDownloads 4.0 and later (Support: www.jDownloads.com)
*/

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\Language\Text;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Module\JDownloadsRelated\Site\Helper\JDownloadsRelatedHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;

    HTMLHelper::_('bootstrap.tooltip');
    
    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($jdparams->get('selected_file_type_icon_set'));

    $num = 0;
    
    if (count($files) > 1 ){		
        $html = '<div style="width:100%;" class="moduletable'.$moduleclass_sfx.'">';
        
        if ($text_before <> ''){
			$html .= '<div style="clear:both;"><div class="jd_module_before" style="text-align:'.$alignment.'">'.$text_before.'</div>';  
		}
        
        if ($title_text <> ''){
            $html .= '<div style="clear:both;"><div class="jd_module_title" style="text-align:'.$alignment.'">'.$title_text.'</div>';   
        }	

        for ($i=0; $i<count($files); $i++) {
            
            // The already viewed 'main download' must be removed from the list
            if ($files[$i]->id == $id){
                continue;
            }
            $html .= '<div style="clear:both;"></div>';
            $has_no_file = false;
            if (!$files[$i]->url_download && !$files[$i]->other_file_id && !$files[$i]->extern_file){
               // Only a document without file
               $has_no_file = true;           
            }
                         
            // Get the first image as thumbnail when it exist           
            $thumbnail = ''; 
            $first_image = '';
			if (isset($files[$i]->images)){
				$images = explode("|",$files[$i]->images);
				if (isset($images[0])) $first_image = $images['0'];
			}
			
            $version = $params->get('short_version', ''); 

            // Shorten the file title when required			
            if ($sum_char > 0){
				$total = strlen($files[$i]->title) + strlen($files[$i]->release) + strlen($short_version) +1;
				if ($total > $sum_char){
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
                                if ($files[$i]->menuc_cat_itemid){
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid);
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
                                if ($files[$i]->menuc_cat_itemid){
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid);
                                } else {
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                                }
                            }   
                        }    
                    } else {
                        // Create a link to the details view
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
                    // Create a link to the Downloads category
                    if ($files[$i]->menuc_cat_itemid){
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid);
                    } else {
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                    }
                }    
            } else {
                $link = $files[$i]->link;
            }
            
            if (!$files[$i]->release) $version = '';
			
			// Add mime file pic
            $size = 0;
			$files_pic = '';
			$number = '';
			
            if ($view_pics && $files[$i]->file_pic != ''){
				$size = (int)$view_pics_size;
				
                if ($view_pics_link) {
					$pic_link = '<a href="'.$link.'">';
					$pic_end = '</a>';
				} else {
					$pic_link = '';
					$pic_end = '';
				}
				
                $files_pic = $pic_link . '<img src="' . $file_pic_folder . $files[$i]->file_pic . '" style="text-align:top;border:0px;" width="' . $size . '" height="' . $size.'" alt="' . substr($files[$i]->file_pic, 0, -4) . '-' . $i . '"/>' . $pic_end; 
			}
            
            // Build number list			
			if ($view_numerical_list){
				$num++;
                $number = "$num. ";
			}    
			
            // Create version message including space char
			$version_msg = '';
			if ($files[$i]->release) {
				$version_msg = '&nbsp;'.$version.$files[$i]->release;
			}
            
            $text = HTMLHelper::_('string.truncate', $files[$i]->description, $view_tooltip_length, true, false);
              
            // Build description in tooltip 
            if ($view_tooltip && $text != ''){
                $title   = htmlspecialchars($files[$i]->title, ENT_QUOTES, 'UTF-8');
                $tooltip = '<strong><small>' . htmlspecialchars(Text::_('MOD_JDOWNLOADS_RELATED_DESCRIPTION_TITLE'), ENT_QUOTES, 'UTF-8') . '</strong><br>' . $text . '</small>';
                
                $link_text = '<a href="' . $link . '" class="">' . $title . '</a>'
                            . '<div role="tooltip" id="tip-' . (int) $files[$i]->id . '-' . (int) $files[$i]->catid . '">' . $tooltip . '</div>';
			} else {    
				$link_text = '<a href="'.$link.'">'.$files[$i]->title.'</a>'.$version_msg;
			}    
            
            $link_div = '<div class="jd_module_link" style="text-align:'.$alignment.'">'.$number.$files_pic.$link_text;
			$link_end = '</div>';
			
            // Create the hits output			
            $hits_same_line = '';
            $hits_own_line = '';
            
            if ($view_hits) {
				$hits_msg = $hits_label.JDownloadsRelatedHelper::strToNumber($files[$i]->downloads);
				if ($files[$i]->downloads){
					if ($view_hits_same_line){
						$hits_same_line = '<scan style="text-align:'.$hits_alignment.';">&nbsp;'.$hits_msg.'</scan>'; // Add space before msg
					} else {
						$hits_own_line = '<div style="text-align:'.$hits_alignment.';">'.$hits_msg.'</div>';
					}
				}    
			}
            
            // Create the date output
            $date_same_line = '';
            $date_own_line = '';
            
            if ($view_date) {
                if ($files[$i]->created){
                    if ($view_date_same_line){
						$date_same_line = '<scan style="text-align:'.$date_alignment.';">&nbsp;'.$view_date_text.substr(HTMLHelper::Date($files[$i]->created,$date_format),0,10).'</scan>';
                    } else {
						$date_own_line= '<div style="text-align:'.$date_alignment.';">'.$view_date_text.substr(HTMLHelper::Date($files[$i]->created,$date_format),0,10).'</div>';
                    } 
                }    
            }
			
            if ($view_hits_same_line && $view_date_same_line) {
				$html .= $link_div.$hits_same_line.$date_same_line.$link_end;  // Both hits and date on same line
			} else {
				if ($view_hits_same_line && !$view_date_same_line) {
					$html .= $link_div.$hits_same_line.$link_end;		// Only hits on same line
					if ($view_date) {
					$html .= $date_own_line;			                // Show date on separate line
					}
				} else {
					if (!$view_hits_same_line && $view_date_same_line) {
						$html .= $link_div.$date_same_line.$link_end;	// Only date on same line
						if ($view_hits) {
							$html .= $hits_own_line;			        // Show hits on separate line
						}
					} else {
						if (!$view_hits_same_line && !$view_date_same_line) {
							$html .= $link_div.$link_end.$hits_own_line.$date_own_line;  // Show on separate lines
						}
					}
				} 
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
                    $thumbnail = $pic_link.'<img class="img jd_module_thumbnail" src="'.$thumbfolder.$first_image.'" style="text-align:top;padding:5px;border:'.$border.';" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" alt="'.$files[$i]->title.'-'.$i.'" />'.$pic_end;
                } else {
                    // Use placeholder
                    if ($view_thumbnails_dummy){
                        $thumbnail = $pic_link.'<img class="img jd_module_thumbnail" src="'.$thumbfolder.'no_pic.png" style="text-align:top;padding:5px;border:'.$border.';" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" alt="no_pic-'.$i.'"/>'.$pic_end;    
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
			$html .= '<div style="margin-bottom:10px; display:block; text-align:'.$alignment.';">'.$text_after.'</div>';
		}
        
    } else {
        // No items found
        if ($view_not_found){
            $html = '<div style="width:100%;text-align:'.$alignment.';" class="moduletable'.$moduleclass_sfx.'">';
            
            if ($title_text <> ''){
                $html .= '<div><b>'.$title_text.'</b></div>';   
            }
            
            $html .= '<div>'.Text::_('MOD_JDOWNLOADS_RELATED_NO_ITEMS_FOUND').'</div>';
        }
    }
    $html .= '<hr style="clear:both;width:100%;height:2px;border-width:0;color:#ccc;background-color:#ccc">';
    echo $html.'</div>';
?>