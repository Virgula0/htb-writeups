<?php
/**
* @version $Id: mod_jdownloads_rated.php
* @package mod_jdownloads_rated
* @copyright (C) 2022 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
* modified ColinM September 2022
* This module shows you the most rated Downloads from the jDownloads component. 
* It is only for jDownloads 4.0 and later (Support: www.jDownloads.com)
*/

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\Language\Text;

use JDownloads\Component\JDownloads\Site\Helper\JDHelper;
use JDownloads\Module\JDownloadsRated\Site\Helper\JDownloadsRatedHelper;
use JDownloads\Component\JDownloads\Site\Helper\RouteHelper;


    // Path to the mime type image folder (for file symbols) 
    $file_pic_folder = JDHelper::getFileTypeIconPath($jdparams->get('selected_file_type_icon_set'));    

    $html = '<div style="100%;" class="moduletable'.$moduleclass_sfx.'" style="float:'.$alignment.';">';
    
    if ($files){
        if ($text_before <> ''){
            $html .= '<div class="jd_module_before" style="text-align:'.$alignment.';">'.$text_before.'</div>';
        }
        
        for ($i=0; $i<count($files); $i++) {
            $html .= '<div style="clear:both;"></div>';
            $has_no_file = false;
            
            if (!$files[$i]->url_download && !$files[$i]->other_file_id && !$files[$i]->extern_file){
               // Only a document without file
               $has_no_file = true;           
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

            $db->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=category&catid=".$files[$i]->catid."' and published = 1");
            $Itemid = $db->loadResult();
            if (!$Itemid){
                $Itemid = $root_itemid;
            } 
                            
            // Create the link
            if ($files[$i]->link == '-'){
                // The user has access to view this item
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
                                if ($files[$i]->menuc_cat_itemid){
                                    $link = Route::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->catid.'&amp;Itemid='.$files[$i]->menuc_cat_itemid);
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
            
            // Build file pic
            $size = 0;
            $files_pic = '';
            $number = '';
            
            if ($view_pics && $files[$i]->file_pic != ''){
				if ($view_pics_link) {
					$pic_link = '<a href="'.$link.'">';
					$pic_end = '</a>';
				}
				else {
					$pic_link = '';
					$pic_end = '';
				}
                $size = (int)$view_pics_size;
                $files_pic = $pic_link.'<img src="'.$file_pic_folder.$files[$i]->file_pic.'" align="top" width="'.$size.'" height="'.$size.'" border="0" style="margin:2px 0;" alt="'.substr($files[$i]->file_pic, 0, -4).'-'.$i.'"/>'.$pic_end;
            }
            
            // Build number list
            if ($view_numerical_list){
                $num = $i+1;
                $number = "$num. ";
            }
            
            $link_text = '<a href="'.$link.'">'.$files[$i]->title.'</a>&nbsp;'.$version.$files[$i]->release;
            
            // Determine which stars pic to show
			$num_stars = (int)(($files[$i]->ratenum *1.0)/20.0);  // Compute number of stars (ratenum is a percentage)
			$rating_stars =  '<scan><img src="'.$image_dir.'star'.$num_stars.'.png" align="top" width="70" height="14" border="0" alt="star'.$num_stars.'-'.$i.'"/></scan>';
            
            //	$view_votes = $view_stars_rating_count;
            if ($view_votes){
				$rating_votes = '<scan style="margin-top:-2px; font-size:95%;">&nbsp;'.$files[$i]->rating_count.' ';
                if ($files[$i]->rating_count != 1) {
					$rating_votes .= Text::_('MOD_JDOWNLOADS_RATED_JDVOTE_VOTES').'</scan>';
                } else {					
                    $rating_votes .= Text::_('MOD_JDOWNLOADS_RATED_JDVOTE_VOTE').'</scan>';
                }
            } 
            
            //Add stars
            if ($view_stars && $view_votes){
				$rating = $rating_stars.$rating_votes;
			} 
			if ($view_stars && !$view_votes){
				$rating = $rating_stars;
			}
			if (!$view_stars && $view_votes){
				$rating = $rating_votes;
			}
			if (!$view_stars && !$view_votes){
				$rating = '';
			}
            
            // Now do hits and date	setup
			$hits_date_msg = '<div style="clear:both;"></div><div>';
			$hits_date_end = '</div>';
			
            // Make hits msg
			$hits_same_line = '';  // When hits not shown or hits are zero
			$hits_own_line = '';			
            if ($view_hits) {
				$hits_msg = $view_hits_label.JDownloadsRatedHelper::strToNumber($files[$i]->downloads);
				if ($files[$i]->downloads){
					if ($view_hits_same_line){
						$hits_same_line = '<scan class="jd_module_hits_sameline" style="text-align:'.$date_hits_alignment.';">&nbsp;'.$hits_msg.'</scan>'; // add space before msg
					} else {
						$hits_own_line = '<scan class="jd_module_hits_newline" style="text-align:'.$date_hits_alignment.';">'.$hits_msg.'</scan>';
					}
				}    
			}
            
            // Make date msg
			$date_same_line = '';  // When date not shown or not set
			$date_own_line = '';
            if ($view_date) {
                if ($files[$i]->created){
                    if ($view_date_same_line){
						$date_same_line = '<scan class="jd_module_date_sameline" style="text-align:'.$alignment.';">&nbsp;'.$view_date_label.substr(HTMLHelper::Date($files[$i]->created,$date_format),0,10).'</scan>';
                    } else {
						$date_own_line= '<scan class="jd_module_date_newline" style="text-align:'.$alignment.';">'.$view_date_label.substr(HTMLHelper::Date($files[$i]->created,$date_format),0,10).'</scan>';
                    } 
                }    
			}
	    
            // Now make the html  - needs updating to if else or case structure
		    $div_start = '<div style="display:block; float:'.$alignment.'; text-align:'.$alignment.';">'.$number.$files_pic.$link_text;
		    $div_end = '</div>';
            
            if ($view_stars_votes_same_line && $view_date_same_line && $view_hits_same_line){
				$html .= $div_start.'&nbsp;'.$rating.$date_same_line.$hits_same_line.$div_end; // All on same line
			}
			
            if ($view_stars_votes_same_line && $view_date_same_line && !$view_hits_same_line){
					$html .= $div_start.'&nbsp;'.$rating.$date_same_line.$div_end; // Hits on separate line
					$html .= '<div style="clear:both;"></div><div style="display:block; text-align:'.$alignment.';">'.$hits_own_line.'</div>';
			}
			
            if ($view_stars_votes_same_line && !$view_date_same_line && $view_hits_same_line){
				$html .= $div_start.'&nbsp;'.$rating.$date_same_line.$hits_same_line.$div_end; // All on same line
				$html .= '<div style="clear:both;"></div><div style="display:block; text-align:'.$alignment.';">'.$date_own_line.'</div>';
			}
			
            if ($view_stars_votes_same_line && !$view_date_same_line && !$view_hits_same_line){
				$html .= $div_start.'&nbsp;'.$rating.$div_end; // All on same line
				$html .= '<div style="clear:both;"></div><div style="display:block; text-align:'.$alignment.';">'.$date_own_line.' '.$hits_own_line.'</div>';
			}
			
            if (!$view_stars_votes_same_line && $view_date_same_line && $view_hits_same_line){
				$html .= $div_start.'&nbsp;'.$date_same_line.$hits_same_line.$div_end; // All on same line
				$html .= '<div style="clear:both;"></div><div style="display:block; text-align:'.$alignment.';">'.$rating.'</div>';
			}
			
            if (!$view_stars_votes_same_line && $view_date_same_line && !$view_hits_same_line){
				$html .= $div_start.'&nbsp;'.$date_same_line.$div_end; // All on same line
				$html .= '<div style="clear:both;"></div><div style="display:block; text-align:'.$alignment.';">'.$rating.$hits_own_line.'</div>';
			}
			
            if (!$view_stars_votes_same_line && !$view_date_same_line && $view_hits_same_line){
				$html .= $div_start.'&nbsp;'.$hits_same_line.$div_end; // All on same line
				$html .= '<div style="clear:both;"></div><div style="display:block; text-align:'.$alignment.';">'.$rating.$date_own_line.'</div>';
			}
			
            if (!$view_stars_votes_same_line && !$view_date_same_line && !$view_hits_same_line){
				$html .= $div_start.$div_end; // All on same line
				$html .= '<div style="clear:both;"></div><div style="display:block; text-align:'.$alignment.';">'.$rating.' '.$date_own_line.' '.$hits_own_line.'</div>';
			}
		}
		$html .= '<div style="clear:both;"></div>';
        if ($text_after <> ''){
			$html .= '<div class="jd_module_after" style="text-align:'.$alignment.';">'.$text_after.'</div>';
        }
    }    
    echo $html.'</div>';
         
?>