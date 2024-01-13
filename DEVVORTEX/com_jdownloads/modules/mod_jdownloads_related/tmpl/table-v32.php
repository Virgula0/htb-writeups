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

    if (count($files) > 1 ){
		
        $html = '<table style="width:100%;" class="moduletable'.$moduleclass_sfx.'">';
        
        if ($text_before <> ''){
			$html .= '<tr class="jd_module_before" style="text-align:'.$alignment.';"><td>'.$text_before.'</td></tr>';
		}
		
        if ($title_text <> ''){
            $html .= '<tr><td><b>'.$title_text.'</b></td></tr>';   
        }
        
        for ($i=0; $i<count($files); $i++) {
            // the already viewed 'main download' is not required
            if ($files[$i]->id == $id){
                continue;
            }
            
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
				$total = strlen($files[$i]->title) + strlen($files[$i]->release) + strlen($short_version) +1;
				if ($total > $sum_char){
				   $files[$i]->title = \Joomla\String\StringHelper::substr($files[$i]->title, 0, $sum_char).$short_char;
				   $files[$i]->release = '';
				}    
			} 
			
            if ($cat_show && $files[$i]->catid > 1) {
                if ($cat_show_type == 'containing') {
                    $cat_show_text2 = $cat_show_text.$files[$i]->category_title;
                } else {
                    if ($files[$i]->category_cat_dir_parent){
                        $cat_show_text2 = $cat_show_text.$files[$i]->category_cat_dir_parent.'/'.$files[$i]->category_cat_dir;
                    } else {
                        $cat_show_text2 = $cat_show_text.$files[$i]->category_cat_dir;
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
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]-> c_cat_itemid);
                    } else {
                        $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid);
                    }
                }    
            } else {
                $link = $files[$i]->link;
            }
            
            if (!$files[$i]->release) $version = '';
			
			// add mime file pic
            $size = 0;
			$files_pic = '';
			$number = '';
			
            if ($view_pics && $files[$i]->file_pic != ''){
				$size = (int)$view_pics_size;
				$files_pic = '<a href="'.$link.'"><img src="'.$file_pic_folder.$files[$i]->file_pic.'" style="text-align:top;border:0px;" width="'.$size.'" height="'.$size.'" alt="'.substr($files[$i]->file_pic,0,-4).'-'.$i.'"/></a>'; 
			}
            
			if ($view_numerical_list){
				$num = $i+1;
				$number = "$num. ";
			}    

            $text = HTMLHelper::_('string.truncate', $files[$i]->description, $view_tooltip_length, true, false);
              
            // Build description in tooltip 
            if ($view_tooltip && $text != ''){
                $title   = htmlspecialchars($files[$i]->title, ENT_QUOTES, 'UTF-8');
                $tooltip = '<strong><small>' . htmlspecialchars(Text::_('MOD_JDOWNLOADS_RELATED_DESCRIPTION_TITLE'), ENT_QUOTES, 'UTF-8') . '</strong><br>' . $text . '</small>';

                $link_text = '<a href="' . $link . '" class="">' . $title . '</a>'
                            . '<div role="tooltip" id="tip-' . (int) $files[$i]->id . '-' . (int) $files[$i]->catid . '">' . $tooltip . '</div>';			
			} else {    
				$link_text = '<a href="'.$link.'">'.$files[$i]->title.' '.$version.$files[$i]->release.'</a>';
			}    
            
            $html .= '<tr style="vertical-align:top;"><td style="text-align:'.$alignment.'">'.$number.$files_pic.$link_text.'</td>';
            
			// add the hits
            if ($view_hits) {
				if ($files[$i]->downloads){
					if ($view_hits_same_line){
						$html .= '<td style="text-align:'.$hits_alignment.';">'.$hits_label.'&nbsp;'.modJdownloadsRelatedHelper::strToNumber($files[$i]->downloads).'</td>'; 
					} else {
						$html .= '<tr style="vertical-align:top;"><td style="text-align:'.$hits_alignment.';">'.$hits_label.'&nbsp;'.modJdownloadsRelatedHelper::strToNumber($files[$i]->downloads).'</td>';
					}
				}    
			} 

            // add the date
            if ($view_date) {
                if ($files[$i]->created){
                    if ($view_date_same_line){
                        $html .= '<td style="text-align:'.$date_alignment.';"><small>&nbsp;'.substr(HTMLHelper::Date($files[$i]->created,$date_format),0,10).'&nbsp;</small></td>';
                    } else {
                        $html .= '</tr><tr><td style="text-align:'.$date_alignment.';"><small>'.substr(HTMLHelper::Date($files[$i]->created,$date_format),0,10).'</small></td>';
                    } 
                }    
            } 
            $html .= '</tr>';
            
            // add the first download screenshot when exists and activated in options
            if ($view_thumbnails){
                if ($first_image){
                    $thumbnail = '<a href="'.$link.'"><img class="img" src="'.$thumbfolder.$first_image.'" style="text-align:top;padding:5px;border:'.$border.';" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" alt="'.substr($first_image,0,-4).'-'.$i.'" /></a>';
                } else {
                    // use placeholder
                    if ($view_thumbnails_dummy){
                        $thumbnail = '<a href="'.$link.'"><img class="img" src="'.$thumbfolder.'no_pic.png" style="text-align:top;padding:5px;border:'.$border.';" width="'.$view_thumbnails_size.'" height="'.$view_thumbnails_size.'" alt="no_pic-'.$i.'"/></a>';    
                    }
                }
                if ($thumbnail) $html .= '<tr style="vertical-align:top;"><td style="text-align:'.$alignment.';">'.$thumbnail.'</td></tr>';
            } 
			
			// add category info 
            if ($cat_show_text2) {
				if ($cat_show_as_link){
                    if ($files[$i]->menuc_cat_itemid){
                        $html .= '<tr style="vertical-align:top;"><td style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';"><a href="index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid.'">'.$cat_show_text2.'</a></td></tr>';
				    } else {
                        $html .= '<tr style="vertical-align:top;"><td style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';"><a href="index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$Itemid.'">'.$cat_show_text2.'</a></td></tr>';
                    }    
                } else {    
				    $html .= '<tr style="vertical-align:top;"><td style="text-align:'.$alignment.';font-size:'.$cat_show_text_size.'; color:'.$cat_show_text_color.';">'.$cat_show_text2.'</td></tr>';
                }
			}    
		}
		if ($text_after <> ''){
            $html .= '<tr class="jd_module_after" style="text-align:'.$alignment.';"><td>'.$text_after.'</td></tr>';
		}
        
    } else {
        // no items found
        if ($view_not_found){
            $html = '<table style="width:100%;" class="moduletable'.$moduleclass_sfx.'">';
            
            if ($title_text <> ''){
                $html .= '<tr><td><b>'.$title_text.'</b></td></tr>';   
            }
            
            $html .= '<tr><td>'. Text::_('MOD_JDOWNLOADS_RELATED_NO_ITEMS_FOUND').'</td></tr>';
        }
    }
    
    echo $html.'</table>';
    ?>