<?php
/**
* @version $Id: mod_jdownloads_last_updated.php
* @package mod_jdownloads_last_updated
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/
// updated July 2022 for jD 4.0  ColinM

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Module\JDownloadsLastUpdated\Site\Helper\JDownloadsLastUpdatedHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\Language\Text;

    HTMLHelper::_('bootstrap.tooltip');
    
    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($jdparams->get('selected_file_type_icon_set'));

    $html = '';
    
    if ($files) {
		$html = '<div style="width:100%;" class="jd_module_before moduletable'.$moduleclass_sfx.'">';
		
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
                                // create a link to the Downloads category as this download has not a file
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
				if ($view_pics_link) {
					$pic_link = '<a href="'.$link.'">';
					$pic_end = '</a>';
				} else {
					$pic_link = '';
					$pic_end = '';
				}
                
                $files_pic = $pic_link.'<img src="'.$file_pic_folder.$files[$i]->file_pic.'" style="margin:2px 0 2px 0; text-align:top:border:0px;" width="'.$size.'" height="'.$size.'" alt="'.substr($files[$i]->file_pic, 0, -4).'-'.$i.'"/>'.$pic_end; 
            }
            
			// build number list
            if ($view_numerical_list){
                $num = $i+1;
                $number = "$num. ";
            }
            
            //  make version message including space char
			$version_msg = '';
			if ($files[$i]->release) {
				$version_msg = '&nbsp;'.$version.$files[$i]->release;
			}  

            $text = HTMLHelper::_('string.truncate', $files[$i]->description, $view_tooltip_length, true, false);
              
            // Build description in tooltip 
            if ($view_tooltip && $text != ''){
                $title   = htmlspecialchars($files[$i]->title, ENT_QUOTES, 'UTF-8');
                $tooltip = '<strong><small>' . htmlspecialchars(Text::_('MOD_JDOWNLOADS_LAST_UPDATED_DESCRIPTION_TITLE'), ENT_QUOTES, 'UTF-8') . '</strong><br>' . $text . '</small>';

                $link_text = '<a href="' . $link . '" class="">' . $title . '</a>'
                            . '<div role="tooltip" id="tip-' . (int) $files[$i]->id . '-' . (int) $files[$i]->catid . '">' . $tooltip . '</div>';
            } else {    
				$link_text = '<a href="'.$link.'">'.$files[$i]->title.'</a>'.$version_msg;
			}    

			// add the modified date and hits as required 
			$link_div = '<div class="jd_module_link" style="text-align:'.$alignment.'">'.$number.$files_pic.$link_text;
			$link_end = '</div>';
			
            // make hits msg
			$hits_same_line = '';  //when hits not shown or no hits
			$hits_own_line = '';
            if ($view_hits) {
				$hits_msg = $hits_label.JDownloadsLastUpdatedHelper::strToNumber($files[$i]->downloads);
				if ($files[$i]->downloads){
					if ($view_hits_same_line){
						$hits_same_line = '<scan class="jd_module_hits_sameline" style="text-align:'.$hits_alignment.';">&nbsp;'.$hits_msg.'</scan>'; // add space before msg
					} else {
						$hits_own_line = '<div class="jd_module_hits_newline" style="text-align:'.$hits_alignment.';">'.$hits_msg.'</div>';
					}
				}    
			}
			
            // make date msg
			$date_same_line = '';  //when date not shown or not set
			$date_own_line = '';
            if ($view_date) {
                if ($files[$i]->modified){
                    if ($view_date_same_line){
						$date_same_line = '<scan class="jd_module_date_sameline" style="text-align:'.$date_alignment.';">&nbsp;'.$view_date_text.substr(HTMLHelper::Date($files[$i]->modified,$date_format),0,10).'</scan>';
                    } else {
						$date_own_line= '<div class="jd_module_date_newline" style="text-align:'.$date_alignment.';">'.$view_date_text.substr(HTMLHelper::Date($files[$i]->modified,$date_format),0,10).'</div>';
                    } 
                }    
            }
			
            // add dates and hits to html
			if ($view_hits_same_line && $view_date_same_line) {
				$html .= $link_div.$hits_same_line.$date_same_line.$link_end;  //both hits and date on same line
			} else {
				if ($view_hits_same_line && !$view_date_same_line) {
					$html .= $link_div.$hits_same_line.$link_end;		//only hits on same line
					if ($view_date) {
					$html .= $date_own_line;			//show date on separate line
					}
				} else {
					if (!$view_hits_same_line && $view_date_same_line) {
						$html .= $link_div.$date_same_line.$link_end;		//only date on same line
						if ($view_hits) {
							$html .= $hits_own_line;			//show hits on separate line
						}
					} else {
						if (!$view_hits_same_line && !$view_date_same_line) {
							$html .= $link_div.$link_end.$hits_own_line.$date_own_line;  //show on separate lines
						}
					}
				} 
			}		
            
            // add the first download screenshot when exists and activated in options
            if ($view_thumbnails){
				if ($view_thumbnails_link) {
					$pic_link = '<a href="'.$link.'">';
					$pic_end = '</a>';
				}
				else {
					$pic_link = '';
					$pic_end = '';
				}
                if ($first_image){
                    $thumbnail = $pic_link.'<img class="img" src="'.$thumbfolder.$first_image.'" style="text-align:top;padding:5px;border:'.$border.';" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" alt="'.substr($first_image, 0, -4).'-'.$i.'"/>'.$pic_end;
                } else {
                    // use placeholder
                    if ($view_thumbnails_dummy){
                        $thumbnail = $pic_link.'<img class="img" src="'.$thumbfolder.'no_pic.png" style="text-align:top;padding:5px;border:'.$border.';" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" alt="no_pic-'.$i.'"/>'.$pic_end;    
                    }
                }
                if ($thumbnail) $html .= '<div style="text-align:'.$alignment.';">'.$thumbnail.'</div>';
            } 
			
			// add category info - do not include text in link
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
			$html .= '<div class="jd_module_after" style="text-align:'.$alignment.'">'.$text_after.'</div>';
		}
        echo $html.'</div>';
    }
    
?>